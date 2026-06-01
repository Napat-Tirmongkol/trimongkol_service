<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_payroll_runs';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'workspace_id', 'no', 'period', 'payment_date', 'status',
        'total_gross', 'total_ss_employee', 'total_ss_employer',
        'total_wht', 'total_net', 'journal_id',
        'salary_expense_account_id', 'ss_expense_account_id',
        'ss_payable_account_id', 'wht_payable_account_id', 'cash_account_id',
        'posted_at', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->orderBy('id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }
}
