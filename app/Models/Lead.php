<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'company', 'message',
        'status', 'assigned_to', 'internal_notes',
        'ip', 'user_agent', 'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public const STATUSES = ['new', 'contacted', 'qualified', 'won', 'lost'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
