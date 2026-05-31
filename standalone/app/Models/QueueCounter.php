<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueCounter extends Model
{
    protected $fillable = ['queue_id', 'label', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'counter_id');
    }
}
