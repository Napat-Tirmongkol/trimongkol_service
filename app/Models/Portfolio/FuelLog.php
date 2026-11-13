<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    protected $table = 'portfolio_fuel_logs';

    protected $fillable = [
        'user_id',
        'refill_date',
        'vehicle_name',
        'odometer',
        'distance',
        'liters',
        'price_per_liter',
        'total_price',
        'fuel_type',
        'gas_station',
        'is_full_tank',
        'efficiency_kml',
        'cost_per_km',
        'ledger_entry_id',
        'notes',
    ];

    protected $casts = [
        'refill_date'     => 'date',
        'odometer'        => 'decimal:2',
        'distance'        => 'decimal:2',
        'liters'          => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_price'     => 'decimal:2',
        'efficiency_kml'  => 'decimal:2',
        'cost_per_km'     => 'decimal:2',
        'is_full_tank'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
