<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhtCertificate extends Model
{
    use BelongsToWorkspace;

    protected $table = 'accounting_wht_certificates';

    public const PND3 = 'PND3';   // ภ.ง.ด.3 — withholding from individuals
    public const PND53 = 'PND53'; // ภ.ง.ด.53 — withholding from companies

    protected $fillable = [
        'workspace_id', 'no', 'payment_id', 'partner_id', 'pnd_type', 'tax_id',
        'income_type', 'paid_on', 'base_amount', 'rate', 'wht_amount',
    ];

    protected $casts = [
        'paid_on' => 'date',
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:3',
        'wht_amount' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
