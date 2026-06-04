<?php

namespace App\Models\Accounting;

use App\Models\Workspace;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AccountingUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'accounting_users';

    protected $fillable = [
        'workspace_id', 'name', 'email', 'password', 'role', 'is_active', 'is_demo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean', 'is_demo' => 'boolean'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    public function canPost(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
