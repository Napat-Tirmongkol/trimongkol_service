<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'workspace_id', 'plan_key', 'status',
        'trial_ends_at', 'current_period_end', 'cancelled_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The plan that's *currently* in effect, accounting for trial expiry.
     * A trial that's run past trial_ends_at silently drops back to Free
     * without needing a scheduler to flip the row.
     */
    public function effectivePlan(): Plan
    {
        $key = $this->effectivePlanKey();
        return Plan::find($key) ?? Plan::find(Plan::FREE);
    }

    public function effectivePlanKey(): string
    {
        if ($this->status === self::STATUS_TRIAL) {
            return $this->trial_ends_at && $this->trial_ends_at->isFuture()
                ? $this->plan_key
                : Plan::FREE;
        }
        if ($this->status === self::STATUS_ACTIVE) {
            return $this->plan_key;
        }
        return Plan::FREE;
    }

    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function trialDaysLeft(): ?int
    {
        if (! $this->isOnTrial()) {
            return null;
        }
        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }
}
