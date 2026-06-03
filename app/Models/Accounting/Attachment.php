<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_attachments';

    /** Immutable after upload — no updated_at column. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id', 'attachable_type', 'attachable_id',
        'disk', 'path', 'original_name', 'mime_type', 'size',
        'accounting_user_id',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function accountingUser(): BelongsTo
    {
        return $this->belongsTo(AccountingUser::class);
    }

    public function url(): string
    {
        return route('accounting.attachments.download', $this);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1).' MB';
        }

        return number_format($bytes / 1_024, 1).' KB';
    }
}
