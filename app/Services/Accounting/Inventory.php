<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Accounting\Product;
use App\Models\Accounting\StockMovement;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Inventory with weighted moving-average cost.
 *
 * Stock IN  → DR inventory  / CR clearing (cash/AP) at the entered unit cost
 * Stock OUT → DR COGS       / CR inventory at the current moving-average cost
 * Adjust    → DR or CR inventory vs an adjustment account; updates on-hand
 *
 * on_hand and unit_cost on the product are kept in sync inside the same
 * transaction so reporting and the ledger never drift.
 */
class Inventory
{
    public static function register(Workspace $workspace, array $attributes): Product
    {
        foreach (['inventory_account_id', 'cogs_account_id'] as $field) {
            $account = Account::query()->forWorkspace($workspace)->find($attributes[$field] ?? null);
            if (! $account) {
                throw new LedgerException("Account for {$field} does not belong to this workspace.");
            }
        }

        return Product::create([
            'workspace_id' => $workspace->id,
            'sku' => $attributes['sku'],
            'name' => $attributes['name'],
            'unit' => $attributes['unit'] ?? 'ชิ้น',
            'unit_cost' => $attributes['unit_cost'] ?? 0,
            'on_hand' => 0,
            'inventory_account_id' => $attributes['inventory_account_id'],
            'cogs_account_id' => $attributes['cogs_account_id'],
            'is_active' => true,
        ]);
    }

    /**
     * Receive stock: update weighted-average cost and on-hand, post a balancing
     * journal against $clearingAccountId (usually cash, AP, or a clearing account).
     */
    public static function receive(Product $product, array $attributes, int $clearingAccountId): StockMovement
    {
        $quantity = (float) $attributes['quantity'];
        $unitCost = (float) $attributes['unit_cost'];

        if ($quantity <= 0 || $unitCost < 0) {
            throw new LedgerException('Quantity must be positive and unit cost non-negative.');
        }

        return DB::transaction(function () use ($product, $attributes, $quantity, $unitCost, $clearingAccountId) {
            $totalValueMinor = Money::toMinor((string) ($quantity * $unitCost));
            $totalValue = Money::fromMinor($totalValueMinor);

            // Weighted moving average:  new_avg = (old_qty × old_cost + new_qty × new_cost) / total_qty
            $oldQty = (float) $product->on_hand;
            $oldCost = (float) $product->unit_cost;
            $newQty = $oldQty + $quantity;
            $newAvg = $newQty > 0
                ? (($oldQty * $oldCost) + ($quantity * $unitCost)) / $newQty
                : 0;

            $journal = LedgerPosting::post($product->workspace, [
                'date' => $attributes['movement_date'],
                'type' => 'inventory_in',
                'memo' => "รับเข้าสินค้า {$product->sku} {$product->name}",
                'created_by' => null,
                'source_type' => $product->getMorphClass(),
                'source_id' => $product->id,
            ], [
                [
                    'account_id' => $product->inventory_account_id,
                    'debit' => $totalValue,
                    'credit' => 0,
                    'description' => "รับเข้า {$quantity} {$product->unit}",
                ],
                [
                    'account_id' => $clearingAccountId,
                    'debit' => 0,
                    'credit' => $totalValue,
                    'description' => "ต้นทุนสินค้ารับเข้า",
                ],
            ]);

            $movement = StockMovement::create([
                'workspace_id' => $product->workspace_id,
                'product_id' => $product->id,
                'movement_date' => $attributes['movement_date'],
                'type' => StockMovement::TYPE_IN,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'reference' => $attributes['reference'] ?? null,
                'note' => $attributes['note'] ?? null,
                'journal_id' => $journal->id,
                'created_by' => null,
            ]);

            $product->update(['on_hand' => $newQty, 'unit_cost' => round($newAvg, 2)]);

            return $movement;
        });
    }

    /**
     * Issue stock (sale or internal use): post DR COGS / CR inventory at the
     * current moving-average cost. Does not change unit_cost.
     */
    public static function issue(Product $product, array $attributes): StockMovement
    {
        $quantity = (float) $attributes['quantity'];

        if ($quantity <= 0) {
            throw new LedgerException('Quantity must be positive.');
        }
        if ($quantity > (float) $product->on_hand) {
            throw new LedgerException(
                "Not enough stock: requested {$quantity}, on hand {$product->on_hand}."
            );
        }

        return DB::transaction(function () use ($product, $attributes, $quantity) {
            $unitCost = (float) $product->unit_cost;
            $totalValueMinor = Money::toMinor((string) ($quantity * $unitCost));
            $totalValue = Money::fromMinor($totalValueMinor);

            $journal = LedgerPosting::post($product->workspace, [
                'date' => $attributes['movement_date'],
                'type' => 'inventory_out',
                'memo' => "ตัดสต็อก {$product->sku} {$product->name}",
                'created_by' => null,
                'source_type' => $product->getMorphClass(),
                'source_id' => $product->id,
            ], [
                [
                    'account_id' => $product->cogs_account_id,
                    'debit' => $totalValue,
                    'credit' => 0,
                    'description' => "ต้นทุนขาย {$quantity} {$product->unit}",
                ],
                [
                    'account_id' => $product->inventory_account_id,
                    'debit' => 0,
                    'credit' => $totalValue,
                    'description' => "ตัดสต็อกออก",
                ],
            ]);

            $movement = StockMovement::create([
                'workspace_id' => $product->workspace_id,
                'product_id' => $product->id,
                'movement_date' => $attributes['movement_date'],
                'type' => StockMovement::TYPE_OUT,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'reference' => $attributes['reference'] ?? null,
                'note' => $attributes['note'] ?? null,
                'journal_id' => $journal->id,
                'created_by' => null,
            ]);

            $product->update(['on_hand' => (float) $product->on_hand - $quantity]);

            return $movement;
        });
    }

    /**
     * Manual adjustment to a target on-hand quantity (positive = increase,
     * negative = decrease). Posts the variance against $adjustmentAccountId.
     */
    public static function adjust(Product $product, array $attributes, int $adjustmentAccountId): StockMovement
    {
        $delta = (float) $attributes['delta_quantity'];
        if ($delta == 0) {
            throw new LedgerException('Adjustment must be non-zero.');
        }

        return DB::transaction(function () use ($product, $attributes, $delta, $adjustmentAccountId) {
            $unitCost = (float) $product->unit_cost;
            $totalValueMinor = Money::toMinor((string) (abs($delta) * $unitCost));
            $totalValue = Money::fromMinor($totalValueMinor);

            // Positive delta = inventory up, adjustment credit; negative = inventory down, adjustment debit
            $lines = $delta > 0
                ? [
                    ['account_id' => $product->inventory_account_id, 'debit' => $totalValue, 'credit' => 0, 'description' => 'ปรับปรุงเพิ่ม'],
                    ['account_id' => $adjustmentAccountId, 'debit' => 0, 'credit' => $totalValue, 'description' => 'ปรับปรุงสต็อก'],
                ]
                : [
                    ['account_id' => $adjustmentAccountId, 'debit' => $totalValue, 'credit' => 0, 'description' => 'ปรับปรุงสต็อก'],
                    ['account_id' => $product->inventory_account_id, 'debit' => 0, 'credit' => $totalValue, 'description' => 'ปรับปรุงลด'],
                ];

            $journal = LedgerPosting::post($product->workspace, [
                'date' => $attributes['movement_date'],
                'type' => 'inventory_adjust',
                'memo' => "ปรับปรุงสต็อก {$product->sku}",
                'created_by' => null,
                'source_type' => $product->getMorphClass(),
                'source_id' => $product->id,
            ], $lines);

            $movement = StockMovement::create([
                'workspace_id' => $product->workspace_id,
                'product_id' => $product->id,
                'movement_date' => $attributes['movement_date'],
                'type' => StockMovement::TYPE_ADJUST,
                'quantity' => abs($delta),
                'unit_cost' => $unitCost,
                'total_value' => ($delta < 0 ? '-' : '').$totalValue,
                'reference' => $attributes['reference'] ?? null,
                'note' => $attributes['note'] ?? null,
                'journal_id' => $journal->id,
                'created_by' => null,
            ]);

            $product->update(['on_hand' => (float) $product->on_hand + $delta]);

            return $movement;
        });
    }

    /** Total inventory value across all active products. */
    public static function totalValue(Workspace $workspace): string
    {
        $totalMinor = Product::query()
            ->forWorkspace($workspace)
            ->where('is_active', true)
            ->get(['on_hand', 'unit_cost'])
            ->reduce(function (int $carry, Product $p): int {
                $value = (float) $p->on_hand * (float) $p->unit_cost;

                return $carry + Money::toMinor((string) $value);
            }, 0);

        return Money::fromMinor($totalMinor);
    }
}
