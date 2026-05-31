<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_departments';

    protected $fillable = ['workspace_id', 'code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
