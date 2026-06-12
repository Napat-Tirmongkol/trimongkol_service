<x-portfolio-layout>
    @php
        $fmtMoney = fn ($n) => number_format((float) $n, 2);

        $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];

        $formatThaiMonth = function ($ym) use ($thaiMonths) {
            $time = strtotime($ym . '-01');
            $m = (int) date('m', $time);
            $y = (int) date('Y', $time) + 543; // BE Era
            return $thaiMonths[$m] . ' ' . $y;
        };
    @endphp

    <div class="py-8" x-data="{
        addIncomeOpen: false,
        addFixedOpen: false,
        addVariableOpen: false,
        addSavingOpen: false,
        addInstallmentOpen: false,
        addSubscriptionOpen: false
    }">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            
            {{-- Header & Dropdown & Reset --}}
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('app.portfolio.budget.heading') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ __('app.portfolio.budget.subheading') }}</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Dropdown history --}}
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-slate-700">เลือกเดือน:</label>
                        <select onchange="window.location.href = '{{ route('portfolio.budget.index') }}?month=' + this.value"
                                class="rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach($allMonths as $m)
                                <option value="{{ $m }}" {{ $activeMonth === $m ? 'selected' : '' }}>
                                    {{ $formatThaiMonth($m) }}
                                </option>
                            @endforeach
                            @if(!$allMonths->contains($activeMonth))
                                <option value="{{ $activeMonth }}" selected>
                                    {{ $formatThaiMonth($activeMonth) }} (ปัจจุบัน)
                                </option>
                            @endif
                        </select>
                    </div>

                    {{-- New Month Reset Button --}}
                    <form method="POST" action="{{ route('portfolio.budget.reset') }}" 
                          data-confirm="ต้องการเริ่มเดือนใหม่? ระบบจะคำนวณเดือนถัดไป คัดลอกรายการแผนงานทั้งหมด บวกรอบผ่อนชำระที่เช็คไว้ และรีเซ็ตสถานะติ๊กชำระเงิน" 
                          data-confirm-danger="0">
                        @csrf
                        <input type="hidden" name="current_month" value="{{ $activeMonth }}">
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            เริ่มเดือนใหม่
                        </button>
                    </form>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid gap-4 sm:grid-cols-3">
                {{-- Income Card --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-1">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">รายได้รวมประจำเดือน</div>
                    <div class="text-3xl font-extrabold text-slate-900">
                        ฿{{ $fmtMoney($incomeTotal) }}
                    </div>
                    <div class="text-[11px] text-slate-500">
                        จากแหล่งรายได้ทั้งหมด {{ $incomes->count() }} รายการ
                    </div>
                </div>

                {{-- Total Expenses Card --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-1">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">แผนรายจ่ายรวม</div>
                    <div class="text-3xl font-extrabold text-slate-900">
                        ฿{{ $fmtMoney($totalExpenses) }}
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Fixed: ฿{{ $fmtMoney($fixedTotal) }} | Variable: ฿{{ $fmtMoney($variableTotal) }} | Savings: ฿{{ $fmtMoney($savingsTotal) }}
                    </div>
                </div>

                {{-- Remaining Balance Card --}}
                <div class="rounded-2xl border {{ $remainingAmount >= 0 ? 'border-emerald-200 bg-emerald-50/50' : 'border-rose-200 bg-rose-50/50' }} p-5 space-y-1">
                    <div class="text-xs font-semibold uppercase tracking-wider {{ $remainingAmount >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">คงเหลือตามแผน</div>
                    <div class="text-3xl font-extrabold {{ $remainingAmount >= 0 ? 'text-emerald-900' : 'text-rose-900' }}">
                        ฿{{ $fmtMoney($remainingAmount) }}
                    </div>
                    <div class="text-[11px] {{ $remainingAmount >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $remainingAmount >= 0 ? 'งบประมาณสมดุลดี' : 'แผนรายจ่ายเกินกว่ารายได้!' }}
                    </div>
                </div>
            </div>

            {{-- 3-Column Spreadsheet Layout --}}
            <div class="grid gap-6 lg:grid-cols-3">
                
                {{-- COLUMN 1: EXPENSES --}}
                <div class="space-y-6">
                    
                    {{-- 1.1 FIXED EXPENSES --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">ค่าใช้จ่ายคงที่</h3>
                                <p class="text-xs text-slate-500">รายจ่ายจำเป็นที่ต้องจ่ายสม่ำเสมอ</p>
                            </div>
                            <button @click="addFixedOpen = !addFixedOpen" class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Add Fixed Expense Form --}}
                        <div x-show="addFixedOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                            <form method="POST" action="{{ route('portfolio.budget.items.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="category" value="fixed_expense">
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">รายการ</label>
                                    <input type="text" name="label" required placeholder="เช่น ค่าเช่าบ้าน, ค่าเทอม"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">จำนวนเงิน (บาท)</label>
                                    <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (ไม่จำเป็น)</label>
                                    <input type="text" name="notes" placeholder="ข้อมูลเพิ่มเติม"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addFixedOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มรายการ</button>
                                </div>
                            </form>
                        </div>

                        {{-- List of Fixed Expenses --}}
                        <div class="divide-y divide-slate-100">
                            @foreach($fixedExpensesList as $item)
                                <div class="py-2.5 flex items-center justify-between gap-3"
                                     x-data="{ editing: false, label: '{{ addslashes($item->label) }}', amount: {{ $item->amount }}, notes: '{{ addslashes($item->notes) }}' }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <form method="POST" action="{{ route('portfolio.budget.toggle', ['type' => 'item', 'id' => $item->id]) }}">
                                            @csrf
                                            <input type="checkbox" {{ $item->is_checked ? 'checked' : '' }}
                                                   @change="$el.form.submit()"
                                                   class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        </form>
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800 {{ $item->is_checked ? 'line-through text-slate-400' : '' }}">
                                                {{ $item->label }}
                                            </span>
                                            @if($item->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 whitespace-nowrap {{ $item->is_checked ? 'text-slate-400' : '' }}">
                                            ฿{{ $fmtMoney($item->amount) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('portfolio.budget.items.destroy', $item) }}" data-confirm="ต้องการลบรายการนี้?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Edit mode --}}
                                    <div x-show="editing" x-cloak class="w-full">
                                        <form method="POST" action="{{ route('portfolio.budget.items.update', $item) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="หมายเหตุ">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            {{-- READ-ONLY: Subscriptions Sum (Auto) --}}
                            @if($subscriptionsPaymentSum > 0)
                                <div class="py-2.5 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-4 w-4 flex items-center justify-center text-[10px] font-bold text-slate-400 bg-slate-100 rounded border border-slate-200">A</span>
                                        <div>
                                            <span class="text-sm font-medium text-slate-700">ค่าบริการรายเดือน (Subscript)</span>
                                            <p class="text-[10px] text-slate-400">คำนวณจากตาราง subscriptions ด้านขวา</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">
                                        ฿{{ $fmtMoney($subscriptionsPaymentSum) }}
                                    </span>
                                </div>
                            @endif

                            {{-- READ-ONLY: Installments Sum (Auto) --}}
                            @if($installmentsPaymentSum > 0)
                                <div class="py-2.5 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-4 w-4 flex items-center justify-center text-[10px] font-bold text-slate-400 bg-slate-100 rounded border border-slate-200">A</span>
                                        <div>
                                            <span class="text-sm font-medium text-slate-700">หนี้สินรายเดือน (ค่าผ่อนของ)</span>
                                            <p class="text-[10px] text-slate-400">คำนวณจากตารางหนี้สินด้านขวา</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">
                                        ฿{{ $fmtMoney($installmentsPaymentSum) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Total Fixed Expenses --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900">
                            <span>รวมค่าใช้จ่ายคงที่</span>
                            <span>฿{{ $fmtMoney($fixedTotal) }}</span>
                        </div>
                    </div>

                    {{-- 1.2 VARIABLE EXPENSES --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">ค่าใช้จ่ายผันแปร</h3>
                                <p class="text-xs text-slate-500">รายจ่ายยืดหยุ่น เช่น อาหาร ช้อปปิ้ง ท่องเที่ยว</p>
                            </div>
                            <button @click="addVariableOpen = !addVariableOpen" class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Add Variable Expense Form --}}
                        <div x-show="addVariableOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                            <form method="POST" action="{{ route('portfolio.budget.items.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="category" value="variable_expense">
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">รายการ</label>
                                    <input type="text" name="label" required placeholder="เช่น ค่าอาหาร, สังสรรค์"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">จำนวนเงิน (บาท)</label>
                                    <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (ไม่จำเป็น)</label>
                                    <input type="text" name="notes" placeholder="ข้อมูลเพิ่มเติม"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addVariableOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มรายการ</button>
                                </div>
                            </form>
                        </div>

                        {{-- List of Variable Expenses --}}
                        <div class="divide-y divide-slate-100">
                            @foreach($variableExpensesList as $item)
                                <div class="py-2.5 flex items-center justify-between gap-3"
                                     x-data="{ editing: false, label: '{{ addslashes($item->label) }}', amount: {{ $item->amount }}, notes: '{{ addslashes($item->notes) }}' }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <form method="POST" action="{{ route('portfolio.budget.toggle', ['type' => 'item', 'id' => $item->id]) }}">
                                            @csrf
                                            <input type="checkbox" {{ $item->is_checked ? 'checked' : '' }}
                                                   @change="$el.form.submit()"
                                                   class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        </form>
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800 {{ $item->is_checked ? 'line-through text-slate-400' : '' }}">
                                                {{ $item->label }}
                                            </span>
                                            @if($item->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 whitespace-nowrap {{ $item->is_checked ? 'text-slate-400' : '' }}">
                                            ฿{{ $fmtMoney($item->amount) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('portfolio.budget.items.destroy', $item) }}" data-confirm="ต้องการลบรายการนี้?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Edit mode --}}
                                    <div x-show="editing" x-cloak class="w-full">
                                        <form method="POST" action="{{ route('portfolio.budget.items.update', $item) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="หมายเหตุ">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Total Variable Expenses --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900">
                            <span>รวมค่าใช้จ่ายผันแปร</span>
                            <span>฿{{ $fmtMoney($variableTotal) }}</span>
                        </div>
                    </div>
                </div>

                {{-- COLUMN 2: INCOME SOURCES & SAVINGS --}}
                <div class="space-y-6">
                    
                    {{-- 2.1 INCOME SOURCES --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">แหล่งรายได้</h3>
                                <p class="text-xs text-slate-500">เงินเดือน, OT, รายได้เสริมต่างๆ ในเดือนนี้</p>
                            </div>
                            <button @click="addIncomeOpen = !addIncomeOpen" class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Add Income Source Form --}}
                        <div x-show="addIncomeOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                            <form method="POST" action="{{ route('portfolio.budget.income.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">ประเภทรายได้</label>
                                    <input type="text" name="label" required placeholder="เช่น เงินเดือน, OT, ขายของออนไลน์"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">จำนวนเงิน (บาท)</label>
                                    <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (ไม่จำเป็น)</label>
                                    <input type="text" name="notes" placeholder="ข้อมูลเพิ่มเติม"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addIncomeOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มรายได้</button>
                                </div>
                            </form>
                        </div>

                        {{-- List of Income Sources --}}
                        <div class="divide-y divide-slate-100">
                            @forelse($incomes as $inc)
                                <div class="py-2.5 flex items-center justify-between gap-3"
                                     x-data="{ editing: false, label: '{{ addslashes($inc->label) }}', amount: {{ $inc->amount }}, notes: '{{ addslashes($inc->notes) }}' }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800">
                                                {{ $inc->label }}
                                            </span>
                                            @if($inc->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $inc->notes }}</p>
                                            @endif
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 whitespace-nowrap">
                                            ฿{{ $fmtMoney($inc->amount) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('portfolio.budget.income.destroy', $inc) }}" data-confirm="ต้องการลบแหล่งรายได้นี้?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Edit mode --}}
                                    <div x-show="editing" x-cloak class="w-full">
                                        <form method="POST" action="{{ route('portfolio.budget.income.update', $inc) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="หมายเหตุ">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="py-4 text-center text-xs text-slate-400">ยังไม่มีข้อมูลรายได้ในเดือนนี้ กดปุ่ม "+" เพื่อเริ่มต้น</div>
                            @endforelse
                        </div>

                        {{-- Total Income --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900">
                            <span>รายได้รวม</span>
                            <span>฿{{ $fmtMoney($incomeTotal) }}</span>
                        </div>
                    </div>
                    
                    {{-- 2.2 SAVINGS & INVESTMENTS --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">เงินออม / ลงทุน</h3>
                                <p class="text-xs text-slate-500">เงินเก็บหรือการแบ่งไปต่อเงินในอนาคต</p>
                            </div>
                            <button @click="addSavingOpen = !addSavingOpen" class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Add Saving Form --}}
                        <div x-show="addSavingOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                            <form method="POST" action="{{ route('portfolio.budget.items.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="category" value="saving">
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">รายการ</label>
                                    <input type="text" name="label" required placeholder="เช่น ออมเงินแต่งงาน, ฝากกับแฟน"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">จำนวนเงิน (บาท)</label>
                                    <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (ไม่จำเป็น)</label>
                                    <input type="text" name="notes" placeholder="ข้อมูลเพิ่มเติม"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addSavingOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มรายการ</button>
                                </div>
                            </form>
                        </div>

                        {{-- List of Savings --}}
                        <div class="divide-y divide-slate-100">
                            @foreach($savingsList as $item)
                                <div class="py-2.5 flex items-center justify-between gap-3"
                                     x-data="{ editing: false, label: '{{ addslashes($item->label) }}', amount: {{ $item->amount }}, notes: '{{ addslashes($item->notes) }}' }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <form method="POST" action="{{ route('portfolio.budget.toggle', ['type' => 'item', 'id' => $item->id]) }}">
                                            @csrf
                                            <input type="checkbox" {{ $item->is_checked ? 'checked' : '' }}
                                                   @change="$el.form.submit()"
                                                   class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        </form>
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800 {{ $item->is_checked ? 'line-through text-slate-400' : '' }}">
                                                {{ $item->label }}
                                            </span>
                                            @if($item->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 whitespace-nowrap {{ $item->is_checked ? 'text-slate-400' : '' }}">
                                            ฿{{ $fmtMoney($item->amount) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('portfolio.budget.items.destroy', $item) }}" data-confirm="ต้องการลบรายการนี้?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Edit mode --}}
                                    <div x-show="editing" x-cloak class="w-full">
                                        <form method="POST" action="{{ route('portfolio.budget.items.update', $item) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="หมายเหตุ">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Total Savings --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900">
                            <span>รวมเงินออม/ลงทุน</span>
                            <span>฿{{ $fmtMoney($savingsTotal) }}</span>
                        </div>
                    </div>
                </div>

                {{-- COLUMN 3: INSTALLMENT DEBTS & SUBSCRIPTIONS --}}
                <div class="space-y-6">
                    
                    {{-- 3.1 DEBTS & INSTALLMENTS --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">หนี้สิน / ผ่อนของ</h3>
                                <p class="text-xs text-slate-500">ติดตามรายการชำระผ่อนจ่ายรายงวด</p>
                            </div>
                            <button @click="addInstallmentOpen = !addInstallmentOpen" class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Add Installment Form --}}
                        <div x-show="addInstallmentOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                            <form method="POST" action="{{ route('portfolio.budget.installments.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อรายการหนี้</label>
                                    <input type="text" name="label" required placeholder="เช่น S24 Ultra, SpayLater"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ยอดผ่อน / เดือน</label>
                                        <input type="number" step="0.01" name="monthly_payment" required placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ยอดหนี้เต็มทั้งหมด</label>
                                        <input type="number" step="0.01" name="total_amount" required placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">จำนวนงวดทั้งหมด</label>
                                        <input type="number" name="total_months" required placeholder="เช่น 24"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ผ่อนไปแล้วกี่งวด</label>
                                        <input type="number" name="paid_months" value="0" required placeholder="เช่น 12"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (ไม่จำเป็น)</label>
                                    <input type="text" name="notes" placeholder="เช่น สัญญาเงินกู้ เลขที่บัญชี"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addInstallmentOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มหนี้สิน</button>
                                </div>
                            </form>
                        </div>

                        {{-- List of Installments --}}
                        <div class="space-y-4">
                            @foreach($installments as $inst)
                                @php
                                    $progress = $inst->progressPercent();
                                    $remaining = $inst->remainingBalance();
                                @endphp
                                <div class="rounded-xl border border-slate-150 bg-slate-50/50 p-4 space-y-3"
                                     x-data="{ 
                                         editing: false, 
                                         label: '{{ addslashes($inst->label) }}', 
                                         monthly_payment: {{ $inst->monthly_payment }}, 
                                         total_amount: {{ $inst->total_amount }}, 
                                         total_months: {{ $inst->total_months }}, 
                                         paid_months: {{ $inst->paid_months }}, 
                                         notes: '{{ addslashes($inst->notes) }}' 
                                     }">
                                    
                                    {{-- Standard read-only view --}}
                                    <div x-show="!editing" class="space-y-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <form method="POST" action="{{ route('portfolio.budget.toggle', ['type' => 'installment', 'id' => $inst->id]) }}">
                                                    @csrf
                                                    <input type="checkbox" {{ $inst->is_checked ? 'checked' : '' }}
                                                           @change="$el.form.submit()"
                                                           class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                </form>
                                                <div>
                                                    <span class="text-sm font-bold text-slate-800 {{ $inst->is_checked ? 'line-through text-slate-400' : '' }}">
                                                        {{ $inst->label }}
                                                    </span>
                                                    <p class="text-[10px] text-slate-500">
                                                        ยอดต่อเดือน: ฿{{ $fmtMoney($inst->monthly_payment) }} (งวด {{ $inst->paid_months }}/{{ $inst->total_months }})
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <button @click="editing = true" class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <form method="POST" action="{{ route('portfolio.budget.installments.destroy', $inst) }}" data-confirm="ต้องการลบรายการหนี้สินนี้?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="space-y-1">
                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-brand-600 h-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                                            </div>
                                            <div class="flex justify-between text-[10px] text-slate-500">
                                                <span>ผ่อนชำระแล้ว {{ $progress }}%</span>
                                                <span>ยอดเหลือ: ฿{{ $fmtMoney($remaining) }} / ฿{{ $fmtMoney($inst->total_amount) }}</span>
                                            </div>
                                        </div>

                                        @if($inst->notes)
                                            <p class="text-[10px] text-slate-500 bg-white border border-slate-100 p-1.5 rounded">{{ $inst->notes }}</p>
                                        @endif
                                    </div>

                                    {{-- Edit Form --}}
                                    <div x-show="editing" x-cloak>
                                        <form method="POST" action="{{ route('portfolio.budget.installments.update', $inst) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-600 mb-0.5">รายการ</label>
                                                <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            </div>
                                            <div class="grid gap-2 grid-cols-2">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">ยอดต่อเดือน</label>
                                                    <input type="number" step="0.01" name="monthly_payment" x-model="monthly_payment" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">ยอดหนี้รวม</label>
                                                    <input type="number" step="0.01" name="total_amount" x-model="total_amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                            </div>
                                            <div class="grid gap-2 grid-cols-2">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">งวดรวม</label>
                                                    <input type="number" name="total_months" x-model="total_months" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">ส่งแล้วกี่งวด</label>
                                                    <input type="number" name="paid_months" x-model="paid_months" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-600 mb-0.5">หมายเหตุ</label>
                                                <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            </div>
                                            <div class="flex justify-end gap-1.5 pt-1">
                                                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3.2 SUBSCRIPTIONS --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">ค่าบริการรายเดือน</h3>
                                <p class="text-xs text-slate-500">Subscriptions (Netflix, เน็ตมือถือ, Cloud)</p>
                            </div>
                            <button @click="addSubscriptionOpen = !addSubscriptionOpen" class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Add Subscription Form --}}
                        <div x-show="addSubscriptionOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                            <form method="POST" action="{{ route('portfolio.budget.subscriptions.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อบริการ</label>
                                    <input type="text" name="label" required placeholder="เช่น YouTube Premium, Netflix"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ค่าบริการ / เดือน</label>
                                        <input type="number" step="0.01" name="monthly_payment" required placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">วันที่หักเงิน (1-31)</label>
                                        <input type="number" name="billing_day" placeholder="เช่น 15 (เว้นได้)"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">ข้อมูลเพิ่มเติม (เช่น เลขบัญชีหักเงิน)</label>
                                    <input type="text" name="notes" placeholder="เช่น โดนหักจากบัตรธนาคารกรุงเทพ"
                                           class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addSubscriptionOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มบริการ</button>
                                </div>
                            </form>
                        </div>

                        {{-- List of Subscriptions --}}
                        <div class="divide-y divide-slate-100">
                            @foreach($subscriptions as $sub)
                                <div class="py-3 flex items-center justify-between gap-3"
                                     x-data="{ editing: false, label: '{{ addslashes($sub->label) }}', monthly_payment: {{ $sub->monthly_payment }}, billing_day: '{{ $sub->billing_day }}', notes: '{{ addslashes($sub->notes) }}' }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <form method="POST" action="{{ route('portfolio.budget.toggle', ['type' => 'subscription', 'id' => $sub->id]) }}">
                                            @csrf
                                            <input type="checkbox" {{ $sub->is_checked ? 'checked' : '' }}
                                                   @change="$el.form.submit()"
                                                   class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        </form>
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800 {{ $sub->is_checked ? 'line-through text-slate-400' : '' }}">
                                                {{ $sub->label }}
                                            </span>
                                            <div class="flex flex-col text-[10px] text-slate-500 mt-0.5">
                                                @if($sub->billing_day)
                                                    <span>ตัดเงินทุกวันที่ {{ $sub->billing_day }} ของเดือน</span>
                                                @endif
                                                @if($sub->notes)
                                                    <span class="text-slate-400 truncate">{{ $sub->notes }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 whitespace-nowrap {{ $sub->is_checked ? 'text-slate-400' : '' }}">
                                            ฿{{ $fmtMoney($sub->monthly_payment) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('portfolio.budget.subscriptions.destroy', $sub) }}" data-confirm="ต้องการลบค่าบริการรายเดือนนี้?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Edit mode --}}
                                    <div x-show="editing" x-cloak class="w-full">
                                        <form method="POST" action="{{ route('portfolio.budget.subscriptions.update', $sub) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                            <div class="grid gap-2 grid-cols-2">
                                                <input type="number" step="0.01" name="monthly_payment" x-model="monthly_payment" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ค่าบริการ">
                                                <input type="number" name="billing_day" x-model="billing_day" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="วันที่ดึงเงิน">
                                            </div>
                                            <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ข้อมูลเพิ่มเติม">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-portfolio-layout>
