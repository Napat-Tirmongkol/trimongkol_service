<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'task_project_id', 'workspace_id', 'user_id', 'assignee_id',
        'title', 'description', 'status', 'priority', 'due_date',
        'sort_order', 'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /** Fixed Kanban columns, in board order. */
    public const STATUSES = ['todo', 'in_progress', 'done'];

    /** Priority levels, low → high. */
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'task_project_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaskItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_task');
    }

    /** Past its due date and not finished yet. */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->status !== 'done'
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    /** True when $user belongs to the workspace that owns this task. */
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
