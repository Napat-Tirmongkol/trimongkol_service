<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Journal;
use App\Models\Accounting\RecurringJournal;
use App\Models\Accounting\RecurringJournalLine;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringJournals
{
    /**
     * Create a recurring journal template.
     *
     * @param  array  $attributes  name, frequency, next_run_date, end_date?
     * @param  array  $lines  each: account_id, debit, credit, description?, department_id?
     */
    public static function create(Workspace $workspace, array $attributes, array $lines): RecurringJournal
    {
        if (count($lines) < 2) {
            throw new LedgerException('A recurring journal template needs at least two lines.');
        }

        return DB::transaction(function () use ($workspace, $attributes, $lines) {
            $template = RecurringJournal::create([
                'workspace_id' => $workspace->id,
                'name' => $attributes['name'],
                'frequency' => $attributes['frequency'] ?? RecurringJournal::FREQ_MONTHLY,
                'next_run_date' => $attributes['next_run_date'],
                'end_date' => $attributes['end_date'] ?? null,
                'is_active' => true,
                'created_by' => $attributes['created_by'] ?? null,
            ]);

            foreach ($lines as $i => $line) {
                RecurringJournalLine::create([
                    'workspace_id' => $workspace->id,
                    'recurring_journal_id' => $template->id,
                    'line_no' => $i + 1,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                ]);
            }

            return $template->load('lines');
        });
    }

    /** Update a recurring journal template — replaces lines. */
    public static function update(RecurringJournal $template, array $attributes, array $lines): RecurringJournal
    {
        if (count($lines) < 2) {
            throw new LedgerException('A recurring journal template needs at least two lines.');
        }

        return DB::transaction(function () use ($template, $attributes, $lines) {
            $template->update([
                'name' => $attributes['name'],
                'frequency' => $attributes['frequency'] ?? $template->frequency,
                'next_run_date' => $attributes['next_run_date'],
                'end_date' => $attributes['end_date'] ?? null,
            ]);

            $template->lines()->delete();
            foreach ($lines as $i => $line) {
                RecurringJournalLine::create([
                    'workspace_id' => $template->workspace_id,
                    'recurring_journal_id' => $template->id,
                    'line_no' => $i + 1,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                ]);
            }

            return $template->refresh()->load('lines');
        });
    }

    /**
     * Execute a template: post one journal from its lines and advance
     * next_run_date by one frequency period. Uses $date if given, otherwise
     * the template's next_run_date.
     */
    public static function run(RecurringJournal $template, ?string $date = null): Journal
    {
        if (! $template->is_active) {
            throw new LedgerException("Recurring journal \"{$template->name}\" is inactive.");
        }

        $template->loadMissing('lines');

        $postDate = $date ? Carbon::parse($date) : $template->next_run_date;

        return DB::transaction(function () use ($template, $postDate) {
            $lines = $template->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'debit' => (string) $line->debit,
                'credit' => (string) $line->credit,
                'description' => $line->description,
                'department_id' => $line->department_id,
            ])->all();

            $journal = LedgerPosting::post($template->workspace, [
                'date' => $postDate->toDateString(),
                'type' => 'recurring',
                'memo' => $template->name,
                'created_by' => null,
                'source_type' => $template->getMorphClass(),
                'source_id' => $template->id,
            ], $lines);

            $nextDate = self::advanceDate($postDate, $template->frequency);

            $updateData = ['last_run_at' => now(), 'next_run_date' => $nextDate];

            // Auto-deactivate if past end date
            if ($template->end_date && $nextDate->gt($template->end_date)) {
                $updateData['is_active'] = false;
            }

            $template->update($updateData);

            return $journal;
        });
    }

    private static function advanceDate(Carbon $from, string $frequency): Carbon
    {
        return match ($frequency) {
            RecurringJournal::FREQ_WEEKLY => $from->copy()->addWeek(),
            RecurringJournal::FREQ_QUARTERLY => $from->copy()->addMonths(3),
            default => $from->copy()->addMonth(),
        };
    }
}
