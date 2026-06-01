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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private function actingMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace = Workspace::create(['name' => 'ACME', 'slug' => 'acme-'.Str::random(6)]);
        $this->workspace->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);
        ChartOfAccounts::seed($this->workspace);
        TaxCodes::seedDefault($this->workspace);
        Period::create([
            'workspace_id' => $this->workspace->id, 'name' => '2569',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => Period::STATUS_OPEN,
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function account(string $code): Account
    {
        return Account::where('workspace_id', $this->workspace->id)->where('code', $code)->firstOrFail();
    }

    public function test_index_lists_the_chart_grouped(): void
    {
        $this->actingMember('owner');

        $this->get(route('accounting.accounts.index'))->assertOk()
            ->assertSee('1130-01')                          // an AR account
            ->assertSee(__('app.accounting.account_system')); // role badge present
    }

    public function test_owner_can_add_their_own_bank_account(): void
    {
        $this->actingMember('owner');

        $this->post(route('accounting.accounts.store'), [
            'code' => '1113-09', 'name' => 'ออมทรัพย์ กสิกร 123-4-56789-0',
            'name_en' => 'Savings — KBank', 'type' => 'asset', 'is_active' => '1', 'is_tax_deductible' => '1',
        ])->assertRedirect(route('accounting.accounts.index'))->assertSessionHas('status');

        $acct = $this->account('1113-09');
        $this->assertSame('asset', $acct->type);
        $this->assertSame('debit', $acct->normal_balance);   // derived from type
        $this->assertNull($acct->system_role);               // user accounts carry no role
        $this->assertTrue($acct->is_active);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->actingMember('owner');

        $this->post(route('accounting.accounts.store'), [
            'code' => '1111-00', 'name' => 'ซ้ำ', 'type' => 'asset',
        ])->assertSessionHasErrors('code');
    }

    public function test_owner_can_rename_and_deactivate_an_account(): void
    {
        $this->actingMember('owner');
        $acct = $this->account('5360-04'); // bank charges — no role, no entries

        $this->patch(route('accounting.accounts.update', $acct), [
            'code' => $acct->code, 'name' => 'ค่าธรรมเนียมธนาคาร (แก้ไข)',
            'type' => 'expense', 'is_active' => '0', 'is_tax_deductible' => '1',
        ])->assertRedirect(route('accounting.accounts.index'));

        $acct->refresh();
        $this->assertSame('ค่าธรรมเนียมธนาคาร (แก้ไข)', $acct->name);
        $this->assertFalse($acct->is_active);
    }

    public function test_system_role_account_keeps_its_type_even_if_posted_otherwise(): void
    {
        $this->actingMember('owner');
        $ar = $this->account('1130-01'); // ar_control

        // Attempt to flip an engine-bound account to a different type — ignored.
        $this->patch(route('accounting.accounts.update', $ar), [
            'code' => $ar->code, 'name' => 'ลูกหนี้การค้า', 'type' => 'expense',
            'is_active' => '1', 'is_tax_deductible' => '1',
        ])->assertRedirect(route('accounting.accounts.index'));

        $ar->refresh();
        $this->assertSame('asset', $ar->type);              // unchanged
        $this->assertSame('ar_control', $ar->system_role);  // still bound
    }

    public function test_cannot_delete_a_system_role_account(): void
    {
        $this->actingMember('owner');
        $ar = $this->account('1130-01');

        $this->delete(route('accounting.accounts.destroy', $ar))->assertSessionHas('error');
        $this->assertDatabaseHas('accounting_accounts', ['id' => $ar->id]);
    }

    public function test_cannot_delete_a_roleless_account_with_journal_entries(): void
    {
        $this->actingMember('owner');
        $customer = Partner::create(['workspace_id' => $this->workspace->id, 'name' => 'ลูกค้า', 'is_customer' => true]);

        // Post a sale whose revenue line books to 4200-01 (interest income) — a
        // roleless account. After posting it carries journal lines, so the
        // entries guard (not the role guard) must block deletion.
        SalesInvoicing::issue(SalesInvoicing::create($this->workspace, [
            'partner_id' => $customer->id, 'issue_date' => '2026-03-01',
        ], [['account_id' => $this->account('4200-01')->id, 'unit_price' => '1000']]));

        $used = $this->account('4200-01');
        $this->assertNull($used->system_role);                 // not the role guard
        $this->assertTrue($used->lines()->exists());           // it does have entries

        $this->delete(route('accounting.accounts.destroy', $used))->assertSessionHas('error');
        $this->assertDatabaseHas('accounting_accounts', ['id' => $used->id]);
    }

    public function test_owner_can_delete_an_unused_roleless_account(): void
    {
        $this->actingMember('owner');
        $acct = $this->account('5360-12'); // stamp duty — no role, no entries

        $this->delete(route('accounting.accounts.destroy', $acct))
            ->assertRedirect(route('accounting.accounts.index'))->assertSessionHas('status');
        $this->assertDatabaseMissing('accounting_accounts', ['id' => $acct->id]);
    }

    public function test_member_cannot_modify_the_chart(): void
    {
        $this->actingMember('member');

        $this->post(route('accounting.accounts.store'), [
            'code' => '9999-99', 'name' => 'x', 'type' => 'asset',
        ])->assertForbidden();
    }

    public function test_cannot_touch_an_account_from_another_workspace(): void
    {
        $this->actingMember('owner');
        $foreign = $this->account('1111-00');

        // Switch to a different owner + workspace.
        $this->actingMember('owner');
        $this->get(route('accounting.accounts.edit', $foreign))->assertNotFound();
    }
}
