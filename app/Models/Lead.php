<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    public const SOURCE_CONTACT = 'contact';
    public const SOURCE_FEEDBACK = 'feedback';

    public const SOURCES = [self::SOURCE_CONTACT, self::SOURCE_FEEDBACK];

    protected $fillable = [
        'name', 'email', 'phone', 'company', 'message', 'source', 'context',
        'status', 'assigned_to', 'internal_notes',
        'ip', 'user_agent', 'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'context' => 'array',
    ];

    public const STATUSES = ['new', 'contacted', 'qualified', 'won', 'lost'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
