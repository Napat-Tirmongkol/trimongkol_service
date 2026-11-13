<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthMetric extends Model
{
    protected $table = 'portfolio_health_metrics';

    protected $fillable = [
        'user_id',
        'date',
        'steps',
        'distance_meters',
        'active_calories',
        'total_calories',
        'heart_rate_avg',
        'heart_rate_min',
        'heart_rate_max',
        'resting_heart_rate',
        'sleep_minutes',
        'deep_sleep_minutes',
        'rem_sleep_minutes',
        'light_sleep_minutes',
        'spo2_avg',
        'source',
        'raw_payload',
    ];

    protected $casts = [
        'date'               => 'date',
        'steps'              => 'integer',
        'distance_meters'    => 'float',
        'active_calories'    => 'float',
        'total_calories'     => 'float',
        'heart_rate_avg'     => 'integer',
        'heart_rate_min'     => 'integer',
        'heart_rate_max'     => 'integer',
        'resting_heart_rate' => 'integer',
        'sleep_minutes'      => 'integer',
        'deep_sleep_minutes' => 'integer',
        'rem_sleep_minutes'  => 'integer',
        'light_sleep_minutes'=> 'integer',
        'spo2_avg'           => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
