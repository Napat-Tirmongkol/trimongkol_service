<x-portfolio-layout>

{{-- Register the calculator Alpine component before Alpine initialises --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('ledgerCalc', () => ({
        calcOpen: false,
        calcTarget: null,
        calcDisplay: '0',
        calcExpr: '',
        calcPrev: null,
        calcOp: null,
        calcWaiting: false,

        openCalc(targetId) {
            this.calcTarget   = targetId;
            this.calcOpen     = true;
            this.calcDisplay  = '0';
            this.calcExpr     = '';
            this.calcPrev     = null;
            this.calcOp       = null;
            this.calcWaiting  = false;
        },

        cn(n) {
            if (this.calcWaiting) {
                this.calcDisplay = String(n);
                this.calcWaiting = false;
            } else {
                this.calcDisplay = this.calcDisplay === '0' ? String(n) : this.calcDisplay + n;
            }
        },

        cd() {
            if (this.calcWaiting) { this.calcDisplay = '0.'; this.calcWaiting = false; return; }
            if (!this.calcDisplay.includes('.')) this.calcDisplay += '.';
        },

        cb() {
            if (this.calcWaiting) return;
            this.calcDisplay = this.calcDisplay.length > 1 ? this.calcDisplay.slice(0, -1) : '0';
        },

        cs() {
            if (this.calcDisplay !== '0') {
                this.calcDisplay = this.calcDisplay.startsWith('-')
                    ? this.calcDisplay.slice(1)
                    : '-' + this.calcDisplay;
            }
        },

        co(op) {
            let v = parseFloat(this.calcDisplay);
            if (this.calcPrev !== null && !this.calcWaiting) {
                v = this.cc(this.calcPrev, v, this.calcOp);
                this.calcDisplay = this.fmt(v);
            }
            this.calcExpr    = this.calcDisplay + ' ' + op;
            this.calcPrev    = parseFloat(this.calcDisplay);
            this.calcOp      = op;
            this.calcWaiting = true;
        },

        cc(a, b, op) {
            if (op === '+') return a + b;
            if (op === '−') return a - b;
            if (op === '×') return a * b;
            if (op === '÷') return b !== 0 ? a / b : 0;
            return b;
        },

        fmt(n) {
            return String(Math.round(n * 1e9) / 1e9);
        },

        ce() {
            if (this.calcPrev === null || this.calcWaiting) return;
            let r = this.cc(this.calcPrev, parseFloat(this.calcDisplay), this.calcOp);
            r = Math.round(r * 1e9) / 1e9;
            this.calcDisplay = this.fmt(r);
            this.calcExpr    = '';
            this.calcPrev    = null;
            this.calcOp      = null;
            this.calcWaiting = false;
        },

        cu() {
            const val = parseFloat(this.calcDisplay) || 0;
            const el  = document.getElementById(this.calcTarget);
            if (el) {
                el.value = val;
                el.dispatchEvent(new Event('input'));
                // Briefly highlight the filled field
                el.classList.add('ring-2', 'ring-brand-400');
                setTimeout(() => el.classList.remove('ring-2', 'ring-brand-400'), 1000);
                el.focus();
            }
            this.calcOpen = false;
        },

        // Keyboard shortcut handler — only active while calculator is open
        ck(e) {
            if (!this.calcOpen) return;
            const k = e.key;
            if (k >= '0' && k <= '9') { this.cn(k); return; }
            if (k === '.')           { this.cd(); return; }
            if (k === 'Backspace')   { this.cb(); return; }
            if (k === 'Escape')      { this.calcOpen = false; return; }
            if (k === 'Enter' || k === '=') { this.ce(); return; }
            if (k === '+')           { this.co('+'); return; }
            if (k === '-')           { this.co('−'); return; }
            if (k === '*')           { this.co('×'); return; }
            if (k === '/') { e.preventDefault(); this.co('÷'); }
        },
    }));
});
</script>

@php
    $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    $monthLabel = function (string $ym) use ($thaiMonths): string {
        [$y, $m] = explode('-', $ym);
        return $thaiMonths[(int) $m] . ' ' . ((int) $y + 543);
    };

    $dayLabel = function (string $ymd) use ($thaiMonths): string {
        $d = \Illuminate\Support\Carbon::parse($ymd);
        return $d->day . ' ' . $thaiMonths[$d->month] . ' ' . ($d->year + 543);
    };

    $categoryLabel = [
        'fixed_expense'    => 'ค่าใช้จ่ายคงที่',
        'variable_expense' => 'ค่าใช้จ่ายผันแปร',
        'saving'           => 'ออมเงิน',
    ];
@endphp

{{-- ── Outer wrapper: owns the calculator singleton ── --}}
<div x-data="ledgerCalc()"
     @open-calculator.window="openCalc($event.detail.target)"
     @keydown.window="ck($event)">

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Page header + month selector --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight" :class="dark ? 'text-slate-100' : 'text-slate-900'">
                {{ __('app.portfolio.ledger.heading') }}
            </h1>
            <p class="mt-1 text-sm" :class="dark ? 'text-slate-400' : 'text-slate-500'">
                {{ __('app.portfolio.ledger.subheading') }}
            </p>
        </div>
        <form method="GET" action="{{ route('portfolio.ledger.index') }}" class="flex-shrink-0">
            <select name="month" onchange="this.form.submit()"
                    class="rounded-lg border px-3 py-2 text-sm font-medium shadow-sm transition focus:outline-none focus:ring-2 focus:ring-brand-500"
                    :class="dark ? 'border-slate-700 bg-slate-800 text-slate-200' : 'border-slate-200 bg-white text-slate-700'">
                @foreach ($allMonths as $m)
                    <option value="{{ $m }}" @selected($m === $activeMonth)>{{ $monthLabel($m) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Summary tiles --}}
    <div class="mb-6 grid grid-cols-3 gap-3 sm:gap-4">
        <div class="rounded-xl border bg-white p-4 text-center shadow-sm">
            <div class="text-xs font-medium text-slate-500">{{ __('app.portfolio.ledger.total_income') }}</div>
            <div class="mt-1 text-lg font-bold tabular-nums text-emerald-600 sm:text-xl">
                ฿{{ number_format($totalIncome, 2) }}
            </div>
        </div>
        <div class="rounded-xl border bg-white p-4 text-center shadow-sm">
            <div class="text-xs font-medium text-slate-500">{{ __('app.portfolio.ledger.total_expense') }}</div>
            <div class="mt-1 text-lg font-bold tabular-nums text-rose-600 sm:text-xl">
                ฿{{ number_format($totalExpense, 2) }}
            </div>
        </div>
        <div class="rounded-xl border bg-white p-4 text-center shadow-sm">
            <div class="text-xs font-medium text-slate-500">{{ __('app.portfolio.ledger.net') }}</div>
            <div class="mt-1 text-lg font-bold tabular-nums sm:text-xl {{ $net >= 0 ? 'text-brand-600' : 'text-rose-600' }}">
                {{ $net >= 0 ? '+' : '' }}฿{{ number_format($net, 2) }}
            </div>
        </div>
    </div>

    {{-- Add entry panel --}}
    <div x-data="{ open: false, newType: 'expense' }" class="mb-6">
        <button type="button" @click="open = !open"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white transition disabled:opacity-60 bg-brand-600 hover:bg-brand-700">
            <svg x-show="!open" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <svg x-show="open" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span x-text="open ? 'ยกเลิก' : '{{ __('app.portfolio.ledger.add_entry') }}'"></span>
        </button>

        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-3 rounded-xl border bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('portfolio.ledger.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">วันที่</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">ประเภท</label>
                        <select name="type" x-model="newType"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="expense">{{ __('app.portfolio.ledger.type_expense') }}</option>
                            <option value="income">{{ __('app.portfolio.ledger.type_income') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">จำนวนเงิน (฿)</label>
                        <div class="flex gap-1">
                            <input id="cal-add" type="number" name="amount" step="0.01" min="0.01"
                                   placeholder="0.00" required
                                   class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('open-calculator', {detail: {target: 'cal-add'}}))"
                                    class="flex-shrink-0 rounded-lg border border-slate-200 px-2.5 py-2 text-slate-500 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-600"
                                    title="เครื่องคิดเลข">
                                @include('portfolio.partials.calc-icon')
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">รายการ / คำอธิบาย</label>
                        <input type="text" name="label" maxlength="200"
                               placeholder="เช่น ค่าข้าวกลางวัน, เงินเดือน" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div x-show="newType === 'expense'" x-cloak>
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                            {{ __('app.portfolio.ledger.link_budget') }}
                            @if ($budgetItemMonth && $budgetItemMonth !== $activeMonth)
                                <span class="font-normal text-amber-600">(จากเดือน {{ $monthLabel($budgetItemMonth) }})</span>
                            @endif
                        </label>
                        <select name="budget_item_id"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">{{ __('app.portfolio.ledger.no_link') }}</option>
                            @foreach ($budgetItems->groupBy('category') as $cat => $items)
                                <optgroup label="{{ $categoryLabel[$cat] ?? $cat }}">
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">หมายเหตุ (ไม่บังคับ)</label>
                        <input type="text" name="notes" maxlength="1000" placeholder=""
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="open = false"
                            class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-300">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Entries list --}}
    @if ($byDate->isEmpty())
        <div class="rounded-xl border-2 border-dashed py-20 text-center"
             :class="dark ? 'border-slate-700' : 'border-slate-200'">
            <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm font-medium" :class="dark ? 'text-slate-500' : 'text-slate-400'">
                {{ __('app.portfolio.ledger.empty') }}
            </p>
        </div>
    @else
        <div class="space-y-5">
            @foreach ($byDate as $date => $dayEntries)
                @php
                    $dayNet = $dayEntries->sum(
                        fn ($e) => $e->type === 'income' ? (float) $e->amount : -(float) $e->amount
                    );
                @endphp
                <div>
                    <div class="mb-2 flex items-center justify-between px-1">
                        <span class="text-sm font-semibold" :class="dark ? 'text-slate-300' : 'text-slate-600'">
                            {{ $dayLabel($date) }}
                        </span>
                        <span class="text-sm font-medium tabular-nums {{ $dayNet >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                            {{ $dayNet >= 0 ? '+' : '' }}฿{{ number_format($dayNet, 2) }}
                        </span>
                    </div>

                    <div class="divide-y overflow-hidden rounded-xl border bg-white shadow-sm">
                        @foreach ($dayEntries as $entry)
                            <div x-data="{ editing: false, eType: '{{ $entry->type }}' }" class="group px-4 py-3">

                                {{-- Display row --}}
                                <div x-show="!editing" class="flex min-h-[2rem] items-center gap-3">
                                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold
                                        {{ $entry->type === 'income'
                                            ? 'bg-emerald-50 text-emerald-600'
                                            : 'bg-rose-50 text-rose-600' }}">
                                        {{ $entry->type === 'income' ? '+' : '−' }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-sm font-medium" :class="dark ? 'text-slate-200' : 'text-slate-800'">
                                                {{ $entry->label }}
                                            </span>
                                            @if ($entry->budgetItem)
                                                <span class="rounded px-1.5 py-0.5 text-[11px] font-medium bg-sky-50 text-sky-700">
                                                    {{ $entry->budgetItem->label }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($entry->notes)
                                            <p class="mt-0.5 truncate text-xs text-slate-400">{{ $entry->notes }}</p>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0 text-sm font-semibold tabular-nums
                                        {{ $entry->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $entry->type === 'income' ? '+' : '−' }}฿{{ number_format($entry->amount, 2) }}
                                    </div>
                                    <div class="flex flex-shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100">
                                        <button type="button" @click="editing = true"
                                                class="rounded p-1.5 text-slate-400 transition hover:bg-brand-50 hover:text-brand-600"
                                                title="แก้ไข">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.172-8.172z"/>
                                            </svg>
                                        </button>
                                        <form method="POST" action="{{ route('portfolio.ledger.destroy', $entry) }}"
                                              data-confirm="{{ __('app.portfolio.ledger.delete_confirm') }}"
                                              data-confirm-danger="1">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="rounded p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                    title="ลบ">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Inline edit form --}}
                                <div x-show="editing" x-cloak>
                                    <form method="POST" action="{{ route('portfolio.ledger.update', $entry) }}" class="space-y-3 py-1">
                                        @csrf @method('PATCH')
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            <div>
                                                <label class="mb-0.5 block text-xs text-slate-500">วันที่</label>
                                                <input type="date" name="date"
                                                       value="{{ $entry->date->format('Y-m-d') }}" required
                                                       class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs text-slate-500">ประเภท</label>
                                                <select name="type" x-model="eType"
                                                        class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                                    <option value="expense">{{ __('app.portfolio.ledger.type_expense') }}</option>
                                                    <option value="income">{{ __('app.portfolio.ledger.type_income') }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs text-slate-500">จำนวนเงิน (฿)</label>
                                                <div class="flex gap-1">
                                                    <input id="cal-{{ $entry->id }}" type="number" name="amount"
                                                           step="0.01" min="0.01"
                                                           value="{{ $entry->amount }}" required
                                                           class="min-w-0 flex-1 rounded border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                                    <button type="button"
                                                            @click="window.dispatchEvent(new CustomEvent('open-calculator', {detail: {target: 'cal-{{ $entry->id }}'}}))"
                                                            class="flex-shrink-0 rounded border border-slate-200 px-2 py-1 text-slate-400 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-600"
                                                            title="เครื่องคิดเลข">
                                                        @include('portfolio.partials.calc-icon')
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs text-slate-500">รายการ</label>
                                                <input type="text" name="label" maxlength="200"
                                                       value="{{ $entry->label }}" required
                                                       class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <div x-show="eType === 'expense'" x-cloak>
                                                <label class="mb-0.5 block text-xs text-slate-500">{{ __('app.portfolio.ledger.link_budget') }}</label>
                                                <select name="budget_item_id"
                                                        class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                                    <option value="">{{ __('app.portfolio.ledger.no_link') }}</option>
                                                    @foreach ($budgetItems->groupBy('category') as $cat => $items)
                                                        <optgroup label="{{ $categoryLabel[$cat] ?? $cat }}">
                                                            @foreach ($items as $item)
                                                                <option value="{{ $item->id }}" @selected($entry->budget_item_id == $item->id)>
                                                                    {{ $item->label }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs text-slate-500">หมายเหตุ</label>
                                                <input type="text" name="notes" maxlength="1000"
                                                       value="{{ $entry->notes ?? '' }}"
                                                       class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end gap-2 pt-1">
                                            <button type="button" @click="editing = false"
                                                    class="rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-300">
                                                ยกเลิก
                                            </button>
                                            <button type="submit"
                                                    class="rounded-lg bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60">
                                                บันทึก
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>{{-- /max-w-5xl --}}

{{-- ════════════════════════════════════════════════════════
     CALCULATOR MODAL — always-dark design (iOS-inspired)
     Uses explicit inline styles to bypass .portfolio-dark overrides.
     ════════════════════════════════════════════════════════ --}}
<style>
/* Scoped to .lc- prefix — unaffected by .portfolio-dark overrides */
.lc-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center; padding: 1rem;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
}
.lc-card {
    position: relative; width: 18rem;
    background: #1c1c1e;
    border-radius: 1.5rem;
    box-shadow: 0 30px 60px -10px rgba(0,0,0,.8), 0 0 0 1px rgba(255,255,255,.06);
    overflow: hidden;
}
.lc-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .75rem 1rem .5rem;
}
.lc-title {
    font-size: .65rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: #636366;
}
.lc-close {
    background: #3a3a3c; border: none; border-radius: 9999px;
    width: 1.625rem; height: 1.625rem; display: flex; align-items: center; justify-content: center;
    color: #aeaeb2; cursor: pointer; font-size: .75rem; transition: background .15s;
}
.lc-close:hover { background: #48484a; color: #fff; }

/* Display */
.lc-display-wrap { padding: .25rem 1rem .75rem; }
.lc-expr {
    text-align: right; font-size: .7rem; color: #48484a;
    font-family: ui-monospace, 'SF Mono', monospace;
    height: 1.1rem; overflow: hidden; white-space: nowrap;
}
.lc-display {
    background: #2c2c2e; border-radius: .875rem;
    padding: .75rem 1rem; text-align: right;
    font-family: ui-monospace, 'SF Mono', monospace;
    font-size: 2.25rem; font-weight: 700; color: #fff;
    letter-spacing: -.025em; overflow: hidden; white-space: nowrap;
    min-height: 3.75rem; display: flex; align-items: center; justify-content: flex-end;
}
.lc-display span { max-width: 100%; overflow: hidden; text-overflow: ellipsis; }

/* Button grid */
.lc-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: .5rem; padding: .5rem 1rem 1rem;
}
.lcb {
    height: 3.25rem; border: none; border-radius: 9999px;
    font-size: 1.1rem; font-weight: 500; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: filter .12s, transform .08s;
    -webkit-tap-highlight-color: transparent; user-select: none;
}
.lcb:active { filter: brightness(1.25); transform: scale(.92); }

.lcb-num { background: #2c2c2e; color: #fff; }
.lcb-fn  { background: #636366; color: #fff; font-weight: 600; }
.lcb-op  { background: #0a84ff; color: #fff; font-weight: 700; font-size: 1.2rem; }
.lcb-eq  { background: #0a84ff; color: #fff; font-weight: 800; font-size: 1.35rem; }

/* Use result */
.lc-use {
    margin: 0 1rem 1rem; display: flex; align-items: center; justify-content: center; gap: .5rem;
    background: #30d158; color: #fff; border: none; border-radius: 9999px;
    width: calc(100% - 2rem); padding: .9rem 1rem;
    font-size: .875rem; font-weight: 700; cursor: pointer;
    transition: filter .15s;
}
.lc-use:hover { filter: brightness(1.08); }
.lc-use-amount { font-family: ui-monospace, 'SF Mono', monospace; font-size: .95rem; }
</style>

<div x-show="calcOpen" x-cloak class="lc-overlay"
     @click.self="calcOpen = false"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    <div class="lc-card"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-90 -translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0">

        {{-- Header --}}
        <div class="lc-header">
            <span class="lc-title">เครื่องคิดเลข</span>
            <button type="button" @click="calcOpen = false" class="lc-close" title="ปิด (Esc)">✕</button>
        </div>

        {{-- Display --}}
        <div class="lc-display-wrap">
            <div class="lc-expr" x-text="calcExpr || ' '"></div>
            <div class="lc-display">
                <span x-text="calcDisplay"></span>
            </div>
        </div>

        {{-- Button grid --}}
        @php
        $buttons = [
            ['C',  "calcDisplay='0';calcExpr='';calcPrev=null;calcOp=null;calcWaiting=false;", 'fn'],
            ['⌫',  "cb()",   'fn'],
            ['%',  "calcDisplay=String(parseFloat(calcDisplay)/100)", 'fn'],
            ['÷',  "co('÷')", 'op'],
            ['7',  "cn('7')", 'num'],
            ['8',  "cn('8')", 'num'],
            ['9',  "cn('9')", 'num'],
            ['×',  "co('×')", 'op'],
            ['4',  "cn('4')", 'num'],
            ['5',  "cn('5')", 'num'],
            ['6',  "cn('6')", 'num'],
            ['−',  "co('−')", 'op'],
            ['1',  "cn('1')", 'num'],
            ['2',  "cn('2')", 'num'],
            ['3',  "cn('3')", 'num'],
            ['+',  "co('+')", 'op'],
            ['±',  "cs()",   'fn'],
            ['0',  "cn('0')", 'num'],
            ['.',  "cd()",   'num'],
            ['=',  "ce()",   'eq'],
        ];
        @endphp

        <div class="lc-grid">
            @foreach ($buttons as [$label, $action, $style])
                <button type="button" @click="{{ $action }}" class="lcb lcb-{{ $style }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Use result --}}
        <button type="button" @click="cu()" class="lc-use">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>ใส่ยอดนี้</span>
            <span class="lc-use-amount"
                  x-text="'฿ ' + parseFloat(calcDisplay).toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2})">
            </span>
        </button>

    </div>
</div>

</div>{{-- /ledgerCalc wrapper --}}
</x-portfolio-layout>
