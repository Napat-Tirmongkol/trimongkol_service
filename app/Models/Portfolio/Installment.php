<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $table = 'portfolio_installments';

    protected $fillable = [
        'user_id',
        'month',
        'label',
        'monthly_payment',
        'total_amount',
        'total_months',
        'paid_months',
        'is_checked',
        'notes',
    ];

    protected $casts = [
        'monthly_payment' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_months' => 'integer',
        'paid_months' => 'integer',
        'is_checked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function remainingBalance(): float
    {
        $leftMonths = max(0, (int) $this->total_months - (int) $this->paid_months);
        if ($leftMonths <= 0) {
            return 0.0;
        }
        if ((float) $this->total_amount > 0) {
            return max(0.0, round((float) $this->total_amount - ((float) $this->monthly_payment * (int) $this->paid_months), 2));
        }
        return round((float) $this->monthly_payment * $leftMonths, 2);
    }

    public function progressPercent(): float
    {
        if ($this->total_months <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->paid_months / $this->total_months) * 100, 2));
    }
}
