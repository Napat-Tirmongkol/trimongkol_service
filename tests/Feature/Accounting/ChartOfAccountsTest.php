<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): Workspace
    {
        return Workspace::create(['name' => 'ACME', 'slug' => 'acme-'.Str::random(6)]);
    }

    public function test_seeds_the_full_chart_and_is_idempotent(): void
    {
        $w = $this->workspace();
        $expected = count(ChartOfAccounts::template());

        $created = ChartOfAccounts::seed($w);
        $this->assertSame($expected, $created);
        $this->assertSame($expected, Account::forWorkspace($w)->count());

        // Seeding again creates nothing and never duplicates a code.
        $this->assertSame(0, ChartOfAccounts::seed($w));
        $this->assertSame($expected, Account::forWorkspace($w)->count());
    }

    public function test_normal_balance_follows_account_type(): void
    {
        $w = $this->workspace();
        ChartOfAccounts::seed($w);
        $byCode = Account::forWorkspace($w)->get()->keyBy('code');

        $this->assertSame('debit', $byCode['1111-00']->normal_balance);  // asset
        $this->assertSame('debit', $byCode['5400-01']->normal_balance);  // expense
        $this->assertSame('credit', $byCode['2120-01']->normal_balance); // liability
        $this->assertSame('credit', $byCode['3100-00']->normal_balance); // equity
        $this->assertSame('credit', $byCode['4100-02']->normal_balance); // income
    }

    public function test_engine_roles_are_bound_to_the_right_accounts(): void
    {
        $w = $this->workspace();
        ChartOfAccounts::seed($w);
        $role = fn (string $r) => Account::forWorkspace($w)->where('system_role', $r)->value('code');

        $this->assertSame('1130-01', $role('ar_control'));
        $this->assertSame('2120-01', $role('ap_control'));
        $this->assertSame('1154-00', $role('vat_input'));
        $this->assertSame('2135-00', $role('vat_output'));
        $this->assertSame('2132-03', $role('wht_payable_pnd3'));
        $this->assertSame('2132-04', $role('wht_payable_pnd53'));
        $this->assertSame('3200-00', $role('retained_earnings'));
        $this->assertSame('4100-02', $role('service_revenue'));
    }

    public function test_non_deductible_expenses_are_flagged(): void
    {
        $w = $this->workspace();
        ChartOfAccounts::seed($w);
        $byCode = Account::forWorkspace($w)->get()->keyBy('code');

        $this->assertFalse($byCode['5390-04']->is_tax_deductible); // ค่าใช้จ่ายต้องห้าม
        $this->assertFalse($byCode['5370-08']->is_tax_deductible); // ภาษีนิติบุคคลตัดจ่าย
        $this->assertTrue($byCode['5400-01']->is_tax_deductible);  // normal expense
    }

    public function test_chart_is_scoped_per_workspace(): void
    {
        $a = $this->workspace();
        $b = $this->workspace();
        ChartOfAccounts::seed($a);

        $this->assertGreaterThan(0, Account::forWorkspace($a)->count());
        $this->assertSame(0, Account::forWorkspace($b)->count());
    }
}
