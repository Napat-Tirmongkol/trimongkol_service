<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Partner;
use App\Models\Accounting\TaxCode;
use App\Services\Accounting\Money;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\SupplierPayments;
use Illuminate\Http\Request;

class BillController extends AccountingController
{
    public function index()
    {
        $workspace = $this->currentWorkspace();
        $bills = $workspace
            ? Bill::forWorkspace($workspace)->with('partner')->latest('issue_date')->latest('id')->paginate(25)
            : collect();

        return view('accounting.bills.index', compact('workspace', 'bills'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $vendors = Partner::forWorkspace($workspace)->where('is_vendor', true)->orderBy('name')->get();
        if ($vendors->isEmpty()) {
            return redirect()->route('accounting.partners.create')->with('error', __('app.accounting.need_vendor_first'));
        }

        $expenseAccounts = Account::forWorkspace($workspace)->where('type', 'expense')->where('is_active', true)->orderBy('code')->get();
        $vatCodes = TaxCode::forWorkspace($workspace)->where('kind', 'vat_input')->where('is_active', true)->get();

        return view('accounting.bills.create', compact('vendors', 'expenseAccounts', 'vatCodes'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();

        $data = $request->validate([
            'partner_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'bill_ref' => 'nullable|string|max:120',
            'memo' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|integer',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_code_id' => 'nullable|integer',
        ]);

        try {
            $bill = Purchasing::create($workspace, [
                'partner_id' => $data['partner_id'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'bill_ref' => $data['bill_ref'] ?? null,
                'memo' => $data['memo'] ?? null,
            ], $data['lines']);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.bills.show', $bill)->with('status', __('app.accounting.bill_created'));
    }

    public function show(Bill $bill)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($bill->workspace_id === $workspace->id, 404);
        $bill->load('partner', 'lines.account', 'lines.taxCode', 'journal.lines.account');

        return view('accounting.bills.show', [
            'bill' => $bill,
            'outstanding' => SupplierPayments::outstanding($bill),
            'bankAccounts' => Account::forWorkspace($workspace)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get(),
            'whtCodes' => TaxCode::forWorkspace($workspace)->where('kind', 'wht')->where('is_active', true)->get(),
            'canPost' => in_array($workspace->roleFor(auth()->user()), ['owner', 'admin'], true),
        ]);
    }

    public function print(Bill $bill)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($bill->workspace_id === $workspace->id, 404);
        $bill->load('partner', 'lines.account');

        return view('accounting.print.bill', compact('bill'));
    }

    public function post(Bill $bill)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($bill->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        try {
            Purchasing::post($bill);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.bill_posted'));
    }

    public function recordPayment(Request $request, Bill $bill)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($bill->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'account_id' => 'required|integer',
            'payment_date' => 'required|date',
            'wht_tax_code_id' => 'nullable|integer',
            'wht_base' => 'nullable|numeric|min:0',
            'income_type' => 'nullable|string|max:120',
        ]);

        try {
            SupplierPayments::record($workspace, [
                'partner_id' => $bill->partner_id,
                'account_id' => $data['account_id'],
                'payment_date' => $data['payment_date'],
                'wht_tax_code_id' => $data['wht_tax_code_id'] ?? null,
                'wht_base' => $data['wht_base'] ?? null,
                'income_type' => $data['income_type'] ?? null,
            ], [
                ['bill_id' => $bill->id, 'amount' => SupplierPayments::outstanding($bill)],
            ]);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.bills.show', $bill)->with('status', __('app.accounting.payment_recorded'));
    }
}
