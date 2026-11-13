<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaperAccount extends Model
{
    protected $table = 'portfolio_paper_accounts';

    protected $fillable = [
        'user_id',
        'initial_balance',
        'cash_balance',
        'currency',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'cash_balance'    => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(PaperTrade::class, 'paper_account_id');
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public static function getOrCreateForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'initial_balance' => 100000.00,
                'cash_balance'    => 100000.00,
                'currency'        => 'THB',
                'notes'           => 'พอร์ตจำลองเริ่มต้น ฿100,000.00',
            ]
        );
    }
}
