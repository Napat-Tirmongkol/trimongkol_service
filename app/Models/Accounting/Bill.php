<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_bills';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'workspace_id', 'no', 'partner_id', 'bill_ref', 'issue_date', 'due_date',
        'status', 'currency', 'subtotal', 'vat_amount', 'total', 'memo',
        'journal_id', 'posted_at', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'posted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class)->orderBy('line_no');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
