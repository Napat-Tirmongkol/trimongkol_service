<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    protected $table = 'portfolio_ledger';

    protected $fillable = [
        'user_id',
        'date',
        'month',
        'type',
        'amount',
        'label',
        'budget_item_id',
        'installment_id',
        'income_id',
        'subscription_id',
        'debt_id',
        'notes',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    const TYPE_INCOME  = 'income';
    const TYPE_EXPENSE = 'expense';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
