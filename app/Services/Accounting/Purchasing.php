<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\InvoiceException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Partner;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Supplier bills (AP) — the purchase mirror of SalesInvoicing. `create()`
 * drafts and computes the money; `post()` records the liability:
 *
 *   DR expense (per account)  +  DR input VAT  /  CR accounts payable
 */
class Purchasing
{
    public static function create(Workspace $workspace, array $attributes, array $lines): Bill
    {
        if ($lines === []) {
            throw new InvoiceException('A bill needs at least one line.');
        }

        return DB::transaction(function () use ($workspace, $attributes, $lines) {
            $partner = Partner::query()->forWorkspace($workspace)->find($attributes['partner_id'] ?? null);
            if (! $partner) {
                throw new InvoiceException('Vendor does not belong to this workspace.');
            }

            $issueDate = Carbon::parse($attributes['issue_date'] ?? now());
            $dueDate = isset($attributes['due_date'])
                ? Carbon::parse($attributes['due_date'])
                : ($partner->credit_days > 0 ? $issueDate->copy()->addDays($partner->credit_days) : null);

            $accounts = Account::query()->forWorkspace($workspace)
                ->whereIn('id', collect($lines)->pluck('account_id')->filter()->unique())
                ->pluck('id')->flip();
            $taxCodes = TaxCode::query()->forWorkspace($workspace)
                ->whereIn('id', collect($lines)->pluck('tax_code_id')->filter()->unique())
                ->get()->keyBy('id');

            $subtotal = 0;
            $vat = 0;
            $rows = [];

            foreach ($lines as $i => $line) {
                $accountId = $line['account_id'] ?? null;
                if (! $accountId || ! $accounts->has($accountId)) {
                    throw new InvoiceException("Expense account {$accountId} is not in this workspace.");
                }

                $amount = isset($line['amount'])
                    ? Money::toMinor($line['amount'])
                    : Money::lineAmount($line['quantity'] ?? 1, $line['unit_price'] ?? 0);
                if ($amount < 0) {
                    throw new InvoiceException('Line amount cannot be negative.');
                }
                $subtotal += $amount;

                $taxCodeId = $line['tax_code_id'] ?? null;
                if ($taxCodeId) {
                    $code = $taxCodes->get($taxCodeId);
                    if (! $code) {
                        throw new InvoiceException("Tax code {$taxCodeId} is not in this workspace.");
                    }
                    if ($code->kind === TaxCode::KIND_VAT_INPUT) {
                        $vat += Money::percentage($amount, $code->rate);
                    }
                }

                $rows[] = [
                    'workspace_id' => $workspace->id,
                    'line_no' => $i + 1,
                    'account_id' => $accountId,
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'] ?? 1,
                    'unit_price' => $line['unit_price'] ?? Money::fromMinor($amount),
                    'amount' => Money::fromMinor($amount),
                    'tax_code_id' => $taxCodeId,
                    'department_id' => $line['department_id'] ?? null,
                ];
            }

            $bill = new Bill([
                'workspace_id' => $workspace->id,
                'no' => $attributes['no'] ?? self::nextNumber($workspace, $issueDate),
                'partner_id' => $partner->id,
                'bill_ref' => $attributes['bill_ref'] ?? null,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'status' => Bill::STATUS_DRAFT,
                'subtotal' => Money::fromMinor($subtotal),
                'vat_amount' => Money::fromMinor($vat),
                'total' => Money::fromMinor($subtotal + $vat),
                'memo' => $attributes['memo'] ?? null,
                'created_by' => $attributes['created_by'] ?? null,
            ]);
            $bill->save();
            $bill->lines()->createMany($rows);

            return $bill->load('lines');
        });
    }

    /** Post a draft bill: DR expenses + DR input VAT / CR accounts payable. */
    public static function post(Bill $bill, array $attributes = []): Bill
    {
        if ($bill->status !== Bill::STATUS_DRAFT) {
            throw new InvoiceException("Bill {$bill->no} is not a draft.");
        }

        return DB::transaction(function () use ($bill, $attributes) {
            $workspace = $bill->workspace;
            $bill->loadMissing('lines', 'partner');

            $apAccountId = $bill->partner->ap_account_id
                ?? Account::query()->forWorkspace($workspace)->where('system_role', 'ap_control')->value('id');
            if (! $apAccountId) {
                throw new InvoiceException('No accounts-payable control account is configured.');
            }

            $expenseByAccount = [];
            $vatByAccount = [];
            foreach ($bill->lines as $line) {
                $amount = Money::toMinor($line->amount);
                $expenseByAccount[$line->account_id] = ($expenseByAccount[$line->account_id] ?? 0) + $amount;

                if ($line->tax_code_id) {
                    $code = TaxCode::query()->forWorkspace($workspace)->find($line->tax_code_id);
                    if ($code && $code->kind === TaxCode::KIND_VAT_INPUT) {
                        $vatByAccount[$code->account_id] = ($vatByAccount[$code->account_id] ?? 0)
                            + Money::percentage($amount, $code->rate);
                    }
                }
            }

            $total = array_sum($expenseByAccount) + array_sum($vatByAccount);

            $journalLines = [];
            foreach ($expenseByAccount as $accountId => $amount) {
                $journalLines[] = ['account_id' => $accountId, 'debit' => Money::fromMinor($amount), 'credit' => 0, 'description' => 'ค่าใช้จ่าย'];
            }
            foreach ($vatByAccount as $accountId => $amount) {
                $journalLines[] = ['account_id' => $accountId, 'debit' => Money::fromMinor($amount), 'credit' => 0, 'description' => 'ภาษีซื้อ'];
            }
            $journalLines[] = ['account_id' => $apAccountId, 'debit' => 0, 'credit' => Money::fromMinor($total), 'description' => "เจ้าหนี้ {$bill->partner->name}"];

            $journal = LedgerPosting::post($workspace, [
                'date' => $bill->issue_date,
                'type' => 'purchase',
                'memo' => $attributes['memo'] ?? "ซื้อเชื่อ {$bill->no} — {$bill->partner->name}",
                'created_by' => $attributes['created_by'] ?? null,
                'source_type' => $bill->getMorphClass(),
                'source_id' => $bill->id,
            ], $journalLines);

            $bill->forceFill([
                'status' => Bill::STATUS_ISSUED,
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();

            return $bill->refresh()->load('lines', 'journal');
        });
    }

    private static function nextNumber(Workspace $workspace, CarbonInterface $date): string
    {
        $prefix = 'BL'.($date->year + 543).'-';

        $last = Bill::query()->forWorkspace($workspace)
            ->where('no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('no')
            ->value('no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
