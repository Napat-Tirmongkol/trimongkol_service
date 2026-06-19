{{-- กยศ per-งวด tracker: yearly targets + flexible logged payments. --}}
@php
    $koyosoPayments = $debt->payments->sortBy('month')->values();
    $totalTarget = (float) $koyosoPayments->sum('amount');
    $totalPaid   = (float) $koyosoPayments->sum('paid_amount');
    $overallPct  = $totalTarget > 0 ? min(100, round($totalPaid / $totalTarget * 100)) : 0;
    // Current งวด = earliest not-fully-paid; fall back to last when all are done.
    $currentPeriod = $koyosoPayments->firstWhere('is_paid', false) ?? $koyosoPayments->last();
    $thShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $periodLabel = function ($ym) use ($thShort) {
        [$y, $m] = explode('-', $ym);
        return 'งวดที่ ' . ((int) $y - 2025) . ' · ครบ 5 ' . $thShort[(int) $m] . ' ' . ((int) $y + 543);
    };
@endphp

<div class="rounded-xl border border-rose-200 bg-rose-50/30 p-4 space-y-3"
     x-data="{ editing: false, logOpen: false, allOpen: false,
               label: '{{ addslashes($debt->label) }}',
               total_amount: {{ $debt->total_amount }},
               notes: '{{ addslashes($debt->notes) }}' }">

    {{-- Header --}}
    <div x-show="!editing" class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-800">{{ $debt->label }}</span>
                <span class="inline-flex items-center rounded-full bg-rose-100 px-1.5 py-0.5 text-[9px] font-semibold text-rose-700">รายปี</span>
            </div>
            <p class="text-[10px] text-slate-500">จ่ายยืดหยุ่นตามจริง · ครบกำหนด 5 ก.ค. ทุกปี</p>
        </div>
        <div class="flex items-center gap-1 shrink-0">
            <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </button>
            <form method="POST" action="{{ route('portfolio.budget.debts.destroy', $debt) }}"
                  data-confirm="ลบ '{{ $debt->label }}' และประวัติการจ่ายทั้งหมด?" data-confirm-danger="1">
                @csrf @method('DELETE')
                <input type="hidden" name="month" value="{{ $activeMonth }}">
                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Edit header --}}
    <div x-show="editing" x-cloak>
        <form method="POST" action="{{ route('portfolio.budget.debts.update', $debt) }}" class="space-y-2 bg-white p-2.5 rounded-lg border border-slate-200">
            @csrf @method('PATCH')
            <input type="hidden" name="month" value="{{ $activeMonth }}">
            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
            <input type="number" step="0.01" name="total_amount" x-model="total_amount" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ยอดรวม">
            <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="หมายเหตุ">
            <div class="flex justify-end gap-1.5">
                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
            </div>
        </form>
    </div>

    {{-- Overall progress --}}
    <div class="rounded-lg bg-white p-3 border border-slate-200">
        <div class="flex items-end justify-between mb-1.5">
            <div>
                <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">จ่ายแล้วทั้งหมด</div>
                <div class="text-lg font-extrabold text-emerald-700 leading-none mt-0.5">฿{{ $fmtMoney($totalPaid) }}</div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400">จาก ฿{{ $fmtMoney($totalTarget) }}</div>
                <div class="text-sm font-bold text-slate-700">{{ $overallPct }}%</div>
            </div>
        </div>
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
            <div class="h-1.5 rounded-full bg-emerald-500 transition-all" style="width: {{ $overallPct }}%"></div>
        </div>
    </div>

    {{-- Current งวด focus + record payment --}}
    @if($currentPeriod)
        @php
            $cpTarget = (float) $currentPeriod->amount;
            $cpPaid   = (float) $currentPeriod->paid_amount;
            $cpRemain = max(0, $cpTarget - $cpPaid);
            $cpPct    = $cpTarget > 0 ? min(100, round($cpPaid / $cpTarget * 100)) : 0;
        @endphp
        <div class="rounded-lg border border-brand-200 bg-brand-50/50 p-3 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-brand-800">{{ $periodLabel($currentPeriod->month) }}</span>
                <span class="text-[10px] font-semibold {{ $currentPeriod->is_paid ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $currentPeriod->is_paid ? 'ครบแล้ว ✓' : 'เหลือ ฿' . $fmtMoney($cpRemain) }}
                </span>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-1.5 rounded-full bg-brand-500 transition-all" style="width: {{ $cpPct }}%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-slate-500">
                <span>จ่ายแล้ว ฿{{ $fmtMoney($cpPaid) }}</span>
                <span>เป้า ฿{{ $fmtMoney($cpTarget) }}</span>
            </div>

            <button @click="logOpen = !logOpen" type="button"
                    class="flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:text-brand-700">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                บันทึกการจ่าย
            </button>
            <div x-show="logOpen" x-cloak>
                <form method="POST" action="{{ route('portfolio.budget.debt-payment-logs.store') }}" class="flex flex-wrap items-end gap-1.5">
                    @csrf
                    <input type="hidden" name="debt_payment_id" value="{{ $currentPeriod->id }}">
                    <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                    <div>
                        <label class="block text-[9px] text-slate-500">วันที่จ่าย</label>
                        <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required class="rounded border-slate-300 px-1.5 py-0.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500">จำนวนเงิน</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-24 rounded border-slate-300 px-1.5 py-0.5 text-xs">
                    </div>
                    <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold hover:bg-brand-700">บันทึก</button>
                </form>
            </div>

            {{-- Logged payments for this งวด --}}
            @if($currentPeriod->logs->isNotEmpty())
                <div class="border-t border-brand-100 pt-1.5 space-y-1">
                    @foreach($currentPeriod->logs as $log)
                        <div class="flex items-center justify-between gap-2 text-[10px]">
                            <span class="text-slate-500">{{ $log->paid_on->locale('th')->isoFormat('D MMM YY') }}</span>
                            <span class="ml-auto font-semibold text-slate-700">฿{{ $fmtMoney($log->amount) }}</span>
                            <form method="POST" action="{{ route('portfolio.budget.debt-payment-logs.destroy', $log) }}"
                                  data-confirm="ลบรายการจ่าย ฿{{ $fmtMoney($log->amount) }} นี้?">
                                @csrf @method('DELETE')
                                <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                                <button type="submit" class="text-rose-400 hover:text-rose-600 shrink-0">
                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- All 15 งวด (collapsible) --}}
    <div>
        <button @click="allOpen = !allOpen" type="button" class="text-[10px] font-semibold text-slate-500 hover:text-slate-700">
            <span x-text="allOpen ? 'ซ่อนทุกงวด' : 'ดูทั้ง {{ $koyosoPayments->count() }} งวด'">ดูทั้ง {{ $koyosoPayments->count() }} งวด</span>
        </button>
        <div x-show="allOpen" x-cloak class="mt-1.5 space-y-1">
            @foreach($koyosoPayments as $p)
                @php
                    $pTarget = (float) $p->amount;
                    $pPct = $pTarget > 0 ? min(100, round((float) $p->paid_amount / $pTarget * 100)) : 0;
                    $pNo  = (int) substr($p->month, 0, 4) - 2025;
                @endphp
                <div class="flex items-center gap-2 text-[10px]">
                    <span class="w-12 shrink-0 text-slate-500">งวด {{ $pNo }}</span>
                    <div class="h-1 flex-1 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-1 rounded-full {{ $p->is_paid ? 'bg-emerald-500' : 'bg-brand-400' }}" style="width: {{ $pPct }}%"></div>
                    </div>
                    <span class="shrink-0 text-slate-600">฿{{ $fmtMoney($p->paid_amount) }}/฿{{ $fmtMoney($p->amount) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
