<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A back-office role: a named bundle of permission keys (see
 * config/permissions.php). Super Admin holds the '*' wildcard, which
 * grants every current and future permission and can't be edited away.
 */
class Role extends Model
{
    public const SUPER = 'super-admin';

    protected $fillable = ['key', 'name', 'description', 'permissions', 'is_system'];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuper(): bool
    {
        return in_array('*', $this->permissions ?? [], true);
    }

    public function grants(string $permission): bool
    {
        $perms = $this->permissions ?? [];

        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }
}
