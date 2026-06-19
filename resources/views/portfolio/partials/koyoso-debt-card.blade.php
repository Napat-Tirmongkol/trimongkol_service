{{-- กยศ per-งวด tracker: yearly targets + flexible logged payments. Fully editable. --}}
@php
    $fromDebts = ($redirectTo ?? null) === 'debts';
    $koyosoPayments = $debt->payments->sortBy('month')->values();
    $totalTarget = (float) $koyosoPayments->sum('amount');
    $totalPaid   = (float) $koyosoPayments->sum('paid_amount');
    $overallPct  = $totalTarget > 0 ? min(100, round($totalPaid / $totalTarget * 100)) : 0;
    // Current งวด = earliest not-fully-paid; fall back to last when all are done.
    $currentPeriod = $koyosoPayments->firstWhere('is_paid', false) ?? $koyosoPayments->last();
    $currentNo = $currentPeriod
        ? ($koyosoPayments->search(fn ($p) => $p->id === $currentPeriod->id) + 1)
        : 0;
    $thShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $dueLabel = function ($ym) use ($thShort) {
        [$y, $m] = explode('-', $ym);
        return '5 ' . ($thShort[(int) $m] ?? $m) . ' ' . ((int) $y + 543);
    };
    // Sensible default for a new งวด: the July after the last existing งวด.
    $lastYm = optional($koyosoPayments->last())->month;
    $nextDueDefault = $lastYm
        ? (((int) substr($lastYm, 0, 4) + 1) . '-07')
        : (date('Y') . '-07');
@endphp

<div class="rounded-xl border border-rose-200 bg-rose-50/30 p-4 space-y-3"
     x-data="{ editing: false, logOpen: false, allOpen: {{ $koyosoPayments->isEmpty() ? 'true' : 'false' }}, addOpen: false,
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
            <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition" title="แก้ไขข้อมูลหนี้">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </button>
            <form method="POST" action="{{ route('portfolio.budget.debts.destroy', $debt) }}"
                  data-confirm="ลบ '{{ $debt->label }}' และประวัติการจ่ายทั้งหมด?" data-confirm-danger="1">
                @csrf @method('DELETE')
                <input type="hidden" name="month" value="{{ $activeMonth }}">
                @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition" title="ลบหนี้นี้">
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
            @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
            <label class="block text-[10px] font-bold text-slate-600">ชื่อหนี้</label>
            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
            <label class="block text-[10px] font-bold text-slate-600">ยอดหนี้รวม (เงินต้น+ดอกเบี้ย)</label>
            <input type="number" step="0.01" name="total_amount" x-model="total_amount" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ยอดรวม">
            <label class="block text-[10px] font-bold text-slate-600">หมายเหตุ</label>
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

    {{-- Current งวด focus + quick record payment --}}
    @if($currentPeriod)
        @php
            $cpTarget = (float) $currentPeriod->amount;
            $cpPaid   = (float) $currentPeriod->paid_amount;
            $cpRemain = max(0, $cpTarget - $cpPaid);
            $cpPct    = $cpTarget > 0 ? min(100, round($cpPaid / $cpTarget * 100)) : 0;
        @endphp
        <div class="rounded-lg border border-brand-200 bg-brand-50/50 p-3 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-brand-800">งวดที่ {{ $currentNo }} · ครบ {{ $dueLabel($currentPeriod->month) }}</span>
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
                    @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
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
                                @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
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

    {{-- All งวด — full editor (add / edit target / edit due date / delete / log) --}}
    <div>
        <div class="flex items-center justify-between">
            <button @click="allOpen = !allOpen" type="button" class="text-[10px] font-semibold text-slate-500 hover:text-slate-700">
                <span x-text="allOpen ? 'ซ่อนงวดทั้งหมด' : 'จัดการทั้ง {{ $koyosoPayments->count() }} งวด'">จัดการทั้ง {{ $koyosoPayments->count() }} งวด</span>
            </button>
            <button @click="addOpen = !addOpen" type="button"
                    class="flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-brand-600 hover:bg-slate-50">
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                เพิ่มงวด
            </button>
        </div>

        {{-- Add งวด --}}
        <div x-show="addOpen" x-cloak class="mt-2 rounded-lg border border-slate-200 bg-white p-2.5">
            <form method="POST" action="{{ route('portfolio.budget.debt-payments.store') }}" class="space-y-2">
                @csrf
                <input type="hidden" name="debt_id" value="{{ $debt->id }}">
                <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[9px] text-slate-500">เดือนครบกำหนด</label>
                        <input type="month" name="month" value="{{ $nextDueDefault }}" required class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500">ยอดเป้าทั้งงวด</label>
                        <input type="number" step="0.01" min="0" name="amount" required placeholder="0.00" class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs">
                    </div>
                </div>
                <input type="text" name="notes" placeholder="หมายเหตุ (ไม่บังคับ)" class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs">
                <div class="flex justify-end gap-1.5">
                    <button type="button" @click="addOpen = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                    <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">เพิ่มงวด</button>
                </div>
            </form>
        </div>

        <div x-show="allOpen" x-cloak class="mt-2 space-y-1.5">
            @forelse($koyosoPayments as $p)
                @php
                    $pTarget = (float) $p->amount;
                    $pPaid   = (float) $p->paid_amount;
                    $pPct = $pTarget > 0 ? min(100, round($pPaid / $pTarget * 100)) : 0;
                    $pNo  = $loop->iteration;
                @endphp
                <div class="rounded-lg border border-slate-150 bg-white p-2"
                     x-data="{ editP: false, payP: false, amt: '{{ $p->amount }}', due: '{{ $p->month }}' }">

                    {{-- Read row --}}
                    <div x-show="!editP" class="space-y-1">
                        <div class="flex items-center gap-2 text-[10px]">
                            <span class="w-10 shrink-0 font-semibold text-slate-600">งวด {{ $pNo }}</span>
                            <span class="shrink-0 text-slate-400">{{ $dueLabel($p->month) }}</span>
                            <div class="h-1 flex-1 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-1 rounded-full {{ $p->is_paid ? 'bg-emerald-500' : 'bg-brand-400' }}" style="width: {{ $pPct }}%"></div>
                            </div>
                            <span class="shrink-0 text-slate-600">฿{{ $fmtMoney($pPaid) }}/฿{{ $fmtMoney($pTarget) }}</span>
                            <button @click="payP = !payP" type="button" class="shrink-0 text-brand-500 hover:text-brand-700" title="บันทึก/ดูการจ่าย">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </button>
                            <button @click="editP = true" type="button" class="shrink-0 text-slate-400 hover:text-slate-700" title="แก้ไขงวด">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </button>
                            <form method="POST" action="{{ route('portfolio.budget.debt-payments.destroy', $p) }}"
                                  data-confirm="ลบงวด {{ $pNo }} ({{ $dueLabel($p->month) }}) และประวัติการจ่ายของงวดนี้?" data-confirm-danger="1" class="shrink-0 flex">
                                @csrf @method('DELETE')
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
                                <button type="submit" class="text-rose-300 hover:text-rose-600" title="ลบงวด">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </form>
                        </div>

                        {{-- Per-งวด payment manager --}}
                        <div x-show="payP" x-cloak class="rounded bg-slate-50 border border-slate-150 p-1.5 space-y-1.5">
                            <form method="POST" action="{{ route('portfolio.budget.debt-payment-logs.store') }}" class="flex flex-wrap items-end gap-1.5">
                                @csrf
                                <input type="hidden" name="debt_payment_id" value="{{ $p->id }}">
                                <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                                @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
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
                            @if($p->logs->isNotEmpty())
                                <div class="border-t border-slate-200 pt-1 space-y-1">
                                    @foreach($p->logs as $log)
                                        <div class="flex items-center justify-between gap-2 text-[10px]">
                                            <span class="text-slate-500">{{ $log->paid_on->locale('th')->isoFormat('D MMM YY') }}</span>
                                            <span class="ml-auto font-semibold text-slate-700">฿{{ $fmtMoney($log->amount) }}</span>
                                            <form method="POST" action="{{ route('portfolio.budget.debt-payment-logs.destroy', $log) }}"
                                                  data-confirm="ลบรายการจ่าย ฿{{ $fmtMoney($log->amount) }} นี้?">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                                                @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
                                                <button type="submit" class="text-rose-400 hover:text-rose-600 shrink-0">
                                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Edit งวด (target + due month) --}}
                    <form x-show="editP" x-cloak method="POST" action="{{ route('portfolio.budget.debt-payments.update', $p) }}" class="space-y-1.5">
                        @csrf @method('PATCH')
                        <input type="hidden" name="month" value="{{ $activeMonth }}">
                        @if($fromDebts)<input type="hidden" name="redirect_to" value="debts">@endif
                        <div class="grid grid-cols-2 gap-1.5">
                            <div>
                                <label class="block text-[9px] text-slate-500">เดือนครบกำหนด</label>
                                <input type="month" name="due_month" x-model="due" required class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] text-slate-500">ยอดเป้าทั้งงวด</label>
                                <input type="number" step="0.01" min="0" name="amount" x-model="amt" required class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs">
                            </div>
                        </div>
                        <div class="flex justify-end gap-1.5">
                            <button type="button" @click="editP = false" class="rounded px-2 py-0.5 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                            <button type="submit" class="rounded bg-brand-600 px-2 py-0.5 text-[10px] text-white font-semibold">บันทึก</button>
                        </div>
                    </form>
                </div>
            @empty
                <p class="text-[10px] text-slate-400 text-center py-2">ยังไม่มีงวด — กด "เพิ่มงวด" เพื่อใส่เป้าหมายรายปีเอง</p>
            @endforelse
        </div>
    </div>
</div>
