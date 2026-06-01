<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\BankStatement;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Bank reconciliation: import bank statement lines (CSV or manual) and
 * match each line to a posted journal line on the same cash/bank account.
 *
 * A statement line is "matched" once journal_line_id is set. The reconciled
 * balance = opening balance + sum of matched deposits − sum of matched
 * withdrawals. Any unmatched lines are the outstanding difference.
 */
class BankReconciliation
{
    /**
     * Parse a CSV string and bulk-import statement lines for $account.
     * Expected columns (order matters, no header required but skipped if first
     * cell is non-numeric): date, description, amount, reference.
     *
     * @return int  number of lines created
     */
    public static function importCsv(Workspace $workspace, Account $account, string $csv): int
    {
        self::assertBankAccount($account);

        $lines = array_filter(
            array_map('str_getcsv', explode("\n", trim($csv))),
            fn ($row) => count($row) >= 3
        );

        $created = 0;
        foreach ($lines as $row) {
            // Skip header row if first cell is not a date
            if (! strtotime($row[0])) {
                continue;
            }

            $amount = (float) str_replace(',', '', $row[2] ?? 0);

            BankStatement::create([
                'workspace_id' => $workspace->id,
                'account_id' => $account->id,
                'statement_date' => date('Y-m-d', strtotime($row[0])),
                'description' => trim($row[1] ?? ''),
                'amount' => $amount,
                'reference' => trim($row[3] ?? '') ?: null,
            ]);
            $created++;
        }

        return $created;
    }

    /** Manually add a single statement line. */
    public static function addLine(Workspace $workspace, Account $account, array $attributes): BankStatement
    {
        self::assertBankAccount($account);

        return BankStatement::create([
            'workspace_id' => $workspace->id,
            'account_id' => $account->id,
            'statement_date' => $attributes['statement_date'],
            'description' => $attributes['description'] ?? null,
            'amount' => $attributes['amount'],
            'reference' => $attributes['reference'] ?? null,
        ]);
    }

    /** Link a statement line to a posted journal line. */
    public static function match(BankStatement $statement, JournalLine $journalLine): BankStatement
    {
        if ($statement->workspace_id !== $journalLine->workspace_id) {
            throw new RuntimeException('Cross-workspace match is not allowed.');
        }
        if ($journalLine->account_id !== $statement->account_id) {
            throw new RuntimeException('Journal line account does not match the bank account.');
        }
        if (! $journalLine->journal || $journalLine->journal->status !== Journal::STATUS_POSTED) {
            throw new RuntimeException('Only posted journal lines can be matched.');
        }

        $statement->update(['journal_line_id' => $journalLine->id]);

        return $statement->refresh();
    }

    /** Remove the match from a statement line. */
    public static function unmatch(BankStatement $statement): BankStatement
    {
        $statement->update(['journal_line_id' => null]);

        return $statement->refresh();
    }

    /**
     * Reconciliation summary for a bank account over a date range.
     *
     * @return array{matched: int, unmatched: int, matched_amount: string, unmatched_amount: string, statements: Collection}
     */
    public static function summary(Workspace $workspace, Account $account, string $from, string $to): array
    {
        $statements = BankStatement::query()
            ->forWorkspace($workspace)
            ->where('account_id', $account->id)
            ->whereBetween('statement_date', [$from, $to])
            ->orderBy('statement_date')
            ->orderBy('id')
            ->with('journalLine.journal')
            ->get();

        $matched = $statements->filter->isMatched();
        $unmatched = $statements->reject->isMatched();

        return [
            'matched' => $matched->count(),
            'unmatched' => $unmatched->count(),
            'matched_amount' => Money::fromMinor(
                $matched->sum(fn ($s) => Money::toMinor($s->amount))
            ),
            'unmatched_amount' => Money::fromMinor(
                $unmatched->sum(fn ($s) => Money::toMinor($s->amount))
            ),
            'statements' => $statements,
        ];
    }

    /**
     * Unmatched posted journal lines for a bank account in a date range
     * (candidate lines to match against statement lines).
     */
    public static function unmatchedJournalLines(Workspace $workspace, Account $account, string $from, string $to): Collection
    {
        $matchedLineIds = BankStatement::query()
            ->forWorkspace($workspace)
            ->where('account_id', $account->id)
            ->whereNotNull('journal_line_id')
            ->pluck('journal_line_id');

        return JournalLine::query()
            ->forWorkspace($workspace)
            ->where('account_id', $account->id)
            ->whereNotIn('id', $matchedLineIds)
            ->whereHas('journal', function ($q) use ($from, $to) {
                $q->where('status', Journal::STATUS_POSTED)
                    ->whereBetween('date', [$from, $to]);
            })
            ->with('journal')
            ->orderBy('id')
            ->get();
    }

    private static function assertBankAccount(Account $account): void
    {
        if ($account->type !== 'asset') {
            throw new RuntimeException('Bank reconciliation is only for asset (cash/bank) accounts.');
        }
    }
}
