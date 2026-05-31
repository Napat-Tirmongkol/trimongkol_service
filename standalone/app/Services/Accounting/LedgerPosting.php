<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\ClosedPeriodException;
use App\Exceptions\Accounting\LedgerException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Accounting\Account;
use App\Models\Accounting\ActivityLog;
use App\Models\Accounting\Journal;
use App\Models\Accounting\Period;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The one and only way to write to the ledger. Every post is atomic
 * (DB::transaction → ACID rollback on any failure) and is rejected unless
 * Debit = Credit and the date falls in an open period. Money is handled as
 * integer satang so the balance check is exact — never float, never bcmath.
 */
class LedgerPosting
{
    /**
     * Create and post a balanced journal in a single transaction.
     *
     * @param  array  $attributes  date, type, memo, created_by, source_type, source_id, reverses_journal_id
     * @param  array  $lines  each: account_id, debit, credit, description?, department_id?, partner_id?, tax_code_id?
     */
    public static function post(Workspace $workspace, array $attributes, array $lines): Journal
    {
        if (count($lines) < 2) {
            throw new UnbalancedJournalException('A journal needs at least two lines.');
        }

        $date = Carbon::parse($attributes['date'] ?? now());

        return DB::transaction(function () use ($workspace, $attributes, $lines, $date) {
            $period = self::openPeriodFor($workspace, $date);
            $accounts = self::resolveAccounts($workspace, $lines);

            $totalDebit = 0;
            $totalCredit = 0;
            $rows = [];

            foreach ($lines as $i => $line) {
                $debit = Money::toMinor($line['debit'] ?? 0);
                $credit = Money::toMinor($line['credit'] ?? 0);

                if ($debit < 0 || $credit < 0) {
                    throw new UnbalancedJournalException('Line amounts cannot be negative.');
                }
                if (($debit > 0) === ($credit > 0)) {
                    throw new UnbalancedJournalException(
                        'Each line must put a positive amount on exactly one side (debit XOR credit).'
                    );
                }

                $totalDebit += $debit;
                $totalCredit += $credit;

                $rows[] = [
                    'workspace_id' => $workspace->id,
                    'line_no' => $i + 1,
                    'account_id' => $accounts[$line['account_id']]->id,
                    'debit' => Money::fromMinor($debit),
                    'credit' => Money::fromMinor($credit),
                    'description' => $line['description'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                    'partner_id' => $line['partner_id'] ?? null,
                    'tax_code_id' => $line['tax_code_id'] ?? null,
                ];
            }

            if ($totalDebit !== $totalCredit) {
                throw new UnbalancedJournalException(sprintf(
                    'Journal does not balance: debit %s ≠ credit %s.',
                    Money::fromMinor($totalDebit),
                    Money::fromMinor($totalCredit),
                ));
            }
            if ($totalDebit <= 0) {
                throw new UnbalancedJournalException('Journal total must be greater than zero.');
            }

            $actor = $attributes['created_by'] ?? auth()->id();

            // Build as a draft first so the immutability guard never trips on
            // the very transition that posts it.
            $journal = new Journal([
                'workspace_id' => $workspace->id,
                'no' => self::nextNumber($workspace, $date),
                'date' => $date->toDateString(),
                'period_id' => $period->id,
                'type' => $attributes['type'] ?? 'general',
                'memo' => $attributes['memo'] ?? null,
                'status' => Journal::STATUS_DRAFT,
                'created_by' => $actor,
                'source_type' => $attributes['source_type'] ?? null,
                'source_id' => $attributes['source_id'] ?? null,
                'reverses_journal_id' => $attributes['reverses_journal_id'] ?? null,
            ]);
            $journal->save();

            $journal->lines()->createMany($rows);

            $journal->status = Journal::STATUS_POSTED;
            $journal->posted_at = now();
            $journal->posted_by = $actor;
            $journal->save();

            self::log($workspace, 'journal.posted', $journal, [
                'no' => $journal->no,
                'amount' => Money::fromMinor($totalDebit),
                'lines' => count($rows),
            ]);

            return $journal->load('lines');
        });
    }

    /**
     * Reverse a posted journal by posting its mirror image (debit ↔ credit),
     * linked back through reverses_journal_id. The original is never touched.
     */
    public static function reverse(Journal $original, array $attributes = []): Journal
    {
        if (! $original->isPosted()) {
            throw new LedgerException('Only a posted journal can be reversed.');
        }
        if ($original->isReversed()) {
            throw new LedgerException("Journal {$original->no} has already been reversed.");
        }

        $lines = $original->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'debit' => $line->credit,   // swap sides
            'credit' => $line->debit,
            'description' => $line->description,
            'department_id' => $line->department_id,
            'partner_id' => $line->partner_id,
            'tax_code_id' => $line->tax_code_id,
        ])->all();

        return self::post($original->workspace, [
            'date' => $attributes['date'] ?? now(),
            'type' => 'reversal',
            'memo' => $attributes['memo'] ?? "กลับรายการ {$original->no}",
            'created_by' => $attributes['created_by'] ?? auth()->id(),
            'reverses_journal_id' => $original->id,
        ], $lines);
    }

    private static function openPeriodFor(Workspace $workspace, CarbonInterface $date): Period
    {
        $period = Period::query()
            ->forWorkspace($workspace)
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();

        if (! $period) {
            throw new ClosedPeriodException("No accounting period covers {$date->toDateString()}.");
        }
        if (! $period->isOpen()) {
            throw new ClosedPeriodException("Period {$period->name} is {$period->status} — cannot post into it.");
        }

        return $period;
    }

    /**
     * Load every referenced account and assert it belongs to this workspace
     * and is active — blocking cross-tenant references at the write boundary.
     *
     * @return array<int, Account> keyed by id
     */
    private static function resolveAccounts(Workspace $workspace, array $lines): array
    {
        $ids = collect($lines)->pluck('account_id')->filter()->unique()->all();

        $accounts = Account::query()
            ->forWorkspace($workspace)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            $account = $accounts->get($id);
            if (! $account) {
                throw new LedgerException("Account {$id} does not belong to this workspace.");
            }
            if (! $account->is_active) {
                throw new LedgerException("Account {$account->code} is inactive.");
            }
        }

        return $accounts->all();
    }

    /** Per-workspace running number keyed on the document's Buddhist year, e.g. JV2569-00001. */
    private static function nextNumber(Workspace $workspace, CarbonInterface $date): string
    {
        $prefix = 'JV'.($date->year + 543).'-';

        $last = Journal::query()
            ->forWorkspace($workspace)
            ->where('no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('no')
            ->value('no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    private static function log(Workspace $workspace, string $action, Journal $journal, array $meta): void
    {
        ActivityLog::create([
            'workspace_id' => $workspace->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $journal->getMorphClass(),
            'subject_id' => $journal->id,
            'subject_label' => $journal->no,
            'metadata' => $meta,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
