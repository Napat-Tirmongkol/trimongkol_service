<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Snapshot extends Model
{
    protected $table = 'portfolio_snapshots';

    protected $fillable = [
        'user_id', 'snapshot_date',
        'total_assets_thb', 'total_debts_thb', 'net_worth_thb',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'total_assets_thb' => 'decimal:2',
        'total_debts_thb' => 'decimal:2',
        'net_worth_thb' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
