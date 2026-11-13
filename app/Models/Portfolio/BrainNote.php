<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrainNote extends Model
{
    protected $table = 'portfolio_brain_notes';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',          // 'inbox', 'project', 'area', 'resource', 'archive'
        'category',      // 'general', 'tech', 'finance', 'travel', 'health', 'work', 'idea'
        'tags',          // array of strings
        'color',         // 'slate', 'amber', 'emerald', 'indigo', 'rose', 'cyan', 'purple'
        'url',
        'is_pinned',
        'is_archived',
        'sort_order',
    ];

    protected $casts = [
        'tags'        => 'array',
        'is_pinned'   => 'boolean',
        'is_archived' => 'boolean',
        'sort_order'  => 'integer',
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
        if ($type && in_array($type, ['inbox', 'project', 'area', 'resource', 'archive'], true)) {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'inbox'    => '⚡ Inbox (ด่วน)',
            'project'  => '🎯 Projects (โปรเจกต์)',
            'area'     => '🌐 Areas (มิติชีวิต)',
            'resource' => '📚 Resources (คลังความรู้)',
            'archive'  => '🗄️ Archives (คลังจัดเก็บ)',
            default    => 'บันทึกทั่วไป',
        };
    }

    public function typeShortLabel(): string
    {
        return match ($this->type) {
            'inbox'    => 'Inbox',
            'project'  => 'Project',
            'area'     => 'Area',
            'resource' => 'Resource',
            'archive'  => 'Archive',
            default    => 'Note',
        };
    }

    public function typeBadgeColor(): string
    {
        return match ($this->type) {
            'inbox'    => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            'project'  => 'bg-rose-500/15 text-rose-400 border-rose-500/30',
            'area'     => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
            'resource' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            'archive'  => 'bg-slate-500/15 text-slate-400 border-slate-500/30',
            default    => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'tech'    => 'เทคโนโลยี & โค้ด',
            'finance' => 'การเงิน & การลงทุน',
            'travel'  => 'ท่องเที่ยว & มอเตอร์ไซค์',
            'health'  => 'สุขภาพ & ฟิตเนส',
            'work'    => 'งาน & ธุรกิจ',
            'idea'    => 'ไอเดียสร้างสรรค์',
            default   => 'ทั่วไป',
        };
    }
}
