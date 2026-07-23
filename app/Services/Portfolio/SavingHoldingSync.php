<?php

namespace App\Services\Portfolio;

use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\Transaction;

/**
 * Mirrors a saving budget item's ACTUAL amount into its matching deposit/cash
 * holding as one auto-managed "money in" transaction — so money set aside for
 * savings (whether ticked on the budget plan OR recorded in the ledger) shows
 * up in the portfolio / net worth.
 *
 * Investment kinds (stock/fund/crypto/gold) are intentionally NOT touched: they
 * are valued by quantity × market price, not by a money sum, so they must be
 * recorded as buy transactions in the holdings UI instead.
 */
class SavingHoldingSync
{
    public function __construct(private PriceFetcher $prices)
    {
    }

    /** Idempotent: re-derives the auto transaction from the item's current actual. */
    public function sync(BudgetItem $item): void
    {
        if ($item->category !== BudgetItem::CATEGORY_SAVING) {
            return;
        }

        $userId = $item->user_id;

        // 1. Remove any prior auto-created transaction for this budget item.
        $oldTransactions = Transaction::query()
            ->whereHas('holding', fn ($q) => $q->where('user_id', $userId))
            ->where('notes', 'like', "%(ID: {$item->id})%")
            ->get();

        $affectedHoldingIds = [];
        foreach ($oldTransactions as $t) {
            $affectedHoldingIds[] = $t->holding_id;
            $t->delete();
        }

        // 2. Effective actual: a real ledger/manual actual wins; otherwise the
        //    planned amount when the item is ticked. Drives the deposit so a
        //    ledger payment syncs even when the checkbox was never ticked.
        $amount = $item->actual_amount !== null
            ? (float) $item->actual_amount
            : ($item->is_checked ? (float) $item->amount : 0.0);

        if ($amount > 0) {
            $matchingHolding = Holding::query()
                ->forUser($userId)
                ->whereIn('kind', [Holding::KIND_DEPOSIT, Holding::KIND_CASH])
                ->whereRaw('LOWER(TRIM(label)) = ?', [strtolower(trim($item->label))])
                ->first();

            if ($matchingHolding) {
                $matchingHolding->transactions()->create([
                    'type'             => 'in',
                    'amount'           => $amount,
                    'transaction_date' => $item->month . '-01',
                    'notes'            => "[รายจ่ายเงินออมอัตโนมัติ] จากแผนงบประมาณเดือน {$item->month} (ID: {$item->id})",
                ]);

                $affectedHoldingIds[] = $matchingHolding->id;
            }
        }

        // 3. Recalculate every affected holding's balance.
        foreach (array_unique($affectedHoldingIds) as $holdingId) {
            $holding = Holding::find($holdingId);
            if ($holding) {
                $holding->recalculateFromTransactions($this->prices);
            }
        }
    }
}
