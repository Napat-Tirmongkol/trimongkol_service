<x-print-layout :title="'บิลซื้อ ' . $bill->no">
    @include('accounting.print.company', ['workspace' => $bill->workspace, 'docTitle' => 'บันทึกซื้อ', 'docTitleEn' => 'Purchase / Bill', 'docNo' => $bill->no])

    <div class="mt-5 flex justify-between gap-6">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400">ผู้ขาย / Vendor</div>
            <div class="mt-0.5 font-semibold text-slate-900">{{ $bill->partner?->name }}</div>
            @if ($bill->partner?->tax_id)
                <div class="text-xs text-slate-500">เลขผู้เสียภาษี {{ $bill->partner->tax_id }}</div>
            @endif
        </div>
        <div class="space-y-0.5 text-right text-xs text-slate-600">
            <div>วันที่ / Date: <span class="font-medium text-slate-800">{{ $bill->issue_date?->format('d/m/Y') }}</span></div>
            @if ($bill->bill_ref)<div>เลขที่ผู้ขาย: <span class="font-medium text-slate-800">{{ $bill->bill_ref }}</span></div>@endif
        </div>
    </div>

    <table class="mt-6 w-full">
        <thead>
            <tr class="border-y border-slate-300 text-left text-xs uppercase tracking-wider text-slate-500">
                <th class="py-2 pr-2 font-medium">#</th>
                <th class="py-2 pr-2 font-medium">รายการ / Description</th>
                <th class="px-2 py-2 text-right font-medium">จำนวน</th>
                <th class="px-2 py-2 text-right font-medium">ราคา/หน่วย</th>
                <th class="py-2 pl-2 text-right font-medium">จำนวนเงิน</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bill->lines as $line)
                <tr class="border-b border-slate-100 align-top">
                    <td class="py-2 pr-2 text-slate-400">{{ $line->line_no }}</td>
                    <td class="py-2 pr-2 text-slate-800">{{ $line->description ?: $line->account?->name }}</td>
                    <td class="px-2 py-2 text-right tabular-nums text-slate-600">{{ rtrim(rtrim(number_format((float) $line->quantity, 4), '0'), '.') }}</td>
                    <td class="px-2 py-2 text-right tabular-nums text-slate-600">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="py-2 pl-2 text-right tabular-nums text-slate-900">{{ number_format((float) $line->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 ml-auto max-w-xs space-y-1">
        <div class="flex justify-between"><span class="text-slate-500">ยอดก่อนภาษี / Subtotal</span><span class="tabular-nums">{{ number_format((float) $bill->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">ภาษีซื้อ 7% / Input VAT</span><span class="tabular-nums">{{ number_format((float) $bill->vat_amount, 2) }}</span></div>
        <div class="flex justify-between border-t-2 border-slate-900 pt-1 text-base font-bold"><span>รวมทั้งสิ้น / Total</span><span class="tabular-nums">฿{{ number_format((float) $bill->total, 2) }}</span></div>
    </div>

    @if ($bill->memo)<p class="mt-6 text-xs text-slate-500">หมายเหตุ: {{ $bill->memo }}</p>@endif
</x-print-layout>
