<?php

namespace Tests\Feature\Accounting;

use App\Exceptions\Accounting\ClosedPeriodException;
use App\Exceptions\Accounting\ImmutableLedgerException;
use App\Exceptions\Accounting\LedgerException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\Period;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LedgerPostingTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::create(['name' => 'ACME Co.', 'slug' => 'acme-'.Str::random(6)]);
        ChartOfAccounts::seed($this->workspace);
        Period::create([
            'workspace_id' => $this->workspace->id,
            'name' => '2569',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => Period::STATUS_OPEN,
        ]);
    }

    private function account(string $code): Account
    {
        return Account::where('workspace_id', $this->workspace->id)->where('code', $code)->firstOrFail();
    }

    /** A simple receipt: debit cash, credit service income. */
    private function balancedLines(string $amount = '100.00'): array
    {
        return [
            ['account_id' => $this->account('1111-00')->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $this->account('4100-02')->id, 'debit' => 0, 'credit' => $amount],
        ];
    }

    public function test_posts_a_balanced_journal_and_freezes_it(): void
    {
        $journal = LedgerPosting::post($this->workspace, [
            'date' => '2026-03-01',
            'memo' => 'รับเงินค่าบริการ',
        ], $this->balancedLines('1500.00'));

        $this->assertSame(Journal::STATUS_POSTED, $journal->status);
        $this->assertSame('JV2569-00001', $journal->no);
        $this->assertNotNull($journal->posted_at);
        $this->assertCount(2, $journal->lines);

        $debit = $journal->lines->sum(fn ($l) => (float) $l->debit);
        $credit = $journal->lines->sum(fn ($l) => (float) $l->credit);
        $this->assertEqualsWithDelta(1500.0, $debit, 0.001);
        $this->assertEqualsWithDelta($debit, $credit, 0.001);

        $this->assertDatabaseHas('accounting_activity_log', [
            'workspace_id' => $this->workspace->id,
            'action' => 'journal.posted',
            'subject_id' => $journal->id,
        ]);
    }

    public function test_running_number_increments_per_workspace(): void
    {
        $a = LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], $this->balancedLines());
        $b = LedgerPosting::post($this->workspace, ['date' => '2026-03-02'], $this->balancedLines());

        $this->assertSame('JV2569-00001', $a->no);
        $this->assertSame('JV2569-00002', $b->no);
    }

    public function test_rejects_an_unbalanced_journal_and_rolls_back(): void
    {
        $lines = [
            ['account_id' => $this->account('1111-00')->id, 'debit' => '100.00', 'credit' => 0],
            ['account_id' => $this->account('4100-02')->id, 'debit' => 0, 'credit' => '90.00'],
        ];

        try {
            LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], $lines);
            $this->fail('expected UnbalancedJournalException');
        } catch (UnbalancedJournalException) {
            // expected
        }

        $this->assertSame(0, Journal::count(), 'nothing should persist on a failed post');
        $this->assertSame(0, JournalLine::count());
    }

    public function test_rejects_a_line_that_is_not_one_sided(): void
    {
        $this->expectException(UnbalancedJournalException::class);

        LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], [
            ['account_id' => $this->account('1111-00')->id, 'debit' => '100.00', 'credit' => '100.00'],
            ['account_id' => $this->account('4100-02')->id, 'debit' => 0, 'credit' => '100.00'],
        ]);
    }

    public function test_a_posted_journal_cannot_be_edited_or_deleted(): void
    {
        $journal = LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], $this->balancedLines());

        try {
            $journal->update(['memo' => 'tampered']);
            $this->fail('expected ImmutableLedgerException on update');
        } catch (ImmutableLedgerException) {
            // expected
        }

        try {
            $journal->lines->first()->update(['debit' => '999.00']);
            $this->fail('expected ImmutableLedgerException on line update');
        } catch (ImmutableLedgerException) {
            // expected
        }

        $this->expectException(ImmutableLedgerException::class);
        $journal->delete();
    }

    public function test_cannot_post_into_a_closed_period(): void
    {
        Period::query()->update(['status' => Period::STATUS_CLOSED]);

        $this->expectException(ClosedPeriodException::class);
        LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], $this->balancedLines());
    }

    public function test_cannot_post_when_no_period_covers_the_date(): void
    {
        $this->expectException(ClosedPeriodException::class);
        LedgerPosting::post($this->workspace, ['date' => '2030-06-01'], $this->balancedLines());
    }

    public function test_rejects_an_account_from_another_workspace(): void
    {
        $other = Workspace::create(['name' => 'Other Co.', 'slug' => 'other-'.Str::random(6)]);
        ChartOfAccounts::seed($other);
        $foreignCash = Account::where('workspace_id', $other->id)->where('code', '1111-00')->firstOrFail();

        $this->expectException(LedgerException::class);
        LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], [
            ['account_id' => $foreignCash->id, 'debit' => '100.00', 'credit' => 0],
            ['account_id' => $this->account('4100-02')->id, 'debit' => 0, 'credit' => '100.00'],
        ]);
    }

    public function test_reverse_creates_a_linked_mirror_entry(): void
    {
        $original = LedgerPosting::post($this->workspace, ['date' => '2026-03-01'], $this->balancedLines('500.00'));

        $reversal = LedgerPosting::reverse($original);

        $this->assertSame('reversal', $reversal->type);
        $this->assertSame($original->id, $reversal->reverses_journal_id);
        $this->assertTrue($original->fresh()->isReversed());

        // Sides are mirrored: original cash debit 500 → reversal cash credit 500.
        $origCash = $original->lines->firstWhere('account_id', $this->account('1111-00')->id);
        $revCash = $reversal->lines->firstWhere('account_id', $this->account('1111-00')->id);
        $this->assertSame($origCash->debit, $revCash->credit);
        $this->assertSame('0.00', $revCash->debit);

        $this->expectException(LedgerException::class);
        LedgerPosting::reverse($original->fresh()); // cannot reverse twice
    }
}
