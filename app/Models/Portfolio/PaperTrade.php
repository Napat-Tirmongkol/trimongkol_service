<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperTrade extends Model
{
    protected $table = 'portfolio_paper_trades';

    public const STATUS_OPEN      = 'open';
    public const STATUS_CLOSED    = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'paper_account_id',
        'symbol',
        'label',
        'kind',
        'currency',
        'side',
        'status',
        'quantity',
        'entry_price',
        'entry_total_thb',
        'current_price',
        'current_value_thb',
        'target_price',
        'stop_loss',
        'exit_price',
        'exit_total_thb',
        'realized_pnl_thb',
        'realized_pnl_pct',
        'trade_thesis',
        'opened_at',
        'closed_at',
        'metadata',
    ];

    protected $casts = [
        'quantity'          => 'decimal:4',
        'entry_price'       => 'decimal:4',
        'entry_total_thb'   => 'decimal:2',
        'current_price'     => 'decimal:4',
        'current_value_thb' => 'decimal:2',
        'target_price'      => 'decimal:4',
        'stop_loss'         => 'decimal:4',
        'exit_price'        => 'decimal:4',
        'exit_total_thb'    => 'decimal:2',
        'realized_pnl_thb'  => 'decimal:2',
        'realized_pnl_pct'  => 'decimal:2',
        'opened_at'         => 'datetime',
        'closed_at'         => 'datetime',
        'metadata'          => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaperAccount::class, 'paper_account_id');
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function unrealizedPnlThb(): float
    {
        if (!$this->isOpen() || $this->current_value_thb === null) {
            return 0.0;
        }

        return round((float) $this->current_value_thb - (float) $this->entry_total_thb, 2);
    }

    public function unrealizedPnlPct(): float
    {
        if (!$this->isOpen() || (float) $this->entry_total_thb <= 0 || $this->current_value_thb === null) {
            return 0.0;
        }

        $pnl = $this->unrealizedPnlThb();

        return round(($pnl / (float) $this->entry_total_thb) * 100, 2);
    }

    /**
     * Percentage progress towards target price relative to entry price.
     */
    public function targetProgressPct(): ?float
    {
        if (!$this->target_price || !$this->current_price || (float) $this->target_price <= (float) $this->entry_price) {
            return null;
        }

        $totalMove = (float) $this->target_price - (float) $this->entry_price;
        $curMove   = (float) $this->current_price - (float) $this->entry_price;

        return round(($curMove / $totalMove) * 100, 1);
    }
}
