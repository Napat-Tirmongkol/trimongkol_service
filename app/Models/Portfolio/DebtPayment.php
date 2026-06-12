<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $table = 'portfolio_debt_payments';

    protected $fillable = ['debt_id', 'user_id', 'month', 'amount', 'is_paid', 'notes'];

    protected $casts = [
        'amount'  => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
