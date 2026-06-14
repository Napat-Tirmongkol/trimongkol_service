<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistItem extends Model
{
    protected $table = 'portfolio_watchlist';

    protected $fillable = [
        'user_id',
        'kind',
        'symbol',
        'label',
        'currency',
        'current_price',
        'current_price_thb',
        'last_priced_at',
        'notes',
    ];

    protected $casts = [
        'current_price' => 'decimal:8',
        'current_price_thb' => 'decimal:2',
        'last_priced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function isPriceable(): bool
    {
        return filled($this->symbol);
    }
}
