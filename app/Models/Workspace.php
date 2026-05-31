<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    protected $fillable = ['name', 'slug', 'queue_plan'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Convenience accessor — always returns a Plan, falling back to
     * Free if no subscription row exists.
     */
    public function currentPlan(): Plan
    {
        return $this->subscription?->effectivePlan() ?? Plan::find(Plan::FREE);
    }

    /**
     * The role $user has in this workspace, or null if not a member.
     */
    public function roleFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }
        return $this->memberships()->where('user_id', $user->id)->value('role');
    }

    public function isOwnedBy(?User $user): bool
    {
        return $this->roleFor($user) === 'owner';
    }

    public function hasMember(?User $user): bool
    {
        return $this->roleFor($user) !== null;
    }
}
