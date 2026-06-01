<?php

namespace Tests\Feature\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\OpeningBalances;
use App\Services\Accounting\Reporting;
use App\Services\Accounting\TaxCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private function actingOwner(): Workspace
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'ACME Co.', 'slug' => 'acme-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        ChartOfAccounts::seed($workspace);
        TaxCodes::seedDefault($workspace);
        \App\Models\Accounting\Period::create([
            'workspace_id' => $workspace->id, 'name' => '2569',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'open',
        ]);
        $this->actingAs($user);

        return $workspace;
    }

    private function account(string $code): Account
    {
        return Account::where('workspace_id', $this->workspace->id)->where('code', $code)->firstOrFail();
    }

    public function test_posting_opening_balances_creates_a_balanced_opening_journal(): void
    {
        $this->workspace = $this->actingOwner();

        $journal = OpeningBalances::post($this->workspace, [
            $this->account('1113-01')->id => ['debit' => '100000'],   // bank
            $this->account('3100-00')->id => ['credit' => '100000'],  // share capital
        ], '2026-01-01');

        $this->assertSame(OpeningBalances::JOURNAL_TYPE, $journal->type);
        $this->assertTrue($journal->isPosted());
        $this->assertCount(2, $journal->lines);
        $this->assertSame('2026-01-01', $journal->date->toDateString());

        // The trial balance now opens from the accountant's figures.
        $tb = Reporting::trialBalance($this->workspace, '2026-12-31');
        $this->assertTrue($tb['balanced']);
        $this->assertSame('100000.00', $tb['total_debit']);
        $this->assertSame('100000.00', $tb['total_credit']);
    }

    public function test_blank_accounts_are_skipped(): void
    {
        $this->workspace = $this->actingOwner();

        $journal = OpeningBalances::post($this->workspace, [
            $this->account('1113-01')->id => ['debit' => '5000', 'credit' => ''],
            $this->account('1111-00')->id => ['debit' => '', 'credit' => ''],   // left blank → skipped
            $this->account('3100-00')->id => ['debit' => null, 'credit' => '5000'],
        ], '2026-01-01');

        $this->assertCount(2, $journal->lines);
    }

    public function test_opening_balances_can_only_be_posted_once(): void
    {
        $this->workspace = $this->actingOwner();

        OpeningBalances::post($this->workspace, [
            $this->account('1113-01')->id => ['debit' => '100000'],
            $this->account('3100-00')->id => ['credit' => '100000'],
        ], '2026-01-01');

        $this->expectException(LedgerException::class);
        OpeningBalances::post($this->workspace, [
            $this->account('1113-01')->id => ['debit' => '1'],
            $this->account('3100-00')->id => ['credit' => '1'],
        ], '2026-01-01');
    }

    public function test_unbalanced_balances_are_rejected(): void
    {
        $this->workspace = $this->actingOwner();

        $this->expectException(UnbalancedJournalException::class);
        OpeningBalances::post($this->workspace, [
            $this->account('1113-01')->id => ['debit' => '100000'],
            $this->account('3100-00')->id => ['credit' => '90000'],
        ], '2026-01-01');
    }

    public function test_an_account_cannot_hold_both_a_debit_and_a_credit(): void
    {
        $this->workspace = $this->actingOwner();

        $this->expectException(LedgerException::class);
        OpeningBalances::post($this->workspace, [
            $this->account('1113-01')->id => ['debit' => '100', 'credit' => '50'],
            $this->account('3100-00')->id => ['credit' => '50'],
        ], '2026-01-01');
    }

    public function test_edit_page_renders_the_entry_sheet(): void
    {
        $this->workspace = $this->actingOwner();

        $this->get(route('accounting.opening-balances.edit'))
            ->assertOk()
            ->assertSee(__('app.accounting.opening_balances'))
            ->assertSee('3100-00')          // an account row is rendered
            ->assertSee('01/01/2026');      // opening date shown
    }

    public function test_store_via_http_posts_then_locks_the_page(): void
    {
        $this->workspace = $this->actingOwner();

        $this->post(route('accounting.opening-balances.store'), [
            'balances' => [
                $this->account('1113-01')->id => ['debit' => '100000'],
                $this->account('3100-00')->id => ['credit' => '100000'],
            ],
        ])->assertRedirect(route('accounting.reports'))->assertSessionHas('status');

        $this->assertDatabaseHas('accounting_journals', [
            'workspace_id' => $this->workspace->id,
            'type' => OpeningBalances::JOURNAL_TYPE,
            'status' => Journal::STATUS_POSTED,
        ]);

        // Reloading the page now shows the locked state, not the form.
        $this->get(route('accounting.opening-balances.edit'))
            ->assertOk()
            ->assertSee(__('app.accounting.opening_exists_title'));
    }

    public function test_unbalanced_http_post_shows_error_and_writes_nothing(): void
    {
        $this->workspace = $this->actingOwner();

        $this->from(route('accounting.opening-balances.edit'))
            ->post(route('accounting.opening-balances.store'), [
                'balances' => [
                    $this->account('1113-01')->id => ['debit' => '100000'],
                    $this->account('3100-00')->id => ['credit' => '90000'],
                ],
            ])
            ->assertRedirect(route('accounting.opening-balances.edit'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('accounting_journals', [
            'workspace_id' => $this->workspace->id,
            'type' => OpeningBalances::JOURNAL_TYPE,
        ]);
    }

    public function test_members_cannot_post_opening_balances(): void
    {
        $this->workspace = $this->actingOwner();

        $member = User::factory()->create();
        $this->workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $this->actingAs($member);

        $this->post(route('accounting.opening-balances.store'), [
            'balances' => [
                $this->account('1113-01')->id => ['debit' => '100000'],
                $this->account('3100-00')->id => ['credit' => '100000'],
            ],
        ])->assertForbidden();
    }
}
