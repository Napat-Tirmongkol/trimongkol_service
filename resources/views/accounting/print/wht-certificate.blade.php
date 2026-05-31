<x-print-layout :title="'หนังสือรับรองหัก ณ ที่จ่าย ' . $certificate->no">
    @php $companyName = setting('company.name') ?: (setting('brand.name') ?: config('app.name')); @endphp

    <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4">
        <div>
            <div class="text-lg font-bold text-slate-900">หนังสือรับรองการหักภาษี ณ ที่จ่าย</div>
            <div class="text-[11px] uppercase tracking-[0.15em] text-slate-400">Withholding Tax Certificate · 50 ทวิ</div>
        </div>
        <div class="text-right">
            <div class="font-mono text-sm font-semibold text-slate-700">{{ $certificate->no }}</div>
            <div class="mt-1 inline-flex rounded border border-slate-400 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $certificate->pnd_type === 'PND3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53' }}</div>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-6">
        <div class="rounded-lg border border-slate-200 p-3">
            <div class="text-xs uppercase tracking-wider text-slate-400">ผู้จ่ายเงิน / ผู้หักภาษี (Payer)</div>
            <div class="mt-1 font-semibold text-slate-900">{{ $companyName }}</div>
            @if (setting('company.tax_id'))<div class="text-xs text-slate-500">เลขผู้เสียภาษี {{ setting('company.tax_id') }}</div>@endif
            @if (setting('company.address'))<div class="mt-0.5 whitespace-pre-line text-xs text-slate-500">{{ setting('company.address') }}</div>@endif
        </div>
        <div class="rounded-lg border border-slate-200 p-3">
            <div class="text-xs uppercase tracking-wider text-slate-400">ผู้ถูกหักภาษี (Payee)</div>
            <div class="mt-1 font-semibold text-slate-900">{{ $certificate->partner?->name }}</div>
            @if ($certificate->tax_id)<div class="text-xs text-slate-500">เลขผู้เสียภาษี {{ $certificate->tax_id }}</div>@endif
        </div>
    </div>

    <table class="mt-6 w-full">
        <thead>
            <tr class="border-y border-slate-300 text-left text-xs uppercase tracking-wider text-slate-500">
                <th class="py-2 pr-2 font-medium">ประเภทเงินได้ / Income type</th>
                <th class="px-2 py-2 text-right font-medium">วันที่จ่าย</th>
                <th class="px-2 py-2 text-right font-medium">จำนวนเงินที่จ่าย</th>
                <th class="px-2 py-2 text-right font-medium">อัตรา</th>
                <th class="py-2 pl-2 text-right font-medium">ภาษีที่หัก</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-slate-100 align-top">
                <td class="py-2 pr-2 text-slate-800">{{ $certificate->income_type ?: 'ค่าบริการ' }}</td>
                <td class="px-2 py-2 text-right tabular-nums text-slate-600">{{ $certificate->paid_on?->format('d/m/Y') }}</td>
                <td class="px-2 py-2 text-right tabular-nums text-slate-700">{{ number_format((float) $certificate->base_amount, 2) }}</td>
                <td class="px-2 py-2 text-right tabular-nums text-slate-600">{{ rtrim(rtrim(number_format((float) $certificate->rate, 3), '0'), '.') }}%</td>
                <td class="py-2 pl-2 text-right font-semibold tabular-nums text-slate-900">{{ number_format((float) $certificate->wht_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="mt-3 ml-auto max-w-xs">
        <div class="flex justify-between border-t-2 border-slate-900 pt-1 text-base font-bold"><span>รวมภาษีที่หัก</span><span class="tabular-nums">฿{{ number_format((float) $certificate->wht_amount, 2) }}</span></div>
    </div>

    <p class="mt-6 text-xs text-slate-500">ขอรับรองว่าข้อความและตัวเลขดังกล่าวข้างต้นถูกต้องตรงกับความจริงทุกประการ</p>

    <div class="mt-14 ml-auto max-w-xs text-center text-xs text-slate-500">
        <div class="border-t border-slate-400 pt-1">ผู้จ่ายเงิน / Authorized signature</div>
    </div>
</x-print-layout>
