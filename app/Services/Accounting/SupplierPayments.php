<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\PaymentException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Payment;
use App\Models\Accounting\PaymentAllocation;
use App\Models\Accounting\Partner;
use App\Models\Accounting\TaxCode;
use App\Models\Accounting\WhtCertificate;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Supplier payments (AP settlement) — the "out" mirror of Receipts. Paying a
 * bill while withholding tax posts, atomically:
 *
 *   DR accounts payable        (the bill amount cleared)
 *   CR cash / bank             (cash actually paid)
 *   CR WHT payable             (tax withheld — owed to the Revenue Dept)
 *
 * and issues a withholding-tax certificate (50 ทวิ) for ภงด.3/53.
 */
class SupplierPayments
{
    /**
     * @param  array  $attributes  partner_id, account_id (cash/bank), payment_date?, method?,
     *                             wht_tax_code_id?, wht_base?, income_type?, note?
     * @param  array  $allocations  each: bill_id, amount (AP cleared on that bill)
     */
    public static function record(Workspace $workspace, array $attributes, array $allocations): Payment
    {
        if ($allocations === []) {
            throw new PaymentException('A payment needs at least one bill allocation.');
        }

        return DB::transaction(function () use ($workspace, $attributes, $allocations) {
            $partner = Partner::query()->forWorkspace($workspace)->find($attributes['partner_id'] ?? null);
            if (! $partner) {
                throw new PaymentException('Partner does not belong to this workspace.');
            }

            $bank = Account::query()->forWorkspace($workspace)->find($attributes['account_id'] ?? null);
            if (! $bank) {
                throw new PaymentException('Cash/bank account is not in this workspace.');
            }

            $allocated = [];
            $allocatedTotal = 0;
            foreach ($allocations as $row) {
                $bill = Bill::query()->forWorkspace($workspace)->find($row['bill_id'] ?? null);
                if (! $bill) {
                    throw new PaymentException("Bill {$row['bill_id']} is not in this workspace.");
                }
                if ($bill->partner_id !== $partner->id) {
                    throw new PaymentException("Bill {$bill->no} belongs to a different partner.");
                }
                if (in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_VOID], true)) {
                    throw new PaymentException("Bill {$bill->no} is not open for payment.");
                }

                $amount = Money::toMinor($row['amount'] ?? 0);
                if ($amount <= 0) {
                    throw new PaymentException('Allocation amount must be positive.');
                }
                if ($amount > self::outstandingMinor($bill)) {
                    throw new PaymentException("Allocation exceeds the outstanding balance of {$bill->no}.");
                }

                $allocatedTotal += $amount;
                $allocated[] = ['bill' => $bill, 'amount' => $amount];
            }

            // Withholding tax we deduct from the supplier (our liability to remit).
            $whtCode = null;
            $wht = 0;
            $whtBase = 0;
            if (! empty($attributes['wht_tax_code_id'])) {
                $whtCode = TaxCode::query()->forWorkspace($workspace)
                    ->where('kind', TaxCode::KIND_WHT)->find($attributes['wht_tax_code_id']);
                if (! $whtCode) {
                    throw new PaymentException('WHT tax code is not in this workspace.');
                }
                if (! isset($attributes['wht_base'])) {
                    throw new PaymentException('A WHT base amount is required when withholding tax.');
                }
                $whtBase = Money::toMinor($attributes['wht_base']);
                $wht = Money::percentage($whtBase, $whtCode->rate);
            }

            $cash = $allocatedTotal - $wht;
            if ($cash < 0) {
                throw new PaymentException('Withholding tax cannot exceed the amount paid.');
            }

            $apAccountId = $partner->ap_account_id
                ?? Account::query()->forWorkspace($workspace)->where('system_role', 'ap_control')->value('id');
            if (! $apAccountId) {
                throw new PaymentException('No accounts-payable control account is configured.');
            }

            $date = Carbon::parse($attributes['payment_date'] ?? now());

            $payment = new Payment([
                'workspace_id' => $workspace->id,
                'no' => $attributes['no'] ?? self::nextNumber($workspace, $date),
                'partner_id' => $partner->id,
                'direction' => Payment::DIRECTION_OUT,
                'payment_date' => $date->toDateString(),
                'method' => $attributes['method'] ?? 'transfer',
                'account_id' => $bank->id,
                'amount' => Money::fromMinor($cash),
                'wht_amount' => Money::fromMinor($wht),
                'wht_account_id' => $whtCode?->account_id,
                'status' => Payment::STATUS_DRAFT,
                'note' => $attributes['note'] ?? null,
                'created_by' => $attributes['created_by'] ?? auth()->id(),
            ]);
            $payment->save();

            foreach ($allocated as $entry) {
                $payment->allocations()->create([
                    'workspace_id' => $workspace->id,
                    'allocatable_type' => $entry['bill']->getMorphClass(),
                    'allocatable_id' => $entry['bill']->id,
                    'amount' => Money::fromMinor($entry['amount']),
                ]);
            }

            // DR payable / CR cash (+ CR WHT payable). Zero sides omitted.
            $lines = [['account_id' => $apAccountId, 'debit' => Money::fromMinor($allocatedTotal), 'credit' => 0, 'description' => "ตัดเจ้าหนี้ {$partner->name}"]];
            if ($cash > 0) {
                $lines[] = ['account_id' => $bank->id, 'debit' => 0, 'credit' => Money::fromMinor($cash), 'description' => "จ่าย {$partner->name}"];
            }
            if ($wht > 0) {
                $lines[] = ['account_id' => $whtCode->account_id, 'debit' => 0, 'credit' => Money::fromMinor($wht), 'description' => 'ภาษีหัก ณ ที่จ่าย'];
            }

            $journal = LedgerPosting::post($workspace, [
                'date' => $date,
                'type' => 'payment',
                'memo' => $attributes['memo'] ?? "จ่ายชำระ {$payment->no} — {$partner->name}",
                'created_by' => $attributes['created_by'] ?? auth()->id(),
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->id,
            ], $lines);

            $payment->forceFill([
                'status' => Payment::STATUS_POSTED,
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();

            if ($wht > 0) {
                self::issueCertificate($workspace, $payment, $partner, $whtCode, $date, $whtBase, $wht, $attributes['income_type'] ?? null);
            }

            foreach ($allocated as $entry) {
                self::refreshBillStatus($entry['bill']);
            }

            return $payment->refresh()->load('allocations', 'journal');
        });
    }

    private static function issueCertificate(
        Workspace $workspace, Payment $payment, Partner $partner, TaxCode $whtCode,
        CarbonInterface $date, int $base, int $wht, ?string $incomeType
    ): WhtCertificate {
        $role = Account::query()->forWorkspace($workspace)->whereKey($whtCode->account_id)->value('system_role');
        $pnd = $role === 'wht_payable_pnd3' ? WhtCertificate::PND3 : WhtCertificate::PND53;

        return WhtCertificate::create([
            'workspace_id' => $workspace->id,
            'no' => self::nextCertNumber($workspace, $date),
            'payment_id' => $payment->id,
            'partner_id' => $partner->id,
            'pnd_type' => $pnd,
            'tax_id' => $partner->tax_id,
            'income_type' => $incomeType,
            'paid_on' => $date->toDateString(),
            'base_amount' => Money::fromMinor($base),
            'rate' => $whtCode->rate,
            'wht_amount' => Money::fromMinor($wht),
        ]);
    }

    private static function outstandingMinor(Bill $bill): int
    {
        $allocated = PaymentAllocation::query()
            ->where('allocatable_type', $bill->getMorphClass())
            ->where('allocatable_id', $bill->id)
            ->get(['amount'])
            ->reduce(fn (int $carry, PaymentAllocation $a): int => $carry + Money::toMinor($a->amount), 0);

        return Money::toMinor($bill->total) - $allocated;
    }

    private static function refreshBillStatus(Bill $bill): void
    {
        $allocated = Money::toMinor($bill->total) - self::outstandingMinor($bill);
        $total = Money::toMinor($bill->total);

        $bill->forceFill(['status' => match (true) {
            $allocated >= $total => Bill::STATUS_PAID,
            $allocated > 0 => Bill::STATUS_PARTIAL,
            default => Bill::STATUS_ISSUED,
        }])->save();
    }

    private static function nextNumber(Workspace $workspace, CarbonInterface $date): string
    {
        return self::sequence($workspace, 'PV'.($date->year + 543).'-', Payment::query()->forWorkspace($workspace));
    }

    private static function nextCertNumber(Workspace $workspace, CarbonInterface $date): string
    {
        return self::sequence($workspace, 'WHT'.($date->year + 543).'-', WhtCertificate::query()->forWorkspace($workspace));
    }

    private static function sequence(Workspace $workspace, string $prefix, $query): string
    {
        $last = $query->where('no', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('no')->value('no');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
