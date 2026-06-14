<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebtPayment extends Model
{
    protected $table = 'portfolio_debt_payments';

    protected $fillable = ['debt_id', 'user_id', 'month', 'amount', 'paid_amount', 'is_paid', 'notes'];

    protected $casts = [
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_paid'     => 'boolean',
    ];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /** Individual real payments logged against this งวด (กยศ-style debts). */
    public function logs(): HasMany
    {
        return $this->hasMany(DebtPaymentLog::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /** Outstanding for this งวด (target minus paid so far), never negative. */
    public function remaining(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->paid_amount);
    }

    /** Percent of this งวด's target that has been paid (0–100). */
    public function paidPercent(): int
    {
        $target = (float) $this->amount;
        return $target > 0
            ? min(100, (int) round((float) $this->paid_amount / $target * 100))
            : 0;
    }
}
