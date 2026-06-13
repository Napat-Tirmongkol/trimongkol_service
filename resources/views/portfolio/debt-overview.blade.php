<x-portfolio-layout>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="debtOverview()">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900">{{ __('app.portfolio.debts.heading') }}</h1>
        <p class="mt-0.5 text-sm text-slate-500">{{ __('app.portfolio.debts.subheading') }}</p>
    </div>

    {{-- ── Summary tiles ───────────────────────────────────────────────── --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Total remaining --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.tile_remaining') }}</p>
            <p class="mt-2 text-2xl font-bold text-rose-600">
                ฿{{ number_format($totalRemaining, 0) }}
            </p>
        </div>

        {{-- Next month (plan starts next month; current month excluded) --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.tile_next_month') }}</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">
                ฿{{ number_format($firstMonthTotal, 0) }}
            </p>
            <p class="mt-1 text-[11px] text-slate-500">{{ \Illuminate\Support\Carbon::parse($startYM . '-01')->locale('th')->isoFormat('MMMM YY') }}</p>
        </div>

        {{-- 12-month average --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.tile_avg_12') }}</p>
            <p class="mt-2 text-2xl font-bold text-sky-600">
                ฿{{ number_format($avgMonthly, 0) }}
            </p>
            <p class="mt-1 text-[11px] text-slate-500">ต่อเดือน</p>
        </div>
    </div>

    @if (empty($schedule))
        <div class="rounded-2xl border bg-white p-12 text-center shadow-sm">
            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-3-3v3m0 9v3m-6-6h12" />
            </svg>
            <p class="mt-3 text-sm text-slate-500">{{ __('app.portfolio.debts.no_data') }}</p>
        </div>
    @else

    {{-- ── Active items summary ─────────────────────────────────────────── --}}
    @if ($installments->isNotEmpty() || $debts->isNotEmpty())
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">

        {{-- Installments --}}
        @if ($installments->isNotEmpty())
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">{{ __('app.portfolio.debts.installments') }}</h2>
            <div class="divide-y divide-slate-100">
                @foreach ($installments as $ins)
                @php
                    $remaining = (int) $ins->total_months - (int) $ins->paid_months;
                    $pct = $ins->total_months > 0 ? round(($ins->paid_months / $ins->total_months) * 100) : 0;
                @endphp
                <div class="py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-800">{{ $ins->label }}</span>
                        <span class="text-sm font-semibold text-slate-900">฿{{ number_format($ins->monthly_payment, 0) }}/เดือน</span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-1.5 rounded-full bg-brand-500 transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="shrink-0 text-[10px] text-slate-500">
                            {{ __('app.portfolio.debts.months_left', ['n' => $remaining]) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Debts --}}
        @if ($debts->isNotEmpty())
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">{{ __('app.portfolio.debts.debts') }}</h2>
            <div class="divide-y divide-slate-100">
                @foreach ($debts as $debt)
                @php
                    $debtTotal  = (float) $debt->payments->sum('amount');
                    $debtPaid   = (float) $debt->payments->where('is_paid', true)->sum('amount');
                    $debtRemain = $debtTotal - $debtPaid;
                    $debtPct    = $debtTotal > 0 ? round(($debtPaid / $debtTotal) * 100) : 0;
                    $monthsLeft = $debt->payments->where('is_paid', false)->count();
                @endphp
                <div class="py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-800">{{ $debt->label }}</span>
                        <span class="text-sm font-semibold text-rose-700">฿{{ number_format($debtRemain, 0) }} คงเหลือ</span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-1.5 rounded-full bg-emerald-500 transition-all" style="width: {{ $debtPct }}%"></div>
                        </div>
                        <span class="shrink-0 text-[10px] text-slate-500">
                            {{ __('app.portfolio.debts.months_left', ['n' => $monthsLeft]) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Monthly schedule table ───────────────────────────────────────── --}}
    <div class="rounded-2xl border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-700">แผนการจ่ายรายเดือน</h2>
            @if (count($schedule) > 12)
            <button @click="showAll = !showAll" type="button"
                    class="text-xs font-medium text-brand-600 hover:text-brand-700 transition">
                <span x-text="showAll ? '{{ __('app.portfolio.debts.show_less') }}' : '{{ __('app.portfolio.debts.show_all') }} ({{ count($schedule) }} เดือน)'"></span>
            </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50/60">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 w-28">{{ __('app.portfolio.debts.col_month') }}</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.col_installments') }}</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.col_debts') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 w-36">{{ __('app.portfolio.debts.col_total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $rowIndex = 0; @endphp
                    @foreach ($schedule as $ym => $row)
                    @php
                        $isFirstMonth = ($ym === $startYM);
                        $barPct = $maxMonthlyTotal > 0 ? round(($row['total'] / $maxMonthlyTotal) * 100) : 0;
                        $rowIndex++;
                    @endphp
                    <tr class="{{ $isFirstMonth ? 'bg-brand-50/30' : 'hover:bg-slate-50/60' }} transition"
                        x-show="showAll || {{ $rowIndex }} <= 12">
                        <td class="px-5 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium {{ $isFirstMonth ? 'text-brand-700' : 'text-slate-700' }}">{{ \Illuminate\Support\Carbon::parse($ym . '-01')->locale('th')->isoFormat('MMM YY') }}</span>
                                @if ($isFirstMonth)
                                    <span class="inline-flex items-center rounded-full bg-brand-100 px-1.5 py-0.5 text-[9px] font-semibold text-brand-700">{{ __('app.portfolio.debts.next_month') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            @if (!empty($row['installments']))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($row['installments'] as $ins)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700">
                                        {{ $ins['label'] }}
                                        <span class="font-medium">฿{{ number_format($ins['amount'], 0) }}</span>
                                    </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @if (!empty($row['debt_payments']))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($row['debt_payments'] as $dp)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] text-rose-700">
                                        {{ $dp['debt_label'] }}
                                        <span class="font-medium">฿{{ number_format($dp['amount'], 0) }}</span>
                                    </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Bar visual --}}
                                <div class="hidden sm:block h-1.5 w-20 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-1.5 rounded-full {{ $isFirstMonth ? 'bg-brand-500' : 'bg-slate-400' }} transition-all" style="width: {{ $barPct }}%"></div>
                                </div>
                                <span class="text-right font-semibold {{ $isFirstMonth ? 'text-brand-700' : 'text-slate-900' }}">
                                    ฿{{ number_format($row['total'], 0) }}
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (count($schedule) > 12)
        <div class="border-t border-slate-100 px-5 py-3 text-center" x-show="!showAll">
            <button @click="showAll = true" type="button"
                    class="text-xs font-medium text-brand-600 hover:text-brand-700 transition">
                {{ __('app.portfolio.debts.show_all') }} ({{ count($schedule) }} เดือน) ▾
            </button>
        </div>
        @endif
    </div>

    @endif

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('debtOverview', () => ({
        showAll: false,
    }));
});
</script>
</x-portfolio-layout>
