<?php

namespace App\Models\Accounting;

use App\Exceptions\Accounting\ImmutableLedgerException;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_journal_lines';

    protected $fillable = [
        'journal_id', 'workspace_id', 'line_no', 'account_id',
        'debit', 'credit', 'description', 'department_id', 'partner_id', 'tax_code_id',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /** Lines are frozen once their journal is posted (reverse to correct). */
    protected static function booted(): void
    {
        $guard = function (JournalLine $line) {
            if ($line->journal && $line->journal->isPosted()) {
                throw new ImmutableLedgerException(
                    'Cannot modify lines of a posted journal — reverse the journal instead.'
                );
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
