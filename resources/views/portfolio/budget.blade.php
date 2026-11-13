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
        payslipModalOpen: false,
        payslipForm: {
            month: '{{ $activeMonth }}',
            company: 'บจก. ดูดีพ',
            base_salary: 30000,
            ot: 3000,
            bonus: 0,
            social_security: 875,
            tax: 314,
            other_deduct: 0,
            notes: ''
        },
        openPayslipModal(initialData = null) {
            if (initialData) {
                this.payslipForm = Object.assign({}, this.payslipForm, initialData);
            }
            this.payslipModalOpen = true;
        },
        calculateGross() {
            let b = parseFloat(this.payslipForm.base_salary) || 0;
            let o = parseFloat(this.payslipForm.ot) || 0;
            let bn = parseFloat(this.payslipForm.bonus) || 0;
            return b + o + bn;
        },
        calculateDeductions() {
            let sso = parseFloat(this.payslipForm.social_security) || 0;
            let tax = parseFloat(this.payslipForm.tax) || 0;
            let od = parseFloat(this.payslipForm.other_deduct) || 0;
            return sso + tax + od;
        },
        calculateNet() {
            return Math.max(0, this.calculateGross() - this.calculateDeductions());
        },
        formatNum(n) {
            return Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        addIncomeOpen: false,
        addFixedOpen: false,
        addVariableOpen: false,
        addSavingOpen: false,
        addInstallmentOpen: false,
        addSubscriptionOpen: false,
        addDebtOpen: false,
        totals: {
            incomeTotal: {{ $incomeTotal }},
            fixedTotal: {{ $fixedTotal }},
            variableTotal: {{ $variableTotal }},
            savingsTotal: {{ $savingsTotal }},
            totalExpenses: {{ $totalExpenses }},
            remainingAmount: {{ $remainingAmount }},
            actualFixedTotal: {{ $actualFixedTotal }},
            actualVariableTotal: {{ $actualVariableTotal }},
            actualSavingsTotal: {{ $actualSavingsTotal }},
            actualExpensesTotal: {{ $actualExpensesTotal }},
            actualRemainingAmount: {{ $actualRemainingAmount }},
            unallocatedAmount: {{ $unallocatedAmount }},
            plannedFixedItem: {{ (float) $fixedExpensesList->sum('amount') }},
            plannedInstallments: {{ $installmentsPaymentSum }},
            plannedSubscriptions: {{ $subscriptionsPaymentSum }},
            plannedDebts: {{ $debtPaymentsSum }},
            actualFixedItem: {{ $actualFixedBudgetItemSum }},
            actualInstallments: {{ $actualInstallmentsPaymentSum }},
            actualSubscriptions: {{ $actualSubscriptionsPaymentSum }},
            actualDebts: {{ $actualDebtPaymentsSum }},
            plannedNonKoyosoDebts: {{ $nonKoyosoDebtPaymentsSum }},
            actualNonKoyosoDebts: {{ $actualNonKoyosoDebtPaymentsSum }},
            emergencyFundTotal: {{ $emergencyFundTotal }},
            emergencyFundGoal: {{ $emergencyFundGoal }}
        },
        chartType: 'planned',
        fmtMoney(val) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
        },
        async toggleCheck(url) {
            try {
                let response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (response.ok) {
                    let data = await response.json();
                    if (data.success && data.totals) {
                        this.totals = data.totals;
                    }
                    return data.is_checked;
                }
            } catch (err) {
                console.error(err);
            }
        },
        getSegments(type) {
            let rawData = [
                { label: 'ค่าใช้จ่ายคงที่ทั่วไป', val: parseFloat(this.totals.plannedFixedItem || 0), actualVal: parseFloat(this.totals.actualFixedItem || 0), color: '#6366f1' },
                { label: 'รายจ่ายผันแปร', val: parseFloat(this.totals.variableTotal || 0), actualVal: parseFloat(this.totals.actualVariableTotal || 0), color: '#f59e0b' },
                { label: 'เงินออม / ลงทุน', val: parseFloat(this.totals.savingsTotal || 0), actualVal: parseFloat(this.totals.actualSavingsTotal || 0), color: '#10b981' },
                { label: 'ผ่อนชำระ', val: parseFloat(this.totals.plannedInstallments || 0), actualVal: parseFloat(this.totals.actualInstallments || 0), color: '#3b82f6' },
                { label: 'บริการรายเดือน', val: parseFloat(this.totals.plannedSubscriptions || 0), actualVal: parseFloat(this.totals.actualSubscriptions || 0), color: '#ec4899' },
                { label: 'ชำระหนี้สิน', val: parseFloat(this.totals.plannedDebts || 0), actualVal: parseFloat(this.totals.actualDebts || 0), color: '#ef4444' }
            ];

            let items = rawData.map(item => {
                return {
                    label: item.label,
                    value: type === 'planned' ? item.val : item.actualVal,
                    color: item.color
                };
            }).filter(item => item.value > 0);

            let total = items.reduce((sum, item) => sum + item.value, 0);
            if (total === 0) {
                return [];
            }

            let accumulatedOffset = 0;
            const circumference = 314.16;

            return items.map(item => {
                let pct = item.value / total;
                let strokeLength = pct * circumference;
                let offset = accumulatedOffset;
                accumulatedOffset += strokeLength;
                return {
                    ...item,
                    pct: pct * 100,
                    dashArray: strokeLength.toFixed(2) + ' ' + circumference,
                    dashOffset: (-offset).toFixed(2)
                };
            });
        },
        donutGradient(type) {
            let segs = this.getSegments(type);
            if (!segs.length) return '#f1f5f9';
            let parts = [];
            let acc = 0;
            segs.forEach((seg, i) => {
                let start = acc;
                // pin the final stop to 100% so float rounding leaves no sliver
                acc = (i === segs.length - 1) ? 100 : acc + seg.pct;
                parts.push(seg.color + ' ' + start + '% ' + acc + '%');
            });
            return 'conic-gradient(' + parts.join(', ') + ')';
        }
    }">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            
            {{-- Header & Dropdown & Reset --}}
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('app.portfolio.budget.heading') }}</h1>
                    <p class="mt-1.5 text-sm text-slate-500">{{ __('app.portfolio.budget.subheading') }}</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Dropdown history --}}
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">เลือกเดือน:</label>
                        <select onchange="window.location.href = '{{ route('portfolio.budget.index') }}?month=' + this.value"
                                class="rounded-xl border-slate-200 bg-slate-50 py-1.5 text-sm font-medium text-slate-700 shadow-sm focus:border-brand-500 focus:ring-brand-500 focus:bg-white transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
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

                    {{-- Quick Payslip & Retrospective Button --}}
                    <button type="button"
                            @click="openPayslipModal({ month: '{{ $activeMonth }}' })"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-sm hover:bg-emerald-100 transition whitespace-nowrap shrink-0 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
                        <span>💼</span>
                        <span>บันทึกสลิป / กรอกย้อนหลัง</span>
                    </button>

                    {{-- Sync Verified Plan Button --}}
                    <form method="POST" action="{{ route('portfolio.budget.reset') }}"
                          data-confirm="ต้องการซิงก์ข้อมูลจริงประจำเดือน พ.ย. 2569 (สลิปเงินเดือน 29,911 ฿ + ค่าหอพัก Thatcharin Place 7,292 ฿ + ตารางหนี้สิน + Subscriptions 1,362.93 ฿) เข้าสู่ตารางงบประมาณหรือไม่?"
                          data-confirm-danger="0">
                        @csrf
                        <input type="hidden" name="sync_verified_plan" value="1">
                        <input type="hidden" name="month" value="{{ $activeMonth }}">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            ซิงก์ข้อมูลแผน พ.ย. 69
                        </button>
                    </form>

                    {{-- New Month Reset Button --}}
                    <form method="POST" action="{{ route('portfolio.budget.reset') }}" 
                          data-confirm="ต้องการเริ่มเดือนใหม่? ระบบจะคำนวณเดือนถัดไป คัดลอกรายการแผนงานทั้งหมด บวกรอบผ่อนชำระที่เช็คไว้ และรีเซ็ตสถานะติ๊กชำระเงิน" 
                          data-confirm-danger="0"
                          class="flex flex-col items-end gap-2">
                        @csrf
                        <input type="hidden" name="current_month" value="{{ $activeMonth }}">
                        
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 bg-slate-50/80 ring-1 ring-slate-100 rounded-lg px-3 py-1.5">
                                <input type="checkbox" name="rollover_unspent" id="rollover" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <label for="rollover" class="text-xs font-medium text-slate-600 cursor-pointer">ทบยอดเหลือไปเดือนหน้า</label>
                            </div>
                            
                            <button type="submit" 
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 hover:shadow-md">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                เริ่มเดือนใหม่
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Retrospective / Empty Month Helper Banner --}}
            @if($incomes->isEmpty() && $items->isEmpty() && $subscriptions->isEmpty() && $installments->isEmpty())
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-5 dark:border-indigo-900/50 dark:bg-indigo-950/30">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-200">
                                📅 เดือน {{ $formatThaiMonth($activeMonth) }} ยังไม่มีบันทึกงบประมาณ
                            </h3>
                            <p class="text-xs text-indigo-700/80 dark:text-indigo-400 mt-0.5">
                                คุณสามารถบันทึกสลิปเงินเดือนย้อนหลัง หรือคัดลอกโครงร่างงบจากเดือนล่าสุดมาเป็นแม่แบบได้ทันที
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                    @click="openPayslipModal({ month: '{{ $activeMonth }}' })"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                                💼 บันทึกสลิปเงินเดือนงวดนี้
                            </button>
                            <form method="POST" action="{{ route('portfolio.budget.copy-month') }}">
                                @csrf
                                <input type="hidden" name="target_month" value="{{ $activeMonth }}">
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition">
                                    📋 คัดลอกโครงร่างงบจากเดือนล่าสุด
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Summary Cards --}}
            <div class="grid gap-4 sm:grid-cols-4">
                {{-- Income Card --}}
                <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-1.5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">รายได้รวมประจำเดือน</div>
                    <div class="text-3xl font-extrabold text-slate-900">
                        ฿{{ $fmtMoney($incomeTotal) }}
                    </div>
                    <div class="text-[11px] text-slate-500">
                        จากแหล่งรายได้ทั้งหมด {{ $incomes->count() }} รายการ
                    </div>
                </div>

                {{-- Unallocated Funds Card (Ready to Assign) --}}
                <div class="rounded-3xl p-6 space-y-1.5 shadow-[0_8px_30px_rgb(0,0,0,0.04)]"
                     :class="totals.unallocatedAmount === 0 ? 'ring-1 ring-emerald-500/20 bg-emerald-50/50' : (totals.unallocatedAmount > 0 ? 'ring-1 ring-blue-500/20 bg-blue-50/50' : 'ring-1 ring-rose-500/20 bg-rose-50/50')">
                    <div class="text-xs font-semibold uppercase tracking-wider"
                         :class="totals.unallocatedAmount === 0 ? 'text-emerald-700' : (totals.unallocatedAmount > 0 ? 'text-blue-700' : 'text-rose-700')">เงินยังไม่ได้จัดสรร</div>
                    <div class="text-3xl font-extrabold"
                         :class="totals.unallocatedAmount === 0 ? 'text-emerald-900' : (totals.unallocatedAmount > 0 ? 'text-blue-900' : 'text-rose-900')">
                        <span x-text="'฿' + fmtMoney(Math.abs(totals.unallocatedAmount))">฿{{ $fmtMoney(abs($unallocatedAmount)) }}</span>
                    </div>
                    <div class="text-[11px]"
                         :class="totals.unallocatedAmount === 0 ? 'text-emerald-700' : (totals.unallocatedAmount > 0 ? 'text-blue-700' : 'text-rose-700')"
                         x-text="totals.unallocatedAmount === 0 ? 'จัดสรรเงินครบทุกบาทแล้ว (Zero-Based)' : (totals.unallocatedAmount > 0 ? 'เหลืองบให้จัดสรรเพิ่ม' : 'จัดสรรงบเกินรายได้!')">
                        {{ $unallocatedAmount === 0.0 ? 'จัดสรรเงินครบทุกบาทแล้ว (Zero-Based)' : ($unallocatedAmount > 0 ? 'เหลืองบให้จัดสรรเพิ่ม' : 'จัดสรรงบเกินรายได้!') }}
                    </div>
                </div>

                {{-- Total Expenses Card --}}
                <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-1.5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">ใช้จริง / แผนรายจ่าย</div>
                    <div class="text-3xl font-extrabold text-slate-900">
                        <span x-text="'฿' + fmtMoney(totals.actualExpensesTotal)">฿{{ $fmtMoney($actualExpensesTotal) }}</span>
                        <span class="text-lg font-normal text-slate-400">/ ฿{{ $fmtMoney($totalExpenses) }}</span>
                    </div>
                    <div class="text-[11px] text-slate-500" x-text="'ใช้จริง: Fixed ฿' + fmtMoney(totals.actualFixedTotal) + ' | Var ฿' + fmtMoney(totals.actualVariableTotal) + ' | Save ฿' + fmtMoney(totals.actualSavingsTotal)">
                        ใช้จริง: Fixed ฿{{ $fmtMoney($actualFixedTotal) }} | Var ฿{{ $fmtMoney($actualVariableTotal) }} | Save ฿{{ $fmtMoney($actualSavingsTotal) }}
                    </div>
                </div>

                {{-- Remaining Balance Card --}}
                <div class="rounded-3xl p-6 space-y-1.5 shadow-[0_8px_30px_rgb(0,0,0,0.04)]"
                     :class="totals.actualRemainingAmount >= 0 ? 'ring-1 ring-emerald-500/20 bg-emerald-50/50' : 'ring-1 ring-rose-500/20 bg-rose-50/50'">
                    <div class="text-xs font-semibold uppercase tracking-wider"
                         :class="totals.actualRemainingAmount >= 0 ? 'text-emerald-700' : 'text-rose-700'">คงเหลือจริง / ตามแผน</div>
                    <div class="text-3xl font-extrabold"
                         :class="totals.actualRemainingAmount >= 0 ? 'text-emerald-900' : 'text-rose-900'">
                        <span x-text="'฿' + fmtMoney(totals.actualRemainingAmount)">฿{{ $fmtMoney($actualRemainingAmount) }}</span>
                        <span class="text-lg font-normal"
                              :class="totals.actualRemainingAmount >= 0 ? 'text-emerald-600/70' : 'text-rose-600/70'">/ ฿{{ $fmtMoney($remainingAmount) }}</span>
                    </div>
                    <div class="text-[11px]"
                         :class="totals.actualRemainingAmount >= 0 ? 'text-emerald-700' : 'text-rose-700'"
                         x-text="totals.actualRemainingAmount >= 0 ? 'กระแสเงินสดจริงยังเป็นบวก' : 'ใช้จริงเกินกว่ารายได้แล้ว!'">
                        {{ $actualRemainingAmount >= 0 ? 'กระแสเงินสดจริงยังเป็นบวก' : 'ใช้จริงเกินกว่ารายได้แล้ว!' }}
                    </div>
                </div>
            </div>

            {{-- 50/30/20 & Debt-Free Cash Flow Roadmap Dashboard --}}
            <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-6"
                 x-data="{
                     selIdx: 0,
                     optSub: false,
                     optElec: false,
                     dedupSpay: false,
                     dailyFoodBudget: 220,
                     baseIncome: {{ $incomeTotal > 0 ? $incomeTotal : 29911 }},
                     months: [
                         { label: 'พ.ย. 69', full: 'พฤศจิกายน 2569 (ครบกำหนดชำระหนี้ 1 ธ.ค.)', bill: 4955.97, spay: 1000, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 1000.00, milestone: 'เดือนที่ภาระหนี้สูงสุด (Peak) จ่ายคืนแม่งวดสุดท้าย 1,000 ฿ และกันเงินค่าหอพัก 7,292 ฿ ภายในวันที่ 7 พ.ย.' },
                         { label: 'ธ.ค. 69', full: 'ธันวาคม 2569 (ครบกำหนดชำระหนี้ 1 ม.ค. 70)', bill: 3867.24, spay: 1000, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 0, milestone: 'ปลดหนี้คืนแม่สำเร็จ + ใบแจ้งยอดลดลง ทำให้มีเงินสดปลดล็อกเพิ่มขึ้นทันที +2,088.73 ฿/เดือน!' },
                         { label: 'ม.ค. 70', full: 'มกราคม 2570 (ครบกำหนดชำระหนี้ 1 ก.พ. 70)', bill: 2614.94, spay: 1000, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 0, milestone: 'ภาระหนี้รวมลดต่ำกว่า 10,000 ฿/เดือน และอัตราเงินออมแตะเกณฑ์มาตรฐาน 20% ของรายได้สุทธิ' },
                         { label: 'ก.พ. 70', full: 'กุมภาพันธ์ 2570 (ครบกำหนดชำระหนี้ 1 มี.ค. 70)', bill: 1429.25, spay: 1000, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 0, milestone: 'ภาระหนี้ลดลงสะสม -4,526.72 ฿/เดือน เงินสำรองฉุกเฉินสะสมผ่านระดับ 1 เดือนของรายจ่ายจำเป็น' },
                         { label: 'มี.ค. 70', full: 'มีนาคม 2570 (ครบกำหนดชำระหนี้ 1 เม.ย. 70)', bill: 1099.97, spay: 1000, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 0, milestone: 'ภาระหนี้ลดลงเหลือ 26% ของรายได้ สะสมเงินออมต่อเนื่องเข้าบัญชี High-Yield e-Savings' },
                         { label: 'เม.ย. 70', full: 'เมษายน 2570 (ครบกำหนดชำระหนี้ 1 พ.ค. 70)', bill: 761.70, spay: 1000, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 0, milestone: 'งวดสุดท้ายของใบแจ้งยอดล่วงหน้า (761.70 ฿) และครบกำหนด 6 งวดคงเหลือของ SPayLater!' },
                         { label: 'พ.ค. 70+', full: 'พฤษภาคม – สิงหาคม 2570', bill: 0, spay: 0, s24: 1498.24, tiktok: 948.62, mono: 243.16, moto: 2989.00, mom: 0, milestone: 'ปลดหนี้ใบแจ้งยอดและ SPayLater หมดเกลี้ยง! ภาระหนี้ลดฮวบเหลือเพียง 5,679.02 ฿/เดือน ออมได้เกือบเดือนละ 10,000 ฿' },
                         { label: 'ก.ย. 70+', full: 'กันยายน 2570 เป็นต้นไป (Phase 2 ขยายพอร์ต)', bill: 0, spay: 0, s24: 0, tiktok: 0, mono: 0, moto: 2989.00, mom: 0, milestone: 'ปลดหนี้ S24 Ultra, TikTok และ MonoMax ครบทั้งหมด! เหลือเฉพาะค่ามอเตอร์ไซค์ 2,989 ฿/เดือน (10% ของเงินเดือน)' }
                     ],
                     housingCost() {
                         return 7292.00 - (this.optElec ? 500.00 : 0);
                     },
                     subCost() {
                         return 1362.93 - (this.optSub ? 329.00 : 0);
                     },
                     foodMonthly() {
                         return Math.max(0, (parseFloat(this.dailyFoodBudget) || 0) * 30);
                     },
                     debtFor(m) {
                         return m.bill + (this.dedupSpay ? 0 : m.spay) + m.s24 + m.tiktok + m.mono + m.moto + m.mom;
                     },
                     curMonth() {
                         return this.months[this.selIdx];
                     },
                     curDebt() {
                         return this.debtFor(this.curMonth());
                     },
                     freedDebt() {
                         return Math.max(0, this.debtFor(this.months[0]) - this.curDebt());
                     },
                     fixedCommitments() {
                         return this.housingCost() + this.subCost() + this.curDebt();
                     },
                     cashAfterFixed() {
                         return this.baseIncome - this.fixedCommitments();
                     },
                     netMonthlySave() {
                         return this.cashAfterFixed() - this.foodMonthly();
                     },
                     cumulativeSave() {
                         let sum = 0;
                         for (let i = 0; i <= this.selIdx; i++) {
                             let d = this.debtFor(this.months[i]);
                             let s = this.baseIncome - (this.housingCost() + this.subCost() + d) - this.foodMonthly();
                             sum += Math.max(0, s);
                         }
                         return sum;
                     },
                     coreNeedsTarget3M() {
                         return (this.housingCost() + this.subCost() + this.foodMonthly() + 2989.00) * 3;
                     }
                 }">
                {{-- Top Header & Simulation Switches --}}
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-slate-100 pb-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-brand-100 px-2.5 py-0.5 text-xs font-bold text-brand-700">AI Financial Copilot</span>
                            <h3 class="text-lg font-bold text-slate-900">แผนจัดสรรเงินเดือน 50/30/20 & ไทม์ไลน์ปลดหนี้ (พ.ย. 69 – ก.ย. 70)</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            อ้างอิงข้อมูลจริง: เงินเดือนสุทธิ ฿29,911.00 · หอพัก Thatcharin Place (ห้อง 410) ฿7,292.00 · ตารางหนี้สิน · Subscriptions ฿1,362.93
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" x-model="optSub" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span>ตัด Sub ซ้ำซ้อน (-฿329/ด.)</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" x-model="optElec" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span>ประหยัดแอร์/ไฟหอพัก (-฿500/ด.)</span>
                        </label>
                        <label class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" x-model="dedupSpay" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span>หักซ้ำ SPayLater (-฿1,000)</span>
                        </label>
                    </div>
                </div>

                {{-- 8-Step Month Timeline Buttons --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <template x-for="(m, idx) in months" :key="idx">
                        <button type="button"
                                @click="selIdx = idx"
                                :class="selIdx === idx ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                class="rounded-xl border p-2.5 text-left transition">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" x-text="m.label"></span>
                                <span class="text-[10px] opacity-80" x-show="idx === 0">Peak</span>
                                <span class="text-[10px] opacity-90" x-show="idx > 0" x-text="'-' + Math.round((debtFor(months[0]) - debtFor(m))/1000) + 'k'"></span>
                            </div>
                            <div class="mt-1 text-xs font-semibold" x-text="'หนี้ ฿' + fmtMoney(debtFor(m))"></div>
                        </button>
                    </template>
                </div>

                {{-- Main 3-Column Roadmap Content --}}
                <div class="grid gap-6 lg:grid-cols-3">
                    {{-- Col 1: Fixed Commitments & Debt Breakdown for Selected Month --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                            <div>
                                <div class="text-xs font-bold text-slate-900" x-text="curMonth().full"></div>
                                <div class="text-[10px] text-slate-500">รายการภาระคงที่และหนี้ที่ต้องชำระในเดือนนี้</div>
                            </div>
                            <span class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700"
                                  x-text="'หนี้รวม ฿' + fmtMoney(curDebt())"></span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between font-semibold text-slate-800">
                                <span>1. ค่าหอพัก Thatcharin Place (ครบวันที่ 7)</span>
                                <span x-text="'฿' + fmtMoney(housingCost())"></span>
                            </div>
                            <div class="flex justify-between font-semibold text-slate-800">
                                <span>2. ค่าบริการรายเดือน (Subscriptions)</span>
                                <span x-text="'฿' + fmtMoney(subCost())"></span>
                            </div>
                            <div class="border-t border-slate-200 pt-2 space-y-1.5">
                                <div class="text-[11px] font-bold text-slate-500">3. รายละเอียดหนี้ผ่อนชำระรายเดือน:</div>
                                <div class="flex justify-between" :class="curMonth().bill === 0 ? 'line-through text-slate-400' : 'text-slate-700'">
                                    <span>• ใบแจ้งยอดล่วงหน้า (ครบวันที่ 1)</span>
                                    <span class="font-semibold" x-text="'฿' + fmtMoney(curMonth().bill)"></span>
                                </div>
                                <div class="flex justify-between text-slate-700">
                                    <span>• มอเตอร์ไซค์ (เหลือ ~30 งวด)</span>
                                    <span class="font-semibold" x-text="'฿' + fmtMoney(curMonth().moto)"></span>
                                </div>
                                <div class="flex justify-between" :class="curMonth().s24 === 0 ? 'line-through text-slate-400' : 'text-slate-700'">
                                    <span>• S24 Ultra (10 งวดสุดท้าย)</span>
                                    <span class="font-semibold" x-text="'฿' + fmtMoney(curMonth().s24)"></span>
                                </div>
                                <div class="flex justify-between" x-show="!dedupSpay" :class="curMonth().spay === 0 ? 'line-through text-slate-400' : 'text-slate-700'">
                                    <span>• SPayLater (6 งวดสุดท้าย)</span>
                                    <span class="font-semibold" x-text="'฿' + fmtMoney(curMonth().spay)"></span>
                                </div>
                                <div class="flex justify-between" :class="curMonth().tiktok === 0 ? 'line-through text-slate-400' : 'text-slate-700'">
                                    <span>• สินเชื่อ TikTok + MonoMax (9 งวด)</span>
                                    <span class="font-semibold" x-text="'฿' + fmtMoney(curMonth().tiktok + curMonth().mono)"></span>
                                </div>
                                <div class="flex justify-between" :class="curMonth().mom === 0 ? 'line-through text-slate-400' : 'text-slate-700'">
                                    <span>• คืนแม่ (งวดสุดท้าย พ.ย. 69)</span>
                                    <span class="font-semibold" x-text="'฿' + fmtMoney(curMonth().mom)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Col 2: Cash Flow Allocation & Daily Food Control --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                            <div>
                                <div class="text-xs font-bold text-slate-900">กระแสเงินสด & สัดส่วนการจัดสรร</div>
                                <div class="text-[10px] text-slate-500">ปรับงบอาหาร+น้ำมันรถรายวันเพื่อดูยอดเงินออม</div>
                            </div>
                            <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                                  x-show="freedDebt() > 0"
                                  x-text="'ปลดล็อกหนี้ +฿' + fmtMoney(freedDebt()) + '/ด.'"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-white p-3 border border-slate-200">
                                <div class="text-[10px] font-semibold text-slate-500">เงินสดเหลือหลังหักหอ+หนี้+Sub</div>
                                <div class="mt-1 text-base font-extrabold text-slate-900" x-text="'฿' + fmtMoney(cashAfterFixed())"></div>
                                <div class="text-[10px] text-slate-500" x-text="'เพดานใช้จ่าย ' + Math.round(cashAfterFixed()/30) + ' ฿/วัน'"></div>
                            </div>
                            <div class="rounded-xl bg-emerald-50/60 p-3 border border-emerald-200">
                                <div class="text-[10px] font-semibold text-emerald-700">โอนเก็บสำรองฉุกเฉินเดือนนี้</div>
                                <div class="mt-1 text-base font-extrabold text-emerald-800" x-text="(netMonthlySave() >= 0 ? '+฿' : '-฿') + fmtMoney(Math.abs(netMonthlySave()))"></div>
                                <div class="text-[10px] text-emerald-700" x-text="((netMonthlySave()/baseIncome)*100).toFixed(1) + '% ของรายได้สุทธิ'"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                <label>ตั้งงบกินอยู่ + น้ำมันรถต่อวัน (฿/วัน):</label>
                                <span class="font-bold text-slate-900" x-text="dailyFoodBudget + ' ฿/วัน (฿' + fmtMoney(foodMonthly()) + '/เดือน)'"></span>
                            </div>
                            <input type="number" step="10" min="100" max="500" x-model="dailyFoodBudget"
                                   class="block w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm">
                        </div>

                        {{-- Progress Bars --}}
                        <div class="space-y-2 text-xs pt-1">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-slate-600">1. ดำรงชีพ (หอพัก + กินอยู่ + Sub)</span>
                                    <span class="font-bold text-slate-900" x-text="(((housingCost() + subCost() + foodMonthly())/baseIncome)*100).toFixed(1) + '%'"></span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-brand-500" :style="'width: ' + Math.min(100, ((housingCost() + subCost() + foodMonthly())/baseIncome)*100) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-slate-600">2. ผ่อนชำระหนี้รายเดือน</span>
                                    <span class="font-bold text-rose-700" x-text="((curDebt()/baseIncome)*100).toFixed(1) + '%'"></span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-rose-500" :style="'width: ' + Math.min(100, (curDebt()/baseIncome)*100) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-slate-600">3. เงินออมสุทธิ (เป้าหมายมาตรฐาน ≥ 20%)</span>
                                    <span class="font-bold text-emerald-700" x-text="Math.max(0, (netMonthlySave()/baseIncome)*100).toFixed(1) + '%'"></span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-emerald-500" :style="'width: ' + Math.min(100, Math.max(0, (netMonthlySave()/baseIncome)*100)) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Col 3: Emergency Fund Progress & Strategic Action Plan --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-4">
                        <div class="border-b border-slate-200 pb-2.5">
                            <div class="text-xs font-bold text-slate-900">เป้าหมายเงินสำรองฉุกเฉิน & แผนปฏิบัติ</div>
                            <div class="text-[10px] text-slate-500">คำนวณจากฐานรายจ่ายจำเป็นเมื่อปลอดหนี้ระยะสั้น</div>
                        </div>

                        <div class="rounded-xl bg-white p-3.5 border border-slate-200 space-y-2">
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs font-semibold text-slate-600">เงินสำรองสะสมถึงเดือนนี้:</span>
                                <span class="text-lg font-extrabold text-emerald-700" x-text="'฿' + fmtMoney(cumulativeSave())"></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-2 rounded-full bg-emerald-500 transition-all"
                                     :style="'width: ' + Math.min(100, (cumulativeSave() / coreNeedsTarget3M()) * 100) + '%'"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-500">
                                <span x-text="'เป้าหมายระยะแรก (3 เดือน): ฿' + fmtMoney(coreNeedsTarget3M())"></span>
                                <span class="font-bold text-slate-700" x-text="Math.min(100, Math.round((cumulativeSave() / coreNeedsTarget3M()) * 100)) + '%'"></span>
                            </div>
                        </div>

                        <div class="rounded-xl bg-sky-50/60 border border-sky-200 p-3.5 text-xs space-y-1.5">
                            <div class="font-bold text-sky-900">กลยุทธ์ประจำเดือน:</div>
                            <p class="text-slate-700 leading-relaxed" x-text="curMonth().milestone"></p>
                        </div>

                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-[11px] text-amber-800 space-y-1">
                            <div class="font-bold">จุดลดรายจ่ายแฝง & สิทธิประโยชน์ภาษี:</div>
                            <div>• <strong>Subscriptions:</strong> พัก Spotify (฿209) + Whoscall (฿59) + สมัคร YouTube ผ่านเว็บ (-฿61) ประหยัดทันที <strong>฿3,948/ปี</strong></div>
                            <div>• <strong>ภาษีสะสมในสลิป:</strong> หัก ณ ที่จ่าย ฿214/ด. (~฿2,568/ปี) เมื่อสำรองครบ 3 เดือนแล้ว แบ่งซื้อกองทุน Thai ESG/RMF ขอคืนภาษีได้ 100%</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chart Card --}}
            <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">สัดส่วนค่าใช้จ่ายรายเดือน</h3>
                        <p class="text-xs text-slate-500">แสดงสัดส่วนการใช้จ่ายแบ่งตามกลุ่มรายจ่ายต่าง ๆ</p>
                    </div>
                    
                    {{-- Toggle Button Group --}}
                    <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-0.5 self-start">
                        <button type="button" 
                                @click="chartType = 'planned'"
                                :class="chartType === 'planned' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition">
                            ตามแผนที่ตั้งไว้
                        </button>
                        <button type="button" 
                                @click="chartType = 'actual'"
                                :class="chartType === 'actual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition">
                            ตามที่ใช้จริง
                        </button>
                    </div>
                </div>

                {{-- Empty State --}}
                <div x-show="getSegments(chartType).length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400">
                    <svg class="h-12 w-12 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    <p class="mt-2 text-sm font-medium">ยังไม่มีข้อมูลรายจ่ายประจำเดือนนี้</p>
                </div>

                {{-- Chart Content --}}
                <div x-show="getSegments(chartType).length > 0" class="grid items-center gap-8 md:grid-cols-2">
                    {{-- Donut chart — CSS conic-gradient. Alpine's <template x-for> can't
                         create SVG-namespaced <circle> nodes, so an SVG donut renders nothing.
                         Sizes/gradient are inlined via :style so they don't depend on the
                         compiled Tailwind build (h-56 / stroke-slate-100 are missing there). --}}
                    <div class="flex justify-center">
                        <div class="relative rounded-full transition-all duration-300 ease-in-out"
                             style="width: 14rem; height: 14rem;"
                             :style="{ background: donutGradient(chartType) }">
                            {{-- donut hole (inherits the card background) --}}
                            <div class="absolute rounded-full bg-white" style="inset: 16%;"></div>

                            {{-- Text in the center --}}
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500" x-text="chartType === 'planned' ? 'งบแผนรวม' : 'ใช้จริงรวม'"></span>
                                <span class="text-xl font-extrabold text-slate-900 mt-0.5" x-text="'฿' + fmtMoney(chartType === 'planned' ? totals.totalExpenses : totals.actualExpensesTotal)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Legend List --}}
                    <div class="divide-y divide-slate-100">
                        <template x-for="(seg, idx) in getSegments(chartType)" :key="idx">
                            <div class="flex items-center justify-between py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-3.5 w-3.5 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                    <span class="text-sm font-semibold text-slate-700" x-text="seg.label"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-slate-900" x-text="'฿' + fmtMoney(seg.value)"></span>
                                    <span class="ml-1.5 text-xs text-slate-500" x-text="'(' + fmtMoney(seg.pct) + '%)'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 3-Column Spreadsheet Layout --}}
            <div class="grid gap-6 lg:grid-cols-3">
                
                {{-- COLUMN 1: EXPENSES --}}
                <div class="space-y-6">
                    
                    {{-- 1.1 FIXED EXPENSES --}}
                    <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-5">
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
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">งบประมาณ (บาท)</label>
                                        <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ใช้จริง (บาท - ไม่จำเป็น)</label>
                                        <input type="number" step="0.01" name="actual_amount" placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
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
                                     x-data="{ 
                                         editing: false, 
                                         label: '{{ addslashes($item->label) }}', 
                                         amount: {{ $item->amount }}, 
                                         actual_amount: {{ $item->actual_amount !== null ? $item->actual_amount : 'null' }},
                                         computed_actual: {{ $getItemActual($item) }},
                                         has_ledger: {{ $item->ledgerEntries->sum('amount') > 0 ? 'true' : 'false' }},
                                         notes: '{{ addslashes($item->notes) }}',
                                         checked: {{ $item->is_checked ? 'true' : 'false' }}
                                     }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <input type="checkbox" 
                                               :checked="checked"
                                               @change="checked = await toggleCheck('{{ route('portfolio.budget.toggle', ['type' => 'item', 'id' => $item->id]) }}')"
                                               class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800"
                                                  :class="checked ? 'line-through text-slate-400' : ''">
                                                {{ $item->label }}
                                            </span>
                                            @if($item->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-slate-900 whitespace-nowrap"
                                                  :class="checked ? 'text-slate-400' : ''">
                                                ฿{{ $fmtMoney($item->amount) }}
                                            </span>
                                            <div class="flex items-center justify-end gap-1 text-[10px] text-slate-500 whitespace-nowrap">
                                                <span x-show="has_ledger && actual_amount === null" class="text-blue-500" title="ดึงข้อมูลจาก Ledger อัตโนมัติ">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </span>
                                                <span x-text="'ใช้จริง: ฿' + fmtMoney(computed_actual)">ใช้จริง: ฿{{ $fmtMoney($getItemActual($item)) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
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
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[9px] text-slate-500 mb-0.5">งบประมาณ</label>
                                                    <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] text-slate-500 mb-0.5">ใช้จริง</label>
                                                    <input type="number" step="0.01" name="actual_amount" x-model="actual_amount" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ยังไม่ได้ระบุ">
                                                </div>
                                            </div>
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

                            {{-- READ-ONLY: Variable Debt Payments Sum (Auto) --}}
                            @if($debtPaymentsSum > 0)
                                <div class="py-2.5 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-4 w-4 flex items-center justify-center text-[10px] font-bold text-slate-400 bg-slate-100 rounded border border-slate-200">A</span>
                                        <div>
                                            <span class="text-sm font-medium text-slate-700">ผ่อนตามตาราง (Variable)</span>
                                            <p class="text-[10px] text-slate-400">คำนวณจากตารางผ่อนด้านขวา</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">
                                        ฿{{ $fmtMoney($debtPaymentsSum) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Total Fixed Expenses --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900">
                            <span>รวมค่าใช้จ่ายคงที่ (ใช้จริง / งบ)</span>
                            <span>
                                <span x-text="'฿' + fmtMoney(totals.actualFixedTotal)">฿{{ $fmtMoney($actualFixedTotal) }}</span>
                                <span class="text-xs font-normal text-slate-500">/ ฿{{ $fmtMoney($fixedTotal) }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- 1.2 VARIABLE EXPENSES --}}
                    <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-5">
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
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">งบประมาณ (บาท)</label>
                                        <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ใช้จริง (บาท - ไม่จำเป็น)</label>
                                        <input type="number" step="0.01" name="actual_amount" placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
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
                                     x-data="{ 
                                         editing: false, 
                                         label: '{{ addslashes($item->label) }}', 
                                         amount: {{ $item->amount }}, 
                                         actual_amount: {{ $item->actual_amount !== null ? $item->actual_amount : 'null' }},
                                         computed_actual: {{ $getItemActual($item) }},
                                         has_ledger: {{ $item->ledgerEntries->sum('amount') > 0 ? 'true' : 'false' }},
                                         notes: '{{ addslashes($item->notes) }}',
                                         checked: {{ $item->is_checked ? 'true' : 'false' }}
                                     }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <input type="checkbox" 
                                               :checked="checked"
                                               @change="checked = await toggleCheck('{{ route('portfolio.budget.toggle', ['type' => 'item', 'id' => $item->id]) }}')"
                                               class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800"
                                                  :class="checked ? 'line-through text-slate-400' : ''">
                                                {{ $item->label }}
                                            </span>
                                            @if($item->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-slate-900 whitespace-nowrap"
                                                  :class="checked ? 'text-slate-400' : ''">
                                                ฿{{ $fmtMoney($item->amount) }}
                                            </span>
                                            <div class="flex items-center justify-end gap-1 text-[10px] text-slate-500 whitespace-nowrap">
                                                <span x-show="has_ledger && actual_amount === null" class="text-blue-500" title="ดึงข้อมูลจาก Ledger อัตโนมัติ">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </span>
                                                <span x-text="'ใช้จริง: ฿' + fmtMoney(computed_actual)">ใช้จริง: ฿{{ $fmtMoney($getItemActual($item)) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
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
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[9px] text-slate-500 mb-0.5">งบประมาณ</label>
                                                    <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] text-slate-500 mb-0.5">ใช้จริง</label>
                                                    <input type="number" step="0.01" name="actual_amount" x-model="actual_amount" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ยังไม่ได้ระบุ">
                                                </div>
                                            </div>
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
                            <span>รวมค่าใช้จ่ายผันแปร (ใช้จริง / งบ)</span>
                            <span>
                                <span x-text="'฿' + fmtMoney(totals.actualVariableTotal)">฿{{ $fmtMoney($actualVariableTotal) }}</span>
                                <span class="text-xs font-normal text-slate-500">/ ฿{{ $fmtMoney($variableTotal) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- COLUMN 2: INCOME SOURCES & SAVINGS --}}
                <div class="space-y-6">
                    
                    {{-- 2.1 INCOME SOURCES --}}
                    @php
                        $salaryIncome = $incomes->first(fn ($inc) => $inc->payslip_data !== null || str_contains($inc->label, 'เงินเดือน'));
                        $salaryPayslip = $salaryIncome ? $salaryIncome->payslip_data : null;
                        $otherIncomes = $incomes->reject(fn ($inc) => $salaryIncome && $inc->id === $salaryIncome->id);
                    @endphp
                    <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-5 dark:bg-slate-900/90 dark:ring-slate-800">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">แหล่งรายได้</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">เงินเดือน, OT, รายได้เสริมต่างๆ ในเดือนนี้</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button"
                                        @click="openPayslipModal({
                                            month: '{{ $activeMonth }}',
                                            company: '{{ addslashes($salaryPayslip['company'] ?? 'บจก. ดูดีพ') }}',
                                            base_salary: {{ (float) ($salaryPayslip['base_salary'] ?? 30000) }},
                                            ot: {{ (float) ($salaryPayslip['ot'] ?? 0) }},
                                            bonus: {{ (float) ($salaryPayslip['bonus'] ?? 0) }},
                                            social_security: {{ (float) ($salaryPayslip['social_security'] ?? 875) }},
                                            tax: {{ (float) ($salaryPayslip['tax'] ?? 314) }},
                                            other_deduct: {{ (float) ($salaryPayslip['other_deduct'] ?? 0) }},
                                            notes: '{{ addslashes($salaryPayslip['custom_notes'] ?? '') }}'
                                        })"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-800 shadow-sm hover:bg-emerald-100 transition whitespace-nowrap shrink-0 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
                                    <span>💼</span>
                                    <span>{{ $salaryIncome ? 'แก้ไขสลิป' : 'บันทึกสลิป' }}</span>
                                </button>
                                <button @click="addIncomeOpen = !addIncomeOpen"
                                        class="rounded-xl border border-slate-200 p-1.5 text-slate-600 hover:bg-slate-50 transition shrink-0 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                        title="เพิ่มรายได้อื่น">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Add Other Income Source Form --}}
                        <div x-show="addIncomeOpen" x-cloak class="rounded-2xl bg-slate-50 p-4 border border-slate-200 space-y-3 dark:bg-slate-800/60 dark:border-slate-700">
                            <form method="POST" action="{{ route('portfolio.budget.income.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">ประเภทรายได้เสริม</label>
                                    <input type="text" name="label" required placeholder="เช่น ขายของออนไลน์, รับจ้างฟรีแลนซ์, ปันผล"
                                           class="block w-full rounded-xl border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">จำนวนเงิน (บาท)</label>
                                    <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                           class="block w-full rounded-xl border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">หมายเหตุ (ไม่จำเป็น)</label>
                                    <input type="text" name="notes" placeholder="ข้อมูลเพิ่มเติม"
                                           class="block w-full rounded-xl border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button type="button" @click="addIncomeOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">ยกเลิก</button>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มรายได้</button>
                                </div>
                            </form>
                        </div>

                        {{-- Itemized Salary Payslip Card --}}
                        @if($salaryIncome && $salaryPayslip)
                            <div class="rounded-2xl border border-emerald-500/20 bg-gradient-to-b from-emerald-50/40 to-slate-50/50 p-4 sm:p-5 dark:bg-slate-900/60 dark:border-emerald-500/30">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-200/70 dark:border-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 text-sm">
                                            💼
                                        </span>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                                {{ $salaryIncome->label }}
                                            </h4>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                                งวด {{ $formatThaiMonth($activeMonth) }} &bull; บริษัท {{ $salaryPayslip['company'] ?? 'บจก. ดูดีพ' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button"
                                                @click="openPayslipModal({
                                                    month: '{{ $activeMonth }}',
                                                    company: '{{ addslashes($salaryPayslip['company'] ?? 'บจก. ดูดีพ') }}',
                                                    base_salary: {{ (float) ($salaryPayslip['base_salary'] ?? 30000) }},
                                                    ot: {{ (float) ($salaryPayslip['ot'] ?? 0) }},
                                                    bonus: {{ (float) ($salaryPayslip['bonus'] ?? 0) }},
                                                    social_security: {{ (float) ($salaryPayslip['social_security'] ?? 875) }},
                                                    tax: {{ (float) ($salaryPayslip['tax'] ?? 314) }},
                                                    other_deduct: {{ (float) ($salaryPayslip['other_deduct'] ?? 0) }},
                                                    notes: '{{ addslashes($salaryPayslip['custom_notes'] ?? '') }}'
                                                })"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition whitespace-nowrap shrink-0 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700">
                                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                            <span class="whitespace-nowrap">แก้ไขสลิป / OT</span>
                                        </button>
                                        <form method="POST" action="{{ route('portfolio.budget.income.destroy', $salaryIncome) }}" data-confirm="ต้องการลบรายการสลิปเงินเดือนนี้?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-rose-400 hover:text-rose-600 transition" title="ลบรายการสลิปเงินเดือน">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Breakdown Rows --}}
                                <div class="mt-3 space-y-2 text-xs">
                                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                        <span>ฐานเงินเดือน (Base Salary)</span>
                                        <span class="font-medium text-slate-800 dark:text-slate-200">฿{{ $fmtMoney($salaryPayslip['base_salary'] ?? 0) }}</span>
                                    </div>
                                    
                                    @if(($salaryPayslip['ot'] ?? 0) > 0)
                                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                        <span class="flex items-center gap-1.5">
                                            <span>ค่าล่วงเวลา (OT)</span>
                                            <span class="rounded bg-emerald-100 px-1.5 py-0.2 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">OT</span>
                                        </span>
                                        <span class="font-medium text-emerald-600 dark:text-emerald-400">+฿{{ $fmtMoney($salaryPayslip['ot']) }}</span>
                                    </div>
                                    @endif

                                    @if(($salaryPayslip['bonus'] ?? 0) > 0)
                                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                        <span>โบนัส / ค่าตอบแทนพิเศษ</span>
                                        <span class="font-medium text-emerald-600 dark:text-emerald-400">+฿{{ $fmtMoney($salaryPayslip['bonus']) }}</span>
                                    </div>
                                    @endif

                                    {{-- Gross Income Subtotal --}}
                                    <div class="flex items-center justify-between pt-1 font-semibold text-slate-800 dark:text-slate-200 border-t border-dashed border-slate-200 dark:border-slate-800">
                                        <span>รวมรายรับ (เงินเดือน + OT)</span>
                                        <span class="font-bold">฿{{ $fmtMoney($salaryPayslip['gross_income'] ?? 0) }}</span>
                                    </div>

                                    {{-- Deductions --}}
                                    <div class="flex items-center justify-between text-rose-600 dark:text-rose-400 pt-1">
                                        <span class="flex items-center gap-1">
                                            <span>หัก ประกันสังคม</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">(SSO)</span>
                                        </span>
                                        <span class="font-medium">-฿{{ $fmtMoney($salaryPayslip['social_security'] ?? 0) }}</span>
                                    </div>

                                    <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                                        <span class="flex items-center gap-1">
                                            <span>หัก ภาษีหัก ณ ที่จ่ายจริง</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">(WHT)</span>
                                        </span>
                                        <span class="font-medium">-฿{{ $fmtMoney($salaryPayslip['tax'] ?? 0) }}</span>
                                    </div>

                                    @if(($salaryPayslip['other_deduct'] ?? 0) > 0)
                                    <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                                        <span>หัก อื่นๆ / กองทุน</span>
                                        <span class="font-medium">-฿{{ $fmtMoney($salaryPayslip['other_deduct']) }}</span>
                                    </div>
                                    @endif

                                    {{-- Net Pay Highlight (Exact Match to user's "3. สรุปยอดเงินได้สุทธิ") --}}
                                    <div class="mt-2.5 flex items-center justify-between rounded-xl bg-white p-3 ring-1 ring-emerald-500/30 shadow-sm dark:bg-slate-800 dark:ring-emerald-500/40">
                                        <div>
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                                เงินได้สุทธิที่ได้รับจริง (Actual Net Pay)
                                            </div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                                ยอดเงินเข้าบัญชีจริงที่นำไปใช้จัดสรรงบ
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">
                                                ฿{{ $fmtMoney($salaryIncome->amount) }}
                                            </span>
                                        </div>
                                    </div>

                                    @if(!empty($salaryPayslip['custom_notes']))
                                    <div class="pt-1 text-[11px] text-slate-500 dark:text-slate-400 italic">
                                        หมายเหตุ: {{ $salaryPayslip['custom_notes'] }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- No salary payslip recorded yet for this month --}}
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-5 text-center dark:border-slate-700 dark:bg-slate-800/40">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400 mb-2">
                                    💼
                                </div>
                                <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    ยังไม่มีข้อมูลสลิปเงินเดือนสำหรับเดือนนี้
                                </div>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                    บันทึกสลิปเงินเดือนแบบแจกแจง ฐานเงินเดือน, OT, ประกันสังคม และภาษี
                                </p>
                                <div class="mt-3">
                                    <button type="button"
                                            @click="openPayslipModal({
                                                month: '{{ $activeMonth }}',
                                                company: 'บจก. ดูดีพ',
                                                base_salary: 30000,
                                                ot: 0,
                                                bonus: 0,
                                                social_security: 875,
                                                tax: 314,
                                                other_deduct: 0,
                                                notes: ''
                                            })"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                                        💼 บันทึกสลิปเงินเดือนงวดนี้
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Other Income Sources List --}}
                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">รายได้เสริม / แหล่งอื่น</span>
                                <button type="button" @click="addIncomeOpen = !addIncomeOpen" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                    + เพิ่มรายได้อื่น
                                </button>
                            </div>

                            @if($otherIncomes->isNotEmpty())
                                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($otherIncomes as $inc)
                                        <div class="py-2.5 flex items-center justify-between gap-3"
                                             x-data="{ editing: false, label: '{{ addslashes($inc->label) }}', amount: {{ $inc->amount }}, notes: '{{ addslashes($inc->notes) }}' }">
                                            
                                            {{-- Read mode --}}
                                            <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                <div class="min-w-0 flex-1">
                                                    <span class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                        {{ $inc->label }}
                                                    </span>
                                                    @if($inc->notes)
                                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate">{{ $inc->notes }}</p>
                                                    @endif
                                                </div>
                                                <span class="text-sm font-bold text-slate-900 whitespace-nowrap dark:text-white">
                                                    ฿{{ $fmtMoney($inc->amount) }}
                                                </span>
                                                <div class="flex items-center gap-1">
                                                    <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition dark:text-slate-400 dark:hover:text-slate-200">
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
                                                <form method="POST" action="{{ route('portfolio.budget.income.update', $inc) }}" class="space-y-2 py-1 bg-slate-50 p-2.5 rounded-lg border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="label" x-model="label" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100">
                                                    <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100">
                                                    <input type="text" name="notes" x-model="notes" class="block w-full rounded border-slate-300 px-2 py-1 text-xs dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100" placeholder="หมายเหตุ">
                                                    <div class="flex justify-end gap-1.5">
                                                        <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold dark:bg-slate-700 dark:text-slate-300">ยกเลิก</button>
                                                        <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-2 text-center text-xs text-slate-400 dark:text-slate-500">ยังไม่มีรายได้เสริมอื่นในเดือนนี้</div>
                            @endif
                        </div>

                        {{-- Total Income --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900 dark:border-slate-800 dark:text-white">
                            <span>รายได้รวมทั้งหมด</span>
                            <span>฿{{ $fmtMoney($incomeTotal) }}</span>
                        </div>
                    </div>
                    
                    {{-- 2.2 SAVINGS & INVESTMENTS --}}
                    <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-5">
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
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">งบประมาณ (บาท)</label>
                                        <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">ออมจริง (บาท - ไม่จำเป็น)</label>
                                        <input type="number" step="0.01" name="actual_amount" placeholder="0.00"
                                               class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    </div>
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
                                     x-data="{ 
                                         editing: false, 
                                         label: '{{ addslashes($item->label) }}', 
                                         amount: {{ $item->amount }}, 
                                         actual_amount: {{ $item->actual_amount !== null ? $item->actual_amount : 'null' }},
                                         computed_actual: {{ $getItemActual($item) }},
                                         has_ledger: {{ $item->ledgerEntries->sum('amount') > 0 ? 'true' : 'false' }},
                                         notes: '{{ addslashes($item->notes) }}',
                                         checked: {{ $item->is_checked ? 'true' : 'false' }}
                                     }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <input type="checkbox" 
                                               :checked="checked"
                                               @change="checked = await toggleCheck('{{ route('portfolio.budget.toggle', ['type' => 'item', 'id' => $item->id]) }}')"
                                               class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800"
                                                  :class="checked ? 'line-through text-slate-400' : ''">
                                                {{ $item->label }}
                                            </span>
                                            @if($item->notes)
                                                <p class="text-[10px] text-slate-400 truncate">{{ $item->notes }}</p>
                                            @endif
                                            @if(str_contains($item->label, 'สำรองฉุกเฉิน') && $emergencyFundGoal > 0)
                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                        <div class="h-full rounded-full transition-all duration-500 ease-out"
                                                             :class="totals.emergencyFundTotal >= {{ $emergencyFundGoal }} ? 'bg-emerald-500' : 'bg-brand-500'"
                                                             :style="'width: ' + Math.min(100, (totals.emergencyFundTotal / {{ $emergencyFundGoal }}) * 100) + '%'"></div>
                                                    </div>
                                                    <span class="text-[9px] font-bold text-slate-500 whitespace-nowrap" x-text="Math.round((totals.emergencyFundTotal / {{ $emergencyFundGoal }}) * 100) + '% เป้าหมาย'"></span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-slate-900 whitespace-nowrap"
                                                  :class="checked ? 'text-slate-400' : ''">
                                                ฿{{ $fmtMoney($item->amount) }}
                                            </span>
                                            <div class="flex items-center justify-end gap-1 text-[10px] text-slate-500 whitespace-nowrap">
                                                <span x-show="has_ledger && actual_amount === null" class="text-blue-500" title="ดึงข้อมูลจาก Ledger อัตโนมัติ">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </span>
                                                <span x-text="'ใช้จริง: ฿' + fmtMoney(computed_actual)">ใช้จริง: ฿{{ $fmtMoney($getItemActual($item)) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
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
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[9px] text-slate-500 mb-0.5">งบประมาณ</label>
                                                    <input type="number" step="0.01" name="amount" x-model="amount" required class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] text-slate-500 mb-0.5">ใช้จริง</label>
                                                    <input type="number" step="0.01" name="actual_amount" x-model="actual_amount" class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ยังไม่ได้ระบุ">
                                                </div>
                                            </div>
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
                            <span>รวมเงินออม/ลงทุน (ออมจริง / เป้า)</span>
                            <span>
                                <span x-text="'฿' + fmtMoney(totals.actualSavingsTotal)">฿{{ $fmtMoney($actualSavingsTotal) }}</span>
                                <span class="text-xs font-normal text-slate-500">/ ฿{{ $fmtMoney($savingsTotal) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- COLUMN 3: INSTALLMENT DEBTS & SUBSCRIPTIONS --}}
                <div class="space-y-6">
                    
                    {{-- 3.1 DEBTS & INSTALLMENTS --}}
                    <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-5">
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
                                         notes: '{{ addslashes($inst->notes) }}',
                                         checked: {{ $inst->is_checked ? 'true' : 'false' }}
                                     }">
                                    
                                    {{-- Standard read-only view --}}
                                    <div x-show="!editing" class="space-y-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" 
                                                       :checked="checked"
                                                       @change="checked = await toggleCheck('{{ route('portfolio.budget.toggle', ['type' => 'installment', 'id' => $inst->id]) }}')"
                                                       class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                <div>
                                                    <span class="text-sm font-bold text-slate-800"
                                                          :class="checked ? 'line-through text-slate-400' : ''">
                                                        {{ $inst->label }}
                                                    </span>
                                                    <p class="text-[10px] text-slate-500">
                                                        ยอดต่อเดือน: ฿{{ $fmtMoney($inst->monthly_payment) }} (งวด {{ $inst->paid_months }}/{{ $inst->total_months }})
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
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

                                        @php $insLedgerActual = (float) ($installmentLedger[$inst->label] ?? 0); @endphp
                                        @if($insLedgerActual > 0)
                                            <div class="flex items-center gap-1 text-[10px] font-semibold text-blue-600">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span title="ดึงจากรายรับรายจ่ายอัตโนมัติ">ใช้จริง: ฿{{ $fmtMoney($insLedgerActual) }}</span>
                                            </div>
                                        @endif

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

                        {{-- 3.1b VARIABLE DEBTS (ผ่อนตามตาราง) --}}
                        @if($debts->count() > 0 || true)
                            @php
                                $thaiMonthsShort = [
                                    '01'=>'ม.ค.','02'=>'ก.พ.','03'=>'มี.ค.','04'=>'เม.ย.',
                                    '05'=>'พ.ค.','06'=>'มิ.ย.','07'=>'ก.ค.','08'=>'ส.ค.',
                                    '09'=>'ก.ย.','10'=>'ต.ค.','11'=>'พ.ย.','12'=>'ธ.ค.'
                                ];
                                $thMonthLabel = function ($ym) use ($thaiMonthsShort) {
                                    [$y, $m] = explode('-', $ym);
                                    return ($thaiMonthsShort[$m] ?? $m) . ' ' . ((int)$y + 543);
                                };
                            @endphp
                            <div class="border-t border-slate-100 pt-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">ผ่อนตามตาราง</p>
                                    <button @click="addDebtOpen = !addDebtOpen"
                                            class="rounded-lg border border-slate-200 p-1 text-slate-600 hover:bg-slate-50 transition"
                                            title="เพิ่มหนี้ผ่อนตามตาราง">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Add Debt Form --}}
                                <div x-show="addDebtOpen" x-cloak class="rounded-xl bg-slate-50 p-4 border border-slate-200 space-y-3">
                                    <form method="POST" action="{{ route('portfolio.budget.debts.store') }}" class="space-y-3">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $activeMonth }}">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อหนี้</label>
                                            <input type="text" name="label" required placeholder="เช่น TikTok Paylater, SPayLater"
                                                   class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">ยอดหนี้รวมทั้งหมด (ไม่บังคับ)</label>
                                            <input type="number" step="0.01" name="total_amount" placeholder="0.00"
                                                   class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">หมายเหตุ (ไม่บังคับ)</label>
                                            <input type="text" name="notes" placeholder="ข้อมูลเพิ่มเติม"
                                                   class="block w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        </div>
                                        <div class="flex justify-end gap-2 pt-1">
                                            <button type="button" @click="addDebtOpen = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">ยกเลิก</button>
                                            <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700">เพิ่มหนี้</button>
                                        </div>
                                    </form>
                                </div>

                                {{-- List of Variable Debts --}}
                                @forelse($debts as $debt)
                                    @if(str_contains($debt->label, 'กยศ'))
                                        @include('portfolio.partials.koyoso-debt-card', ['debt' => $debt])
                                    @else
                                    @php
                                        $currPay = $debt->payments->firstWhere('month', $activeMonth);
                                        $paidSum = $debt->payments->where('is_paid', true)->sum('amount');
                                        $progressPct = $debt->total_amount > 0
                                            ? min(100, round($paidSum / $debt->total_amount * 100))
                                            : 0;
                                    @endphp
                                    <div class="rounded-xl border border-slate-150 bg-slate-50/50 p-4 space-y-2"
                                         x-data="{
                                             scheduleOpen: false,
                                             editing: false,
                                             label: '{{ addslashes($debt->label) }}',
                                             total_amount: {{ $debt->total_amount }},
                                             notes: '{{ addslashes($debt->notes) }}',
                                             checked: {{ ($currPay && $currPay->is_paid) ? 'true' : 'false' }}
                                         }">

                                        {{-- Read view --}}
                                        <div x-show="!editing" class="flex items-start justify-between gap-3">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                {{-- Checkbox for current month's payment --}}
                                                @if($currPay)
                                                    <input type="checkbox" 
                                                           :checked="checked"
                                                           @change="checked = await toggleCheck('{{ route('portfolio.budget.toggle', ['type' => 'debt-payment', 'id' => $currPay->id]) }}')"
                                                           class="mt-0.5 h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                @else
                                                    <div class="mt-0.5 h-4 w-4 shrink-0 rounded border-2 border-dashed border-slate-200"></div>
                                                @endif

                                                <div class="min-w-0 flex-1">
                                                    <span class="text-sm font-bold text-slate-800"
                                                          :class="checked ? 'line-through text-slate-400' : ''">
                                                        {{ $debt->label }}
                                                    </span>
                                                    <p class="text-[10px] text-slate-500">
                                                        @if($currPay)
                                                            งวดเดือนนี้: ฿{{ $fmtMoney($currPay->amount) }}
                                                        @else
                                                            ไม่มีงวดชำระเดือนนี้
                                                        @endif
                                                    </p>
                                                    @if($debt->notes)
                                                        <p class="text-[10px] text-slate-400">{{ $debt->notes }}</p>
                                                    @endif
                                                </div>

                                                @if($currPay)
                                                    <span class="text-sm font-bold whitespace-nowrap"
                                                          :class="checked ? 'text-slate-400' : 'text-slate-900'">
                                                        ฿{{ $fmtMoney($currPay->amount) }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <button @click="scheduleOpen = !scheduleOpen"
                                                        class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 hover:bg-slate-50 transition">
                                                    <span x-text="scheduleOpen ? 'ซ่อน' : 'ตาราง'">ตาราง</span>
                                                </button>
                                                <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
                                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <form method="POST" action="{{ route('portfolio.budget.debts.destroy', $debt) }}"
                                                      data-confirm="ลบหนี้ '{{ $debt->label }}' และตารางผ่อนทั้งหมด?"
                                                      data-confirm-danger="1">
                                                    @csrf @method('DELETE')
                                                    <input type="hidden" name="month" value="{{ $activeMonth }}">
                                                    <button type="submit" class="text-rose-400 hover:text-rose-600 p-0.5 rounded transition">
                                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Edit debt header --}}
                                        <div x-show="editing" x-cloak class="w-full">
                                            <form method="POST" action="{{ route('portfolio.budget.debts.update', $debt) }}"
                                                  class="space-y-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="month" value="{{ $activeMonth }}">
                                                <input type="text" name="label" x-model="label" required
                                                       class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="ชื่อหนี้">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-slate-600 mb-0.5">ยอดหนี้รวม (ถ้ามี)</label>
                                                        <input type="number" step="0.01" name="total_amount" x-model="total_amount"
                                                               class="block w-full rounded border-slate-300 px-2 py-1 text-xs">
                                                    </div>
                                                </div>
                                                <input type="text" name="notes" x-model="notes"
                                                       class="block w-full rounded border-slate-300 px-2 py-1 text-xs" placeholder="หมายเหตุ">
                                                <div class="flex justify-end gap-1.5">
                                                    <button type="button" @click="editing = false" class="rounded px-2 py-1 text-[10px] bg-slate-200 text-slate-700 font-semibold">ยกเลิก</button>
                                                    <button type="submit" class="rounded bg-brand-600 px-2 py-1 text-[10px] text-white font-semibold">บันทึก</button>
                                                </div>
                                            </form>
                                        </div>

                                        {{-- Schedule table (expandable) --}}
                                        <div x-show="scheduleOpen" x-cloak class="border-t border-slate-100 pt-2 space-y-1">
                                            @foreach($debt->payments->sortBy('month') as $pay)
                                                @php $isNow = $pay->month === $activeMonth; @endphp
                                                <div class="flex items-center gap-2"
                                                     x-data="{
                                                         editP: false,
                                                         editAmt: {{ $pay->amount }},
                                                         editNotes: '{{ addslashes($pay->notes) }}',
                                                         isPaid: {{ $pay->is_paid ? 'true' : 'false' }},
                                                         async toggle() {
                                                             try {
                                                                 const r = await fetch('{{ route('portfolio.budget.toggle', ['type' => 'debt-payment', 'id' => $pay->id]) }}', {
                                                                     method: 'POST',
                                                                     headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
                                                                 });
                                                                 if (r.ok) { const d = await r.json(); if (d.success) this.isPaid = d.is_checked; }
                                                             } catch(e) { console.error(e); }
                                                         }
                                                     }">

                                                    {{-- Read row --}}
                                                    <div x-show="!editP" class="flex items-center gap-2 flex-1 min-w-0">
                                                        {{-- Clickable paid toggle --}}
                                                        <button type="button" @click="toggle()"
                                                                class="shrink-0 flex items-center justify-center w-4 h-4 cursor-pointer focus:outline-none">
                                                            <svg x-show="isPaid" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" class="text-emerald-500">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            <div x-show="!isPaid"
                                                                 class="h-3 w-3 rounded-full border-2 {{ $isNow ? 'border-brand-500' : 'border-slate-200' }}"></div>
                                                        </button>
                                                        <span class="text-xs"
                                                              :class="isPaid ? 'text-slate-400 line-through' : '{{ $isNow ? 'font-bold text-slate-900' : 'text-slate-600' }}'">
                                                            {{ $thMonthLabel($pay->month) }}
                                                        </span>
                                                        @if($isNow)
                                                            <span class="text-[10px] text-brand-600 font-medium">(เดือนนี้)</span>
                                                        @endif
                                                        <span class="ml-auto text-xs whitespace-nowrap"
                                                              :class="isPaid ? 'text-slate-400' : 'text-slate-700'">
                                                            ฿{{ $fmtMoney($pay->amount) }}
                                                        </span>
                                                        <button @click="editP = true" class="text-slate-400 hover:text-slate-600 shrink-0">
                                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </button>
                                                        <form method="POST" action="{{ route('portfolio.budget.debt-payments.destroy', $pay) }}"
                                                              data-confirm="ลบงวด {{ $thMonthLabel($pay->month) }}?">
                                                            @csrf @method('DELETE')
                                                            <input type="hidden" name="month" value="{{ $activeMonth }}">
                                                            <button type="submit" class="text-rose-400 hover:text-rose-500 shrink-0">
                                                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>

                                                    {{-- Edit row --}}
                                                    <form x-show="editP" x-cloak method="POST"
                                                          action="{{ route('portfolio.budget.debt-payments.update', $pay) }}"
                                                          class="flex gap-1 flex-1 items-center min-w-0">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="month" value="{{ $activeMonth }}">
                                                        <span class="text-[10px] text-slate-500 shrink-0 w-14">{{ $thMonthLabel($pay->month) }}</span>
                                                        <input type="number" step="0.01" name="amount" x-model="editAmt" required
                                                               class="block w-full rounded border-slate-300 px-1.5 py-0.5 text-xs min-w-0">
                                                        <input type="text" name="notes" x-model="editNotes"
                                                               class="block w-16 rounded border-slate-300 px-1.5 py-0.5 text-xs" placeholder="หมายเหตุ">
                                                        <button type="submit" class="rounded bg-brand-600 px-1.5 py-0.5 text-[10px] text-white font-semibold shrink-0">บันทึก</button>
                                                        <button type="button" @click="editP = false" class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] text-slate-600 font-semibold shrink-0">ยกเลิก</button>
                                                    </form>
                                                </div>
                                            @endforeach

                                            {{-- Add payment entry --}}
                                            <div class="pt-1" x-data="{ addP: false, newMonth: '{{ $activeMonth }}', newAmt: '' }">
                                                <button @click="addP = !addP"
                                                        class="flex items-center gap-1 text-[10px] font-semibold text-brand-600 hover:text-brand-700">
                                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    เพิ่มงวดชำระ
                                                </button>
                                                <div x-show="addP" x-cloak class="mt-1.5">
                                                    <form method="POST" action="{{ route('portfolio.budget.debt-payments.store') }}"
                                                          class="flex flex-wrap gap-1.5 items-center">
                                                        @csrf
                                                        <input type="hidden" name="debt_id" value="{{ $debt->id }}">
                                                        <input type="hidden" name="redirect_month" value="{{ $activeMonth }}">
                                                        <input type="month" name="month" x-model="newMonth" required
                                                               class="rounded border-slate-300 px-1.5 py-0.5 text-xs">
                                                        <input type="number" step="0.01" name="amount" x-model="newAmt" required
                                                               placeholder="จำนวนเงิน"
                                                               class="rounded border-slate-300 px-1.5 py-0.5 text-xs w-28">
                                                        <button type="submit" class="rounded bg-brand-600 px-2 py-0.5 text-[10px] text-white font-semibold">เพิ่ม</button>
                                                        <button type="button" @click="addP = false" class="rounded bg-slate-200 px-2 py-0.5 text-[10px] text-slate-600 font-semibold">ยกเลิก</button>
                                                    </form>
                                                </div>
                                            </div>

                                            {{-- Progress bar (if total_amount set) --}}
                                            @if($debt->total_amount > 0)
                                                <div class="pt-2 border-t border-slate-100 space-y-1">
                                                    <div class="w-full rounded-full overflow-hidden bg-slate-200" style="height:6px">
                                                        <div class="bg-brand-600 h-full transition-all" style="width:{{ $progressPct }}%"></div>
                                                    </div>
                                                    <div class="flex justify-between text-[10px] text-slate-500">
                                                        <span>ชำระแล้ว {{ $progressPct }}%</span>
                                                        <span>฿{{ $fmtMoney($paidSum) }} / ฿{{ $fmtMoney((float)$debt->total_amount) }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-2">ยังไม่มีหนี้ผ่อนตามตาราง กดปุ่ม "+" เพื่อเพิ่ม</p>
                                @endforelse
                            </div>
                        @endif

                        {{-- Total monthly installments + non-กยศ debts --}}
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-sm font-bold text-slate-900">
                            <span>รวมผ่อนต่อเดือน (ใช้จริง / งบ)</span>
                            <span>
                                <span x-text="'฿' + fmtMoney(parseFloat(totals.actualInstallments || 0) + parseFloat(totals.actualNonKoyosoDebts || 0))">฿{{ $fmtMoney($actualInstallmentsPaymentSum + $actualNonKoyosoDebtPaymentsSum) }}</span>
                                <span class="text-xs font-normal text-slate-500">/ ฿{{ $fmtMoney($installmentsPaymentSum + $nonKoyosoDebtPaymentsSum) }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- 3.2 SUBSCRIPTIONS --}}
                    <div class="rounded-3xl ring-1 ring-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] space-y-5">
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
                                     x-data="{ 
                                         editing: false, 
                                         label: '{{ addslashes($sub->label) }}', 
                                         monthly_payment: {{ $sub->monthly_payment }}, 
                                         billing_day: '{{ $sub->billing_day }}', 
                                         notes: '{{ addslashes($sub->notes) }}',
                                         checked: {{ $sub->is_checked ? 'true' : 'false' }}
                                     }">
                                    
                                    {{-- Read mode --}}
                                    <div x-show="!editing" class="flex items-center gap-3 flex-1 min-w-0">
                                        <input type="checkbox" 
                                               :checked="checked"
                                               @change="checked = await toggleCheck('{{ route('portfolio.budget.toggle', ['type' => 'subscription', 'id' => $sub->id]) }}')"
                                               class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-slate-800"
                                                  :class="checked ? 'line-through text-slate-400' : ''">
                                                {{ $sub->label }}
                                            </span>
                                            <div class="flex flex-col text-[10px] text-slate-500 mt-0.5">
                                                @if($sub->billing_day)
                                                    <span>ตัดเงินทุกวันที่ {{ $sub->billing_day }} ของเดือน</span>
                                                @endif
                                                @php $subLedgerActual = (float) ($subscriptionLedger[$sub->label] ?? 0); @endphp
                                                @if($subLedgerActual > 0)
                                                    <span class="font-semibold text-blue-600" title="ดึงจากรายรับรายจ่ายอัตโนมัติ">ใช้จริง: ฿{{ $fmtMoney($subLedgerActual) }}</span>
                                                @endif
                                                @if($sub->notes)
                                                    <span class="text-slate-400 truncate">{{ $sub->notes }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 whitespace-nowrap"
                                              :class="checked ? 'text-slate-400' : ''">
                                            ฿{{ $fmtMoney($sub->monthly_payment) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="editing = true" class="text-slate-500 hover:text-slate-700 p-0.5 rounded transition">
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

        {{-- Modal: Record / Update Salary Payslip (Current & Past Months) --}}
        <div x-show="payslipModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div x-show="payslipModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="payslipModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

            {{-- Modal Dialog Panel --}}
            <div x-show="payslipModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-xl rounded-3xl bg-white p-6 sm:p-8 shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 dark:ring-slate-800 my-8">

                {{-- Close button --}}
                <button type="button" @click="payslipModalOpen = false" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- Modal Header --}}
                <div class="mb-5">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 text-lg dark:bg-emerald-400/10 dark:text-emerald-400">
                            💼
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">บันทึกสลิปเงินเดือน (Payslip Breakdown)</h3>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        กรอกรายละเอียดสลิปเงินเดือนแบบแจกแจง สามารถเลือกบันทึกย้อนหลังได้ทุกงวด
                    </p>
                </div>

                <form method="POST" action="{{ route('portfolio.budget.payslip.store') }}" class="space-y-4">
                    @csrf

                    {{-- 1. Month & Employer --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                งวดประจำเดือน (Month) <span class="text-rose-500">*</span>
                            </label>
                            <input type="month" name="month" x-model="payslipForm.month" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <button type="button" @click="payslipForm.month = '2026-11'" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300">พ.ย. 69</button>
                                <button type="button" @click="payslipForm.month = '2026-10'" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300">ต.ค. 69 (ย้อนหลัง)</button>
                                <button type="button" @click="payslipForm.month = '2026-09'" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300">ก.ย. 69 (ย้อนหลัง)</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                บริษัท / นายจ้าง <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="company" x-model="payslipForm.company" required placeholder="เช่น บจก. ดูดีพ"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                        </div>
                    </div>

                    {{-- 2. รายรับ (Gross Earnings) --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3.5 space-y-3 dark:border-slate-800 dark:bg-slate-800/40">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span>1. รายรับทั้งหมด (Gross Earnings)</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="'รวม ฿' + formatNum(calculateGross())"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    ฐานเงินเดือน (บาท) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" name="base_salary" x-model.number="payslipForm.base_salary" required placeholder="30000.00"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    ค่าล่วงเวลา / OT (บาท)
                                </label>
                                <input type="number" step="0.01" name="ot" x-model.number="payslipForm.ot" placeholder="3000.00"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                <div class="flex gap-1 mt-1">
                                    <button type="button" @click="payslipForm.ot = 0" class="text-[9px] px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700 dark:text-slate-300">0</button>
                                    <button type="button" @click="payslipForm.ot = 1000" class="text-[9px] px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700 dark:text-slate-300">+1k</button>
                                    <button type="button" @click="payslipForm.ot = 3000" class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 font-bold">+3k</button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    โบนัส / รายได้อื่น (บาท)
                                </label>
                                <input type="number" step="0.01" name="bonus" x-model.number="payslipForm.bonus" placeholder="0.00"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            </div>
                        </div>
                    </div>

                    {{-- 3. รายการหัก (Deductions) --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3.5 space-y-3 dark:border-slate-800 dark:bg-slate-800/40">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span>2. รายการหัก (Deductions)</span>
                            <span class="text-rose-500 dark:text-rose-400 font-bold" x-text="'หักรวม -฿' + formatNum(calculateDeductions())"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    หัก ประกันสังคม (บาท)
                                </label>
                                <input type="number" step="0.01" name="social_security" x-model.number="payslipForm.social_security" placeholder="875.00"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                <div class="flex gap-1 mt-1">
                                    <button type="button" @click="payslipForm.social_security = 875" class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 font-bold">875</button>
                                    <button type="button" @click="payslipForm.social_security = 750" class="text-[9px] px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700 dark:text-slate-300">750</button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    หัก ภาษีหัก ณ ที่จ่ายจริง (บาท)
                                </label>
                                <input type="number" step="0.01" name="tax" x-model.number="payslipForm.tax" placeholder="314.00"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                <div class="flex gap-1 mt-1">
                                    <button type="button" @click="payslipForm.tax = 314" class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 font-bold">314</button>
                                    <button type="button" @click="payslipForm.tax = 0" class="text-[9px] px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700 dark:text-slate-300">0</button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    หัก อื่นๆ / กองทุน (บาท)
                                </label>
                                <input type="number" step="0.01" name="other_deduct" x-model.number="payslipForm.other_deduct" placeholder="0.00"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            </div>
                        </div>
                    </div>

                    {{-- 4. LIVE PREVIEW: สรุปยอดเงินได้สุทธิ (Actual Net Pay) --}}
                    <div class="rounded-2xl border-2 border-emerald-500/30 bg-emerald-50/50 p-4 dark:bg-emerald-950/20 dark:border-emerald-500/40">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 mb-2">
                            3. สรุปยอดเงินได้สุทธิ (Actual Net Pay)
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-700 dark:text-slate-300">
                                <span>รวมรายรับ (เงินเดือน + OT)</span>
                                <span class="font-bold text-slate-900 dark:text-white" x-text="'฿' + formatNum(calculateGross())"></span>
                            </div>
                            <div class="flex justify-between text-rose-600 dark:text-rose-400">
                                <span>หัก ประกันสังคม</span>
                                <span class="font-medium" x-text="'-฿' + formatNum(payslipForm.social_security)"></span>
                            </div>
                            <div class="flex justify-between text-rose-600 dark:text-rose-400">
                                <span>หัก ภาษีหัก ณ ที่จ่ายจริง</span>
                                <span class="font-medium" x-text="'-฿' + formatNum(payslipForm.tax)"></span>
                            </div>
                            <template x-if="payslipForm.other_deduct > 0">
                                <div class="flex justify-between text-rose-600 dark:text-rose-400">
                                    <span>หัก อื่นๆ</span>
                                    <span class="font-medium" x-text="'-฿' + formatNum(payslipForm.other_deduct)"></span>
                                </div>
                            </template>
                            <div class="border-t border-emerald-500/30 pt-2 flex items-center justify-between">
                                <span class="text-sm font-black text-slate-900 dark:text-white">เงินได้สุทธิที่ได้รับจริง</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400" x-text="'฿' + formatNum(calculateNet())"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">หมายเหตุเพิ่มเติม</label>
                        <input type="text" name="notes" x-model="payslipForm.notes" placeholder="เช่น เงินเดือนเข้าวันที่ 25, รวม OT 15 ชม."
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="payslipModalOpen = false"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition dark:text-slate-300 dark:hover:bg-slate-800">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-700 transition">
                            💾 บันทึกสลิปเงินเดือน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-portfolio-layout>
