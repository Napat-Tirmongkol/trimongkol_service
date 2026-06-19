<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    protected $table = 'portfolio_budget_items';

    protected $fillable = [
        'user_id',
        'month',
        'category',
        'label',
        'amount',
        'actual_amount',
        'is_checked',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'is_checked' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CATEGORY_FIXED = 'fixed_expense';
    const CATEGORY_VARIABLE = 'variable_expense';
    const CATEGORY_SAVING = 'saving';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}

