<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_periods';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = ['workspace_id', 'name', 'starts_on', 'ends_on', 'status'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
