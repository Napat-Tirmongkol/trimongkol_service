<?php

namespace App\Models\Accounting;

use App\Exceptions\Accounting\ImmutableLedgerException;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_journals';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'workspace_id', 'no', 'date', 'period_id', 'type', 'memo',
        'status', 'posted_at', 'posted_by', 'created_by',
        'source_type', 'source_id', 'reverses_journal_id',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    /**
     * A posted journal is immutable — history is corrected by posting a
     * reversing entry, never by editing or deleting it. Drafts stay editable.
     * This guard is defence-in-depth; LedgerPosting is the only writer.
     */
    protected static function booted(): void
    {
        static::updating(function (Journal $journal) {
            if ($journal->getOriginal('status') === self::STATUS_POSTED) {
                throw new ImmutableLedgerException(
                    "Journal {$journal->getOriginal('no')} is posted and cannot be modified — reverse it instead."
                );
            }
        });

        static::deleting(function (Journal $journal) {
            if ($journal->status === self::STATUS_POSTED) {
                throw new ImmutableLedgerException(
                    "Journal {$journal->no} is posted and cannot be deleted — reverse it instead."
                );
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('line_no');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_journal_id');
    }

    public function reversedBy(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_journal_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /** A posted journal counts as reversed once another journal points back. */
    public function isReversed(): bool
    {
        return $this->reversedBy()->exists();
    }
}
