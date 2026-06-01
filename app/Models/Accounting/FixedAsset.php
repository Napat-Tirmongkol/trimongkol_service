<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_fixed_assets';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'workspace_id', 'code', 'name', 'purchase_date', 'cost',
        'salvage_value', 'useful_life_months',
        'asset_account_id', 'accumulated_account_id', 'depreciation_expense_account_id',
        'status', 'disposed_on', 'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'disposed_on' => 'date',
    ];

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
