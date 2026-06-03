<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_approvals';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'workspace_id', 'subject_type', 'subject_id', 'subject_label',
        'requested_by_id', 'reviewed_by_id', 'status', 'note',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(AccountingUser::class, 'requested_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(AccountingUser::class, 'reviewed_by_id');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING);
    }
}
