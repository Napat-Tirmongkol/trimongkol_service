<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Workspace;

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
