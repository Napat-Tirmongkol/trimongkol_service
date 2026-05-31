<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\WhtCertificate;
use App\Models\Workspace;

/**
 * Tax reports for filing / the accountant: output & input VAT (from issued
 * sales invoices and posted bills) and the withholding-tax summary (from the
 * 50-ทวิ certificates), all over a date window. Totals in integer satang.
 */
class TaxReporting
{
    /** Output VAT — issued sales tax invoices (รายงานภาษีขาย). */
    public static function vatSales(Workspace $workspace, string $from, string $to): array
    {
        $invoices = Invoice::query()->forWorkspace($workspace)->with('partner')
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID])
            ->whereDate('issue_date', '>=', $from)->whereDate('issue_date', '<=', $to)
            ->orderBy('issue_date')->orderBy('id')->get();

        return self::summarise($invoices->map(fn ($i) => [
            'no' => $i->no, 'tax_no' => $i->tax_invoice_no, 'date' => $i->issue_date,
            'partner' => $i->partner?->name, 'tax_id' => $i->partner?->tax_id,
            'base' => $i->subtotal, 'vat' => $i->vat_amount,
        ]));
    }

    /** Input VAT — posted supplier bills (รายงานภาษีซื้อ). */
    public static function vatPurchases(Workspace $workspace, string $from, string $to): array
    {
        $bills = Bill::query()->forWorkspace($workspace)->with('partner')
            ->whereNotIn('status', [Bill::STATUS_DRAFT, Bill::STATUS_VOID])
            ->whereDate('issue_date', '>=', $from)->whereDate('issue_date', '<=', $to)
            ->orderBy('issue_date')->orderBy('id')->get();

        return self::summarise($bills->map(fn ($b) => [
            'no' => $b->no, 'tax_no' => $b->bill_ref, 'date' => $b->issue_date,
            'partner' => $b->partner?->name, 'tax_id' => $b->partner?->tax_id,
            'base' => $b->subtotal, 'vat' => $b->vat_amount,
        ]));
    }

    /** Withholding tax withheld from suppliers (ภงด.3/53), from the 50-ทวิ certs. */
    public static function wht(Workspace $workspace, string $from, string $to): array
    {
        $certs = WhtCertificate::query()->forWorkspace($workspace)->with('partner')
            ->whereDate('paid_on', '>=', $from)->whereDate('paid_on', '<=', $to)
            ->orderBy('paid_on')->orderBy('id')->get();

        $base = 0;
        $wht = 0;
        $pnd = ['PND3' => 0, 'PND53' => 0];
        foreach ($certs as $c) {
            $base += Money::toMinor($c->base_amount);
            $wht += Money::toMinor($c->wht_amount);
            $pnd[$c->pnd_type] = ($pnd[$c->pnd_type] ?? 0) + Money::toMinor($c->wht_amount);
        }

        return [
            'rows' => $certs,
            'base_total' => Money::fromMinor($base),
            'wht_total' => Money::fromMinor($wht),
            'pnd3_total' => Money::fromMinor($pnd['PND3'] ?? 0),
            'pnd53_total' => Money::fromMinor($pnd['PND53'] ?? 0),
        ];
    }

    /** @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows */
    private static function summarise($rows): array
    {
        $base = 0;
        $vat = 0;
        foreach ($rows as $r) {
            $base += Money::toMinor($r['base']);
            $vat += Money::toMinor($r['vat']);
        }

        return [
            'rows' => $rows,
            'base_total' => Money::fromMinor($base),
            'vat_total' => Money::fromMinor($vat),
        ];
    }
}
