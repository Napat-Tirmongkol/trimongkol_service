<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use App\Models\Accounting\Department;
use App\Models\Workspace;
use App\Services\Accounting\AccountingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Planned revenue/expense per (account × department × fiscal period). One
 * entry covers a fiscal year (fiscal_month=null) or a specific month (1-12).
 * The Budget vs Actual report joins this against posted journal lines.
 */
class BudgetController extends AccountingController
{
    private const MONTHS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    public function index(Request $request)
    {
        $workspace = $this->requireWorkspace();

        $year = (int) ($request->query('year') ?: now()->year);

        $budgets = Budget::forWorkspace($workspace)
            ->with('account', 'department')
            ->where('fiscal_year', $year)
            ->orderBy('account_id')
            ->orderByRaw('IFNULL(fiscal_month, 0)')
            ->get();

        $years = Budget::forWorkspace($workspace)
            ->selectRaw('DISTINCT fiscal_year')
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('accounting.budgets.index', compact('workspace', 'budgets', 'year', 'years'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        return view('accounting.budgets.form', [
            'budget' => new Budget(['fiscal_year' => now()->year, 'fiscal_month' => null]),
            'accounts' => $this->budgetableAccounts($workspace),
            'departments' => Department::forWorkspace($workspace)->where('is_active', true)->orderBy('code')->get(),
            'months' => self::MONTHS,
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $this->validated($request, $workspace);
        $this->assertAccountIsBudgetable($workspace, (int) $data['account_id']);

        Budget::create([
            'workspace_id' => $workspace->id,
            'account_id' => $data['account_id'],
            'department_id' => $data['department_id'] ?? null,
            'fiscal_year' => $data['fiscal_year'],
            'fiscal_month' => $data['fiscal_month'] ?: null,
            'amount' => $data['amount'],
        ]);

        AccountingAuditLog::record($workspace, 'budget.created', null,
            sprintf('FY%d M%s · ฿%s', $data['fiscal_year'], $data['fiscal_month'] ?: '—', $data['amount']));

        return redirect()->route('accounting.budgets.index', ['year' => $data['fiscal_year']])
            ->with('status', __('app.accounting.budget_saved'));
    }

    public function edit(Budget $budget)
    {
        $workspace = $this->scopedBudget($budget);

        return view('accounting.budgets.form', [
            'budget' => $budget,
            'accounts' => $this->budgetableAccounts($workspace),
            'departments' => Department::forWorkspace($workspace)->where('is_active', true)->orderBy('code')->get(),
            'months' => self::MONTHS,
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        $workspace = $this->scopedBudget($budget);
        $this->assertPoster($workspace);

        $data = $this->validated($request, $workspace, $budget);
        $this->assertAccountIsBudgetable($workspace, (int) $data['account_id']);

        $budget->update([
            'account_id' => $data['account_id'],
            'department_id' => $data['department_id'] ?? null,
            'fiscal_year' => $data['fiscal_year'],
            'fiscal_month' => $data['fiscal_month'] ?: null,
            'amount' => $data['amount'],
        ]);

        AccountingAuditLog::record($workspace, 'budget.updated', $budget,
            sprintf('FY%d M%s · ฿%s', $data['fiscal_year'], $data['fiscal_month'] ?: '—', $data['amount']));

        return redirect()->route('accounting.budgets.index', ['year' => $data['fiscal_year']])
            ->with('status', __('app.accounting.budget_updated'));
    }

    public function destroy(Budget $budget)
    {
        $workspace = $this->scopedBudget($budget);
        $this->assertPoster($workspace);

        $year = $budget->fiscal_year;
        AccountingAuditLog::record($workspace, 'budget.deleted', null,
            sprintf('FY%d M%s', $budget->fiscal_year, $budget->fiscal_month ?: '—'));
        $budget->delete();

        return redirect()->route('accounting.budgets.index', ['year' => $year])
            ->with('status', __('app.accounting.budget_deleted'));
    }

    private function scopedBudget(Budget $budget): Workspace
    {
        $workspace = $this->requireWorkspace();
        abort_unless($budget->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    /** Only income and expense accounts make sense to budget. */
    private function budgetableAccounts(Workspace $workspace)
    {
        return Account::forWorkspace($workspace)
            ->whereIn('type', ['income', 'expense'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function assertAccountIsBudgetable(Workspace $workspace, int $accountId): void
    {
        $account = Account::forWorkspace($workspace)->find($accountId);
        abort_unless($account && in_array($account->type, ['income', 'expense'], true), 422,
            __('app.accounting.budget_account_invalid'));
    }

    private function validated(Request $request, Workspace $workspace, ?Budget $budget = null): array
    {
        $rules = [
            'account_id' => 'required|integer',
            'department_id' => ['nullable', 'integer',
                Rule::exists('accounting_departments', 'id')->where('workspace_id', $workspace->id),
            ],
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'fiscal_month' => 'nullable|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
        ];

        $data = $request->validate($rules);

        // App-layer uniqueness on (account, dept, year, month) — nullable dept
        // means MySQL UNIQUE wouldn't reject duplicates with NULL.
        $duplicate = Budget::forWorkspace($workspace)
            ->where('account_id', $data['account_id'])
            ->where('fiscal_year', $data['fiscal_year'])
            ->when(($data['fiscal_month'] ?? null) === null,
                fn ($q) => $q->whereNull('fiscal_month'),
                fn ($q) => $q->where('fiscal_month', $data['fiscal_month']))
            ->when(($data['department_id'] ?? null) === null,
                fn ($q) => $q->whereNull('department_id'),
                fn ($q) => $q->where('department_id', $data['department_id']))
            ->when($budget, fn ($q) => $q->where('id', '!=', $budget->id))
            ->exists();

        abort_if($duplicate, 422, __('app.accounting.budget_duplicate'));

        return $data;
    }
}
