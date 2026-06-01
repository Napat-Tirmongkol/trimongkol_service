<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_stock_movements';

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUST = 'adjust';

    protected $fillable = [
        'workspace_id', 'product_id', 'movement_date', 'type',
        'quantity', 'unit_cost', 'total_value', 'reference', 'note',
        'journal_id', 'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
