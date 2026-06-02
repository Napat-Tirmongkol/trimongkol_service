<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\ClosedPeriodException;
use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\ActivityLog;
use App\Models\Accounting\Journal;
use App\Models\Accounting\Period;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Period lifecycle management: open → closed → locked.
 *
 * close()      — prevents new postings into the period (reversible).
 * reopen()     — reverses a close (only while not yet locked).
 * yearEndClose() — posts the year-end income-summary closing entry that
 *                  zeroes all income/expense accounts into retained earnings,
 *                  then locks every period in that fiscal year permanently.
 *
 * Locking is one-way (by design) — once a year is closed the ledger for
 * that year is immutable.
 */
class PeriodClose
{
    public static function close(Period $period, ?int $userId = null): Period
    {
        if ($period->status === Period::STATUS_LOCKED) {
            throw new ClosedPeriodException("Period {$period->name} is locked and cannot be modified.");
        }
        if ($period->status === Period::STATUS_CLOSED) {
            return $period;
        }

        $period->update(['status' => Period::STATUS_CLOSED]);

        self::log($period->workspace, 'period.closed', $period, $userId);

        return $period->refresh();
    }

    public static function reopen(Period $period, ?int $userId = null): Period
    {
        if ($period->status === Period::STATUS_LOCKED) {
            throw new ClosedPeriodException("Period {$period->name} is locked — year-end has been posted.");
        }
        if ($period->status === Period::STATUS_OPEN) {
            return $period;
        }

        $period->update(['status' => Period::STATUS_OPEN]);

        self::log($period->workspace, 'period.reopened', $period, $userId);

        return $period->refresh();
    }

    /**
     * Post the year-end income-summary closing entry and lock all periods in
     * the given Buddhist year (e.g. 2568). Safe to call only once — throws if
     * a closing journal already exists.
     *
     * Closing entry:
     *   DR each income account (positive balance)   → zero it out
     *   CR each expense account (positive balance)  → zero it out
     *   CR/DR retained earnings                     → net profit/(loss)
     */
    public static function yearEndClose(Workspace $workspace, int $buddhistYear, ?int $userId = null): Journal
    {
        $gregorianYear = $buddhistYear - 543;
        $startDate = Carbon::createFromDate($gregorianYear, 1, 1)->toDateString();
        $endDate = Carbon::createFromDate($gregorianYear, 12, 31)->toDateString();

        return DB::transaction(function () use ($workspace, $buddhistYear, $gregorianYear, $startDate, $endDate, $userId) {
            // Guard: only once per year
            $alreadyClosed = Journal::query()
                ->forWorkspace($workspace)
                ->where('type', 'year_end_close')
                ->whereYear('date', $gregorianYear)
                ->exists();

            if ($alreadyClosed) {
                throw new LedgerException("Year-end closing for {$buddhistYear} has already been posted.");
            }

            // Ensure all periods in this year are closed before locking
            $periods = Period::query()
                ->forWorkspace($workspace)
                ->whereDate('starts_on', '>=', $startDate)
                ->whereDate('ends_on', '<=', $endDate)
                ->get();

            if ($periods->isEmpty()) {
                throw new LedgerException("No accounting periods found for Buddhist year {$buddhistYear}.");
            }

            foreach ($periods as $period) {
                if ($period->status === Period::STATUS_OPEN) {
                    throw new LedgerException(
                        "Period {$period->name} is still open — close all periods before running year-end."
                    );
                }
            }

            // Calculate net balances per income/expense account for this year
            $balances = Reporting::profitAndLoss($workspace, $startDate, $endDate);

            $retainedEarningsId = Account::query()
                ->forWorkspace($workspace)
                ->where('system_role', 'retained_earnings')
                ->value('id');

            if (! $retainedEarningsId) {
                throw new LedgerException('No retained earnings account (system_role = retained_earnings) found.');
            }

            // Build journal lines: zero out every income and expense account
            $lines = [];
            $netToRetained = 0; // positive = profit (CR retained), negative = loss (DR retained)

            foreach ($balances['income'] as $row) {
                $amount = Money::toMinor($row['amount']);
                if ($amount > 0) {
                    // Income has CR normal balance; to close, DR it
                    $lines[] = [
                        'account_id' => $row['account']->id,
                        'debit' => Money::fromMinor($amount),
                        'credit' => 0,
                        'description' => 'ปิดบัญชีรายได้ปีสิ้นสุด',
                    ];
                    $netToRetained += $amount;
                }
            }

            foreach ($balances['expense'] as $row) {
                $amount = Money::toMinor($row['amount']);
                if ($amount > 0) {
                    // Expense has DR normal balance; to close, CR it
                    $lines[] = [
                        'account_id' => $row['account']->id,
                        'debit' => 0,
                        'credit' => Money::fromMinor($amount),
                        'description' => 'ปิดบัญชีค่าใช้จ่ายปีสิ้นสุด',
                    ];
                    $netToRetained -= $amount;
                }
            }

            if (empty($lines)) {
                throw new LedgerException('No income or expense transactions found for this year — nothing to close.');
            }

            // Retained earnings line: net profit → CR, net loss → DR
            if ($netToRetained > 0) {
                $lines[] = [
                    'account_id' => $retainedEarningsId,
                    'debit' => 0,
                    'credit' => Money::fromMinor($netToRetained),
                    'description' => 'กำไรสุทธิยกเข้ากำไรสะสม',
                ];
            } elseif ($netToRetained < 0) {
                $lines[] = [
                    'account_id' => $retainedEarningsId,
                    'debit' => Money::fromMinor(-$netToRetained),
                    'credit' => 0,
                    'description' => 'ขาดทุนสุทธิยกเข้ากำไรสะสม',
                ];
            } else {
                // Break-even: add a zero-balance retained earnings line to satisfy 2-line minimum
                $lines[] = [
                    'account_id' => $retainedEarningsId,
                    'debit' => 0,
                    'credit' => 0,
                    'description' => 'ปิดบัญชี — กำไรสุทธิเป็นศูนย์',
                ];
            }

            // Post through the last closed period of the year (re-open it temporarily)
            $lastPeriod = $periods->sortByDesc('ends_on')->first();
            $lastPeriod->update(['status' => Period::STATUS_OPEN]);

            $journal = LedgerPosting::post($workspace, [
                'date' => $endDate,
                'type' => 'year_end_close',
                'memo' => "ปิดบัญชีประจำปี พ.ศ. {$buddhistYear}",
                'created_by' => null,
            ], $lines);

            // Lock every period in this fiscal year permanently
            Period::query()
                ->forWorkspace($workspace)
                ->whereDate('starts_on', '>=', $startDate)
                ->whereDate('ends_on', '<=', $endDate)
                ->update(['status' => Period::STATUS_LOCKED]);

            ActivityLog::create([
                'workspace_id'       => $workspace->id,
                'user_id'            => null,
                'accounting_user_id' => auth('accounting')->id(),
                'action'             => 'period.year_end_close',
                'subject_type'       => $journal->getMorphClass(),
                'subject_id'         => $journal->id,
                'subject_label'      => "ปิดบัญชีปี {$buddhistYear}",
                'metadata'           => ['year' => $buddhistYear, 'journal_no' => $journal->no],
                'ip'                 => request()?->ip(),
                'created_at'         => now(),
            ]);

            return $journal;
        });
    }

    private static function log(Workspace $workspace, string $action, Period $period, ?int $userId): void
    {
        ActivityLog::create([
            'workspace_id'       => $workspace->id,
            'user_id'            => null,
            'accounting_user_id' => auth('accounting')->id(),
            'action'             => $action,
            'subject_type'       => $period->getMorphClass(),
            'subject_id'         => $period->id,
            'subject_label'      => $period->name,
            'metadata'           => ['period' => $period->name, 'status' => $period->status],
            'ip'                 => request()?->ip(),
            'created_at'         => now(),
        ]);
    }
}
