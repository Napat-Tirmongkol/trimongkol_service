<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatement extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_bank_statements';

    protected $fillable = [
        'workspace_id', 'account_id', 'statement_date', 'description',
        'amount', 'reference', 'journal_line_id',
    ];

    protected $casts = [
        'statement_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }

    public function isMatched(): bool
    {
        return $this->journal_line_id !== null;
    }
}
