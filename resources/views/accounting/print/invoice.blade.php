<x-print-layout :title="'ใบกำกับภาษี ' . $invoice->no">
    @include('accounting.print.company', ['workspace' => $invoice->workspace, 'docTitle' => 'ใบกำกับภาษี / ใบเสร็จรับเงิน', 'docTitleEn' => 'Tax Invoice / Receipt', 'docNo' => $invoice->no])

    <div class="mt-5 flex justify-between gap-6">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400">ลูกค้า / Customer</div>
            <div class="mt-0.5 font-semibold text-slate-900">{{ $invoice->partner?->name }}</div>
            @if ($invoice->partner?->tax_id)
                <div class="text-xs text-slate-500">เลขผู้เสียภาษี {{ $invoice->partner->tax_id }} @if ($invoice->partner->branch_code)({{ $invoice->partner->branch_code }})@endif</div>
            @endif
            @if ($invoice->partner?->address)<div class="mt-0.5 max-w-xs whitespace-pre-line text-xs text-slate-500">{{ $invoice->partner->address }}</div>@endif
        </div>
        <div class="space-y-0.5 text-right text-xs text-slate-600">
            <div>วันที่ / Date: <span class="font-medium text-slate-800">{{ $invoice->issue_date?->format('d/m/Y') }}</span></div>
            @if ($invoice->due_date)<div>ครบกำหนด / Due: <span class="font-medium text-slate-800">{{ $invoice->due_date->format('d/m/Y') }}</span></div>@endif
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
            @foreach ($invoice->lines as $line)
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
        <div class="flex justify-between"><span class="text-slate-500">ยอดก่อนภาษี / Subtotal</span><span class="tabular-nums">{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">ภาษีมูลค่าเพิ่ม 7% / VAT</span><span class="tabular-nums">{{ number_format((float) $invoice->vat_amount, 2) }}</span></div>
        <div class="flex justify-between border-t-2 border-slate-900 pt-1 text-base font-bold"><span>รวมทั้งสิ้น / Total</span><span class="tabular-nums">฿{{ number_format((float) $invoice->total, 2) }}</span></div>
        @if ((float) $invoice->wht_amount > 0)
            <div class="flex justify-between text-xs text-slate-500"><span>หัก ณ ที่จ่าย (คาดว่า)</span><span class="tabular-nums">{{ number_format((float) $invoice->wht_amount, 2) }}</span></div>
        @endif
    </div>

    @if ($invoice->memo)<p class="mt-6 text-xs text-slate-500">หมายเหตุ: {{ $invoice->memo }}</p>@endif

    <div class="mt-16 grid grid-cols-2 gap-12 text-center text-xs text-slate-500">
        <div class="border-t border-slate-400 pt-1">ผู้รับเงิน / Received by</div>
        <div class="border-t border-slate-400 pt-1">ผู้มีอำนาจลงนาม / Authorized signature</div>
    </div>
</x-print-layout>
