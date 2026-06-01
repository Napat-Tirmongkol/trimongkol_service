<?php

namespace Tests\Feature\Accounting;

use App\Exceptions\Accounting\PaymentException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Payment;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReceiptsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $w;

    protected function setUp(): void
    {
        parent::setUp();

        $this->w = Workspace::create(['name' => 'ACME Co.', 'slug' => 'acme-'.Str::random(6)]);
        ChartOfAccounts::seed($this->w);
        TaxCodes::seedDefault($this->w);
        Period::create([
            'workspace_id' => $this->w->id, 'name' => '2569',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => Period::STATUS_OPEN,
        ]);
    }

    private function account(string $code): Account
    {
        return Account::where('workspace_id', $this->w->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::where('workspace_id', $this->w->id)->where('code', $code)->firstOrFail();
    }

    private function customer(): Partner
    {
        return Partner::firstOrCreate(
            ['workspace_id' => $this->w->id, 'code' => 'C-001'],
            ['name' => 'ลูกค้า ก', 'is_customer' => true],
        );
    }

    /** An issued invoice for 1,000 + 7% VAT = 1,070 (optionally with a WHT code). */
    private function issuedInvoice(string $amount = '1000', ?string $whtCode = null): Invoice
    {
        $attrs = ['partner_id' => $this->customer()->id, 'issue_date' => '2026-03-01'];
        if ($whtCode) {
            $attrs['wht_tax_code_id'] = $this->taxCode($whtCode)->id;
        }

        return SalesInvoicing::issue(SalesInvoicing::create($this->w, $attrs, [[
            'account_id' => $this->account('4100-02')->id, 'unit_price' => $amount,
            'tax_code_id' => $this->taxCode('VAT7')->id,
        ]]));
    }

    public function test_full_receipt_with_wht_settles_invoice_and_posts(): void
    {
        $invoice = $this->issuedInvoice('1000', 'WHT3'); // total 1,070 — customer withholds 30

        $payment = Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1113-01')->id, // KTB bank
            'payment_date' => '2026-03-15',
            'amount' => '1040',       // cash received
            'wht_amount' => '30',     // withheld by customer
        ], [
            ['invoice_id' => $invoice->id, 'amount' => '1070'],
        ]);

        $this->assertSame(Payment::STATUS_POSTED, $payment->status);
        $this->assertSame('receipt', $payment->journal->type);
        $this->assertSame(Payment::class, $payment->journal->source_type);

        $byAccount = $payment->journal->lines->keyBy('account_id');
        $this->assertSame('1040.00', $byAccount[$this->account('1113-01')->id]->debit);  // DR ธนาคาร
        $this->assertSame('30.00', $byAccount[$this->account('1151-02')->id]->debit);    // DR ภาษีจ่ายล่วงหน้า (WHT)
        $this->assertSame('1070.00', $byAccount[$this->account('1130-01')->id]->credit); // CR ลูกหนี้การค้า

        $debit = $payment->journal->lines->sum(fn ($l) => (float) $l->debit);
        $credit = $payment->journal->lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta($debit, $credit, 0.001);

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_full_receipt_without_wht(): void
    {
        $invoice = $this->issuedInvoice('1000'); // 1,070, no WHT

        $payment = Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1111-00')->id, // cash
            'amount' => '1070',
        ], [
            ['invoice_id' => $invoice->id, 'amount' => '1070'],
        ]);

        $this->assertSame('0.00', $payment->wht_amount);
        $this->assertCount(2, $payment->journal->lines); // DR cash / CR AR only
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_partial_receipt_marks_partial_then_paid(): void
    {
        $invoice = $this->issuedInvoice('1000'); // 1,070

        Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1111-00')->id,
            'amount' => '500',
        ], [['invoice_id' => $invoice->id, 'amount' => '500']]);

        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);

        Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1111-00')->id,
            'amount' => '570',
        ], [['invoice_id' => $invoice->id, 'amount' => '570']]);

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_rejects_allocation_over_outstanding(): void
    {
        $invoice = $this->issuedInvoice('1000'); // 1,070

        $this->expectException(PaymentException::class);
        Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1111-00')->id,
            'amount' => '2000',
        ], [['invoice_id' => $invoice->id, 'amount' => '2000']]);
    }

    public function test_rejects_when_cash_plus_wht_does_not_equal_allocations(): void
    {
        $invoice = $this->issuedInvoice('1000'); // 1,070

        $this->expectException(PaymentException::class);
        Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1111-00')->id,
            'amount' => '1000',   // 1000 + 0 WHT != 1070 allocated
        ], [['invoice_id' => $invoice->id, 'amount' => '1070']]);
    }

    public function test_rejects_invoice_from_another_workspace(): void
    {
        $invoice = $this->issuedInvoice('1000');

        $other = Workspace::create(['name' => 'Other Co.', 'slug' => 'other-'.Str::random(6)]);

        $this->expectException(PaymentException::class);
        Receipts::record($other, [
            'partner_id' => $this->customer()->id, // also from this workspace -> rejected first
            'account_id' => $this->account('1111-00')->id,
            'amount' => '1070',
        ], [['invoice_id' => $invoice->id, 'amount' => '1070']]);
    }

    public function test_duplicate_slip_reference_is_rejected(): void
    {
        $a = $this->issuedInvoice('1000');
        $b = $this->issuedInvoice('1000');

        Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1113-01')->id,
            'amount' => '1070', 'slip_ref' => 'SLIP-001',
        ], [['invoice_id' => $a->id, 'amount' => '1070']]);

        $this->expectException(PaymentException::class);
        Receipts::record($this->w, [
            'partner_id' => $this->customer()->id,
            'account_id' => $this->account('1113-01')->id,
            'amount' => '1070', 'slip_ref' => 'SLIP-001', // reused slip
        ], [['invoice_id' => $b->id, 'amount' => '1070']]);
    }
}
