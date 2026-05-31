<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Queue extends Model
{
    protected $fillable = [
        'user_id', 'workspace_id', 'name', 'prefix',
        'public_token', 'last_number', 'voice_enabled', 'is_active',
    ];

    protected $casts = [
        'last_number' => 'integer',
        'voice_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Queue $queue) {
            if (empty($queue->public_token)) {
                $queue->public_token = Str::lower(Str::random(10));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(QueueCounter::class)->orderBy('id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /** Display code for a ticket number, e.g. "A001". */
    public function code(int $number): string
    {
        return $this->prefix . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * True when $user belongs to the workspace that owns this queue.
     * Mirrors Classroom::canBeAccessedBy — anyone in the workspace can
     * operate the queue, not just the original creator.
     */
    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if (! $this->workspace_id) {
            return $this->user_id === $user->id;
        }
        return WorkspaceMember::query()
            ->where('workspace_id', $this->workspace_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
