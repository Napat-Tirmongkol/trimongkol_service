<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\Reporting;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportingTest extends TestCase
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

    /**
     * Sell 1,000 (+70 VAT), collect it in cash, and book a 500 (+35 VAT) bill.
     * Ledger after: DR cash 1070, input VAT 35, expense 500 = 1605
     *               CR revenue 1000, output VAT 70, payable 535 = 1605
     */
    private function buildScenario(): void
    {
        $customer = Partner::create(['workspace_id' => $this->w->id, 'name' => 'ลูกค้า', 'is_customer' => true]);
        $vendor = Partner::create(['workspace_id' => $this->w->id, 'name' => 'ผู้ขาย', 'is_vendor' => true]);

        $invoice = SalesInvoicing::issue(SalesInvoicing::create($this->w, [
            'partner_id' => $customer->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account('4100-02')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode('VAT7')->id]]));

        Receipts::record($this->w, [
            'partner_id' => $customer->id, 'account_id' => $this->account('1111-00')->id,
            'payment_date' => '2026-03-10', 'amount' => '1070',
        ], [['invoice_id' => $invoice->id, 'amount' => '1070']]);

        Purchasing::post(Purchasing::create($this->w, [
            'partner_id' => $vendor->id, 'issue_date' => '2026-03-05',
        ], [['account_id' => $this->account('5400-04')->id, 'unit_price' => '500', 'tax_code_id' => $this->taxCode('VAT7P')->id]]));
    }

    public function test_trial_balance_ties_out(): void
    {
        $this->buildScenario();
        $tb = Reporting::trialBalance($this->w, '2026-12-31');

        $this->assertTrue($tb['balanced']);
        $this->assertSame('1605.00', $tb['total_debit']);
        $this->assertSame('1605.00', $tb['total_credit']);

        // The fully-settled receivable nets to zero and is omitted.
        $codes = collect($tb['rows'])->map(fn ($r) => $r['account']->code);
        $this->assertFalse($codes->contains('1130-01'));
        $this->assertTrue($codes->contains('1111-00'));
    }

    public function test_profit_and_loss(): void
    {
        $this->buildScenario();
        $pnl = Reporting::profitAndLoss($this->w, '2026-01-01', '2026-12-31');

        $this->assertSame('1000.00', $pnl['income_total']);
        $this->assertSame('500.00', $pnl['expense_total']);
        $this->assertSame('500.00', $pnl['net_profit']);
    }

    public function test_balance_sheet_balances(): void
    {
        $this->buildScenario();
        $bs = Reporting::balanceSheet($this->w, '2026-12-31');

        $this->assertTrue($bs['balanced']);
        $this->assertSame('1105.00', $bs['assets_total']);          // cash 1070 + input VAT 35
        $this->assertSame('500.00', $bs['net_income']);             // current-period profit
        $this->assertSame('1105.00', $bs['liabilities_and_equity']);
    }

    public function test_empty_books_are_balanced(): void
    {
        $tb = Reporting::trialBalance($this->w, '2026-12-31');
        $this->assertTrue($tb['balanced']);
        $this->assertSame([], $tb['rows']);
        $this->assertTrue(Reporting::balanceSheet($this->w, '2026-12-31')['balanced']);
    }
}
