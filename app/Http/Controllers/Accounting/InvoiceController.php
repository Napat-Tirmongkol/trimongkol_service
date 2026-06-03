<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Models\Accounting\TaxCode;
use App\Services\Accounting\Money;
use App\Services\Accounting\AccountingAuditLog;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\SalesInvoicing;
use App\Services\AccountingPlan;
use Illuminate\Http\Request;

class InvoiceController extends AccountingController
{
    public function index()
    {
        $workspace = $this->currentWorkspace();
        $invoices = $workspace
            ? Invoice::forWorkspace($workspace)->with('partner')
                ->latest('issue_date')->latest('id')->paginate(25)
            : collect();

        return view('accounting.invoices.index', compact('workspace', 'invoices'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }
        if ($reason = AccountingPlan::reasonCannotCreateInvoice($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', $reason);
        }

        $partners = Partner::forWorkspace($workspace)->where('is_customer', true)->orderBy('name')->get();
        if ($partners->isEmpty()) {
            return redirect()->route('accounting.partners.create')->with('error', __('app.accounting.need_customer_first'));
        }

        $revenueAccounts = Account::forWorkspace($workspace)->where('type', 'income')->where('is_active', true)->orderBy('code')->get();
        $vatCodes = TaxCode::forWorkspace($workspace)->where('kind', 'vat_output')->where('is_active', true)->get();
        $whtCodes = TaxCode::forWorkspace($workspace)->where('kind', 'wht')->where('is_active', true)->get();

        return view('accounting.invoices.create', compact('partners', 'revenueAccounts', 'vatCodes', 'whtCodes'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if ($reason = AccountingPlan::reasonCannotCreateInvoice($workspace)) {
            return back()->withInput()->with('error', $reason);
        }

        $data = $request->validate([
            'partner_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'wht_tax_code_id' => 'nullable|integer',
            'memo' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|integer',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_code_id' => 'nullable|integer',
        ]);

        try {
            $invoice = SalesInvoicing::create($workspace, [
                'partner_id' => $data['partner_id'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'wht_tax_code_id' => $data['wht_tax_code_id'] ?? null,
                'memo' => $data['memo'] ?? null,
            ], $data['lines']);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'invoice.created', $invoice, $invoice->no);

        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('status', __('app.accounting.invoice_created'));
    }

    public function edit(Invoice $invoice)
    {
        $workspace = $this->scopedInvoice($invoice);

        if (! $invoice->isDraft()) {
            return redirect()->route('accounting.invoices.show', $invoice)->with('error', __('app.accounting.invoice_edit_only_draft'));
        }

        $invoice->load('lines');
        $partners = Partner::forWorkspace($workspace)->where('is_customer', true)->orderBy('name')->get();
        $revenueAccounts = Account::forWorkspace($workspace)->where('type', 'income')->where('is_active', true)->orderBy('code')->get();
        $vatCodes = TaxCode::forWorkspace($workspace)->where('kind', 'vat_output')->where('is_active', true)->get();
        $whtCodes = TaxCode::forWorkspace($workspace)->where('kind', 'wht')->where('is_active', true)->get();

        return view('accounting.invoices.edit', compact('invoice', 'partners', 'revenueAccounts', 'vatCodes', 'whtCodes'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $workspace = $this->scopedInvoice($invoice);

        if (! $invoice->isDraft()) {
            return redirect()->route('accounting.invoices.show', $invoice)->with('error', __('app.accounting.invoice_edit_only_draft'));
        }

        $data = $request->validate([
            'partner_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'wht_tax_code_id' => 'nullable|integer',
            'memo' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|integer',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_code_id' => 'nullable|integer',
        ]);

        try {
            SalesInvoicing::update($invoice, [
                'partner_id' => $data['partner_id'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'wht_tax_code_id' => $data['wht_tax_code_id'] ?? null,
                'memo' => $data['memo'] ?? null,
            ], $data['lines']);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'invoice.updated', $invoice, $invoice->no);

        return redirect()->route('accounting.invoices.show', $invoice)->with('status', __('app.accounting.invoice_updated'));
    }

    public function show(Invoice $invoice)
    {
        $workspace = $this->scopedInvoice($invoice);
        $invoice->load('partner', 'lines.account', 'lines.taxCode', 'journal.lines.account', 'attachments.accountingUser');

        $outstandingMinor = Money::toMinor(Receipts::outstanding($invoice));
        // Pre-fill the receipt form: default WHT to the expected amount on a
        // first full settlement, with cash = outstanding − WHT.
        $whtDefaultMinor = $outstandingMinor === Money::toMinor($invoice->total)
            ? Money::toMinor($invoice->wht_amount)
            : 0;

        $canPost = auth('accounting')->user()?->canPost() ?? false;

        return view('accounting.invoices.show', [
            'invoice' => $invoice,
            'outstanding' => Money::fromMinor($outstandingMinor),
            'cashDefault' => Money::fromMinor($outstandingMinor - $whtDefaultMinor),
            'whtDefault' => Money::fromMinor($whtDefaultMinor),
            'bankAccounts' => Account::forWorkspace($workspace)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get(),
            'canPost' => $canPost,
            'pendingApproval' => $invoice->pendingApproval,
        ]);
    }

    public function print(Invoice $invoice)
    {
        $this->scopedInvoice($invoice);
        $invoice->load('partner', 'lines.account');

        return view('accounting.print.invoice', compact('invoice'));
    }

    public function issue(Invoice $invoice)
    {
        $workspace = $this->scopedInvoice($invoice);
        $this->assertPoster($workspace);

        try {
            SalesInvoicing::issue($invoice);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'invoice.issued', $invoice, $invoice->no);

        return back()->with('status', __('app.accounting.invoice_issued'));
    }

    public function void(Invoice $invoice)
    {
        $workspace = $this->scopedInvoice($invoice);
        $this->assertPoster($workspace);

        try {
            SalesInvoicing::void($invoice);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'invoice.voided', $invoice, $invoice->no);

        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('status', __('app.accounting.invoice_voided'));
    }

    public function recordReceipt(Request $request, Invoice $invoice)
    {
        $workspace = $this->scopedInvoice($invoice);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'account_id' => 'required|integer',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'wht_amount' => 'nullable|numeric|min:0',
            'slip_ref' => 'nullable|string|max:120',
        ]);

        $allocation = Money::fromMinor(Money::toMinor($data['amount']) + Money::toMinor($data['wht_amount'] ?? 0));

        try {
            Receipts::record($workspace, [
                'partner_id' => $invoice->partner_id,
                'account_id' => $data['account_id'],
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'wht_amount' => $data['wht_amount'] ?? 0,
                'slip_ref' => $data['slip_ref'] ?? null,
            ], [
                ['invoice_id' => $invoice->id, 'amount' => $allocation],
            ]);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'invoice.receipt_recorded', $invoice, $invoice->no,
            ['amount' => $data['amount']]);

        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('status', __('app.accounting.receipt_recorded'));
    }
}
