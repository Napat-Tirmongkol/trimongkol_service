<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = ['user_id', 'workspace_id', 'name', 'grade_level', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class)->orderByRaw('LENGTH(number), number')->orderBy('name');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->latest();
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class)->latest('date');
    }

    /**
     * True when $user belongs to the workspace that owns this classroom.
     * Authorization gate that should be used instead of comparing user_id —
     * user_id is now just the original creator, anyone in the workspace
     * can manage the classroom.
     */
    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if (! $this->workspace_id) {
            // Legacy row pre-Workspace migration. Fall back to creator check.
            return $this->user_id === $user->id;
        }
        return WorkspaceMember::query()
            ->where('workspace_id', $this->workspace_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
