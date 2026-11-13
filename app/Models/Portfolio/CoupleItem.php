<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoupleItem extends Model
{
    protected $table = 'portfolio_couple_items';

    protected $fillable = [
        'user_id',
        'type',             // 'food', 'trip', 'wishlist', 'date_idea', 'movie', 'savings'
        'title',
        'description',
        'location',
        'target_date',
        'target_amount',
        'saved_amount',
        'is_done',
        'done_at',
        'rating',
        'partner_name',
        'notes',
    ];

    protected $casts = [
        'target_date'   => 'date',
        'target_amount' => 'decimal:2',
        'saved_amount'  => 'decimal:2',
        'is_done'       => 'boolean',
        'done_at'       => 'datetime',
        'rating'        => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        if ($type && in_array($type, ['food', 'trip', 'wishlist', 'date_idea', 'movie', 'savings'], true)) {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'food'      => '🍽️ ร้านอาหาร & คาเฟ่',
            'trip'      => '🏍️ ทริปเที่ยวคู่',
            'wishlist'  => '🎁 ของที่อยากได้ร่วมกัน',
            'date_idea' => '✨ ไอเดียเดต & กิจกรรม',
            'movie'     => '🎬 หนัง & ซีรีส์',
            'savings'   => '💰 กระปุกเงินเที่ยวคู่',
            default     => '📌 บันทึกคู่รัก',
        };
    }

    public function typeBadgeColor(): string
    {
        return match ($this->type) {
            'food'      => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            'trip'      => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            'wishlist'  => 'bg-rose-500/15 text-rose-400 border-rose-500/30',
            'date_idea' => 'bg-pink-500/15 text-pink-400 border-pink-500/30',
            'movie'     => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
            'savings'   => 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
            default     => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        };
    }

    public function savingsPercent(): int
    {
        $target = (float) $this->target_amount;
        if ($target <= 0) return 0;
        $saved = (float) $this->saved_amount;
        return (int) min(100, round(($saved / $target) * 100));
    }
}
