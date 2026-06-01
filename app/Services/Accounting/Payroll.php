<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Employee;
use App\Models\Accounting\Journal;
use App\Models\Accounting\PayrollItem;
use App\Models\Accounting\PayrollRun;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monthly payroll for Thai SMEs.
 *
 * Posting (post()):
 *   DR salary expense        total gross
 *   DR social-security expense   employer SS contribution
 *   CR social-security payable   employee SS + employer SS
 *   CR withholding-tax payable   PND.1
 *   CR cash / bank               net paid
 *
 * Social security: 5% of gross, capped at 750 baht per side
 * (ฐานสูงสุด 15,000 / ฐานต่ำสุด 1,650 — มี cap ทั้งฝั่งนายจ้างและลูกจ้าง).
 */
class Payroll
{
    public const SS_RATE = 0.05;        // 5%
    public const SS_BASE_MAX = 15000;   // ฐานคำนวณสูงสุด
    public const SS_BASE_MIN = 1650;    // ฐานคำนวณต่ำสุด

    public static function registerEmployee(Workspace $workspace, array $attributes): Employee
    {
        return Employee::create([
            'workspace_id' => $workspace->id,
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'tax_id' => $attributes['tax_id'] ?? null,
            'social_security_id' => $attributes['social_security_id'] ?? null,
            'base_salary' => $attributes['base_salary'] ?? 0,
            'bank_account' => $attributes['bank_account'] ?? null,
            'position' => $attributes['position'] ?? null,
            'hired_on' => $attributes['hired_on'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Create a draft payroll run for $period (YYYY-MM), populating items for
     * every active employee at their base salary. WHT is left at 0 — adjust
     * per-item before posting if needed.
     *
     * @param  array  $attributes  payment_date, *_account_id (5 required)
     */
    public static function createRun(Workspace $workspace, string $period, array $attributes): PayrollRun
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new LedgerException('Period must be in YYYY-MM format.');
        }

        $existing = PayrollRun::query()->forWorkspace($workspace)->where('period', $period)->first();
        if ($existing) {
            throw new LedgerException("A payroll run for {$period} already exists.");
        }

        return DB::transaction(function () use ($workspace, $period, $attributes) {
            foreach (['salary_expense_account_id', 'ss_expense_account_id',
                      'ss_payable_account_id', 'wht_payable_account_id', 'cash_account_id'] as $field) {
                $account = Account::query()->forWorkspace($workspace)->find($attributes[$field] ?? null);
                if (! $account) {
                    throw new LedgerException("Account for {$field} does not belong to this workspace.");
                }
            }

            $run = PayrollRun::create([
                'workspace_id' => $workspace->id,
                'no' => self::nextNumber($workspace, $period),
                'period' => $period,
                'payment_date' => $attributes['payment_date'],
                'status' => PayrollRun::STATUS_DRAFT,
                'salary_expense_account_id' => $attributes['salary_expense_account_id'],
                'ss_expense_account_id' => $attributes['ss_expense_account_id'],
                'ss_payable_account_id' => $attributes['ss_payable_account_id'],
                'wht_payable_account_id' => $attributes['wht_payable_account_id'],
                'cash_account_id' => $attributes['cash_account_id'],
                'created_by' => auth()->id(),
            ]);

            $employees = Employee::query()->forWorkspace($workspace)->where('is_active', true)->get();
            foreach ($employees as $employee) {
                $gross = (float) $employee->base_salary;
                [$ssEmployee, $ssEmployer] = self::socialSecurity($gross);

                PayrollItem::create([
                    'workspace_id' => $workspace->id,
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'gross' => $gross,
                    'ss_employee' => $ssEmployee,
                    'ss_employer' => $ssEmployer,
                    'wht' => 0,
                    'other_deductions' => 0,
                    'net' => $gross - $ssEmployee,
                ]);
            }

            self::refreshTotals($run);

            return $run->refresh()->load('items.employee');
        });
    }

    /** Update a single payroll item (only on a draft run). */
    public static function updateItem(PayrollItem $item, array $attributes): PayrollItem
    {
        if ($item->run->isPosted()) {
            throw new LedgerException('Cannot edit items on a posted payroll run.');
        }

        $gross = isset($attributes['gross']) ? (float) $attributes['gross'] : (float) $item->gross;
        [$ssEmployee, $ssEmployer] = self::socialSecurity($gross);
        $wht = (float) ($attributes['wht'] ?? $item->wht);
        $other = (float) ($attributes['other_deductions'] ?? $item->other_deductions);
        $net = $gross - $ssEmployee - $wht - $other;

        $item->update([
            'gross' => $gross,
            'ss_employee' => $ssEmployee,
            'ss_employer' => $ssEmployer,
            'wht' => $wht,
            'other_deductions' => $other,
            'net' => $net,
        ]);

        self::refreshTotals($item->run);

        return $item->refresh();
    }

    /** Post the payroll run to the ledger and freeze it. */
    public static function post(PayrollRun $run): PayrollRun
    {
        if ($run->isPosted()) {
            throw new LedgerException("Payroll run {$run->no} has already been posted.");
        }

        $run->loadMissing('items');
        if ($run->items->isEmpty()) {
            throw new LedgerException('Cannot post an empty payroll run.');
        }

        return DB::transaction(function () use ($run) {
            self::refreshTotals($run);
            $run->refresh();

            $gross = (float) $run->total_gross;
            $ssEmp = (float) $run->total_ss_employee;
            $ssCo = (float) $run->total_ss_employer;
            $wht = (float) $run->total_wht;
            $net = (float) $run->total_net;

            if ($gross <= 0) {
                throw new LedgerException('Total gross must be positive.');
            }

            $lines = [
                ['account_id' => $run->salary_expense_account_id, 'debit' => Money::fromMinor(Money::toMinor($gross)), 'credit' => 0, 'description' => "เงินเดือนพนักงาน {$run->period}"],
            ];
            if ($ssCo > 0) {
                $lines[] = ['account_id' => $run->ss_expense_account_id, 'debit' => Money::fromMinor(Money::toMinor($ssCo)), 'credit' => 0, 'description' => 'ประกันสังคมส่วนนายจ้าง'];
            }
            if ($ssEmp + $ssCo > 0) {
                $lines[] = ['account_id' => $run->ss_payable_account_id, 'debit' => 0, 'credit' => Money::fromMinor(Money::toMinor($ssEmp + $ssCo)), 'description' => 'ประกันสังคมค้างจ่าย'];
            }
            if ($wht > 0) {
                $lines[] = ['account_id' => $run->wht_payable_account_id, 'debit' => 0, 'credit' => Money::fromMinor(Money::toMinor($wht)), 'description' => 'ภาษีหัก ณ ที่จ่าย ภงด.1'];
            }
            $lines[] = ['account_id' => $run->cash_account_id, 'debit' => 0, 'credit' => Money::fromMinor(Money::toMinor($net)), 'description' => "จ่ายเงินเดือนสุทธิ {$run->period}"];

            $journal = LedgerPosting::post($run->workspace, [
                'date' => $run->payment_date,
                'type' => 'payroll',
                'memo' => "เงินเดือนงวด {$run->period}",
                'created_by' => auth()->id(),
                'source_type' => $run->getMorphClass(),
                'source_id' => $run->id,
            ], $lines);

            $run->forceFill([
                'status' => PayrollRun::STATUS_POSTED,
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();

            return $run->refresh();
        });
    }

    /**
     * Thai social security: 5% of gross, but the contribution base is clamped
     * between 1,650 and 15,000 baht (max contribution = 750/side).
     *
     * @return array{0: float, 1: float} [employeeShare, employerShare]
     */
    public static function socialSecurity(float $gross): array
    {
        if ($gross < self::SS_BASE_MIN) {
            return [0.0, 0.0];
        }

        $base = min($gross, self::SS_BASE_MAX);
        $each = round($base * self::SS_RATE, 2);

        return [$each, $each];
    }

    private static function refreshTotals(PayrollRun $run): void
    {
        $items = $run->items()->get();

        $run->update([
            'total_gross' => $items->sum('gross'),
            'total_ss_employee' => $items->sum('ss_employee'),
            'total_ss_employer' => $items->sum('ss_employer'),
            'total_wht' => $items->sum('wht'),
            'total_net' => $items->sum('net'),
        ]);
    }

    private static function nextNumber(Workspace $workspace, string $period): string
    {
        // Format: PR2569-01 (Buddhist year + month)
        [$y, $m] = explode('-', $period);
        $prefix = 'PR'.((int) $y + 543).'-';

        $last = PayrollRun::query()->forWorkspace($workspace)
            ->where('no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('no')
            ->value('no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : (int) $m;

        return $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
    }
}
