<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringJournal extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_recurring_journals';

    public const FREQ_MONTHLY = 'monthly';
    public const FREQ_QUARTERLY = 'quarterly';
    public const FREQ_WEEKLY = 'weekly';

    protected $fillable = [
        'workspace_id', 'name', 'frequency', 'next_run_date',
        'end_date', 'last_run_at', 'is_active', 'created_by',
    ];

    protected $casts = [
        'next_run_date' => 'date',
        'end_date' => 'date',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(RecurringJournalLine::class)->orderBy('line_no');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDue(): bool
    {
        return $this->is_active && $this->next_run_date->lte(now());
    }
}
