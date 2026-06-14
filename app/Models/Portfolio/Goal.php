<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    protected $table = 'portfolio_goals';

    protected $fillable = [
        'user_id',
        'title',
        'target_amount',
        'target_date',
        'notes',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function progressPercent(float $netWorth): float
    {
        $target = (float) $this->target_amount;
        if ($target <= 0) {
            return 0.0;
        }
        return min(100.0, round(($netWorth / $target) * 100, 2));
    }
}
