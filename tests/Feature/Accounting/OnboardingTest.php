<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Partner;
use App\Models\Accounting\TaxCode;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'บริษัท ทดสอบ จำกัด',
            'tax_id' => '0105500000017',
            'branch' => 'สำนักงานใหญ่',
            'phone' => '021112222',
            'company_address' => '99 หมู่ 1 กรุงเทพฯ',
            'chart_template' => 'standard_th',
        ], $overrides);
    }

    public function test_wizard_is_shown_to_a_new_workspace(): void
    {
        $this->actingMember('owner');

        $this->get(route('accounting.onboarding'))->assertOk()
            ->assertSee(__('app.accounting.onboarding_company'))
            ->assertSee(__('app.accounting.onboarding_chart'));
    }

    public function test_completing_the_wizard_seeds_books_and_saves_company(): void
    {
        [, $workspace] = $this->actingMember('owner');

        $this->post(route('accounting.onboarding.store'), $this->validPayload())
            ->assertRedirect(route('accounting.dashboard'))
            ->assertSessionHas('status');

        $this->assertGreaterThan(0, Account::forWorkspace($workspace)->count());
        $this->assertGreaterThan(0, TaxCode::forWorkspace($workspace)->count());

        // The engine roles are present (tax codes wired correctly).
        $this->assertNotNull(Account::forWorkspace($workspace)->where('system_role', 'vat_output')->first());

        $workspace->refresh();
        $this->assertNotNull($workspace->onboarded_at);
        $this->assertSame('บริษัท ทดสอบ จำกัด', $workspace->company_name);
        $this->assertSame('standard_th', $workspace->chart_template);
    }

    public function test_company_name_is_required(): void
    {
        $this->actingMember('owner');

        $this->post(route('accounting.onboarding.store'), $this->validPayload(['company_name' => '']))
            ->assertSessionHasErrors('company_name');
    }

    public function test_unknown_chart_template_is_rejected(): void
    {
        $this->actingMember('owner');

        $this->post(route('accounting.onboarding.store'), $this->validPayload(['chart_template' => 'nope']))
            ->assertSessionHasErrors('chart_template');
    }

    public function test_blank_branch_defaults_to_head_office(): void
    {
        [, $workspace] = $this->actingMember('owner');

        $this->post(route('accounting.onboarding.store'), $this->validPayload(['branch' => '']));

        $this->assertSame('สำนักงานใหญ่', $workspace->fresh()->branch);
    }

    public function test_onboarding_only_runs_once(): void
    {
        [, $workspace] = $this->actingMember('owner');

        $this->post(route('accounting.onboarding.store'), $this->validPayload());
        $firstCount = Account::forWorkspace($workspace)->count();

        // Visiting again redirects away; posting again is a no-op redirect.
        $this->get(route('accounting.onboarding'))->assertRedirect(route('accounting.dashboard'));
        $this->post(route('accounting.onboarding.store'), $this->validPayload(['company_name' => 'เปลี่ยนชื่อ']))
            ->assertRedirect(route('accounting.dashboard'));

        $this->assertSame($firstCount, Account::forWorkspace($workspace)->count());
        $this->assertSame('บริษัท ทดสอบ จำกัด', $workspace->fresh()->company_name); // unchanged
    }

    public function test_members_cannot_complete_onboarding(): void
    {
        $this->actingMember('member');

        $this->post(route('accounting.onboarding.store'), $this->validPayload())->assertForbidden();
    }

    public function test_company_profile_prints_on_an_invoice(): void
    {
        [, $workspace] = $this->actingMember('owner');
        $this->post(route('accounting.onboarding.store'), $this->validPayload([
            'company_name' => 'พิมพ์บนเอกสาร จำกัด',
        ]));

        $customer = Partner::create(['workspace_id' => $workspace->id, 'name' => 'ลูกค้า', 'is_customer' => true]);
        $vat = TaxCode::where('workspace_id', $workspace->id)->where('code', 'VAT7')->firstOrFail();
        $account = Account::where('workspace_id', $workspace->id)->where('code', '4100-02')->firstOrFail();

        $invoice = \App\Services\Accounting\SalesInvoicing::issue(
            \App\Services\Accounting\SalesInvoicing::create($workspace, [
                'partner_id' => $customer->id, 'issue_date' => '2026-03-01',
            ], [['account_id' => $account->id, 'unit_price' => '1000', 'tax_code_id' => $vat->id]])
        );

        $this->get(route('accounting.invoices.print', $invoice))->assertOk()
            ->assertSee('พิมพ์บนเอกสาร จำกัด')   // company name from the workspace
            ->assertSee('0105500000017');         // tax id from the workspace
    }
}
