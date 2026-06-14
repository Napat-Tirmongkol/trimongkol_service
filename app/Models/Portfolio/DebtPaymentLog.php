<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPaymentLog extends Model
{
    protected $table = 'portfolio_debt_payment_logs';

    protected $fillable = ['debt_payment_id', 'user_id', 'paid_on', 'amount', 'reference', 'notes'];

    protected $casts = [
        'paid_on' => 'date',
        'amount'  => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(DebtPayment::class, 'debt_payment_id');
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
