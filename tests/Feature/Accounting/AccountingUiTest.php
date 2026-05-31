<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingUiTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Workspace} */
    private function actingMember(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'ACME Co.', 'slug' => 'acme-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);
        $this->actingAs($user);

        return [$user, $workspace];
    }

    private function seedAccounting(Workspace $workspace): void
    {
        ChartOfAccounts::seed($workspace);
        TaxCodes::seedDefault($workspace);
        Period::create([
            'workspace_id' => $workspace->id, 'name' => '2569',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => Period::STATUS_OPEN,
        ]);
    }

    private function account(Workspace $workspace, string $code): Account
    {
        return Account::where('workspace_id', $workspace->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(Workspace $workspace, string $code): TaxCode
    {
        return TaxCode::where('workspace_id', $workspace->id)->where('code', $code)->firstOrFail();
    }

    private function customer(Workspace $workspace): Partner
    {
        return Partner::create(['workspace_id' => $workspace->id, 'name' => 'ลูกค้า ก', 'is_customer' => true]);
    }

    public function test_owner_sees_setup_then_bootstraps_the_books(): void
    {
        [, $workspace] = $this->actingMember('owner');

        $this->get(route('accounting.dashboard'))->assertOk();

        $this->post(route('accounting.setup'))->assertRedirect(route('accounting.dashboard'));

        $this->assertSame(count(ChartOfAccounts::template()), Account::forWorkspace($workspace)->count());
        $this->assertGreaterThan(0, TaxCode::forWorkspace($workspace)->count());
        $this->assertTrue(Period::forWorkspace($workspace)->exists());
    }

    public function test_member_cannot_set_up_accounting(): void
    {
        $this->actingMember('member');
        $this->post(route('accounting.setup'))->assertForbidden();
    }

    public function test_owner_creates_a_partner_via_the_form(): void
    {
        [, $workspace] = $this->actingMember('owner');

        $this->get(route('accounting.partners.create'))->assertOk();
        $this->post(route('accounting.partners.store'), [
            'name' => 'บริษัท ลูกค้า จำกัด', 'tax_id' => '0105500000017', 'credit_days' => 30, 'is_customer' => '1',
        ])->assertRedirect(route('accounting.partners.index'));

        $this->assertDatabaseHas('accounting_partners', [
            'workspace_id' => $workspace->id, 'name' => 'บริษัท ลูกค้า จำกัด', 'credit_days' => 30,
        ]);
    }

    public function test_create_then_issue_invoice_posts_a_journal(): void
    {
        [, $workspace] = $this->actingMember('owner');
        $this->seedAccounting($workspace);
        $customer = $this->customer($workspace);

        $this->get(route('accounting.invoices.create'))->assertOk();

        $this->post(route('accounting.invoices.store'), [
            'partner_id' => $customer->id,
            'issue_date' => '2026-03-01',
            'lines' => [[
                'account_id' => $this->account($workspace, '4100-02')->id,
                'description' => 'ค่าบริการ', 'quantity' => 1, 'unit_price' => '1000',
                'tax_code_id' => $this->taxCode($workspace, 'VAT7')->id,
            ]],
        ])->assertRedirect();

        $invoice = Invoice::forWorkspace($workspace)->firstOrFail();
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertSame('1070.00', $invoice->total);

        $this->post(route('accounting.invoices.issue', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->status);
        $this->assertNotNull($invoice->journal_id);
    }

    public function test_member_cannot_issue_an_invoice(): void
    {
        // Owner sets up + drafts; a plain member may not post it.
        [$owner, $workspace] = $this->actingMember('owner');
        $this->seedAccounting($workspace);
        $invoice = SalesInvoicing::create($workspace, [
            'partner_id' => $this->customer($workspace)->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account($workspace, '4100-02')->id, 'unit_price' => '1000']]);

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $this->actingAs($member);

        $this->post(route('accounting.invoices.issue', $invoice))->assertForbidden();
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->fresh()->status);
    }

    public function test_recording_a_full_receipt_marks_the_invoice_paid(): void
    {
        [, $workspace] = $this->actingMember('owner');
        $this->seedAccounting($workspace);
        $invoice = SalesInvoicing::issue(SalesInvoicing::create($workspace, [
            'partner_id' => $this->customer($workspace)->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account($workspace, '4100-02')->id, 'unit_price' => '1000',
            'tax_code_id' => $this->taxCode($workspace, 'VAT7')->id]]));

        $this->post(route('accounting.invoices.receipts.store', $invoice), [
            'account_id' => $this->account($workspace, '1111-00')->id,
            'payment_date' => '2026-03-15',
            'amount' => '1070',
        ])->assertRedirect(route('accounting.invoices.show', $invoice));

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_cannot_open_an_invoice_from_another_workspace(): void
    {
        [, $workspaceA] = $this->actingMember('owner');
        $this->seedAccounting($workspaceA);
        $invoice = SalesInvoicing::create($workspaceA, [
            'partner_id' => $this->customer($workspaceA)->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account($workspaceA, '4100-02')->id, 'unit_price' => '1000']]);

        // A different user in a different workspace must not see it.
        $this->actingMember('owner');
        $this->get(route('accounting.invoices.show', $invoice))->assertNotFound();
    }
}
