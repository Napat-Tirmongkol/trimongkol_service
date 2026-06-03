<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\PaymentAllocation;
use App\Models\Workspace;
use Illuminate\Support\Carbon;

/**
 * Financial statements, derived entirely from posted journal lines (the
 * ledger is the single source of truth — no stored running balances). All
 * arithmetic is integer satang via Money, so the statements tie out exactly.
 */
class Reporting
{
    /** Net balance per account on its normal side, up to $asOf. */
    public static function trialBalance(Workspace $workspace, ?string $asOf = null): array
    {
        $balances = self::balances($workspace, null, $asOf);
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach (Account::query()->forWorkspace($workspace)->orderBy('code')->get() as $account) {
            [$debit, $credit] = $balances[$account->id] ?? [0, 0];
            $net = $debit - $credit;
            if ($net === 0) {
                continue;
            }

            $totalDebit += max($net, 0);
            $totalCredit += max(-$net, 0);
            $rows[] = [
                'account' => $account,
                'debit' => Money::fromMinor(max($net, 0)),
                'credit' => Money::fromMinor(max(-$net, 0)),
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => Money::fromMinor($totalDebit),
            'total_credit' => Money::fromMinor($totalCredit),
            'balanced' => $totalDebit === $totalCredit,
        ];
    }

    /** Income and expense within [$from, $to] and the resulting profit. */
    public static function profitAndLoss(Workspace $workspace, ?string $from, ?string $to): array
    {
        $balances = self::balances($workspace, $from, $to);
        $income = [];
        $expense = [];
        $incomeTotal = 0;
        $expenseTotal = 0;

        foreach (Account::query()->forWorkspace($workspace)->whereIn('type', ['income', 'expense'])->orderBy('code')->get() as $account) {
            [$debit, $credit] = $balances[$account->id] ?? [0, 0];

            if ($account->type === 'income') {
                $amount = $credit - $debit;
                if ($amount !== 0) {
                    $income[] = ['account' => $account, 'amount' => Money::fromMinor($amount)];
                    $incomeTotal += $amount;
                }
            } else {
                $amount = $debit - $credit;
                if ($amount !== 0) {
                    $expense[] = ['account' => $account, 'amount' => Money::fromMinor($amount)];
                    $expenseTotal += $amount;
                }
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'income_total' => Money::fromMinor($incomeTotal),
            'expense_total' => Money::fromMinor($expenseTotal),
            'net_profit' => Money::fromMinor($incomeTotal - $expenseTotal),
        ];
    }

    /** Assets / liabilities / equity as at $asOf, with the period's unclosed profit. */
    public static function balanceSheet(Workspace $workspace, ?string $asOf = null): array
    {
        $balances = self::balances($workspace, null, $asOf);
        $assets = [];
        $liabilities = [];
        $equity = [];
        $assetTotal = 0;
        $liabilityTotal = 0;
        $equityTotal = 0;
        $netIncome = 0;

        foreach (Account::query()->forWorkspace($workspace)->orderBy('code')->get() as $account) {
            [$debit, $credit] = $balances[$account->id] ?? [0, 0];

            switch ($account->type) {
                case 'asset':
                    $amount = $debit - $credit;
                    if ($amount !== 0) {
                        $assets[] = ['account' => $account, 'amount' => Money::fromMinor($amount)];
                        $assetTotal += $amount;
                    }
                    break;
                case 'liability':
                    $amount = $credit - $debit;
                    if ($amount !== 0) {
                        $liabilities[] = ['account' => $account, 'amount' => Money::fromMinor($amount)];
                        $liabilityTotal += $amount;
                    }
                    break;
                case 'equity':
                    $amount = $credit - $debit;
                    if ($amount !== 0) {
                        $equity[] = ['account' => $account, 'amount' => Money::fromMinor($amount)];
                        $equityTotal += $amount;
                    }
                    break;
                case 'income':
                    $netIncome += $credit - $debit;
                    break;
                case 'expense':
                    $netIncome -= $debit - $credit;
                    break;
            }
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'assets_total' => Money::fromMinor($assetTotal),
            'liabilities_total' => Money::fromMinor($liabilityTotal),
            'equity_total' => Money::fromMinor($equityTotal),
            'net_income' => Money::fromMinor($netIncome),                         // current period, not yet closed
            'liabilities_and_equity' => Money::fromMinor($liabilityTotal + $equityTotal + $netIncome),
            'balanced' => $assetTotal === ($liabilityTotal + $equityTotal + $netIncome),
        ];
    }

    public const AGING_BUCKETS = ['current', '1_30', '31_60', '61_90', '90_plus'];

    /**
     * Aged receivables: outstanding amount on every open invoice, grouped by
     * partner and bucketed by days past due as of $asOf (default today).
     * "Open" = issued or partial; void/draft/paid are excluded.
     */
    public static function agedReceivables(Workspace $workspace, ?string $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?: now()->toDateString());

        $invoices = Invoice::query()->forWorkspace($workspace)
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
            ->whereDate('issue_date', '<=', $asOfDate->toDateString())
            ->with('partner')
            ->get();

        $allocated = self::allocatedByDocument($workspace, Invoice::class, $invoices->pluck('id'));

        $byPartner = [];
        foreach ($invoices as $invoice) {
            $outstandingMinor = Money::toMinor($invoice->total) - ($allocated[$invoice->id] ?? 0);
            if ($outstandingMinor <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date ?: $invoice->issue_date;
            $bucket = self::ageBucket($dueDate, $asOfDate);

            $partnerId = $invoice->partner_id;
            if (! isset($byPartner[$partnerId])) {
                $byPartner[$partnerId] = [
                    'partner' => $invoice->partner,
                    'amounts' => array_fill_keys(self::AGING_BUCKETS, 0),
                    'total' => 0,
                ];
            }
            $byPartner[$partnerId]['amounts'][$bucket] += $outstandingMinor;
            $byPartner[$partnerId]['total'] += $outstandingMinor;
        }

        return self::buildAgingResult($byPartner, $asOfDate);
    }

    /**
     * Aged payables: outstanding on every open bill, grouped by vendor and
     * bucketed by days past due as of $asOf.
     */
    public static function agedPayables(Workspace $workspace, ?string $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?: now()->toDateString());

        $bills = Bill::query()->forWorkspace($workspace)
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIAL])
            ->whereDate('issue_date', '<=', $asOfDate->toDateString())
            ->with('partner')
            ->get();

        $allocated = self::allocatedByDocument($workspace, Bill::class, $bills->pluck('id'));

        $byPartner = [];
        foreach ($bills as $bill) {
            $outstandingMinor = Money::toMinor($bill->total) - ($allocated[$bill->id] ?? 0);
            if ($outstandingMinor <= 0) {
                continue;
            }

            $dueDate = $bill->due_date ?: $bill->issue_date;
            $bucket = self::ageBucket($dueDate, $asOfDate);

            $partnerId = $bill->partner_id;
            if (! isset($byPartner[$partnerId])) {
                $byPartner[$partnerId] = [
                    'partner' => $bill->partner,
                    'amounts' => array_fill_keys(self::AGING_BUCKETS, 0),
                    'total' => 0,
                ];
            }
            $byPartner[$partnerId]['amounts'][$bucket] += $outstandingMinor;
            $byPartner[$partnerId]['total'] += $outstandingMinor;
        }

        return self::buildAgingResult($byPartner, $asOfDate);
    }

    /** Days-past-due → bucket key. Future/today → 'current'. */
    private static function ageBucket($dueDate, Carbon $asOf): string
    {
        $due = Carbon::parse($dueDate);
        $days = $due->diffInDays($asOf, false);  // positive when asOf is after due

        return match (true) {
            $days <= 0 => 'current',
            $days <= 30 => '1_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => '90_plus',
        };
    }

    /**
     * Sum allocated amounts per document, in minor units.
     *
     * @return array<int, int> document_id => allocatedMinor
     */
    private static function allocatedByDocument(Workspace $workspace, string $modelClass, $documentIds): array
    {
        if (count($documentIds) === 0) {
            return [];
        }

        $instance = new $modelClass;
        $morphClass = $instance->getMorphClass();

        return PaymentAllocation::query()
            ->forWorkspace($workspace)
            ->where('allocatable_type', $morphClass)
            ->whereIn('allocatable_id', $documentIds)
            ->selectRaw('allocatable_id, SUM(amount) as total')
            ->groupBy('allocatable_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->allocatable_id => Money::toMinor((string) $row->total)])
            ->all();
    }

    /** Convert internal minor-unit aging map into the view-friendly shape. */
    private static function buildAgingResult(array $byPartner, Carbon $asOf): array
    {
        $rows = [];
        $totals = array_fill_keys(self::AGING_BUCKETS, 0);
        $grandTotal = 0;

        // Sort by total descending so the biggest debtors/creditors lead.
        usort($byPartner, fn ($a, $b) => $b['total'] <=> $a['total']);

        foreach ($byPartner as $entry) {
            $row = [
                'partner' => $entry['partner'],
                'amounts' => [],
                'total' => Money::fromMinor($entry['total']),
            ];
            foreach (self::AGING_BUCKETS as $bucket) {
                $minor = $entry['amounts'][$bucket];
                $row['amounts'][$bucket] = Money::fromMinor($minor);
                $totals[$bucket] += $minor;
            }
            $grandTotal += $entry['total'];
            $rows[] = $row;
        }

        return [
            'as_of' => $asOf->toDateString(),
            'rows' => $rows,
            'totals' => array_map(fn ($v) => Money::fromMinor($v), $totals),
            'grand_total' => Money::fromMinor($grandTotal),
        ];
    }

    /**
     * Sum debit/credit (integer satang) per account over posted journals in
     * the date window.
     *
     * @return array<int, array{0:int,1:int}> account_id => [debitMinor, creditMinor]
     */
    private static function balances(Workspace $workspace, ?string $from, ?string $to): array
    {
        return JournalLine::query()
            ->forWorkspace($workspace)
            ->whereHas('journal', function ($q) use ($from, $to) {
                $q->where('status', Journal::STATUS_POSTED);
                if ($from) {
                    $q->whereDate('date', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('date', '<=', $to);
                }
            })
            ->selectRaw('account_id, SUM(debit) as d, SUM(credit) as c')
            ->groupBy('account_id')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->account_id => [Money::toMinor((string) $r->d), Money::toMinor((string) $r->c)]])
            ->all();
    }
}
