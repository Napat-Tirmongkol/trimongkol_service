<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Journal;
use App\Models\Workspace;

/**
 * Opening balances (ยอดยกมา) — the one-time bridge from the books kept by the
 * outsourced accountant into this system. The user types their year-end trial
 * balance (งบทดลอง) and we post it as a single, balanced "opening" journal so
 * every statement from day one ties back to the accountant's figures.
 */
class OpeningBalances
{
    public const JOURNAL_TYPE = 'opening';

    /** The posted opening journal, if balances have already been entered. */
    public static function existing(Workspace $workspace): ?Journal
    {
        return Journal::query()
            ->forWorkspace($workspace)
            ->where('type', self::JOURNAL_TYPE)
            ->where('status', Journal::STATUS_POSTED)
            ->latest('id')
            ->first();
    }

    /**
     * Post the trial balance as the opening journal. Each account carries an
     * amount on exactly one side; the whole entry must balance (LedgerPosting
     * enforces it). Blank/zero accounts are skipped.
     *
     * @param  array<int|string, array{debit?:string|null, credit?:string|null}>  $balances  keyed by account_id
     */
    public static function post(Workspace $workspace, array $balances, string $date, ?string $memo = null): Journal
    {
        if (self::existing($workspace)) {
            throw new LedgerException(__('app.accounting.opening_exists'));
        }

        $lines = [];
        foreach ($balances as $accountId => $amounts) {
            $debit = self::amount($amounts['debit'] ?? null);
            $credit = self::amount($amounts['credit'] ?? null);

            if ($debit > 0 && $credit > 0) {
                throw new LedgerException(__('app.accounting.opening_two_sided'));
            }
            if ($debit === 0 && $credit === 0) {
                continue;
            }

            $lines[] = [
                'account_id' => (int) $accountId,
                'debit' => Money::fromMinor($debit),
                'credit' => Money::fromMinor($credit),
                'description' => __('app.accounting.opening_line_memo'),
            ];
        }

        return LedgerPosting::post($workspace, [
            'date' => $date,
            'type' => self::JOURNAL_TYPE,
            'memo' => $memo ?: __('app.accounting.opening_memo'),
        ], $lines);
    }

    /** A blank cell on the entry sheet means zero, not an error. */
    private static function amount($value): int
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === null || $value === '') ? 0 : Money::toMinor($value);
    }
}
