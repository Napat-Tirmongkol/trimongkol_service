<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskProject extends Model
{
    protected $fillable = [
        'workspace_id', 'user_id', 'name', 'description', 'color', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /** Accent colours a board can use — kept as full class strings in the view. */
    public const COLORS = ['brand', 'emerald', 'amber', 'rose', 'violet', 'sky', 'slate'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order')->orderBy('id');
    }

    /** True when $user belongs to the workspace that owns this board. */
    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return WorkspaceMember::query()
            ->where('workspace_id', $this->workspace_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
