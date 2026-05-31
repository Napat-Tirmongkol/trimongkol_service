<?php

namespace Tests\Feature\Accounting;

use App\Exceptions\Accounting\PaymentException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Payment;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Accounting\WhtCertificate;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\SupplierPayments;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountsPayableTest extends TestCase
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

    private function vendor(): Partner
    {
        return Partner::firstOrCreate(
            ['workspace_id' => $this->w->id, 'code' => 'V-001'],
            ['name' => 'ผู้ขาย ก', 'tax_id' => '0105500000017', 'is_vendor' => true],
        );
    }

    private function postedBill(string $amount = '1000'): Bill
    {
        $bill = Purchasing::create($this->w, [
            'partner_id' => $this->vendor()->id, 'issue_date' => '2026-03-01',
        ], [[
            'account_id' => $this->account('5400-04')->id, 'description' => 'ค่าจ้างเหมา',
            'unit_price' => $amount, 'tax_code_id' => $this->taxCode('VAT7P')->id,
        ]]);

        return Purchasing::post($bill);
    }

    public function test_post_bill_records_expense_input_vat_and_payable(): void
    {
        $bill = $this->postedBill('1000');

        $this->assertSame(Bill::STATUS_ISSUED, $bill->status);
        $this->assertSame('1000.00', $bill->subtotal);
        $this->assertSame('70.00', $bill->vat_amount);
        $this->assertSame('1070.00', $bill->total);

        $byAccount = $bill->journal->lines->keyBy('account_id');
        $this->assertSame('1000.00', $byAccount[$this->account('5400-04')->id]->debit); // DR ค่าจ้างและค่าบริการ
        $this->assertSame('70.00', $byAccount[$this->account('1154-00')->id]->debit);   // DR ภาษีซื้อ
        $this->assertSame('1070.00', $byAccount[$this->account('2120-01')->id]->credit); // CR เจ้าหนี้การค้า

        $debit = $bill->journal->lines->sum(fn ($l) => (float) $l->debit);
        $credit = $bill->journal->lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta($debit, $credit, 0.001);
    }

    public function test_pay_supplier_with_wht_settles_bill_and_issues_certificate(): void
    {
        $bill = $this->postedBill('1000'); // total 1,070

        $payment = SupplierPayments::record($this->w, [
            'partner_id' => $this->vendor()->id,
            'account_id' => $this->account('1113-01')->id,
            'payment_date' => '2026-03-20',
            'wht_tax_code_id' => $this->taxCode('WHT3')->id,
            'wht_base' => '1000',        // 3% of 1,000 = 30 withheld
            'income_type' => 'ค่าจ้างทำของ',
        ], [
            ['bill_id' => $bill->id, 'amount' => '1070'],
        ]);

        $this->assertSame(Payment::DIRECTION_OUT, $payment->direction);
        $this->assertSame('1040.00', $payment->amount);     // cash out = 1,070 − 30
        $this->assertSame('30.00', $payment->wht_amount);

        $byAccount = $payment->journal->lines->keyBy('account_id');
        $this->assertSame('1070.00', $byAccount[$this->account('2120-01')->id]->debit);  // DR เจ้าหนี้การค้า
        $this->assertSame('1040.00', $byAccount[$this->account('1113-01')->id]->credit); // CR ธนาคาร
        $this->assertSame('30.00', $byAccount[$this->account('2132-04')->id]->credit);   // CR ภาษีหัก ณ ที่จ่าย ภงด.53

        $this->assertSame(Bill::STATUS_PAID, $bill->fresh()->status);

        $cert = WhtCertificate::where('workspace_id', $this->w->id)->firstOrFail();
        $this->assertSame(WhtCertificate::PND53, $cert->pnd_type);
        $this->assertSame('1000.00', $cert->base_amount);
        $this->assertSame('30.00', $cert->wht_amount);
        $this->assertSame('0105500000017', $cert->tax_id);
    }

    public function test_pay_supplier_without_wht(): void
    {
        $bill = $this->postedBill('1000');

        $payment = SupplierPayments::record($this->w, [
            'partner_id' => $this->vendor()->id,
            'account_id' => $this->account('1111-00')->id,
        ], [['bill_id' => $bill->id, 'amount' => '1070']]);

        $this->assertSame('1070.00', $payment->amount);
        $this->assertSame('0.00', $payment->wht_amount);
        $this->assertCount(2, $payment->journal->lines); // DR AP / CR cash
        $this->assertSame(Bill::STATUS_PAID, $bill->fresh()->status);
        $this->assertSame(0, WhtCertificate::where('workspace_id', $this->w->id)->count());
    }

    public function test_rejects_allocation_over_outstanding(): void
    {
        $bill = $this->postedBill('1000');

        $this->expectException(PaymentException::class);
        SupplierPayments::record($this->w, [
            'partner_id' => $this->vendor()->id,
            'account_id' => $this->account('1111-00')->id,
        ], [['bill_id' => $bill->id, 'amount' => '5000']]);
    }

    public function test_rejects_a_bill_from_another_workspace(): void
    {
        $bill = $this->postedBill('1000');

        $other = Workspace::create(['name' => 'Other Co.', 'slug' => 'other-'.Str::random(6)]);

        $this->expectException(PaymentException::class);
        SupplierPayments::record($other, [
            'partner_id' => $this->vendor()->id,
            'account_id' => $this->account('1111-00')->id,
        ], [['bill_id' => $bill->id, 'amount' => '1070']]);
    }
}
