<?php

namespace Tests\Feature\Accounting;

use App\Exceptions\Accounting\InvoiceException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesInvoicingTest extends TestCase
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
            ['name' => 'ลูกค้า ก', 'tax_id' => '0105500000017', 'is_customer' => true, 'credit_days' => 30],
        );
    }

    private function draftWithVat(string $amount = '1000'): Invoice
    {
        return SalesInvoicing::create($this->w, [
            'partner_id' => $this->customer()->id,
            'issue_date' => '2026-03-01',
        ], [[
            'account_id' => $this->account('4100-02')->id, 'description' => 'ค่าบริการ',
            'quantity' => 1, 'unit_price' => $amount, 'tax_code_id' => $this->taxCode('VAT7')->id,
        ]]);
    }

    public function test_create_draft_computes_subtotal_vat_and_total(): void
    {
        $invoice = $this->draftWithVat('1000');

        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertSame('INV2569-00001', $invoice->no);
        $this->assertSame('1000.00', $invoice->subtotal);
        $this->assertSame('70.00', $invoice->vat_amount);
        $this->assertSame('1070.00', $invoice->total);
        $this->assertNull($invoice->journal_id);
        $this->assertCount(1, $invoice->lines);
        $this->assertSame('1000.00', $invoice->lines->first()->amount);
        $this->assertSame('2026-03-31', $invoice->due_date->toDateString()); // credit term from partner
    }

    public function test_issue_posts_a_balanced_journal_to_the_right_accounts(): void
    {
        $invoice = SalesInvoicing::issue($this->draftWithVat('1000'));

        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->status);
        $this->assertNotNull($invoice->journal_id);

        $journal = $invoice->journal;
        $this->assertSame('sales', $journal->type);
        $this->assertSame(Invoice::class, $journal->source_type);
        $this->assertSame($invoice->id, (int) $journal->source_id);

        $byAccount = $journal->lines->keyBy('account_id');
        $this->assertSame('1070.00', $byAccount[$this->account('1130-01')->id]->debit);  // DR ลูกหนี้การค้า
        $this->assertSame('1000.00', $byAccount[$this->account('4100-02')->id]->credit); // CR รายได้บริการ
        $this->assertSame('70.00', $byAccount[$this->account('2135-00')->id]->credit);   // CR ภาษีขาย

        $debit = $journal->lines->sum(fn ($l) => (float) $l->debit);
        $credit = $journal->lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta($debit, $credit, 0.001);
        $this->assertEqualsWithDelta(1070.0, $debit, 0.001);
    }

    public function test_wht_is_recorded_but_not_posted(): void
    {
        $invoice = SalesInvoicing::create($this->w, [
            'partner_id' => $this->customer()->id,
            'issue_date' => '2026-03-01',
            'wht_tax_code_id' => $this->taxCode('WHT3')->id,
        ], [[
            'account_id' => $this->account('4100-02')->id, 'unit_price' => '1000',
            'tax_code_id' => $this->taxCode('VAT7')->id,
        ]]);

        $this->assertSame('30.00', $invoice->wht_amount); // 3% of 1,000 — informational only

        $issued = SalesInvoicing::issue($invoice);

        // The posted journal must not touch the WHT payable account, and stays balanced.
        $this->assertFalse($issued->journal->lines->contains('account_id', $this->account('2132-04')->id));
        $this->assertEqualsWithDelta(1070.0, $issued->journal->lines->sum(fn ($l) => (float) $l->debit), 0.001);
    }

    public function test_invoice_number_increments_per_workspace(): void
    {
        $this->assertSame('INV2569-00001', $this->draftWithVat('100')->no);
        $this->assertSame('INV2569-00002', $this->draftWithVat('200')->no);
    }

    public function test_cannot_issue_twice(): void
    {
        $invoice = SalesInvoicing::issue($this->draftWithVat('500'));

        $this->expectException(InvoiceException::class);
        SalesInvoicing::issue($invoice);
    }

    public function test_rejects_a_partner_from_another_workspace(): void
    {
        $other = Workspace::create(['name' => 'Other Co.', 'slug' => 'other-'.Str::random(6)]);
        $foreign = Partner::create(['workspace_id' => $other->id, 'name' => 'X', 'is_customer' => true]);

        $this->expectException(InvoiceException::class);
        SalesInvoicing::create($this->w, ['partner_id' => $foreign->id, 'issue_date' => '2026-03-01'], [
            ['account_id' => $this->account('4100-02')->id, 'unit_price' => '100'],
        ]);
    }
}
