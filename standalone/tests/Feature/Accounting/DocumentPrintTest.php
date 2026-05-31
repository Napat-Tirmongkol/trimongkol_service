<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Accounting\WhtCertificate;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\SupplierPayments;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentPrintTest extends TestCase
{
    use RefreshDatabase;

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

    private function account(Workspace $w, string $code): Account
    {
        return Account::where('workspace_id', $w->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(Workspace $w, string $code): TaxCode
    {
        return TaxCode::where('workspace_id', $w->id)->where('code', $code)->firstOrFail();
    }

    public function test_invoice_bill_and_wht_certificate_print(): void
    {
        $w = $this->actingOwner();
        $customer = Partner::create(['workspace_id' => $w->id, 'name' => 'ลูกค้า', 'is_customer' => true]);
        $vendor = Partner::create(['workspace_id' => $w->id, 'name' => 'ผู้ขาย', 'tax_id' => '0105500000017', 'is_vendor' => true]);

        $invoice = SalesInvoicing::issue(SalesInvoicing::create($w, [
            'partner_id' => $customer->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account($w, '4100-02')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode($w, 'VAT7')->id]]));

        $this->get(route('accounting.invoices.print', $invoice))->assertOk()->assertSee($invoice->no);

        $bill = Purchasing::post(Purchasing::create($w, [
            'partner_id' => $vendor->id, 'issue_date' => '2026-03-02',
        ], [['account_id' => $this->account($w, '5400-04')->id, 'unit_price' => '1000', 'tax_code_id' => $this->taxCode($w, 'VAT7P')->id]]));

        $this->get(route('accounting.bills.print', $bill))->assertOk()->assertSee($bill->no);

        SupplierPayments::record($w, [
            'partner_id' => $vendor->id, 'account_id' => $this->account($w, '1113-01')->id,
            'payment_date' => '2026-03-20', 'wht_tax_code_id' => $this->taxCode($w, 'WHT3')->id, 'wht_base' => '1000',
        ], [['bill_id' => $bill->id, 'amount' => '1070']]);

        $cert = WhtCertificate::where('workspace_id', $w->id)->firstOrFail();
        $this->get(route('accounting.wht-certificates.index'))->assertOk()->assertSee($cert->no);
        $this->get(route('accounting.wht-certificates.print', $cert))->assertOk()->assertSee('50 ทวิ', false);
    }

    public function test_cannot_print_a_document_from_another_workspace(): void
    {
        $w = $this->actingOwner();
        $customer = Partner::create(['workspace_id' => $w->id, 'name' => 'ลูกค้า', 'is_customer' => true]);
        $invoice = SalesInvoicing::create($w, ['partner_id' => $customer->id, 'issue_date' => '2026-03-01'],
            [['account_id' => $this->account($w, '4100-02')->id, 'unit_price' => '1000']]);

        $this->actingOwner(); // different user + workspace
        $this->get(route('accounting.invoices.print', $invoice))->assertNotFound();
    }
}
