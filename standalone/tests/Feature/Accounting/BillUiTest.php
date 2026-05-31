<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Accounting\WhtCertificate;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillUiTest extends TestCase
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

    private function account(Workspace $w, string $code): Account
    {
        return Account::where('workspace_id', $w->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(Workspace $w, string $code): TaxCode
    {
        return TaxCode::where('workspace_id', $w->id)->where('code', $code)->firstOrFail();
    }

    private function vendor(Workspace $w): Partner
    {
        return Partner::create(['workspace_id' => $w->id, 'name' => 'ผู้ขาย ก', 'tax_id' => '0105500000017', 'is_vendor' => true]);
    }

    public function test_create_post_then_pay_a_bill_with_wht(): void
    {
        [, $workspace] = $this->actingMember('owner');
        $this->seedAccounting($workspace);
        $vendor = $this->vendor($workspace);

        $this->get(route('accounting.bills.create'))->assertOk();

        $this->post(route('accounting.bills.store'), [
            'partner_id' => $vendor->id,
            'issue_date' => '2026-03-01',
            'lines' => [[
                'account_id' => $this->account($workspace, '5400-04')->id,
                'description' => 'ค่าจ้างเหมา', 'quantity' => 1, 'unit_price' => '1000',
                'tax_code_id' => $this->taxCode($workspace, 'VAT7P')->id,
            ]],
        ])->assertRedirect();

        $bill = Bill::forWorkspace($workspace)->firstOrFail();
        $this->assertSame(Bill::STATUS_DRAFT, $bill->status);
        $this->assertSame('1070.00', $bill->total);

        $this->post(route('accounting.bills.post', $bill))->assertRedirect();
        $this->assertSame(Bill::STATUS_ISSUED, $bill->fresh()->status);

        $this->post(route('accounting.bills.payments.store', $bill), [
            'account_id' => $this->account($workspace, '1113-01')->id,
            'payment_date' => '2026-03-20',
            'wht_tax_code_id' => $this->taxCode($workspace, 'WHT3')->id,
            'wht_base' => '1000',
            'income_type' => 'ค่าจ้างทำของ',
        ])->assertRedirect(route('accounting.bills.show', $bill));

        $this->assertSame(Bill::STATUS_PAID, $bill->fresh()->status);

        $cert = WhtCertificate::where('workspace_id', $workspace->id)->firstOrFail();
        $this->assertSame(WhtCertificate::PND53, $cert->pnd_type);
        $this->assertSame('30.00', $cert->wht_amount);
    }

    public function test_member_cannot_post_a_bill(): void
    {
        [, $workspace] = $this->actingMember('owner');
        $this->seedAccounting($workspace);
        $bill = Purchasing::create($workspace, [
            'partner_id' => $this->vendor($workspace)->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account($workspace, '5400-04')->id, 'unit_price' => '1000']]);

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $this->actingAs($member);

        $this->post(route('accounting.bills.post', $bill))->assertForbidden();
        $this->assertSame(Bill::STATUS_DRAFT, $bill->fresh()->status);
    }

    public function test_cannot_open_a_bill_from_another_workspace(): void
    {
        [, $workspaceA] = $this->actingMember('owner');
        $this->seedAccounting($workspaceA);
        $bill = Purchasing::create($workspaceA, [
            'partner_id' => $this->vendor($workspaceA)->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account($workspaceA, '5400-04')->id, 'unit_price' => '1000']]);

        $this->actingMember('owner');
        $this->get(route('accounting.bills.show', $bill))->assertNotFound();
    }
}
