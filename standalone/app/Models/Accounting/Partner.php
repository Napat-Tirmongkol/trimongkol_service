<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_partners';

    protected $fillable = [
        'workspace_id', 'code', 'name', 'tax_id', 'branch_code',
        'is_customer', 'is_vendor', 'address', 'phone', 'email',
        'credit_days', 'ar_account_id', 'ap_account_id', 'is_active',
    ];

    protected $casts = [
        'is_customer' => 'boolean',
        'is_vendor' => 'boolean',
        'is_active' => 'boolean',
        'credit_days' => 'integer',
    ];

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ar_account_id');
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ap_account_id');
    }
}
