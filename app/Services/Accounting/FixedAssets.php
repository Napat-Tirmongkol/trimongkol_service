<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Workspace;
use Illuminate\Support\Carbon;

class FixedAssets
{
    /**
     * Register a new fixed asset.
     *
     * @param  array  $attributes  code, name, purchase_date, cost, salvage_value,
     *                             useful_life_months, asset_account_id,
     *                             accumulated_account_id, depreciation_expense_account_id
     */
    public static function register(Workspace $workspace, array $attributes): FixedAsset
    {
        foreach (['asset_account_id', 'accumulated_account_id', 'depreciation_expense_account_id'] as $field) {
            $account = Account::query()->forWorkspace($workspace)->find($attributes[$field] ?? null);
            if (! $account) {
                throw new LedgerException("Account for {$field} does not belong to this workspace.");
            }
        }

        return FixedAsset::create([
            'workspace_id' => $workspace->id,
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'purchase_date' => $attributes['purchase_date'],
            'cost' => $attributes['cost'],
            'salvage_value' => $attributes['salvage_value'] ?? 0,
            'useful_life_months' => $attributes['useful_life_months'],
            'asset_account_id' => $attributes['asset_account_id'],
            'accumulated_account_id' => $attributes['accumulated_account_id'],
            'depreciation_expense_account_id' => $attributes['depreciation_expense_account_id'],
            'status' => FixedAsset::STATUS_ACTIVE,
            'created_by' => $attributes['created_by'] ?? null,
        ]);
    }

    /**
     * Post straight-line depreciation for a calendar month (YYYY-MM).
     * Posts on the last day of that month.
     *
     * DR depreciation expense   monthly amount
     * CR accumulated depreciation  monthly amount
     */
    public static function depreciate(FixedAsset $asset, string $forMonth): Journal
    {
        if (! $asset->isActive()) {
            throw new LedgerException("Asset {$asset->code} is not active.");
        }

        $monthDate = Carbon::createFromFormat('Y-m', $forMonth);
        $postDate = $monthDate->copy()->endOfMonth()->toDateString();

        // Guard: already depreciated this month
        $already = Journal::query()
            ->forWorkspace($asset->workspace)
            ->where('type', 'depreciation')
            ->where('source_type', $asset->getMorphClass())
            ->where('source_id', $asset->id)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->exists();

        if ($already) {
            throw new LedgerException(
                "Depreciation for {$asset->code} in {$forMonth} has already been posted."
            );
        }

        $monthly = self::monthlyDepreciationMinor($asset);
        if ($monthly <= 0) {
            throw new LedgerException("Asset {$asset->code} is fully depreciated.");
        }

        // Check we haven't exceeded total depreciable amount
        $accumulated = self::accumulatedDepreciationMinor($asset);
        $depreciable = Money::toMinor($asset->cost) - Money::toMinor($asset->salvage_value);
        $remaining = $depreciable - $accumulated;

        if ($remaining <= 0) {
            throw new LedgerException("Asset {$asset->code} is fully depreciated.");
        }

        $amount = min($monthly, $remaining);

        return LedgerPosting::post($asset->workspace, [
            'date' => $postDate,
            'type' => 'depreciation',
            'memo' => "ค่าเสื่อมราคา {$asset->name} เดือน {$forMonth}",
            'created_by' => null,
            'source_type' => $asset->getMorphClass(),
            'source_id' => $asset->id,
        ], [
            [
                'account_id' => $asset->depreciation_expense_account_id,
                'debit' => Money::fromMinor($amount),
                'credit' => 0,
                'description' => "ค่าเสื่อมราคา {$asset->name}",
            ],
            [
                'account_id' => $asset->accumulated_account_id,
                'debit' => 0,
                'credit' => Money::fromMinor($amount),
                'description' => "ค่าเสื่อมราคาสะสม {$asset->name}",
            ],
        ]);
    }

    /** Monthly straight-line depreciation amount as an integer satang. */
    public static function monthlyDepreciationMinor(FixedAsset $asset): int
    {
        $depreciable = Money::toMinor($asset->cost) - Money::toMinor($asset->salvage_value);

        return (int) floor($depreciable / $asset->useful_life_months);
    }

    /** Sum of all posted depreciation entries for this asset in satang. */
    public static function accumulatedDepreciationMinor(FixedAsset $asset): int
    {
        $rows = JournalLine::query()
            ->forWorkspace($asset->workspace)
            ->where('account_id', $asset->accumulated_account_id)
            ->whereHas('journal', function ($q) use ($asset) {
                $q->where('status', Journal::STATUS_POSTED)
                    ->where('type', 'depreciation')
                    ->where('source_type', $asset->getMorphClass())
                    ->where('source_id', $asset->id);
            })
            ->get(['credit']);

        return $rows->reduce(fn (int $carry, $r): int => $carry + Money::toMinor($r->credit), 0);
    }

    /** Mark an asset as disposed. */
    public static function dispose(FixedAsset $asset, string $disposedOn): FixedAsset
    {
        if (! $asset->isActive()) {
            throw new LedgerException("Asset {$asset->code} is already disposed.");
        }

        $asset->update([
            'status' => FixedAsset::STATUS_DISPOSED,
            'disposed_on' => $disposedOn,
        ]);

        return $asset->refresh();
    }

    /** Net book value = cost − accumulated depreciation, as a decimal string. */
    public static function bookValue(FixedAsset $asset): string
    {
        $accumulated = self::accumulatedDepreciationMinor($asset);

        return Money::fromMinor(Money::toMinor($asset->cost) - $accumulated);
    }
}
