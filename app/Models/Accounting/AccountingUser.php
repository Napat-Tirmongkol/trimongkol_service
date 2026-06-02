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
        'workspace_id', 'name', 'email', 'password', 'role', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function canPost(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
