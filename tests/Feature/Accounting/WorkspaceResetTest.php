<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingUser;
use App\Models\Accounting\Employee;
use App\Models\Accounting\Partner;
use App\Models\Workspace;
use App\Services\Accounting\WorkspaceReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkspaceWithBooks(string $name): Workspace
    {
        $ws = Workspace::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'company_name' => 'Profile '.$name,
            'tax_id' => '0105500000017',
        ]);

        Account::create([
            'workspace_id' => $ws->id, 'code' => '1000', 'name' => 'Cash',
            'type' => 'asset', 'normal_balance' => 'debit', 'is_active' => true,
        ]);
        Partner::create([
            'workspace_id' => $ws->id, 'name' => 'A Co.',
            'is_customer' => true, 'is_vendor' => false, 'is_active' => true,
        ]);
        Employee::create([
            'workspace_id' => $ws->id, 'code' => 'E001', 'name' => 'Alice',
            'base_salary' => 25000, 'is_active' => true,
        ]);
        AccountingUser::create([
            'workspace_id' => $ws->id, 'name' => 'Owner',
            'email' => Str::random(6).'@example.com',
            'password' => Hash::make('x'), 'role' => 'owner',
            'is_active' => true, 'is_demo' => false,
        ]);

        return $ws;
    }

    public function test_reset_wipes_all_accounting_rows_for_one_workspace(): void
    {
        $target = $this->makeWorkspaceWithBooks('Target');

        $this->assertSame(1, DB::table('accounting_accounts')->where('workspace_id', $target->id)->count());
        $this->assertSame(1, DB::table('accounting_partners')->where('workspace_id', $target->id)->count());
        $this->assertSame(1, DB::table('accounting_employees')->where('workspace_id', $target->id)->count());
        $this->assertSame(1, DB::table('accounting_users')->where('workspace_id', $target->id)->count());

        $deleted = WorkspaceReset::reset($target);

        $this->assertGreaterThanOrEqual(4, array_sum($deleted));
        $this->assertSame(0, DB::table('accounting_accounts')->where('workspace_id', $target->id)->count());
        $this->assertSame(0, DB::table('accounting_partners')->where('workspace_id', $target->id)->count());
        $this->assertSame(0, DB::table('accounting_employees')->where('workspace_id', $target->id)->count());
        $this->assertSame(0, DB::table('accounting_users')->where('workspace_id', $target->id)->count());
    }

    public function test_reset_keeps_workspace_row_but_clears_company_profile(): void
    {
        $target = $this->makeWorkspaceWithBooks('Keep Me');

        WorkspaceReset::reset($target);
        $target->refresh();

        $this->assertNotNull($target->id, 'workspace row must survive');
        $this->assertNull($target->company_name);
        $this->assertNull($target->tax_id);
    }

    public function test_reset_does_not_touch_other_workspaces(): void
    {
        $target = $this->makeWorkspaceWithBooks('Target');
        $other = $this->makeWorkspaceWithBooks('Other');

        WorkspaceReset::reset($target);

        // Other workspace's books survive untouched
        $this->assertSame(1, DB::table('accounting_accounts')->where('workspace_id', $other->id)->count());
        $this->assertSame(1, DB::table('accounting_partners')->where('workspace_id', $other->id)->count());
        $this->assertSame(1, DB::table('accounting_employees')->where('workspace_id', $other->id)->count());
        $this->assertSame(1, DB::table('accounting_users')->where('workspace_id', $other->id)->count());

        $other->refresh();
        $this->assertSame('Profile Other', $other->company_name);
    }
}
