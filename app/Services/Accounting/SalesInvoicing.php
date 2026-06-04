<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\InvoiceException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Models\Accounting\PaymentAllocation;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sales invoices (AR). `create()` builds a draft and computes the money;
 * `issue()` posts the double-entry through LedgerPosting and freezes the
 * numbers. VAT sits on each line (output VAT); WHT is invoice-level and
 * informational — the customer withholds it when they pay (receipt phase).
 */
class SalesInvoicing
{
    /**
     * Build a draft invoice. Each line: account_id (revenue), description?,
     * quantity?, unit_price? (or amount?), tax_code_id? (a vat_output code),
     * department_id?. Optional $attributes['wht_tax_code_id'] applies WHT to
     * the subtotal.
     */
    public static function create(Workspace $workspace, array $attributes, array $lines): Invoice
    {
        if ($lines === []) {
            throw new InvoiceException('An invoice needs at least one line.');
        }

        return DB::transaction(function () use ($workspace, $attributes, $lines) {
            $partner = Partner::query()->forWorkspace($workspace)->find($attributes['partner_id'] ?? null);
            if (! $partner) {
                throw new InvoiceException('Partner does not belong to this workspace.');
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
                    throw new InvoiceException("Revenue account {$accountId} is not in this workspace.");
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
                    if ($code->kind === TaxCode::KIND_VAT_OUTPUT) {
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

            $wht = self::withholding($workspace, $attributes['wht_tax_code_id'] ?? null, $subtotal);

            $invoice = new Invoice([
                'workspace_id' => $workspace->id,
                'no' => $attributes['no'] ?? self::nextNumber($workspace, $issueDate),
                'partner_id' => $partner->id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => Money::fromMinor($subtotal),
                'vat_amount' => Money::fromMinor($vat),
                'wht_amount' => Money::fromMinor($wht),
                'total' => Money::fromMinor($subtotal + $vat),
                'tax_invoice_no' => $attributes['tax_invoice_no'] ?? null,
                'memo' => $attributes['memo'] ?? null,
                'created_by' => $attributes['created_by'] ?? null,
            ]);
            $invoice->save();
            $invoice->lines()->createMany($rows);

            return $invoice->load('lines');
        });
    }

    /** Update a draft invoice in place — replaces lines and recomputes totals. */
    public static function update(Invoice $invoice, array $attributes, array $lines): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw new InvoiceException("Invoice {$invoice->no} is not a draft.");
        }
        if ($lines === []) {
            throw new InvoiceException('An invoice needs at least one line.');
        }

        return DB::transaction(function () use ($invoice, $attributes, $lines) {
            $workspace = $invoice->workspace;
            $partner = Partner::query()->forWorkspace($workspace)->find($attributes['partner_id'] ?? $invoice->partner_id);
            if (! $partner) {
                throw new InvoiceException('Partner does not belong to this workspace.');
            }

            $issueDate = Carbon::parse($attributes['issue_date'] ?? $invoice->issue_date);
            $dueDate = isset($attributes['due_date']) && $attributes['due_date'] !== null
                ? Carbon::parse($attributes['due_date'])
                : null;

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
                    throw new InvoiceException("Revenue account {$accountId} is not in this workspace.");
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
                    if ($code->kind === TaxCode::KIND_VAT_OUTPUT) {
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

            $wht = self::withholding($workspace, $attributes['wht_tax_code_id'] ?? null, $subtotal);

            $invoice->forceFill([
                'partner_id' => $partner->id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'subtotal' => Money::fromMinor($subtotal),
                'vat_amount' => Money::fromMinor($vat),
                'wht_amount' => Money::fromMinor($wht),
                'total' => Money::fromMinor($subtotal + $vat),
                'tax_invoice_no' => $attributes['tax_invoice_no'] ?? null,
                'memo' => $attributes['memo'] ?? null,
            ])->save();

            $invoice->lines()->delete();
            $invoice->lines()->createMany($rows);

            return $invoice->refresh()->load('lines');
        });
    }

    /**
     * Issue a draft: post DR accounts-receivable / CR revenue (per account) /
     * CR output VAT, link the journal, and mark it issued. WHT is not posted
     * here — it is settled when the customer pays.
     */
    public static function issue(Invoice $invoice, array $attributes = []): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw new InvoiceException("Invoice {$invoice->no} is not a draft.");
        }

        return DB::transaction(function () use ($invoice, $attributes) {
            $workspace = $invoice->workspace;
            $invoice->loadMissing('lines', 'partner');

            $arAccountId = $invoice->partner->ar_account_id
                ?? Account::query()->forWorkspace($workspace)->where('system_role', 'ar_control')->value('id');
            if (! $arAccountId) {
                throw new InvoiceException('No accounts-receivable control account is configured.');
            }

            $revenueByAccount = [];
            $vatByAccount = [];
            foreach ($invoice->lines as $line) {
                $amount = Money::toMinor($line->amount);
                $revenueByAccount[$line->account_id] = ($revenueByAccount[$line->account_id] ?? 0) + $amount;

                if ($line->tax_code_id) {
                    $code = TaxCode::query()->forWorkspace($workspace)->find($line->tax_code_id);
                    if ($code && $code->kind === TaxCode::KIND_VAT_OUTPUT) {
                        $vatByAccount[$code->account_id] = ($vatByAccount[$code->account_id] ?? 0)
                            + Money::percentage($amount, $code->rate);
                    }
                }
            }

            $total = array_sum($revenueByAccount) + array_sum($vatByAccount);

            $journalLines = [[
                'account_id' => $arAccountId,
                'debit' => Money::fromMinor($total),
                'credit' => 0,
                'description' => "ลูกหนี้ {$invoice->partner->name}",
            ]];
            foreach ($revenueByAccount as $accountId => $amount) {
                $journalLines[] = ['account_id' => $accountId, 'debit' => 0, 'credit' => Money::fromMinor($amount), 'description' => 'รายได้'];
            }
            foreach ($vatByAccount as $accountId => $amount) {
                $journalLines[] = ['account_id' => $accountId, 'debit' => 0, 'credit' => Money::fromMinor($amount), 'description' => 'ภาษีขาย'];
            }

            $journal = LedgerPosting::post($workspace, [
                'date' => $invoice->issue_date,
                'type' => 'sales',
                'memo' => $attributes['memo'] ?? "ขายเชื่อ {$invoice->no} — {$invoice->partner->name}",
                'created_by' => $attributes['created_by'] ?? null,
                'source_type' => $invoice->getMorphClass(),
                'source_id' => $invoice->id,
            ], $journalLines);

            $invoice->forceFill([
                'status' => Invoice::STATUS_ISSUED,
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();

            return $invoice->refresh()->load('lines', 'journal');
        });
    }

    /**
     * Void an issued invoice: reverse its posting journal and mark void.
     * Refuses if any receipt is already allocated — the receipts must be
     * unwound first so AR doesn't end up double-cleared.
     */
    public static function void(Invoice $invoice, array $attributes = []): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_ISSUED) {
            throw new InvoiceException("Only issued invoices can be voided. {$invoice->no} is {$invoice->status}.");
        }
        if (! $invoice->journal_id) {
            throw new InvoiceException("Invoice {$invoice->no} has no journal to reverse.");
        }

        $hasAllocations = PaymentAllocation::query()
            ->where('allocatable_type', $invoice->getMorphClass())
            ->where('allocatable_id', $invoice->id)
            ->exists();
        if ($hasAllocations) {
            throw new InvoiceException("Invoice {$invoice->no} has receipts allocated — reverse the receipts before voiding.");
        }

        return DB::transaction(function () use ($invoice, $attributes) {
            $invoice->loadMissing('journal.lines');

            LedgerPosting::reverse($invoice->journal, [
                'date' => $attributes['date'] ?? now(),
                'memo' => $attributes['memo'] ?? "ยกเลิกใบกำกับ {$invoice->no}",
                'created_by' => $attributes['created_by'] ?? null,
            ]);

            $invoice->forceFill(['status' => Invoice::STATUS_VOID])->save();

            return $invoice->refresh();
        });
    }

    /** WHT the customer will withhold on the subtotal (informational), in minor units. */
    private static function withholding(Workspace $workspace, $whtTaxCodeId, int $subtotal): int
    {
        if (! $whtTaxCodeId) {
            return 0;
        }

        $code = TaxCode::query()->forWorkspace($workspace)
            ->where('kind', TaxCode::KIND_WHT)
            ->find($whtTaxCodeId);
        if (! $code) {
            throw new InvoiceException('WHT tax code is not in this workspace.');
        }

        return Money::percentage($subtotal, $code->rate);
    }

    /** Per-workspace running number keyed on the issue date's Buddhist year. */
    private static function nextNumber(Workspace $workspace, CarbonInterface $date): string
    {
        $prefix = 'INV'.($date->year + 543).'-';

        $last = Invoice::query()->forWorkspace($workspace)
            ->where('no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('no')
            ->value('no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
