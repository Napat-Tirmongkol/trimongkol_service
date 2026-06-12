<?php

namespace App\Models\Portfolio;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'portfolio_transactions';

    protected $fillable = [
        'holding_id',
        'type',
        'amount',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class, 'holding_id');
    }
}
