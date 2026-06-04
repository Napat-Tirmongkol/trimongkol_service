<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Workspace;
use App\Services\Accounting\AccountingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tenant-managed chart of accounts. Every workspace starts from a template
 * (see ChartOfAccounts) and can then add their own accounts — e.g. their real
 * bank accounts — rename, deactivate, or delete the ones they don't use.
 *
 * Two safety rails: accounts the posting/tax engine binds to (system_role) are
 * locked from type changes and deletion, and any account that already has
 * journal lines can be deactivated but not deleted (the ledger is immutable).
 */
class AccountController extends AccountingController
{
    private const TYPES = ['asset', 'liability', 'equity', 'income', 'expense'];

    public function index()
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $accounts = Account::forWorkspace($workspace)->orderBy('code')->get()->groupBy('type');

        return view('accounting.accounts.index', [
            'accountsByType' => $accounts,
            'types' => self::TYPES,
        ]);
    }

    public function create()
    {
        $this->requireWorkspace();

        return view('accounting.accounts.form', [
            'account' => new Account(['is_active' => true, 'is_tax_deductible' => true]),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $this->validated($request, $workspace);

        Account::create([
            'workspace_id' => $workspace->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?? null,
            'type' => $data['type'],
            'normal_balance' => Account::normalBalanceFor($data['type']),
            'is_active' => $request->boolean('is_active'),
            'is_tax_deductible' => $request->boolean('is_tax_deductible'),
        ]);

        AccountingAuditLog::record($workspace, 'account.created', null, "{$data['code']} {$data['name']}");

        return redirect()->route('accounting.accounts.index')->with('status', __('app.accounting.account_saved'));
    }

    public function edit(Account $account)
    {
        $this->scopedAccount($account);

        return view('accounting.accounts.form', [
            'account' => $account,
            'types' => self::TYPES,
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $workspace = $this->scopedAccount($account);
        $this->assertPoster($workspace);

        // Engine-bound accounts keep their type (and role) so postings stay valid.
        $locked = (bool) $account->system_role;
        $data = $this->validated($request, $workspace, $account, lockType: $locked);

        $type = $locked ? $account->type : $data['type'];
        $account->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?? null,
            'type' => $type,
            'normal_balance' => Account::normalBalanceFor($type),
            'is_active' => $request->boolean('is_active'),
            'is_tax_deductible' => $request->boolean('is_tax_deductible'),
        ]);

        AccountingAuditLog::record($workspace, 'account.updated', $account, "{$account->code} {$account->name}");

        return redirect()->route('accounting.accounts.index')->with('status', __('app.accounting.account_saved'));
    }

    public function destroy(Account $account)
    {
        $workspace = $this->scopedAccount($account);
        $this->assertPoster($workspace);

        if ($account->system_role) {
            return back()->with('error', __('app.accounting.account_locked_role'));
        }
        if ($account->lines()->exists()) {
            return back()->with('error', __('app.accounting.account_has_entries'));
        }

        $label = "{$account->code} {$account->name}";
        $account->delete();

        AccountingAuditLog::record($workspace, 'account.deleted', null, $label);

        return redirect()->route('accounting.accounts.index')->with('status', __('app.accounting.account_deleted'));
    }

    /** Resolve the workspace and confirm the account belongs to it. */
    private function scopedAccount(Account $account): Workspace
    {
        $workspace = $this->requireWorkspace();
        abort_unless($account->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    private function validated(Request $request, Workspace $workspace, ?Account $account = null, bool $lockType = false): array
    {
        $rules = [
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('accounting_accounts', 'code')
                    ->where('workspace_id', $workspace->id)
                    ->ignore($account?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => [$lockType ? 'nullable' : 'required', Rule::in(self::TYPES)],
            'is_active' => ['nullable', 'boolean'],
            'is_tax_deductible' => ['nullable', 'boolean'],
        ];

        return $request->validate($rules);
    }
}
