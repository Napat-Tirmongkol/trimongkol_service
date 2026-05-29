<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function ownedWorkspaces(): BelongsToMany
    {
        return $this->workspaces()->wherePivot('role', 'owner');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role) {
            return $this->role->grants($permission);
        }

        // Backward-compat: a bare is_admin flag with no role (legacy rows or
        // test fixtures) keeps the old "full admin" meaning. Production never
        // reaches this — every path that sets is_admin also sets a role.
        return (bool) $this->is_admin;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->role?->isSuper();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at) && ! is_null($this->two_factor_secret);
    }
}
