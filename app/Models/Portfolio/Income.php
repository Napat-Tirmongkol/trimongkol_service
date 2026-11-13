<?php

namespace App\Models\Portfolio;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    protected $table = 'portfolio_income';

    protected $fillable = [
        'user_id',
        'month',
        'label',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $q, User|int $user): Builder
    {
        return $q->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * Parse structured salary payslip breakdown if present in notes or regex.
     */
    public function getPayslipDataAttribute(): ?array
    {
        if ($this->notes) {
            $decoded = json_decode($this->notes, true);
            if (is_array($decoded) && (!empty($decoded['is_payslip']) || isset($decoded['base_salary']))) {
                return $decoded;
            }
        }

        // Fallback for income containing 'เงินเดือน'
        if (str_contains($this->label, 'เงินเดือน')) {
            $base = 30000.0;
            $sso = 875.0;
            $tax = 314.0;
            if ($this->notes && preg_match('/ฐานเงินเดือน\s*([\d,.]+)/u', $this->notes, $mBase)) {
                $base = (float) str_replace(',', '', $mBase[1]);
            }
            if ($this->notes && preg_match('/ประกันสังคม\s*([\d,.]+)/u', $this->notes, $mSso)) {
                $sso = (float) str_replace(',', '', $mSso[1]);
            }
            if ($this->notes && preg_match('/ภาษี\s*([\d,.]+)/u', $this->notes, $mTax)) {
                $tax = (float) str_replace(',', '', $mTax[1]);
            }
            $net = (float) $this->amount;
            $ot = max(0.0, round(($net + $sso + $tax) - $base, 2));
            $gross = $base + $ot;
            return [
                'is_payslip'       => true,
                'company'          => str_contains($this->label, '(') ? trim(explode(')', explode('(', $this->label)[1])[0]) : 'บจก. ดูดีพ',
                'base_salary'      => $base,
                'ot'               => $ot,
                'bonus'            => 0.0,
                'gross_income'     => $gross,
                'social_security'  => $sso,
                'tax'              => $tax,
                'other_deduct'     => 0.0,
                'total_deductions' => $sso + $tax,
                'net_pay'          => $net,
                'custom_notes'     => $this->notes,
            ];
        }

        return null;
    }
}
