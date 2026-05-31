<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxCode extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_tax_codes';

    public const KIND_VAT_OUTPUT = 'vat_output';
    public const KIND_VAT_INPUT = 'vat_input';
    public const KIND_WHT = 'wht';

    protected $fillable = [
        'workspace_id', 'code', 'name', 'kind', 'rate', 'account_id', 'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isVat(): bool
    {
        return in_array($this->kind, [self::KIND_VAT_OUTPUT, self::KIND_VAT_INPUT], true);
    }
}
