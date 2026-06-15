<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskLabel extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'color',
    ];

    /** Selectable label colours — kept as full class strings in the view. */
    public const COLORS = ['slate', 'rose', 'amber', 'emerald', 'sky', 'violet', 'brand'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label_task');
    }
}
