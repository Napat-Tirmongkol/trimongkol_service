<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\PaymentException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Payment;
use App\Models\Accounting\PaymentAllocation;
use App\Models\Accounting\Partner;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Customer receipts (AR settlement). Recording one settles the allocated
 * invoices and posts, in one atomic transaction:
 *
 *   DR  cash / bank            (cash actually received)
 *   DR  prepaid income tax     (WHT the customer withheld — our tax credit)
 *   CR  accounts receivable    (the invoice amount cleared)
 *
 * cash + WHT must equal the total allocated. WHT lands in the account tagged
 * `wht_receivable` (ภาษีนิติบุคคลจ่ายล่วงหน้า).
 */
class Receipts
{
    /**
     * @param  array  $attributes  partner_id, account_id (cash/bank), amount, wht_amount?,
     *                             wht_account_id?, payment_date?, method?, slip_path?, slip_ref?, memo?
     * @param  array  $allocations  each: invoice_id, amount (AR amount cleared on that invoice)
     */
    public static function record(Workspace $workspace, array $attributes, array $allocations): Payment
    {
        if ($allocations === []) {
            throw new PaymentException('A receipt needs at least one invoice allocation.');
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

            $cash = Money::toMinor($attributes['amount'] ?? 0);
            $wht = Money::toMinor($attributes['wht_amount'] ?? 0);
            if ($cash < 0 || $wht < 0) {
                throw new PaymentException('Amounts cannot be negative.');
            }

            $slipRef = $attributes['slip_ref'] ?? null;
            if ($slipRef && Payment::query()->forWorkspace($workspace)->where('slip_ref', $slipRef)->exists()) {
                throw new PaymentException("Slip reference {$slipRef} has already been recorded.");
            }

            // Validate allocations against each invoice's outstanding balance.
            $allocated = [];
            $allocatedTotal = 0;
            foreach ($allocations as $row) {
                $invoice = Invoice::query()->forWorkspace($workspace)->find($row['invoice_id'] ?? null);
                if (! $invoice) {
                    throw new PaymentException("Invoice {$row['invoice_id']} is not in this workspace.");
                }
                if ($invoice->partner_id !== $partner->id) {
                    throw new PaymentException("Invoice {$invoice->no} belongs to a different partner.");
                }
                if (in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID], true)) {
                    throw new PaymentException("Invoice {$invoice->no} is not open for payment.");
                }

                $amount = Money::toMinor($row['amount'] ?? 0);
                if ($amount <= 0) {
                    throw new PaymentException('Allocation amount must be positive.');
                }
                if ($amount > self::outstandingMinor($invoice)) {
                    throw new PaymentException("Allocation exceeds the outstanding balance of {$invoice->no}.");
                }

                $allocatedTotal += $amount;
                $allocated[] = ['invoice' => $invoice, 'amount' => $amount];
            }

            if ($allocatedTotal !== $cash + $wht) {
                throw new PaymentException('Cash received plus WHT must equal the total allocated.');
            }

            $arAccountId = $partner->ar_account_id
                ?? Account::query()->forWorkspace($workspace)->where('system_role', 'ar_control')->value('id');
            if (! $arAccountId) {
                throw new PaymentException('No accounts-receivable control account is configured.');
            }

            $whtAccountId = null;
            if ($wht > 0) {
                $whtAccountId = $attributes['wht_account_id']
                    ?? Account::query()->forWorkspace($workspace)->where('system_role', 'wht_receivable')->value('id');
                if (! $whtAccountId) {
                    throw new PaymentException('No WHT-receivable (prepaid income tax) account is configured.');
                }
            }

            $date = Carbon::parse($attributes['payment_date'] ?? now());

            $payment = new Payment([
                'workspace_id' => $workspace->id,
                'no' => $attributes['no'] ?? self::nextNumber($workspace, $date),
                'partner_id' => $partner->id,
                'direction' => Payment::DIRECTION_IN,
                'payment_date' => $date->toDateString(),
                'method' => $attributes['method'] ?? 'transfer',
                'account_id' => $bank->id,
                'amount' => Money::fromMinor($cash),
                'wht_amount' => Money::fromMinor($wht),
                'wht_account_id' => $whtAccountId,
                'status' => Payment::STATUS_DRAFT,
                'slip_path' => $attributes['slip_path'] ?? null,
                'slip_ref' => $slipRef,
                'note' => $attributes['note'] ?? null,
                'created_by' => $attributes['created_by'] ?? auth()->id(),
            ]);
            $payment->save();

            foreach ($allocated as $entry) {
                $payment->allocations()->create([
                    'workspace_id' => $workspace->id,
                    'allocatable_type' => $entry['invoice']->getMorphClass(),
                    'allocatable_id' => $entry['invoice']->id,
                    'amount' => Money::fromMinor($entry['amount']),
                ]);
            }

            // DR cash/bank (+ DR prepaid WHT) / CR receivable. Zero-value sides
            // are omitted so every line stays one-sided for LedgerPosting.
            $lines = [];
            if ($cash > 0) {
                $lines[] = ['account_id' => $bank->id, 'debit' => Money::fromMinor($cash), 'credit' => 0, 'description' => "รับเงิน {$partner->name}"];
            }
            if ($wht > 0) {
                $lines[] = ['account_id' => $whtAccountId, 'debit' => Money::fromMinor($wht), 'credit' => 0, 'description' => 'ภาษีถูกหัก ณ ที่จ่าย'];
            }
            $lines[] = ['account_id' => $arAccountId, 'debit' => 0, 'credit' => Money::fromMinor($allocatedTotal), 'description' => "ตัดลูกหนี้ {$partner->name}"];

            $journal = LedgerPosting::post($workspace, [
                'date' => $date,
                'type' => 'receipt',
                'memo' => $attributes['memo'] ?? "รับชำระ {$payment->no} — {$partner->name}",
                'created_by' => $attributes['created_by'] ?? auth()->id(),
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->id,
            ], $lines);

            $payment->forceFill([
                'status' => Payment::STATUS_POSTED,
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();

            foreach ($allocated as $entry) {
                self::refreshInvoiceStatus($entry['invoice']);
            }

            return $payment->refresh()->load('allocations', 'journal');
        });
    }

    /** Remaining balance on an invoice as a decimal string. */
    public static function outstanding(Invoice $invoice): string
    {
        return Money::fromMinor(self::outstandingMinor($invoice));
    }

    /** Invoice total minus everything already allocated to it, in minor units. */
    private static function outstandingMinor(Invoice $invoice): int
    {
        return Money::toMinor($invoice->total) - self::allocatedMinor($invoice);
    }

    private static function allocatedMinor(Invoice $invoice): int
    {
        return PaymentAllocation::query()
            ->where('allocatable_type', $invoice->getMorphClass())
            ->where('allocatable_id', $invoice->id)
            ->get(['amount'])
            ->reduce(fn (int $carry, PaymentAllocation $a): int => $carry + Money::toMinor($a->amount), 0);
    }

    private static function refreshInvoiceStatus(Invoice $invoice): void
    {
        $allocated = self::allocatedMinor($invoice);
        $total = Money::toMinor($invoice->total);

        $status = match (true) {
            $allocated >= $total => Invoice::STATUS_PAID,
            $allocated > 0 => Invoice::STATUS_PARTIAL,
            default => Invoice::STATUS_ISSUED,
        };

        $invoice->forceFill(['status' => $status])->save();
    }

    private static function nextNumber(Workspace $workspace, CarbonInterface $date): string
    {
        $prefix = 'RV'.($date->year + 543).'-';

        $last = Payment::query()->forWorkspace($workspace)
            ->where('no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('no')
            ->value('no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
