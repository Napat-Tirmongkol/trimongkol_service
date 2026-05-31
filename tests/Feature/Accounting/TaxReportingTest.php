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
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\SupplierPayments;
use App\Services\Accounting\TaxCodes;
use App\Services\Accounting\TaxReporting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaxReportingTest extends TestCase
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
        Period::create([
            'workspace_id' => $workspace->id, 'name' => '2569',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => Period::STATUS_OPEN,
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

    /** One issued invoice, one posted bill, one supplier payment with WHT — the data every report draws on. */
    private function seedActivity(): void
    {
        $customer = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ลูกค้า', 'tax_id' => '0105500000017', 'is_customer' => true]);
        $vendor = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ผู้ขาย', 'tax_id' => '0105500000025', 'is_vendor' => true]);

        SalesInvoicing::issue(SalesInvoicing::create($this->workspace, [
            'partner_id' => $customer->id, 'issue_date' => '2026-03-05',
        ], [['account_id' => $this->account('4100-02')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode('VAT7')->id]]));

        $bill = Purchasing::post(Purchasing::create($this->workspace, [
            'partner_id' => $vendor->id, 'issue_date' => '2026-03-10',
        ], [['account_id' => $this->account('5400-04')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode('VAT7P')->id]]));

        SupplierPayments::record($this->workspace, [
            'partner_id' => $vendor->id, 'account_id' => $this->account('1113-01')->id,
            'payment_date' => '2026-03-20', 'wht_tax_code_id' => $this->taxCode('WHT3')->id, 'wht_base' => '1000',
        ], [['bill_id' => $bill->id, 'amount' => '1070']]);
    }

    public function test_vat_and_wht_summaries_total_the_period_activity(): void
    {
        $this->workspace = $this->actingOwner();
        $this->seedActivity();

        $sales = TaxReporting::vatSales($this->workspace, '2026-03-01', '2026-03-31');
        $this->assertCount(1, $sales['rows']);
        $this->assertSame('1000.00', $sales['base_total']);
        $this->assertSame('70.00', $sales['vat_total']);

        $purchases = TaxReporting::vatPurchases($this->workspace, '2026-03-01', '2026-03-31');
        $this->assertCount(1, $purchases['rows']);
        $this->assertSame('1000.00', $purchases['base_total']);
        $this->assertSame('70.00', $purchases['vat_total']);

        $wht = TaxReporting::wht($this->workspace, '2026-03-01', '2026-03-31');
        $this->assertCount(1, $wht['rows']);
        $this->assertSame('30.00', $wht['wht_total']);
        // WHT3 (ค่าบริการ) is wired to the company bucket, ภ.ง.ด.53.
        $this->assertSame('30.00', $wht['pnd53_total']);
        $this->assertSame('0.00', $wht['pnd3_total']);
    }

    public function test_reports_are_scoped_to_the_date_window(): void
    {
        $this->workspace = $this->actingOwner();
        $this->seedActivity();

        $sales = TaxReporting::vatSales($this->workspace, '2026-04-01', '2026-04-30');
        $this->assertCount(0, $sales['rows']);
        $this->assertSame('0.00', $sales['vat_total']);

        $wht = TaxReporting::wht($this->workspace, '2026-04-01', '2026-04-30');
        $this->assertCount(0, $wht['rows']);
        $this->assertSame('0.00', $wht['wht_total']);
    }

    public function test_draft_documents_are_excluded(): void
    {
        $this->workspace = $this->actingOwner();
        $customer = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ลูกค้า', 'is_customer' => true]);

        // Draft (never issued) — must not appear in the output-VAT report.
        SalesInvoicing::create($this->workspace, ['partner_id' => $customer->id, 'issue_date' => '2026-03-05'],
            [['account_id' => $this->account('4100-02')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode('VAT7')->id]]);

        $sales = TaxReporting::vatSales($this->workspace, '2026-03-01', '2026-03-31');
        $this->assertCount(0, $sales['rows']);
    }

    public function test_tax_report_page_renders(): void
    {
        $this->workspace = $this->actingOwner();
        $this->seedActivity();

        $this->get(route('accounting.reports.tax', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('ลูกค้า')
            ->assertSee('ผู้ขาย')
            ->assertSee('0105500000017'); // customer tax id in the sales report
    }

    public function test_journal_export_streams_posted_lines_as_csv(): void
    {
        $this->workspace = $this->actingOwner();
        $this->seedActivity();

        $response = $this->get(route('accounting.reports.export', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('4100-02', $csv); // revenue from the invoice
        $this->assertStringContainsString('5400-04', $csv); // expense from the bill
        $this->assertStringContainsString('Debit', $csv);   // header row present
    }
}
