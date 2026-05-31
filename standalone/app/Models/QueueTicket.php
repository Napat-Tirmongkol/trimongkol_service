<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTicket extends Model
{
    public const ACTIVE_STATUSES = ['called', 'serving'];

    protected $fillable = ['queue_id', 'counter_id', 'number', 'status', 'called_at', 'served_at'];

    protected $casts = [
        'number' => 'integer',
        'called_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(QueueCounter::class, 'counter_id');
    }

    /** Zero-padded number without the prefix, e.g. "001". */
    public function getPaddedNumberAttribute(): string
    {
        return str_pad((string) $this->number, 3, '0', STR_PAD_LEFT);
    }
}
