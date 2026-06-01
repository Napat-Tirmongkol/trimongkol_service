<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_payroll_items';

    protected $fillable = [
        'workspace_id', 'payroll_run_id', 'employee_id',
        'gross', 'ss_employee', 'ss_employer', 'wht', 'other_deductions', 'net',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
