<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingUser;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAccounting(string $role = 'owner'): AccountingUser
    {
        $workspace = Workspace::create([
            'name' => 'ACME Co.',
            'slug' => 'acme-'.Str::random(6),
        ]);

        // Seed one account so DashboardController::isSetUp() returns true and
        // the dashboard (which is what the sidebar is wrapped around) renders.
        Account::create([
            'workspace_id' => $workspace->id,
            'code' => '1000',
            'name' => 'เงินสด',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $user = AccountingUser::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sidebar Tester',
            'email' => Str::random(8).'@example.com',
            'password' => Hash::make('secret123'),
            'role' => $role,
            'is_active' => true,
            'is_demo' => false,
        ]);

        // Set the accounting guard *without* swapping the default 'web' guard —
        // global middleware (RequireTwoFactor) uses $request->user() which
        // reads the default guard, and AccountingUser has no 2FA trait.
        $this->app['auth']->guard('accounting')->setUser($user);

        return $user;
    }

    public function test_dashboard_renders_the_sidebar_with_all_group_headings(): void
    {
        $this->actingAsAccounting('owner');

        $response = $this->get(route('accounting.dashboard'));

        $response->assertOk();
        // Group headings (TH default locale)
        $response->assertSee(__('app.accounting.sidebar_overview'));
        $response->assertSee(__('app.accounting.sidebar_sales'));
        $response->assertSee(__('app.accounting.sidebar_purchases'));
        $response->assertSee(__('app.accounting.sidebar_books'));
        $response->assertSee(__('app.accounting.sidebar_assets'));
        $response->assertSee(__('app.accounting.sidebar_reports'));
        $response->assertSee(__('app.accounting.sidebar_admin'));
    }

    public function test_sidebar_lists_every_top_level_menu_link(): void
    {
        $this->actingAsAccounting('owner');

        $response = $this->get(route('accounting.dashboard'));
        $response->assertOk();

        // One canonical URL from each group — proves the sidebar wires every section
        $response->assertSee(route('accounting.dashboard'), false);
        $response->assertSee(route('accounting.invoices.index'), false);
        $response->assertSee(route('accounting.partners.index'), false);
        $response->assertSee(route('accounting.wht-certificates.index'), false);
        $response->assertSee(route('accounting.bills.index'), false);
        $response->assertSee(route('accounting.accounts.index'), false);
        $response->assertSee(route('accounting.manual-journals.index'), false);
        $response->assertSee(route('accounting.recurring-journals.index'), false);
        $response->assertSee(route('accounting.bank-reconciliation.index'), false);
        $response->assertSee(route('accounting.periods.index'), false);
        $response->assertSee(route('accounting.opening-balances.edit'), false);
        $response->assertSee(route('accounting.products.index'), false);
        $response->assertSee(route('accounting.fixed-assets.index'), false);
        $response->assertSee(route('accounting.payroll.employees'), false);
        $response->assertSee(route('accounting.departments.index'), false);
        $response->assertSee(route('accounting.budgets.index'), false);
        $response->assertSee(route('accounting.reports'), false);
        $response->assertSee(route('accounting.reports.tax'), false);
    }

    public function test_admin_section_shows_users_link_only_for_owner(): void
    {
        $this->actingAsAccounting('owner');
        $response = $this->get(route('accounting.dashboard'));
        $response->assertSee(route('accounting.users.index'), false);
        $response->assertSee(route('accounting.approvals.index'), false);
        $response->assertSee(route('accounting.audit-log.index'), false);
    }

    public function test_admin_section_hides_users_link_for_admin_role(): void
    {
        $this->actingAsAccounting('admin');
        $response = $this->get(route('accounting.dashboard'));
        // admin can post (sees approvals + audit log) but is not owner (no user mgmt)
        $response->assertSee(route('accounting.approvals.index'), false);
        $response->assertSee(route('accounting.audit-log.index'), false);
        $response->assertDontSee(route('accounting.users.index'), false);
    }

    public function test_admin_section_is_empty_for_staff_role(): void
    {
        $this->actingAsAccounting('staff');
        $response = $this->get(route('accounting.dashboard'));
        $response->assertDontSee(route('accounting.users.index'), false);
        $response->assertDontSee(route('accounting.approvals.index'), false);
        $response->assertDontSee(route('accounting.audit-log.index'), false);
    }

    public function test_active_state_highlights_current_route(): void
    {
        $this->actingAsAccounting('owner');

        // Dashboard route → "แดชบอร์ด" entry should carry the active brand classes
        $response = $this->get(route('accounting.dashboard'));
        $html = $response->getContent();

        // The active link uses bg-brand-50 + text-brand-700 (see partials/accounting/sidebar.blade.php)
        $this->assertStringContainsString('bg-brand-50', $html);
        $this->assertStringContainsString('text-brand-700', $html);
    }

    public function test_dashboard_header_no_longer_carries_the_legacy_chip_row(): void
    {
        $this->actingAsAccounting('owner');

        $response = $this->get(route('accounting.dashboard'));
        $html = $response->getContent();

        // The redesign removed the rounded chip row in the dashboard header.
        // Pre-redesign that row rendered ≥8 chips of this exact shape.
        // We allow a couple (e.g. the primary "new invoice" CTA stays as a chip)
        // but it must not be the old wall of links.
        $chipCount = substr_count(
            $html,
            'rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700'
        );
        $this->assertLessThanOrEqual(1, $chipCount, 'Old chip-row should be gone from dashboard header');
    }

    public function test_sidebar_is_not_rendered_on_the_login_page(): void
    {
        // Login uses its own layout — no sidebar, no Alpine drawer
        $response = $this->get(route('accounting.login'));
        $response->assertOk();
        $response->assertDontSee('partials.accounting.sidebar');
        $response->assertDontSee(__('app.accounting.sidebar_overview'));
    }

    public function test_hamburger_button_is_present_for_authed_users(): void
    {
        $this->actingAsAccounting('owner');

        $response = $this->get(route('accounting.dashboard'));
        $html = $response->getContent();

        // Mobile drawer toggle — Alpine click handler on the hamburger
        $this->assertStringContainsString('sidebarOpen = true', $html);
        // Alpine root for the layout
        $this->assertStringContainsString('x-data="{ sidebarOpen: false }"', $html);
    }
}
