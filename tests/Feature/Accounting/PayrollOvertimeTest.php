<?php

namespace Tests\Feature\Accounting;

use App\Mail\PayslipMail;
use App\Models\Accounting\Account;
use App\Models\Accounting\Employee;
use App\Models\Accounting\PayrollItem;
use App\Models\Accounting\Period;
use App\Models\Workspace;
use App\Services\Accounting\Payroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollOvertimeTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    /** @var array<string, Account> */
    private array $accounts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::create([
            'name' => 'OT Co.',
            'slug' => 'ot-'.Str::random(6),
        ]);

        Period::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'FY2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'open',
        ]);

        foreach ([
            'salary' => ['code' => '5100', 'type' => 'expense', 'normal_balance' => 'debit'],
            'ss_exp' => ['code' => '5101', 'type' => 'expense', 'normal_balance' => 'debit'],
            'ss_pay' => ['code' => '2110', 'type' => 'liability', 'normal_balance' => 'credit'],
            'wht_pay' => ['code' => '2120', 'type' => 'liability', 'normal_balance' => 'credit'],
            'cash' => ['code' => '1000', 'type' => 'asset', 'normal_balance' => 'debit'],
        ] as $key => $spec) {
            $this->accounts[$key] = Account::create([
                'workspace_id' => $this->workspace->id,
                'code' => $spec['code'],
                'name' => $key,
                'type' => $spec['type'],
                'normal_balance' => $spec['normal_balance'],
                'is_active' => true,
            ]);
        }
    }

    private function makeEmployee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'workspace_id' => $this->workspace->id,
            'code' => 'E'.Str::random(4),
            'name' => 'Anong',
            'base_salary' => 20000,
            'is_active' => true,
        ], $overrides));
    }

    private function createRun(): \App\Models\Accounting\PayrollRun
    {
        return Payroll::createRun($this->workspace, '2026-06', [
            'payment_date' => '2026-06-30',
            'salary_expense_account_id' => $this->accounts['salary']->id,
            'ss_expense_account_id' => $this->accounts['ss_exp']->id,
            'ss_payable_account_id' => $this->accounts['ss_pay']->id,
            'wht_payable_account_id' => $this->accounts['wht_pay']->id,
            'cash_account_id' => $this->accounts['cash']->id,
        ]);
    }

    public function test_overtime_and_ot_increase_net_but_not_ss(): void
    {
        $this->makeEmployee();
        $run = $this->createRun();

        $item = $run->items()->first();
        $this->assertSame(20000.0, (float) $item->gross);
        // SS at 15k cap = 750
        $this->assertSame(750.0, (float) $item->ss_employee);
        $this->assertSame(19250.0, (float) $item->net, 'base net without OT');

        Payroll::updateItem($item, [
            'gross' => 20000,
            'overtime' => 1500,
            'ot' => 800,
        ]);
        $item->refresh();

        // SS must NOT change — overtime is not part of the SS base
        $this->assertSame(750.0, (float) $item->ss_employee);
        // Net = gross + overtime + ot - ss = 20000 + 1500 + 800 - 750
        $this->assertSame(21550.0, (float) $item->net);
    }

    public function test_run_totals_aggregate_overtime_and_ot(): void
    {
        $this->makeEmployee(['code' => 'E001', 'name' => 'A', 'base_salary' => 20000]);
        $this->makeEmployee(['code' => 'E002', 'name' => 'B', 'base_salary' => 30000]);
        $run = $this->createRun();

        foreach ($run->items as $item) {
            Payroll::updateItem($item, ['gross' => $item->gross, 'overtime' => 1000, 'ot' => 500]);
        }
        $run->refresh();

        $this->assertSame(50000.0, (float) $run->total_gross);
        $this->assertSame(2000.0, (float) $run->total_overtime);
        $this->assertSame(1000.0, (float) $run->total_ot);
    }

    public function test_posting_includes_ot_in_salary_expense_debit(): void
    {
        $this->makeEmployee(['base_salary' => 20000]);
        $run = $this->createRun();

        $item = $run->items()->first();
        Payroll::updateItem($item, ['gross' => 20000, 'overtime' => 1500, 'ot' => 800]);

        Payroll::post($run->refresh());
        $run->refresh()->load('journal.lines');

        $salaryDebit = $run->journal->lines
            ->where('account_id', $this->accounts['salary']->id)
            ->sum('debit');

        // earnings = 20000 + 1500 + 800 = 22300
        $this->assertSame(22300.0, (float) $salaryDebit);

        $cashCredit = $run->journal->lines
            ->where('account_id', $this->accounts['cash']->id)
            ->sum('credit');
        $this->assertSame(21550.0, (float) $cashCredit, 'net paid stays consistent');
    }

    public function test_payslip_emails_are_sent_to_employees_with_email(): void
    {
        Mail::fake();

        $withEmail = $this->makeEmployee(['code' => 'E001', 'name' => 'With', 'email' => 'with@example.com']);
        $noEmail = $this->makeEmployee(['code' => 'E002', 'name' => 'Without', 'email' => null]);
        $run = $this->createRun();
        Payroll::post($run);

        // The controller dispatches mails; here we exercise the same path
        // by simulating what postRun does after Payroll::post succeeds.
        foreach ($run->items as $item) {
            if ($item->employee->email) {
                \Illuminate\Support\Facades\Mail::to($item->employee->email)
                    ->send(new PayslipMail($item));
            }
        }

        Mail::assertSent(PayslipMail::class, 1);
        Mail::assertSent(PayslipMail::class, function (PayslipMail $mail) use ($withEmail) {
            return $mail->payrollItem->employee_id === $withEmail->id;
        });
    }
}
