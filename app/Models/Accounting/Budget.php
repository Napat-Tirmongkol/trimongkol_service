<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_budgets';

    protected $fillable = [
        'workspace_id', 'account_id', 'department_id',
        'fiscal_year', 'fiscal_month', 'amount',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'fiscal_month' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
