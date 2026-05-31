<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuePayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'workspace_id', 'user_id', 'plan_key', 'amount', 'months',
        'status', 'slip_path', 'trans_ref', 'note', 'meta', 'verified_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'months' => 'integer',
        'meta' => 'array',
        'verified_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
