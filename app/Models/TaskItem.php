<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskItem extends Model
{
    protected $fillable = [
        'task_id', 'title', 'is_done', 'sort_order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
