<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\SalesInvoicing;
use App\Services\Accounting\TaxCodes;
use App\Services\AccountingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingPlanTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private function actingOwner(): void
    {
        $user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'ACME', 'slug' => 'acme-'.Str::random(6), 'onboarded_at' => now(),
        ]);
        $this->workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        ChartOfAccounts::seed($this->workspace);
        TaxCodes::seedDefault($this->workspace);
        Period::create([
            'workspace_id' => $this->workspace->id, 'name' => '2569',
            'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString(),
            'status' => Period::STATUS_OPEN,
        ]);
        $this->actingAs($user);
    }

    private function makeInvoice(): void
    {
        $customer = Partner::firstOrCreate(
            ['workspace_id' => $this->workspace->id, 'name' => 'ลูกค้า'],
            ['is_customer' => true]
        );
        SalesInvoicing::create($this->workspace, [
            'partner_id' => $customer->id, 'issue_date' => now()->toDateString(),
        ], [['account_id' => Account::where('workspace_id', $this->workspace->id)->where('code', '4100-02')->value('id'), 'unit_price' => '100']]);
    }

    public function test_defaults_to_the_free_plan(): void
    {
        $this->actingOwner();
        $this->assertSame('free', AccountingPlan::key($this->workspace));
        $this->assertSame('Free', AccountingPlan::name($this->workspace));
    }

    public function test_free_plan_caps_documents_per_month(): void
    {
        $this->actingOwner();
        config(['accounting-plans.free.limits.max_invoices_per_month' => 2]);

        $this->makeInvoice();
        $this->assertNull(AccountingPlan::reasonCannotCreateInvoice($this->workspace));
        $this->makeInvoice();
        $this->assertNotNull(AccountingPlan::reasonCannotCreateInvoice($this->workspace));

        // The create screen bounces back with the upgrade message once capped.
        $this->get(route('accounting.invoices.create'))
            ->assertRedirect(route('accounting.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_paid_plan_lifts_the_cap(): void
    {
        $this->actingOwner();
        config(['accounting-plans.free.limits.max_invoices_per_month' => 1]);
        $this->workspace->update(['accounting_plan' => 'pro']);  // unlimited

        $this->makeInvoice();
        $this->makeInvoice();
        $this->assertNull(AccountingPlan::reasonCannotCreateInvoice($this->workspace));
    }

    public function test_csv_export_is_gated_by_plan(): void
    {
        $this->actingOwner();

        // Free: blocked, redirected with an upgrade message.
        $this->get(route('accounting.reports.export'))
            ->assertRedirect(route('accounting.reports.tax'))
            ->assertSessionHas('error');

        // Starter unlocks the journal CSV hand-off.
        $this->workspace->update(['accounting_plan' => 'starter']);
        $this->assertTrue(AccountingPlan::can($this->workspace, 'csv_export'));
        $this->get(route('accounting.reports.export'))->assertOk();
    }

    public function test_expired_paid_plan_falls_back_to_free(): void
    {
        $this->actingOwner();
        $this->workspace->update(['accounting_plan' => 'pro', 'accounting_plan_until' => now()->subDay()]);

        $this->assertSame('free', AccountingPlan::key($this->workspace));   // effective
        $this->assertSame('pro', AccountingPlan::storedKey($this->workspace)); // stored
        $this->assertTrue(AccountingPlan::isExpired($this->workspace));
    }

    public function test_active_paid_plan_is_in_effect(): void
    {
        $this->actingOwner();
        $this->workspace->update(['accounting_plan' => 'starter', 'accounting_plan_until' => now()->addMonth()]);

        $this->assertSame('starter', AccountingPlan::key($this->workspace));
        $this->assertFalse(AccountingPlan::isExpired($this->workspace));
    }
}
