<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalLine extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_recurring_journal_lines';

    protected $fillable = [
        'workspace_id', 'recurring_journal_id', 'line_no',
        'account_id', 'debit', 'credit', 'description', 'department_id',
    ];

    public function recurringJournal(): BelongsTo
    {
        return $this->belongsTo(RecurringJournal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
