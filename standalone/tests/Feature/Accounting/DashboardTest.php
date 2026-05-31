<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\SupplierPayments;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private function actingOwner(): Workspace
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'ACME Co.', 'slug' => 'acme-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        ChartOfAccounts::seed($workspace);
        TaxCodes::seedDefault($workspace);
        // A wide open period so dates around "today" always post cleanly.
        Period::create([
            'workspace_id' => $workspace->id, 'name' => 'test',
            'starts_on' => now()->subYear()->startOfYear()->toDateString(),
            'ends_on' => now()->addYear()->endOfYear()->toDateString(),
            'status' => Period::STATUS_OPEN,
        ]);
        $this->actingAs($user);

        return $workspace;
    }

    private function account(string $code): Account
    {
        return Account::where('workspace_id', $this->workspace->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::where('workspace_id', $this->workspace->id)->where('code', $code)->firstOrFail();
    }

    public function test_dashboard_shows_the_finance_overview(): void
    {
        $this->workspace = $this->actingOwner();
        $today = now()->toDateString();
        $customer = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ลูกค้า', 'is_customer' => true]);
        $vendor = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ผู้ขาย', 'is_vendor' => true]);

        // Sale 2,000 + 7% VAT = 2,140; collect 2,000 to the bank (partial).
        $invoice = SalesInvoicing::issue(SalesInvoicing::create($this->workspace, [
            'partner_id' => $customer->id, 'issue_date' => $today,
        ], [['account_id' => $this->account('4100-02')->id, 'unit_price' => '2000', 'tax_code_id' => $this->taxCode('VAT7')->id]]));
        Receipts::record($this->workspace, [
            'partner_id' => $customer->id, 'account_id' => $this->account('1113-01')->id,
            'payment_date' => $today, 'amount' => '2000',
        ], [['invoice_id' => $invoice->id, 'amount' => '2000']]);

        // Purchase 1,000 + 7% VAT = 1,070; pay it with 3% WHT (30) → bank out 1,040.
        $bill = Purchasing::post(Purchasing::create($this->workspace, [
            'partner_id' => $vendor->id, 'issue_date' => $today,
        ], [['account_id' => $this->account('5400-04')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode('VAT7P')->id]]));
        SupplierPayments::record($this->workspace, [
            'partner_id' => $vendor->id, 'account_id' => $this->account('1113-01')->id,
            'payment_date' => $today, 'wht_tax_code_id' => $this->taxCode('WHT3')->id, 'wht_base' => '1000',
        ], [['bill_id' => $bill->id, 'amount' => '1070']]);

        $response = $this->get(route('accounting.dashboard'))->assertOk()
            ->assertSee(__('app.accounting.stat_cash'))
            ->assertSee(__('app.accounting.taxes_due'))
            ->assertSee(__('app.accounting.action_items'))
            ->assertSee(__('app.accounting.all_clear'));

        $f = $response->viewData('finance');
        $this->assertSame('960.00', $f['cash']);        // 2,000 in − 1,040 out
        $this->assertSame('140.00', $f['ar']);          // 2,140 invoiced − 2,000 received
        $this->assertSame('0.00', $f['ap']);            // bill fully paid
        $this->assertSame('1000.00', $f['net_profit']); // revenue 2,000 − expense 1,000
        $this->assertSame('70.00', $f['vat_net']);      // output 140 − input 70
        $this->assertSame('30.00', $f['wht_pnd53']);    // WHT3 → company bucket
        $this->assertSame('0.00', $f['wht_pnd3']);
        $this->assertSame(0, $f['draft_invoices']);
        $this->assertSame(0, $f['draft_bills']);
        $this->assertSame(0, $f['overdue_count']);
    }

    public function test_dashboard_flags_drafts_and_overdue_invoices(): void
    {
        $this->workspace = $this->actingOwner();
        $customer = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ลูกค้า', 'is_customer' => true]);
        $vendor = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ผู้ขาย', 'is_vendor' => true]);

        // A draft invoice and a draft bill (never posted).
        SalesInvoicing::create($this->workspace, ['partner_id' => $customer->id, 'issue_date' => now()->toDateString()],
            [['account_id' => $this->account('4100-02')->id, 'unit_price' => '500']]);
        Purchasing::create($this->workspace, ['partner_id' => $vendor->id, 'issue_date' => now()->toDateString()],
            [['account_id' => $this->account('5400-04')->id, 'unit_price' => '300']]);

        // An issued invoice already past its due date → overdue (500 + 7% = 535).
        SalesInvoicing::issue(SalesInvoicing::create($this->workspace, [
            'partner_id' => $customer->id,
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
        ], [['account_id' => $this->account('4100-02')->id, 'unit_price' => '500', 'tax_code_id' => $this->taxCode('VAT7')->id]]));

        $response = $this->get(route('accounting.dashboard'))->assertOk()
            ->assertSee(__('app.accounting.overdue_invoices_n'))
            ->assertSee(__('app.accounting.draft_invoices_n'))
            ->assertSee(__('app.accounting.draft_bills_n'))
            ->assertDontSee(__('app.accounting.all_clear'));

        $f = $response->viewData('finance');
        $this->assertSame(1, $f['draft_invoices']);
        $this->assertSame(1, $f['draft_bills']);
        $this->assertSame(1, $f['overdue_count']);
        $this->assertSame('535.00', $f['overdue_amount']);
    }
}
