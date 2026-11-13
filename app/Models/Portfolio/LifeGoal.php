<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeGoal extends Model
{
    protected $table = 'portfolio_life_goals';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'horizon',        // 'short' (1-3 เดือน), 'medium' (6 เดือน - 1 ปี), 'long' (1-3 ปีขึ้นไป)
        'category',       // 'finance', 'health', 'career', 'travel', 'learning', 'personal'
        'target_year',    // e.g. 2027 (2570)
        'target_date',
        'progress_percent',
        'is_completed',
        'completed_at',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'target_year'      => 'integer',
        'target_date'      => 'date',
        'progress_percent' => 'integer',
        'is_completed'     => 'boolean',
        'completed_at'     => 'datetime',
        'sort_order'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeByHorizon(Builder $query, ?string $horizon): Builder
    {
        if ($horizon && in_array($horizon, ['short', 'medium', 'long'], true)) {
            return $query->where('horizon', $horizon);
        }
        return $query;
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        if ($status === 'completed') {
            return $query->where('is_completed', true);
        }
        if ($status === 'pending') {
            return $query->where('is_completed', false);
        }
        return $query;
    }

    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function horizonLabel(): string
    {
        return match ($this->horizon) {
            'short'  => 'ระยะสั้น (1–3 เดือน)',
            'medium' => 'ระยะกลาง (6 เดือน – 1 ปี)',
            'long'   => 'ระยะไกล (1–3 ปีขึ้นไป)',
            default  => 'ทั่วไป',
        };
    }

    public function horizonShortLabel(): string
    {
        return match ($this->horizon) {
            'short'  => 'ระยะสั้น',
            'medium' => 'ระยะกลาง',
            'long'   => 'ระยะไกล',
            default  => 'ทั่วไป',
        };
    }

    public function horizonBadgeColor(): array
    {
        return match ($this->horizon) {
            'short'  => [
                'bg'     => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
                'dot'    => 'bg-emerald-400',
                'accent' => 'emerald',
            ],
            'medium' => [
                'bg'     => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
                'dot'    => 'bg-amber-400',
                'accent' => 'amber',
            ],
            'long'   => [
                'bg'     => 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
                'dot'    => 'bg-indigo-400',
                'accent' => 'indigo',
            ],
            default  => [
                'bg'     => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
                'dot'    => 'bg-slate-400',
                'accent' => 'slate',
            ],
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'finance'  => 'การเงิน & การลงทุน',
            'health'   => 'สุขภาพ & ฟิตเนส',
            'career'   => 'การงาน & ธุรกิจ',
            'travel'   => 'ท่องเที่ยว & กิจกรรม',
            'learning' => 'การเรียนรู้ & ทักษะใหม่',
            'personal' => 'พัฒนาตัวเอง & นิสัย',
            default    => 'เป้าหมายทั่วไป',
        };
    }

    public function categoryIcon(): string
    {
        return match ($this->category) {
            'finance'  => 'wallet',
            'health'   => 'heart',
            'career'   => 'briefcase',
            'travel'   => 'map',
            'learning' => 'book',
            'personal' => 'sparkles',
            default    => 'flag',
        };
    }
}
