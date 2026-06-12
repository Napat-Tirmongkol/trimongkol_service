<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $table = 'portfolio_subscriptions';

    protected $fillable = [
        'user_id',
        'label',
        'monthly_payment',
        'billing_day',
        'is_checked',
        'notes',
    ];

    protected $casts = [
        'monthly_payment' => 'decimal:2',
        'billing_day' => 'integer',
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
}
