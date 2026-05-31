<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_accounts';

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    /** Account types whose normal balance sits on the debit side. */
    public const DEBIT_TYPES = [self::TYPE_ASSET, self::TYPE_EXPENSE];

    protected $fillable = [
        'workspace_id', 'code', 'name', 'name_en', 'type', 'normal_balance',
        'parent_id', 'system_role', 'is_active', 'is_tax_deductible', 'currency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_tax_deductible' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** Normal balance implied by the account type — debit for assets/expenses. */
    public static function normalBalanceFor(string $type): string
    {
        return in_array($type, self::DEBIT_TYPES, true) ? 'debit' : 'credit';
    }
}
