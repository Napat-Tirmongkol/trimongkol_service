<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holding extends Model
{
    protected $table = 'portfolio_holdings';

    public const KIND_STOCK      = 'stock';
    public const KIND_DEPOSIT    = 'deposit';
    public const KIND_CASH       = 'cash';
    public const KIND_DEBT       = 'debt';
    public const KIND_GOLD       = 'gold';
    public const KIND_CRYPTO     = 'crypto';
    public const KIND_FUND       = 'fund';
    public const KIND_REALESTATE = 'realestate';
    public const KIND_OTHER      = 'other';

    public const KINDS = [
        self::KIND_STOCK,
        self::KIND_DEPOSIT,
        self::KIND_CASH,
        self::KIND_DEBT,
        self::KIND_GOLD,
        self::KIND_CRYPTO,
        self::KIND_FUND,
        self::KIND_REALESTATE,
        self::KIND_OTHER,
    ];

    /** Kinds that can fetch live prices from an external feed. */
    public const PRICEABLE_KINDS = [self::KIND_STOCK, self::KIND_CRYPTO, self::KIND_FUND];

    protected $fillable = [
        'user_id', 'kind', 'label', 'symbol',
        'quantity', 'cost_basis', 'currency',
        'current_price', 'current_value_thb', 'last_priced_at',
        'notes', 'metadata', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'cost_basis' => 'decimal:2',
        'current_price' => 'decimal:8',
        'current_value_thb' => 'decimal:2',
        'last_priced_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function isDebt(): bool
    {
        return $this->kind === self::KIND_DEBT;
    }

    public function isPriceable(): bool
    {
        return in_array($this->kind, self::PRICEABLE_KINDS, true) && filled($this->symbol);
    }

    /** Net contribution to net worth — debts subtract, everything else adds. */
    public function netValueThb(): float
    {
        $val = (float) ($this->current_value_thb ?? 0);

        return $this->isDebt() ? -$val : $val;
    }
}
