<x-portfolio-layout>
@php $fmtMoney = fn ($n) => number_format((float) $n, 2); @endphp
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="debtOverview()">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-slate-900">{{ __('app.portfolio.debts.heading') }}</h1>
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">อัปเดตล่าสุด พ.ย. 2026</span>
            </div>
            <p class="mt-0.5 text-sm text-slate-500">สรุปภาระผ่อนชำระ ใบแจ้งยอดล่วงหน้า 6 งวด และแผนปลดหนี้รายเดือน (อ้างอิงสลิปเงินเดือนสุทธิ ฿29,911.00)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('portfolio.budget.index', ['month' => '2026-11']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                ← แดชบอร์ดแผนการเงิน 50/30/20
            </a>
            <form method="POST" action="{{ route('portfolio.budget.reset') }}">
                @csrf
                <input type="hidden" name="month" value="2026-11">
                <input type="hidden" name="sync_verified_plan" value="1">
                <input type="hidden" name="redirect_to" value="debts">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    ซิงก์ข้อมูลหนี้ พ.ย. 69
                </button>
            </form>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- ── Summary tiles ───────────────────────────────────────────────── --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- 1. Short-term & Installments remaining --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">หนี้ผ่อนของ + ใบแจ้งยอดคงเหลือ</p>
            <p class="mt-2 text-2xl font-bold text-rose-600">
                ฿{{ number_format($totalRemainingNonKoyoso, 2) }}
            </p>
            <p class="mt-1 text-[11px] text-slate-500">
                ผ่อนของ ฿{{ number_format($totalRemainingInstallments, 2) }} + ใบแจ้งยอด ฿{{ number_format($totalRemainingShortTermDebts, 2) }}
            </p>
            @if($totalRemainingKoyoso > 0)
            <p class="mt-1 text-[10px] text-slate-400">
                (กยศ. แยกต่างหาก ฿{{ number_format($totalRemainingKoyoso, 2) }} · รวมทั้งหมด ฿{{ number_format($totalRemaining, 2) }})
            </p>
            @endif
        </div>

        {{-- 2. Current month (Nov 2026) --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ยอดจ่ายเดือนนี้ ({{ \Illuminate\Support\Carbon::parse($startYM . '-01')->locale('th')->isoFormat('MMM YY') }})</p>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700">42.2% ของรายได้</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-amber-600">
                ฿{{ number_format($firstMonthNonKoyoso, 2) }}
            </p>
            <p class="mt-1 text-[11px] text-slate-500">
                ผ่อนของ ฿{{ number_format($monthlyInstallmentsSum, 2) }} + ใบแจ้งยอด ฿{{ number_format(max(0, $firstMonthNonKoyoso - $monthlyInstallmentsSum), 2) }}
            </p>
        </div>

        {{-- 3. Next month (Dec 2026) --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ยอดเดือนหน้า ({{ \Illuminate\Support\Carbon::parse($nextYM . '-01')->locale('th')->isoFormat('MMM YY') }})</p>
                @if($firstMonthNonKoyoso > $nextMonthNonKoyoso)
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">-฿{{ number_format($firstMonthNonKoyoso - $nextMonthNonKoyoso, 2) }}</span>
                @endif
            </div>
            <p class="mt-2 text-2xl font-bold text-sky-600">
                ฿{{ number_format($nextMonthNonKoyoso, 2) }}
            </p>
            <p class="mt-1 text-[11px] text-slate-500">
                ปลดหนี้คืนแม่ (-฿1,000) + ใบแจ้งยอดลดเหลือ ฿3,867.24
            </p>
        </div>

        {{-- 4. Debt-free milestone --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ไทม์ไลน์ปลดหนี้ระยะสั้น</p>
            <p class="mt-2 text-lg font-bold text-emerald-600">
                พ.ค. 70 → ก.ย. 70
            </p>
            <p class="mt-1 text-[11px] text-slate-500">
                พ.ค. 70 จบใบแจ้งยอด+SPayLater (เหลือ ฿5,679/ด.) · ก.ย. 70 เหลือเฉพาะมอไซต์ ฿2,989/ด.
            </p>
        </div>
    </div>

    {{-- ── Management section ─────────────────────────────────────────────── --}}
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- ── INSTALLMENTS ─────────────────────────────────────────────────── --}}
        <div class="rounded-2xl border bg-white shadow-sm" x-data="{ addOpen: false }">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-700">{{ __('app.portfolio.debts.installments') }} ({{ $installments->count() }} รายการ)</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        รวมเดือนละ <span class="font-semibold text-slate-700">฿{{ number_format($monthlyInstallmentsSum, 2) }}</span>
                        · ยอดคงเหลือรวม <span class="font-semibold text-rose-600">-฿{{ number_format($totalRemainingInstallments, 2) }}</span>
                    </p>
                </div>
                <button @click="addOpen = !addOpen" type="button"
                        class="flex items-center gap-1.5 rounded-lg border border-brand-200 px-2.5 py-1 text-xs font-semibold text-brand-600 hover:bg-brand-50 transition">
                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    เพิ่มรายการผ่อน
                </button>
            </div>

            {{-- Add installment form --}}
            <div x-show="addOpen" x-cloak class="border-b border-slate-100 bg-slate-50/50 px-5 py-4"
                 x-data="{ ta: '', tm: '', mp: '' }">
                <form method="POST" action="{{ route('portfolio.budget.installments.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="month" value="{{ date('Y-m') }}">
                    <input type="hidden" name="redirect_to" value="debts">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">ชื่อรายการ *</label>
                            <input type="text" name="label" required placeholder="เช่น S24 Ultra" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">ยอดรวม (฿)</label>
                            <input type="number" step="0.01" min="0" name="total_amount" x-model="ta" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">งวดทั้งหมด *</label>
                            <input type="number" min="1" name="total_months" x-model="tm" required placeholder="12" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-0.5">
                                <label class="block text-[10px] font-semibold text-slate-500">ผ่อนต่อเดือน (฿) *</label>
                                <button type="button" @click="if(ta && mp && !tm) tm = Math.ceil(ta / mp); else if(ta && tm && !mp) mp = (ta / tm).toFixed(2); else if(mp && tm && !ta) ta = (mp * tm).toFixed(2); else if(ta && tm) mp = (ta / tm).toFixed(2); else if(mp && tm) ta = (mp * tm).toFixed(2); else if(ta && mp) tm = Math.ceil(ta / mp);" class="text-[9px] text-brand-600 hover:text-brand-700 font-semibold cursor-pointer">คำนวณให้อัตโนมัติ</button>
                            </div>
                            <input type="number" step="0.01" min="0" name="monthly_payment" x-model="mp" required placeholder="0.00" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">จ่ายไปแล้ว (งวด)</label>
                            <input type="number" min="0" name="paid_months" value="0" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">หมายเหตุ</label>
                            <input type="text" name="notes" placeholder="หมายเหตุ (ไม่บังคับ)" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="addOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-700 hover:bg-slate-300">ยกเลิก</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">เพิ่มรายการ</button>
                    </div>
                </form>
            </div>

            {{-- Installments list --}}
            <div class="divide-y divide-slate-100">
                @forelse($installments as $ins)
                @php
                    $insLeft = (int) $ins->total_months - (int) $ins->paid_months;
                    $insPct  = $ins->total_months > 0 ? round(($ins->paid_months / $ins->total_months) * 100) : 0;
                    $insRem  = $ins->remainingBalance();
                @endphp
                <div class="px-5 py-3.5"
                     x-data="{ editIns: false,
                                label: '{{ addslashes($ins->label) }}',
                                mp: '{{ $ins->monthly_payment }}',
                                ta: '{{ $ins->total_amount }}',
                                tm: '{{ $ins->total_months }}',
                                pm: '{{ $ins->paid_months }}' }">
                    {{-- Read mode --}}
                    <div x-show="!editIns">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-sm font-semibold {{ $insLeft <= 0 ? 'line-through text-slate-400' : 'text-slate-800' }} truncate">{{ $ins->label }}</span>
                                        @if($insLeft <= 0)
                                            <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">✓ ผ่อนครบแล้ว</span>
                                        @elseif($insLeft === 1)
                                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">งวดสุดท้าย พ.ย. 69</span>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-sm font-bold {{ $insLeft <= 0 ? 'line-through text-slate-400' : 'text-slate-900' }}">฿{{ number_format($ins->monthly_payment, 2) }}/ด.</span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                    <span>ราคาเต็ม ฿{{ number_format($ins->total_amount, 2) }} · ผ่อนแล้ว {{ $ins->paid_months }}/{{ $ins->total_months }} เดือน</span>
                                    @if($insRem > 0)
                                        <span class="font-semibold text-rose-600">คงเหลือ -฿{{ number_format($insRem, 2) }}</span>
                                    @else
                                        <span class="font-semibold text-emerald-600">คงเหลือ ฿0.00</span>
                                    @endif
                                </div>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-1.5 rounded-full {{ $insLeft <= 0 ? 'bg-emerald-500' : 'bg-brand-500' }} transition-all" style="width: {{ $insPct }}%"></div>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-medium text-slate-500">
                                        {{ $insLeft > 0 ? "เหลือ {$insLeft} งวด" : 'ครบงวดแล้ว' }}
                                    </span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    @if($ins->notes)
                                        <p class="text-[10px] text-slate-400">{{ $ins->notes }}</p>
                                    @else
                                        <span></span>
                                    @endif
                                    <div class="flex items-center gap-1">
                                        @if((int) $ins->paid_months > 0)
                                            <form method="POST" action="{{ route('portfolio.budget.installments.update', $ins) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="step_paid" value="-1">
                                                <input type="hidden" name="redirect_to" value="debts">
                                                <button type="submit"
                                                        class="inline-flex items-center rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-slate-100 transition"
                                                        title="ลดงวดที่จ่ายแล้ว 1 งวด">
                                                    -1 งวด
                                                </button>
                                            </form>
                                        @endif
                                        @if($insLeft > 0)
                                            <form method="POST" action="{{ route('portfolio.budget.installments.update', $ins) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="step_paid" value="1">
                                                <input type="hidden" name="redirect_to" value="debts">
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 hover:bg-emerald-100 transition"
                                                        title="บันทึกจ่ายเพิ่ม 1 งวด">
                                                    ✓ จ่ายงวดนี้ (+1)
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-0.5 shrink-0 pt-0.5">
                                <button @click="editIns = true" type="button"
                                        class="p-1 rounded text-slate-400 hover:text-slate-700 transition" title="แก้ไข">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                <form method="POST" action="{{ route('portfolio.budget.installments.destroy', $ins) }}"
                                      data-confirm="ลบ '{{ $ins->label }}'?" data-confirm-danger="1">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="redirect_to" value="debts">
                                    <button type="submit" class="p-1 rounded text-rose-400 hover:text-rose-600 transition" title="ลบ">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    {{-- Edit mode --}}
                    <form x-show="editIns" x-cloak method="POST"
                          action="{{ route('portfolio.budget.installments.update', $ins) }}" class="space-y-2">
                        @csrf @method('PATCH')
                        <input type="hidden" name="redirect_to" value="debts">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="col-span-2">
                                <label class="block text-[9px] font-semibold text-slate-500">ชื่อรายการ</label>
                                <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-slate-500 mb-0.5">ยอดรวม (฿)</label>
                                <input type="number" step="0.01" min="0" name="total_amount" x-model="ta" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-slate-500 mb-0.5">งวดทั้งหมด</label>
                                <input type="number" min="1" name="total_months" x-model="tm" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-0.5">
                                    <label class="block text-[9px] font-semibold text-slate-500">ผ่อนต่อเดือน (฿)</label>
                                    <button type="button" @click="if(ta && mp && !tm) tm = Math.ceil(ta / mp); else if(ta && tm && !mp) mp = (ta / tm).toFixed(2); else if(mp && tm && !ta) ta = (mp * tm).toFixed(2); else if(ta && tm) mp = (ta / tm).toFixed(2); else if(mp && tm) ta = (mp * tm).toFixed(2); else if(ta && mp) tm = Math.ceil(ta / mp);" class="text-[9px] text-brand-600 hover:text-brand-700 font-semibold cursor-pointer">คำนวณอัตโนมัติ</button>
                                </div>
                                <input type="number" step="0.01" min="0" name="monthly_payment" x-model="mp" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-slate-500">จ่ายไปแล้ว (งวด)</label>
                                <input type="number" min="0" name="paid_months" x-model="pm" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[9px] font-semibold text-slate-500">หมายเหตุ</label>
                                <input type="text" name="notes" value="{{ $ins->notes }}" class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                        </div>
                        <div class="flex justify-end gap-1.5">
                            <button type="button" @click="editIns = false"
                                    class="rounded px-2.5 py-1 text-xs font-semibold bg-slate-200 text-slate-700">ยกเลิก</button>
                            <button type="submit"
                                    class="rounded bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white">บันทึก</button>
                        </div>
                    </form>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-slate-400">
                    <p class="text-sm">ยังไม่มีรายการผ่อน</p>
                    <p class="mt-1 text-xs">กด "เพิ่มรายการผ่อน" เพื่อเริ่มต้น</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── DEBTS ─────────────────────────────────────────────────────────── --}}
        <div x-data="{ addDebtOpen: false }">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">{{ __('app.portfolio.debts.debts') }} & ใบแจ้งยอดล่วงหน้า</h2>
                <button @click="addDebtOpen = !addDebtOpen" type="button"
                        class="flex items-center gap-1.5 rounded-lg border border-brand-200 px-2.5 py-1 text-xs font-semibold text-brand-600 hover:bg-brand-50 transition">
                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    เพิ่มหนี้
                </button>
            </div>

            {{-- Add debt form --}}
            <div x-show="addDebtOpen" x-cloak class="mb-4 rounded-2xl border bg-white p-4 shadow-sm"
                 x-data="{ total: '', mp: '', tm: '' }">
                <p class="mb-3 text-xs font-semibold text-slate-600">เพิ่มหนี้สินใหม่</p>
                <form method="POST" action="{{ route('portfolio.budget.debts.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="month" value="{{ date('Y-m') }}">
                    <input type="hidden" name="redirect_to" value="debts">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">ชื่อหนี้ *</label>
                            <input type="text" name="label" required placeholder="เช่น เงินกู้สหกรณ์"
                                   class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">ยอดรวม (฿)</label>
                            <input type="number" step="0.01" min="0" name="total_amount" x-model="total" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">งวดทั้งหมด (ถ้ามี)</label>
                            <input type="number" min="1" name="total_months" x-model="tm" placeholder="12" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-0.5">
                                <label class="block text-[10px] font-semibold text-slate-500">ผ่อนต่อเดือน (ถ้ามี)</label>
                                <button type="button" @click="if(total && mp && !tm) tm = Math.ceil(total / mp); else if(total && tm && !mp) mp = (total / tm).toFixed(2); else if(mp && tm && !total) total = (mp * tm).toFixed(2); else if(total && tm) mp = (total / tm).toFixed(2); else if(mp && tm) total = (mp * tm).toFixed(2); else if(total && mp) tm = Math.ceil(total / mp);" class="text-[9px] text-brand-600 hover:text-brand-700 font-semibold cursor-pointer">คำนวณอัตโนมัติ</button>
                            </div>
                            <input type="number" step="0.01" min="0" name="monthly_payment" x-model="mp" placeholder="0.00" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">หมายเหตุ</label>
                            <input type="text" name="notes" placeholder="หมายเหตุ (ไม่บังคับ)" class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" @click="addDebtOpen = false"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-700">ยกเลิก</button>
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">เพิ่มหนี้</button>
                    </div>
                </form>
            </div>

            {{-- Debt cards --}}
            <div class="space-y-4">
                @forelse($allDebts as $debt)
                    @if(str_contains($debt->label, 'กยศ'))
                        @include('portfolio.partials.koyoso-debt-card', ['debt' => $debt, 'redirectTo' => 'debts'])
                    @else
                        @php
                            $dTotal  = (float) $debt->payments->sum('amount');
                            $dPaid   = (float) $debt->payments->where('is_paid', true)->sum('amount');
                            $dRemain = (float) $debt->payments->where('is_paid', false)->sum('amount');
                            $dPct    = $dTotal > 0 ? min(100, round($dPaid / $dTotal * 100)) : 0;
                            $futurePayments = $debt->payments->where('month', '>=', date('Y-m'))->sortBy('month')->values();
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-2 shadow-sm"
                             x-data="{ editDebt: false, paymentsOpen: true, addPayment: false,
                                        label: '{{ addslashes($debt->label) }}',
                                        total: '{{ $debt->total_amount }}',
                                        mp: '', tm: '' }">

                            {{-- Header read --}}
                            <div x-show="!editDebt" class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-sm font-bold text-slate-800">{{ $debt->label }}</span>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        คงเหลือ <span class="font-semibold text-rose-600">฿{{ number_format($dRemain, 2) }}</span>
                                        · ทั้งหมด {{ $futurePayments->count() }} งวด
                                        @if($dTotal > 0 && $dPaid > 0)
                                            · จ่ายแล้ว {{ $dPct }}%
                                        @endif
                                    </p>
                                    @if($debt->notes)
                                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $debt->notes }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <button @click="paymentsOpen = !paymentsOpen" type="button"
                                            class="p-1 rounded text-slate-400 hover:text-slate-700 transition" title="ดูงวดชำระ">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </button>
                                    <button @click="editDebt = true" type="button"
                                            class="p-1 rounded text-slate-400 hover:text-slate-700 transition" title="แก้ไข">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                    <form method="POST" action="{{ route('portfolio.budget.debts.destroy', $debt) }}"
                                          data-confirm="ลบ '{{ $debt->label }}' และงวดชำระทั้งหมด?" data-confirm-danger="1">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="month" value="{{ $activeMonth }}">
                                        <input type="hidden" name="redirect_to" value="debts">
                                        <button type="submit" class="p-1 rounded text-rose-400 hover:text-rose-600 transition" title="ลบ">
                                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Header edit --}}
                            <form x-show="editDebt" x-cloak method="POST"
                                  action="{{ route('portfolio.budget.debts.update', $debt) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <input type="hidden" name="redirect_to" value="debts">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="col-span-2">
                                        <label class="block text-[9px] font-semibold text-slate-500 mb-0.5">ชื่อหนี้</label>
                                        <input type="text" name="label" x-model="label" required
                                               class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-slate-500 mb-0.5">ยอดหนี้รวม (฿)</label>
                                        <input type="number" step="0.01" min="0" name="total_amount" x-model="total"
                                               class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-slate-500 mb-0.5">งวดทั้งหมด</label>
                                        <input type="number" min="1" name="total_months" x-model="tm" class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-0.5">
                                            <label class="block text-[9px] font-semibold text-slate-500">ผ่อนต่อเดือน</label>
                                            <button type="button" @click="if(total && mp && !tm) tm = Math.ceil(total / mp); else if(total && tm && !mp) mp = (total / tm).toFixed(2); else if(mp && tm && !total) total = (mp * tm).toFixed(2); else if(total && tm) mp = (total / tm).toFixed(2); else if(mp && tm) total = (mp * tm).toFixed(2); else if(total && mp) tm = Math.ceil(total / mp);" class="text-[9px] text-brand-600 hover:text-brand-700 font-semibold cursor-pointer">คำนวณอัตโนมัติ</button>
                                        </div>
                                        <input type="number" step="0.01" min="0" name="monthly_payment" x-model="mp" class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-slate-500 mb-0.5">หมายเหตุ</label>
                                        <input type="text" name="notes" value="{{ $debt->notes }}" class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-1.5 mt-2">
                                    <button type="button" @click="editDebt = false"
                                            class="rounded px-2.5 py-1 text-xs font-semibold bg-slate-200 text-slate-700">ยกเลิก</button>
                                    <button type="submit"
                                            class="rounded bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white">บันทึก</button>
                                </div>
                            </form>

                            {{-- Payment schedule (collapsible, open by default) --}}
                            <div x-show="paymentsOpen" class="border-t border-slate-100 pt-2 space-y-1.5">
                                @forelse($futurePayments as $fp)
                                @php
                                    $dueDateLabel = \Illuminate\Support\Carbon::parse($fp->month . '-01')->addMonth()->locale('th')->isoFormat('D MMM YYYY');
                                @endphp
                                <div class="flex items-center gap-2 text-[11px] py-0.5" x-data="{ editFp: false, fpAmt: '{{ $fp->amount }}' }">
                                    <div x-show="!editFp" class="flex flex-1 items-center gap-2">
                                        <form method="POST" action="{{ route('portfolio.budget.toggle', ['type' => 'debt-payment', 'id' => $fp->id]) }}" class="shrink-0">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[10px] font-semibold transition {{ $fp->is_paid ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700' }}"
                                                    title="{{ $fp->is_paid ? 'คลิกเพื่อยกเลิกสถานะจ่ายแล้ว' : 'คลิกเพื่อบันทึกว่าจ่ายงวดนี้แล้ว' }}">
                                                @if($fp->is_paid)
                                                    ✓ จ่ายแล้ว
                                                @else
                                                    ○ รอชำระ
                                                @endif
                                            </button>
                                        </form>
                                        <span class="shrink-0 font-medium {{ $fp->is_paid ? 'line-through text-slate-400' : 'text-slate-700' }} w-14">{{ \Illuminate\Support\Carbon::parse($fp->month . '-01')->locale('th')->isoFormat('MMM YY') }}</span>
                                        <span class="shrink-0 text-[10px] text-slate-400">(ครบ {{ $dueDateLabel }})</span>
                                        <span class="flex-1 text-right font-bold {{ $fp->is_paid ? 'line-through text-slate-400' : 'text-slate-800' }}">฿{{ number_format($fp->amount, 2) }}</span>
                                        <button @click="editFp = true" type="button" class="shrink-0 text-slate-400 hover:text-slate-600" title="แก้ไขยอด">
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                        <form method="POST" action="{{ route('portfolio.budget.debt-payments.destroy', $fp) }}"
                                              data-confirm="ลบงวด {{ \Illuminate\Support\Carbon::parse($fp->month . '-01')->locale('th')->isoFormat('MMM YY') }}?">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="month" value="{{ $activeMonth }}">
                                            <input type="hidden" name="redirect_to" value="debts">
                                            <button type="submit" class="shrink-0 text-rose-400 hover:text-rose-600" title="ลบงวด">
                                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                    <form x-show="editFp" x-cloak method="POST"
                                          action="{{ route('portfolio.budget.debt-payments.update', $fp) }}"
                                          class="flex flex-1 items-end gap-1.5">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="month" value="{{ $activeMonth }}">
                                        <input type="hidden" name="redirect_to" value="debts">
                                        <div class="flex-1">
                                            <label class="block text-[9px] text-slate-500">จำนวน (฿)</label>
                                            <input type="number" step="0.01" min="0" name="amount" x-model="fpAmt" required
                                                   class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs">
                                        </div>
                                        <button type="button" @click="editFp = false"
                                                class="rounded px-2 py-0.5 text-[10px] bg-slate-200 text-slate-700 font-semibold shrink-0">ยกเลิก</button>
                                        <button type="submit"
                                                class="rounded bg-brand-600 px-2 py-0.5 text-[10px] text-white font-semibold shrink-0">บันทึก</button>
                                    </form>
                                </div>
                                @empty
                                <p class="text-[11px] text-slate-400 py-1">ยังไม่มีงวดชำระในอนาคต</p>
                                @endforelse

                                {{-- Add payment row --}}
                                <div class="pt-1 border-t border-slate-100">
                                    <button @click="addPayment = !addPayment" type="button"
                                            class="flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:text-brand-700">
                                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                        เพิ่มงวดชำระ
                                    </button>
                                    <div x-show="addPayment" x-cloak class="mt-1.5">
                                        <form method="POST" action="{{ route('portfolio.budget.debt-payments.store') }}"
                                              class="flex flex-wrap items-end gap-1.5">
                                            @csrf
                                            <input type="hidden" name="debt_id" value="{{ $debt->id }}">
                                            <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                                            <input type="hidden" name="redirect_to" value="debts">
                                            <div>
                                                <label class="block text-[9px] text-slate-500">เดือนครบกำหนด</label>
                                                <input type="month" name="month" required
                                                       class="rounded border-slate-300 px-1.5 py-0.5 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] text-slate-500">จำนวน (฿)</label>
                                                <input type="number" step="0.01" min="0" name="amount" required
                                                       placeholder="0.00" class="w-24 rounded border-slate-300 px-1.5 py-0.5 text-xs">
                                            </div>
                                            <button type="submit"
                                                    class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-brand-700">เพิ่ม</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                <div class="rounded-2xl border bg-white p-8 text-center text-slate-400 shadow-sm">
                    <p class="text-sm">ยังไม่มีหนี้สิน</p>
                    <p class="mt-1 text-xs">กด "เพิ่มหนี้" เพื่อเริ่มต้น</p>
                </div>
                @endforelse
            </div>
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

    {{-- ── Monthly schedule table ───────────────────────────────────────── --}}
    <div class="rounded-2xl border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">แผนการจ่ายรายเดือน (เริ่ม พ.ย. 2569)</h2>
                <p class="text-[11px] text-slate-500 mt-0.5">แสดงภาระผ่อนชำระและใบแจ้งยอดล่วงหน้ารายเดือนตามลำดับเวลาปลดหนี้</p>
            </div>
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
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 w-32">{{ __('app.portfolio.debts.col_month') }}</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.col_installments') }}</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.portfolio.debts.col_debts') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 w-44">{{ __('app.portfolio.debts.col_total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $rowIndex = 0; @endphp
                    @foreach ($schedule as $ym => $row)
                    @php
                        $isFirstMonth = ($ym === $startYM);
                        $isNextMonth  = ($ym === $nextYM);
                        $barPct = $maxMonthlyTotal > 0 ? round(($row['total'] / $maxMonthlyTotal) * 100) : 0;
                        $rowIndex++;
                    @endphp
                    <tr class="{{ $isFirstMonth ? 'bg-brand-50/30' : 'hover:bg-slate-50/60' }} transition"
                        x-show="showAll || {{ $rowIndex }} <= 12">
                        <td class="px-5 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium {{ $isFirstMonth ? 'text-brand-700' : 'text-slate-700' }}">{{ \Illuminate\Support\Carbon::parse($ym . '-01')->locale('th')->isoFormat('MMM YY') }}</span>
                                @if ($isFirstMonth)
                                    <span class="inline-flex items-center rounded-full bg-brand-100 px-1.5 py-0.5 text-[9px] font-semibold text-brand-700">เดือนนี้</span>
                                @elseif ($isNextMonth)
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-1.5 py-0.5 text-[9px] font-semibold text-sky-700">เดือนหน้า</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            @if (!empty($row['installments']))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($row['installments'] as $ins)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700">
                                        {{ $ins['label'] }}
                                        <span class="font-medium">฿{{ number_format($ins['amount'], 2) }}</span>
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
                                    <span class="inline-flex items-center gap-1 rounded-full {{ !empty($dp['is_koyoso']) ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700' }} px-2 py-0.5 text-[11px]">
                                        {{ $dp['debt_label'] }}
                                        <span class="font-medium">฿{{ number_format($dp['amount'], 2) }}</span>
                                    </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex flex-col items-end gap-0.5">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Bar visual --}}
                                    <div class="hidden sm:block h-1.5 w-20 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-1.5 rounded-full {{ $isFirstMonth ? 'bg-brand-500' : 'bg-slate-400' }} transition-all" style="width: {{ $barPct }}%"></div>
                                    </div>
                                    <span class="text-right font-semibold {{ $isFirstMonth ? 'text-brand-700' : 'text-slate-900' }}">
                                        ฿{{ number_format($row['non_koyoso_total'] > 0 ? $row['non_koyoso_total'] : $row['total'], 2) }}
                                    </span>
                                </div>
                                @if ($row['non_koyoso_total'] > 0 && abs($row['total'] - $row['non_koyoso_total']) > 0.01)
                                    <span class="text-[10px] text-slate-400">รวมออม กยศ.: ฿{{ number_format($row['total'], 2) }}</span>
                                @endif
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
