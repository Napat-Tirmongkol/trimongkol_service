<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_employees';

    protected $fillable = [
        'workspace_id', 'code', 'name', 'email', 'tax_id', 'social_security_id',
        'base_salary', 'bank_account', 'position', 'hired_on', 'is_active',
    ];

    protected $casts = [
        'hired_on' => 'date',
        'is_active' => 'boolean',
    ];
}
