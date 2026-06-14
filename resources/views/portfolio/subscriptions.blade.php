<x-portfolio-layout>
@php
    $fmtMoney    = fn ($n) => number_format((float) $n, 2);
    $fmtMoneyInt = fn ($n) => number_format((float) $n, 0);

    $thShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
                'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $activeMonthLabel = $thShort[(int) substr($activeMonth, 5)]
        . ' ' . ((int) substr($activeMonth, 0, 4) + 543);
@endphp

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ addOpen: false }">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">ค่าบริการรายเดือน</h1>
            <p class="mt-0.5 text-sm text-slate-500">Subscriptions · ข้อมูลจาก {{ $activeMonthLabel }}</p>
        </div>
        <button @click="addOpen = !addOpen"
                class="flex shrink-0 items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700 transition disabled:opacity-60">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            เพิ่มบริการ
        </button>
    </div>

    {{-- ── Add Form ─────────────────────────────────────────────────────── --}}
    <div x-show="addOpen" x-cloak class="mb-6 rounded-2xl border border-brand-200 bg-brand-50/50 p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-brand-800">เพิ่มค่าบริการรายเดือนใหม่</h3>
        <form method="POST" action="{{ route('portfolio.subscriptions.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="month" value="{{ $activeMonth }}">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อบริการ</label>
                    <input type="text" name="label" required placeholder="เช่น Netflix, AIS, iCloud"
                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ค่าบริการ / เดือน (฿)</label>
                    <input type="number" step="0.01" name="monthly_payment" required placeholder="0.00"
                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">วันที่ตัดเงิน (1–31, เว้นได้)</label>
                    <input type="number" name="billing_day" min="1" max="31" placeholder="เช่น 5"
                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (เช่น บัตร/บัญชีที่หัก)</label>
                    <input type="text" name="notes" placeholder="เช่น หักจากบัตร KBank"
                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="addOpen = false"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-700 hover:bg-slate-100 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="rounded-lg bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition">
                    เพิ่มบริการ
                </button>
            </div>
        </form>
    </div>

    {{-- ── Stats tiles ─────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">ต่อเดือน</p>
            <p class="mt-1.5 text-xl font-extrabold text-brand-700">฿{{ $fmtMoneyInt($monthlyTotal) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">ต่อปี</p>
            <p class="mt-1.5 text-xl font-extrabold text-emerald-700">฿{{ $fmtMoneyInt($annualTotal) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">จำนวนบริการ</p>
            <p class="mt-1.5 text-xl font-extrabold text-slate-900">{{ $count }}</p>
            <p class="text-[10px] text-slate-500">รายการ</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">เฉลี่ย/บริการ</p>
            <p class="mt-1.5 text-xl font-extrabold text-slate-700">฿{{ $fmtMoneyInt($avgPerService) }}</p>
            <p class="text-[10px] text-slate-500">ต่อเดือน</p>
        </div>
    </div>

    {{-- ── Empty state ─────────────────────────────────────────────────── --}}
    @if ($subscriptions->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <p class="mt-3 text-sm font-medium text-slate-600">ยังไม่มีค่าบริการรายเดือน</p>
            <p class="mt-1 text-xs text-slate-400">
                กดปุ่ม "เพิ่มบริการ" ด้านบน หรือเพิ่มผ่านหน้า
                <a href="{{ route('portfolio.budget.index') }}" class="text-brand-600 underline hover:text-brand-700">แผนรายเดือน</a>
            </p>
        </div>
    @else

    {{-- ── Two-column: calendar + list ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Calendar: billing day groups ──────────────────────────────── --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-slate-800">ปฏิทินตัดเงิน</h2>

                <div class="space-y-0">
                    @foreach ($byDay as $day => $subs)
                    @php $dayTotal = $subs->sum(fn ($s) => (float) $s->monthly_payment); @endphp
                    <div class="flex gap-2.5 py-3 @if(!$loop->first) border-t border-slate-100 @endif">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                            {{ $day }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-700">ตัดเงินวันที่ {{ $day }}</span>
                                <span class="shrink-0 text-xs font-bold text-slate-900">฿{{ $fmtMoneyInt($dayTotal) }}</span>
                            </div>
                            <div class="mt-1.5 space-y-1">
                                @foreach ($subs as $s)
                                <div class="flex items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-xs text-slate-600">{{ $s->label }}</span>
                                    <span class="shrink-0 text-xs font-semibold text-slate-800">฿{{ $fmtMoney($s->monthly_payment) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach

                    {{-- Unscheduled --}}
                    @if ($unscheduled->isNotEmpty())
                    <div class="flex gap-2.5 py-3 @if($byDay->isNotEmpty()) border-t border-slate-100 @endif">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-500">
                            —
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-500">ไม่ระบุวันตัดเงิน</span>
                                <span class="shrink-0 text-xs font-bold text-slate-700">฿{{ $fmtMoneyInt($unscheduled->sum(fn ($s) => (float) $s->monthly_payment)) }}</span>
                            </div>
                            <div class="mt-1.5 space-y-1">
                                @foreach ($unscheduled as $s)
                                <div class="flex items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-xs text-slate-600">{{ $s->label }}</span>
                                    <span class="shrink-0 text-xs font-semibold text-slate-800">฿{{ $fmtMoney($s->monthly_payment) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Footer total --}}
                <div class="mt-3 border-t border-slate-200 pt-3 flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">รวม / เดือน</span>
                    <span class="text-sm font-extrabold text-slate-900">฿{{ $fmtMoney($monthlyTotal) }}</span>
                </div>
            </div>
        </div>

        {{-- Full list with CRUD ──────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-3">
            @foreach ($subscriptions as $sub)
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"
                 x-data="{
                     editing: false,
                     label: '{{ addslashes($sub->label) }}',
                     monthly_payment: {{ $sub->monthly_payment }},
                     billing_day: '{{ $sub->billing_day ?? '' }}',
                     notes: '{{ addslashes($sub->notes ?? '') }}'
                 }">

                {{-- Read mode ─────────────────────────────────────────── --}}
                <div x-show="!editing" class="flex items-start gap-3">
                    {{-- Service icon circle --}}
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700 uppercase">
                        {{ mb_substr($sub->label, 0, 2) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- Row 1: name + actions (name wraps freely, actions pinned right) --}}
                        <div class="flex items-start justify-between gap-2">
                            <span class="min-w-0 text-sm font-semibold text-slate-800 leading-snug break-words">{{ $sub->label }}</span>
                            <div class="flex shrink-0 items-center gap-0.5">
                                <button @click="editing = true"
                                        class="p-1 text-slate-400 hover:text-slate-700 transition rounded">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('portfolio.subscriptions.destroy', $sub) }}"
                                      data-confirm="ลบบริการ '{{ $sub->label }}'?" data-confirm-danger="1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1 text-rose-400 hover:text-rose-600 transition rounded">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Row 2: billing badge (left) + price (right) --}}
                        <div class="mt-1.5 flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                            @if ($sub->billing_day)
                            <span class="inline-flex shrink-0 items-center rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">
                                ตัดเงินวันที่ {{ $sub->billing_day }}
                            </span>
                            @else
                            <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                ไม่ระบุวัน
                            </span>
                            @endif
                            <span class="shrink-0 whitespace-nowrap text-sm font-bold text-slate-900">
                                ฿{{ $fmtMoney($sub->monthly_payment) }}<span class="ml-1 text-[10px] font-normal text-slate-400">/ เดือน</span>
                            </span>
                        </div>

                        @if ($sub->notes)
                        <p class="mt-1.5 text-[10px] text-slate-500 break-words">{{ $sub->notes }}</p>
                        @endif
                    </div>
                </div>

                {{-- Edit mode ─────────────────────────────────────────── --}}
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('portfolio.subscriptions.update', $sub) }}"
                          class="space-y-2 bg-slate-50 rounded-lg p-2.5 border border-slate-200">
                        @csrf @method('PATCH')
                        <input type="text" name="label" x-model="label" required
                               class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[9px] text-slate-500 mb-0.5">฿/เดือน</label>
                                <input type="number" step="0.01" name="monthly_payment" x-model="monthly_payment" required
                                       class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] text-slate-500 mb-0.5">วันที่ตัดเงิน (1–31)</label>
                                <input type="number" name="billing_day" x-model="billing_day" min="1" max="31"
                                       placeholder="ว่างได้" class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                            </div>
                        </div>
                        <input type="text" name="notes" x-model="notes" placeholder="หมายเหตุ"
                               class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                        <div class="flex justify-end gap-1.5">
                            <button type="button" @click="editing = false"
                                    class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                    class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold hover:bg-brand-700">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

    </div>
    @endif

</div>
</x-portfolio-layout>
