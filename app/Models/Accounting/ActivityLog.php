<?php

namespace App\Models\Accounting;

use App\Exceptions\Accounting\ImmutableLedgerException;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_activity_log';

    /** Append-only — there is no updated_at and rows are never changed. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id', 'user_id', 'accounting_user_id', 'action',
        'subject_type', 'subject_id', 'subject_label', 'metadata', 'ip', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new ImmutableLedgerException('Accounting activity log is append-only.'));
        static::deleting(fn () => throw new ImmutableLedgerException('Accounting activity log is append-only.'));
    }

    public function accountingUser(): BelongsTo
    {
        return $this->belongsTo(AccountingUser::class, 'accounting_user_id');
    }
}
