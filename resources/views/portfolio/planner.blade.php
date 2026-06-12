<x-portfolio-layout>
    @php
        $fmtMoney = fn ($n) => number_format((float) $n, 2);
        
        $kindBadge = [
            'stock'      => 'bg-sky-50 text-sky-700 ring-sky-200',
            'deposit'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'cash'       => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'debt'       => 'bg-rose-50 text-rose-700 ring-rose-200',
            'gold'       => 'bg-amber-50 text-amber-700 ring-amber-200',
            'crypto'     => 'bg-violet-50 text-violet-700 ring-violet-200',
            'fund'       => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'realestate' => 'bg-stone-100 text-stone-700 ring-stone-200',
            'other'      => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];

        // Format What-if holdings data for JSON serialization
        $holdingsJson = $holdings->map(function ($h) {
            $qty = (float) $h->quantity;
            $price = (float) $h->current_price;
            $valThb = (float) $h->current_value_thb;
            $denom = $qty * $price;
            $fx = $denom > 0 ? ($valThb / $denom) : 1.0;
            return [
                'id' => $h->id,
                'label' => $h->label,
                'kind' => $h->kind,
                'quantity' => $qty,
                'price' => $price,
                'currency' => $h->currency,
                'fx_rate' => $fx,
                'is_debt' => $h->isDebt(),
            ];
        });

        // Performers list sorted by gain percentage
        $performers = $holdings->filter(fn($h) => !$h->isDebt() && $h->cost_basis > 0)->map(function($h) {
            $cost = (float) $h->cost_basis;
            $val = (float) $h->current_value_thb;
            $gain = $val - $cost;
            $pct = $cost > 0 ? ($gain / $cost) * 100 : 0;
            return [
                'label' => $h->label,
                'cost' => $cost,
                'value' => $val,
                'gain' => $gain,
                'pct' => $pct,
                'kind' => $h->kind
            ];
        })->sortByDesc('pct');
    @endphp

    <div class="py-8" x-data="{ 
        activeTab: ['dca', 'goals', 'watchlist', 'whatif', 'performance'].includes(window.location.hash.substring(1)) ? window.location.hash.substring(1) : 'dca',
        init() {
            window.addEventListener('hashchange', () => {
                let h = window.location.hash.substring(1);
                if (['dca', 'goals', 'watchlist', 'whatif', 'performance'].includes(h)) {
                    this.activeTab = h;
                }
            });
        }
    }">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            
            {{-- Header section --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('app.portfolio.planner.heading') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ __('app.portfolio.planner.subheading') }}</p>
                </div>
            </div>

            {{-- Planner sub-tabs navigation --}}
            <div class="border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-2" aria-label="Planner Tabs">
                    <button @click="activeTab = 'dca'; window.location.hash = 'dca'"
                            :class="activeTab === 'dca' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            class="border-b-2 px-1 py-2.5 text-sm font-medium transition whitespace-nowrap">
                        {{ __('app.portfolio.planner.tabs.dca') }}
                    </button>
                    <button @click="activeTab = 'goals'; window.location.hash = 'goals'"
                            :class="activeTab === 'goals' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            class="border-b-2 px-1 py-2.5 text-sm font-medium transition whitespace-nowrap">
                        {{ __('app.portfolio.planner.tabs.goals') }}
                    </button>
                    <button @click="activeTab = 'watchlist'; window.location.hash = 'watchlist'"
                            :class="activeTab === 'watchlist' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            class="border-b-2 px-1 py-2.5 text-sm font-medium transition whitespace-nowrap">
                        {{ __('app.portfolio.planner.tabs.watchlist') }}
                    </button>
                    <button @click="activeTab = 'whatif'; window.location.hash = 'whatif'"
                            :class="activeTab === 'whatif' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            class="border-b-2 px-1 py-2.5 text-sm font-medium transition whitespace-nowrap">
                        {{ __('app.portfolio.planner.tabs.whatif') }}
                    </button>
                    <button @click="activeTab = 'performance'; window.location.hash = 'performance'"
                            :class="activeTab === 'performance' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            class="border-b-2 px-1 py-2.5 text-sm font-medium transition whitespace-nowrap">
                        {{ __('app.portfolio.planner.tabs.performance') }}
                    </button>
                </nav>
            </div>

            {{-- Tab 1: DCA Calculator --}}
            <div x-show="activeTab === 'dca'" class="space-y-6" x-cloak>
                <div class="grid gap-6 md:grid-cols-3"
                     x-data="{
                         initialCapital: 100000,
                         monthlyContribution: 5000,
                         annualReturn: 8,
                         years: 15,
                         get dcaResults() {
                             let init = parseFloat(this.initialCapital) || 0;
                             let monthly = parseFloat(this.monthlyContribution) || 0;
                             let rate = parseFloat(this.annualReturn) / 100 || 0;
                             let Y = parseInt(this.years) || 1;
                             
                             let balance = init;
                             let totalInvested = init;
                             let yearlyData = [];
                             
                             let monthlyRate = rate / 12;
                             
                             for (let y = 1; y <= Y; y++) {
                                 for (let m = 0; m < 12; m++) {
                                     balance = (balance + monthly) * (1 + monthlyRate);
                                     totalInvested += monthly;
                                 }
                                 yearlyData.push({
                                     year: y,
                                     invested: totalInvested,
                                     value: balance,
                                     gains: Math.max(0, balance - totalInvested)
                                 });
                             }
                             
                             return {
                                 totalInvested: totalInvested,
                                 futureValue: balance,
                                 totalGains: Math.max(0, balance - totalInvested),
                                 yearlyData: yearlyData
                             };
                         }
                     }">
                    
                    {{-- Form Sliders --}}
                    <div class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-1">
                        <h3 class="text-base font-semibold text-slate-900">ตั้งค่าแผน DCA</h3>
                        
                        {{-- Initial Capital --}}
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <label class="font-medium text-slate-700">เงินต้นเริ่มแรก (บาท)</label>
                                <span class="font-semibold text-brand-600" x-text="new Intl.NumberFormat().format(initialCapital)"></span>
                            </div>
                            <input type="range" min="0" max="1000000" step="5000" x-model="initialCapital"
                                   class="h-2 w-full cursor-pointer rounded-lg bg-slate-200 accent-brand-600">
                            <input type="number" x-model.number="initialCapital" class="mt-1 w-full rounded-lg border-slate-300 py-1.5 text-sm">
                        </div>

                        {{-- Monthly Contribution --}}
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <label class="font-medium text-slate-700">เงินออมต่อเดือน (บาท)</label>
                                <span class="font-semibold text-brand-600" x-text="new Intl.NumberFormat().format(monthlyContribution)"></span>
                            </div>
                            <input type="range" min="0" max="100000" step="500" x-model="monthlyContribution"
                                   class="h-2 w-full cursor-pointer rounded-lg bg-slate-200 accent-brand-600">
                            <input type="number" x-model.number="monthlyContribution" class="mt-1 w-full rounded-lg border-slate-300 py-1.5 text-sm">
                        </div>

                        {{-- Expected Annual Return --}}
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <label class="font-medium text-slate-700">ผลตอบแทนคาดหวังต่อปี (%)</label>
                                <span class="font-semibold text-brand-600" x-text="annualReturn + '%'"></span>
                            </div>
                            <input type="range" min="-10" max="30" step="0.5" x-model="annualReturn"
                                   class="h-2 w-full cursor-pointer rounded-lg bg-slate-200 accent-brand-600">
                            <input type="number" step="0.1" x-model.number="annualReturn" class="mt-1 w-full rounded-lg border-slate-300 py-1.5 text-sm">
                        </div>

                        {{-- Period --}}
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <label class="font-medium text-slate-700">ระยะเวลาลงทุน (ปี)</label>
                                <span class="font-semibold text-brand-600" x-text="years + ' ปี'"></span>
                            </div>
                            <input type="range" min="1" max="40" step="1" x-model="years"
                                   class="h-2 w-full cursor-pointer rounded-lg bg-slate-200 accent-brand-600">
                            <input type="number" x-model.number="years" class="mt-1 w-full rounded-lg border-slate-300 py-1.5 text-sm">
                        </div>
                    </div>

                    {{-- Output results and SVG Chart --}}
                    <div class="space-y-6 md:col-span-2">
                        {{-- Results Tiles --}}
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">มูลค่ารวมในอนาคต</div>
                                <div class="mt-2 text-2xl font-bold text-slate-900" x-text="'฿' + new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(dcaResults.futureValue)"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">เงินต้นสะสม</div>
                                <div class="mt-2 text-2xl font-bold text-slate-700" x-text="'฿' + new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(dcaResults.totalInvested)"></div>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5">
                                <div class="text-xs font-medium uppercase tracking-wider text-emerald-700">ผลตอบแทนสะสม</div>
                                <div class="mt-2 text-2xl font-bold text-emerald-800" x-text="'฿' + new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(dcaResults.totalGains)"></div>
                            </div>
                        </div>

                        {{-- SVG Chart --}}
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-900 mb-4">กราฟแสดงการเติบโตรายปี</h3>
                            <div class="relative h-64 w-full">
                                <svg viewBox="0 0 500 200" preserveAspectRatio="none" class="h-full w-full">
                                    <template x-for="(data, idx) in dcaResults.yearlyData" :key="idx">
                                        <g>
                                            {{-- Invested bar --}}
                                            <rect :x="idx * (500 / years) + 2"
                                                  :y="180 - (data.invested / dcaResults.futureValue) * 160"
                                                  :width="Math.max(1, (500 / years) - 4)"
                                                  :height="(data.invested / dcaResults.futureValue) * 160"
                                                  fill="#94a3b8" />
                                            {{-- Gains bar --}}
                                            <rect :x="idx * (500 / years) + 2"
                                                  :y="180 - (data.value / dcaResults.futureValue) * 160"
                                                  :width="Math.max(1, (500 / years) - 4)"
                                                  :height="(data.gains / dcaResults.futureValue) * 160"
                                                  fill="#059669" />
                                        </g>
                                    </template>
                                    {{-- Base line --}}
                                    <line x1="0" y1="180" x2="500" y2="180" stroke="#cbd5e1" stroke-width="1"/>
                                </svg>
                            </div>
                            <div class="mt-2 flex justify-between text-xs text-slate-500">
                                <span>ปีที่ 1</span>
                                <span x-text="'ปีที่ ' + years"></span>
                            </div>
                            <div class="mt-4 flex items-center justify-center gap-6 text-xs font-medium">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded bg-slate-400"></span>
                                    <span>เงินต้นสะสม</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded bg-emerald-600"></span>
                                    <span>ผลตอบแทนสะสม</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Investment Goals --}}
            <div x-show="activeTab === 'goals'" class="space-y-6" x-cloak>
                <div class="grid gap-6 md:grid-cols-3">
                    
                    {{-- Goal Creation Form --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-1 h-fit">
                        <h3 class="text-base font-semibold text-slate-900 mb-4">เพิ่มเป้าหมายใหม่</h3>
                        <form method="POST" action="{{ route('portfolio.goals.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ชื่อเป้าหมาย</label>
                                <input type="text" name="title" required placeholder="เช่น ซื้อบ้าน, แต่งงาน, เกษียณ"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">จำนวนเงินเป้าหมาย (บาท)</label>
                                <input type="number" name="target_amount" step="1000" required placeholder="เช่น 5000000"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">วันที่เป้าหมาย (ไม่บังคับ)</label>
                                <input type="date" name="target_date"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">หมายเหตุ (ไม่บังคับ)</label>
                                <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"></textarea>
                            </div>
                            <button type="submit"
                                    class="w-full justify-center inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                บันทึกเป้าหมาย
                            </button>
                        </form>
                    </div>

                    {{-- Goal List --}}
                    <div class="space-y-4 md:col-span-2">
                        @if($goals->isEmpty())
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                                ยังไม่มีการบันทึกเป้าหมายทางการเงินในอนาคต
                            </div>
                        @else
                            @foreach($goals as $goal)
                                @php
                                    $prog = $goal->progressPercent($netWorth);
                                @endphp
                                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4"
                                     x-data="{ editing: false, title: '{{ addslashes($goal->title) }}', target_amount: {{ $goal->target_amount }}, target_date: '{{ $goal->target_date ? $goal->target_date->format('Y-m-d') : '' }}', notes: '{{ addslashes($goal->notes) }}' }">
                                    
                                    {{-- Standard View --}}
                                    <div x-show="!editing">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h4 class="text-lg font-bold text-slate-900">{{ $goal->title }}</h4>
                                                @if($goal->target_date)
                                                    <p class="text-xs text-slate-500">เป้าหมายภายในปี {{ $goal->target_date->format('d/m/Y') }} ({{ $goal->target_date->diffForHumans() }})</p>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button @click="editing = true" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <form method="POST" action="{{ route('portfolio.goals.destroy', $goal) }}" data-confirm="ลบเป้าหมายนี้?" data-confirm-danger="1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded p-1 text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="mt-4 space-y-1.5">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-500">ความคืบหน้า (อ้างอิงจากทรัพย์สินสุทธิปัจจุบัน)</span>
                                                <span class="font-bold text-brand-600">{{ $prog }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                                <div class="bg-brand-600 h-full transition-all duration-500" style="width: {{ $prog }}%"></div>
                                            </div>
                                            <div class="flex justify-between text-xs text-slate-500">
                                                <span>ปัจจุบัน: ฿{{ $fmtMoney($netWorth) }}</span>
                                                <span>เป้าหมาย: ฿{{ $fmtMoney($goal->target_amount) }}</span>
                                            </div>
                                        </div>

                                        @if($goal->notes)
                                            <div class="mt-3 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                                                {{ $goal->notes }}
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Inline Edit Form --}}
                                    <div x-show="editing" x-cloak>
                                        <form method="POST" action="{{ route('portfolio.goals.update', $goal) }}" class="space-y-4">
                                            @csrf
                                            @method('PATCH')
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-700">ชื่อเป้าหมาย</label>
                                                    <input type="text" name="title" x-model="title" required
                                                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-700">จำนวนเงินเป้าหมาย (บาท)</label>
                                                    <input type="number" name="target_amount" x-model="target_amount" required
                                                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-700">วันที่เป้าหมาย (ไม่บังคับ)</label>
                                                    <input type="date" name="target_date" x-model="target_date"
                                                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-700">หมายเหตุ (ไม่บังคับ)</label>
                                                    <input type="text" name="notes" x-model="notes"
                                                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="editing = false"
                                                        class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                    ยกเลิก
                                                </button>
                                                <button type="submit"
                                                        class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">
                                                    บันทึกการเปลี่ยนแปลง
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab 3: Watchlist --}}
            <div x-show="activeTab === 'watchlist'" class="space-y-6" x-cloak>
                <div class="grid gap-6 md:grid-cols-3">
                    
                    {{-- Watchlist Form --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-1 h-fit">
                        <h3 class="text-base font-semibold text-slate-900 mb-4">เพิ่มรายการใน Watchlist</h3>
                        <form method="POST" action="{{ route('portfolio.watchlist.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ประเภททรัพย์สิน</label>
                                <select name="kind" required
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                    <option value="stock">หุ้น / กองทุน (Stock/Fund)</option>
                                    <option value="crypto">คริปโต (Crypto)</option>
                                    <option value="fund">กองทุนรวม (Fund/ETF)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ชื่อย่อ (Symbol)</label>
                                <input type="text" name="symbol" required placeholder="เช่น AAPL, PTT.BK, bitcoin"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                <p class="mt-1 text-xs text-slate-400">หุ้นไทยใส่ต่อท้ายด้วย .BK เช่น PTT.BK, คริปโตใส่เป็นชื่อเต็มตัวเล็ก เช่น bitcoin</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ชื่อแสดงผล</label>
                                <input type="text" name="label" required placeholder="เช่น หุ้น Apple, บิตคอยน์"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">สกุลเงินหลัก</label>
                                <select name="currency" required
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                    <option value="THB">THB (บาท)</option>
                                    <option value="USD">USD (ดอลลาร์สหรัฐ)</option>
                                    <option value="EUR">EUR (ยูโร)</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="w-full justify-center inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                เพิ่มรายการ
                            </button>
                        </form>
                    </div>

                    {{-- Watchlist Table --}}
                    <div class="space-y-4 md:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-900">ตารางติดตามราคา</h3>
                            <form method="POST" action="{{ route('portfolio.watchlist.refresh') }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    อัปเดตราคาล่าสุด
                                </button>
                            </form>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3">ประเภท</th>
                                        <th class="px-6 py-3">รายการ / Symbol</th>
                                        <th class="px-6 py-3 text-right">ราคาล่าสุด</th>
                                        <th class="px-6 py-3 text-right">ราคาในไทย (บาท)</th>
                                        <th class="px-6 py-3 text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-700">
                                    @if($watchlist->isEmpty())
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">ยังไม่มีรายการที่เพิ่มไว้ใน Watchlist</td>
                                        </tr>
                                    @else
                                        @foreach($watchlist as $item)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kindBadge[$item->kind] ?? 'bg-slate-100 text-slate-700' }}">
                                                        {{ strtoupper($item->kind) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="font-bold text-slate-900">{{ $item->label }}</div>
                                                    <div class="text-xs text-slate-500 font-mono">{{ $item->symbol }} ({{ $item->currency }})</div>
                                                </td>
                                                <td class="px-6 py-4 text-right font-semibold whitespace-nowrap">
                                                    @if($item->current_price)
                                                        {{ $item->currency }} {{ number_format($item->current_price, $item->kind === 'crypto' ? 2 : 4) }}
                                                    @else
                                                        <span class="text-slate-400 italic">รอราคา</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-right font-bold text-slate-900 whitespace-nowrap">
                                                    @if($item->current_price_thb)
                                                        ฿{{ number_format($item->current_price_thb, 2) }}
                                                    @else
                                                        <span class="text-slate-400 italic">รอราคา</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                                    <form method="POST" action="{{ route('portfolio.watchlist.destroy', $item) }}" data-confirm="ลบรายการนี้?" data-confirm-danger="1">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded p-1 text-rose-500 hover:bg-rose-50 transition">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 4: What-if Simulator --}}
            <div x-show="activeTab === 'whatif'" class="space-y-6" x-cloak
                 x-data="{
                     holdings: {{ json_encode($holdingsJson) }},
                     newHolding: { label: '', kind: 'stock', quantity: 1, price: 100, currency: 'THB', fx_rate: 1.0, is_debt: false },
                     actualNetWorth: {{ $netWorth }},
                     
                     addSimHolding() {
                         if (!this.newHolding.label) return;
                         let isDebt = this.newHolding.kind === 'debt';
                         let fx = 1.0;
                         if (this.newHolding.currency === 'USD') fx = 35.0;
                         else if (this.newHolding.currency === 'EUR') fx = 38.0;
                         
                         this.holdings.push({
                             id: 'sim_' + Date.now(),
                             label: this.newHolding.label,
                             kind: this.newHolding.kind,
                             quantity: parseFloat(this.newHolding.quantity) || 0,
                             price: parseFloat(this.newHolding.price) || 0,
                             currency: this.newHolding.currency,
                             fx_rate: fx,
                             is_debt: isDebt
                         });
                         this.newHolding.label = '';
                     },
                     removeHolding(id) {
                         this.holdings = this.holdings.filter(h => h.id !== id);
                     },
                     get simulatedNetWorth() {
                         let total = 0;
                         this.holdings.forEach(h => {
                             let val = h.quantity * h.price * h.fx_rate;
                             if (h.is_debt) {
                                 total -= val;
                             } else {
                                 total += val;
                             }
                         });
                         return total;
                     }
                 }">
                
                {{-- Comparison summary --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">มูลค่าพอร์ตสุทธิจริงปัจจุบัน</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900">฿{{ $fmtMoney($netWorth) }}</div>
                    </div>
                    <div class="rounded-2xl border border-brand-200 bg-brand-50/60 p-5">
                        <div class="text-xs font-medium uppercase tracking-wider text-brand-700">มูลค่าพอร์ตสุทธิจำลอง (What-if)</div>
                        <div class="mt-2 text-3xl font-bold text-brand-800" x-text="'฿' + new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(simulatedNetWorth)"></div>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    {{-- Form simulation helper --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-1 h-fit">
                        <h3 class="text-base font-semibold text-slate-900 mb-4">จำลองเพิ่มสินทรัพย์ / หนี้สิน</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ชื่อรายการจำลอง</label>
                                <input type="text" x-model="newHolding.label" placeholder="เช่น หุ้นปันผล A, กู้รถคันใหม่"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ประเภท</label>
                                <select x-model="newHolding.kind"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                    <option value="stock">หุ้น (Stock)</option>
                                    <option value="deposit">เงินฝาก (Deposit)</option>
                                    <option value="crypto">คริปโต (Crypto)</option>
                                    <option value="debt">หนี้สินจำลอง (Debt)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">จำนวนหน่วย</label>
                                <input type="number" step="0.01" x-model.number="newHolding.quantity"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">ราคาต่อหน่วยจำลอง</label>
                                <input type="number" step="0.01" x-model.number="newHolding.price"
                                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">สกุลเงิน</label>
                                <select x-model="newHolding.currency"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                    <option value="THB">THB</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <button type="button" @click="addSimHolding()"
                                    class="w-full justify-center inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                เพิ่มเข้าระบบจำลอง
                            </button>
                        </div>
                    </div>

                    {{-- Simulated Holdings list --}}
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-sm font-semibold text-slate-900">รายการพอร์ตจำลอง</h3>
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3">รายการ</th>
                                        <th class="px-6 py-3 text-right">จำนวน</th>
                                        <th class="px-6 py-3 text-right">ราคาต่อหน่วย</th>
                                        <th class="px-6 py-3 text-right">มูลค่ารวมจำลอง</th>
                                        <th class="px-6 py-3 text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-700">
                                    <template x-for="h in holdings" :key="h.id">
                                        <tr :class="h.id.toString().startsWith('sim_') ? 'bg-amber-50/50' : ''">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-bold text-slate-900" x-text="h.label"></div>
                                                <div class="text-xs text-slate-500" x-text="h.kind.toUpperCase() + (h.id.toString().startsWith('sim_') ? ' (จำลอง)' : '')"></div>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <input type="number" step="any" x-model.number="h.quantity"
                                                       class="w-24 text-right rounded border-slate-300 py-1 text-sm">
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <input type="number" step="any" x-model.number="h.price"
                                                       class="w-28 text-right rounded border-slate-300 py-1 text-sm">
                                                <span class="text-xs text-slate-500 font-mono" x-text="h.currency"></span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold whitespace-nowrap"
                                                :class="h.is_debt ? 'text-rose-700' : 'text-slate-900'"
                                                x-text="(h.is_debt ? '-' : '') + '฿' + new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(h.quantity * h.price * h.fx_rate)">
                                            </td>
                                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                                <button type="button" @click="removeHolding(h.id)"
                                                        class="rounded p-1 text-rose-500 hover:bg-rose-50 transition">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 5: Asset Performance --}}
            <div x-show="activeTab === 'performance'" class="space-y-6" x-cloak>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900 mb-6">เปรียบเทียบผลตอบแทนสินทรัพย์ (Gain / Loss %)</h3>
                    
                    @if($performers->isEmpty())
                        <p class="text-center text-sm italic text-slate-500">ต้องมีสินทรัพย์ประเภทการลงทุน (หุ้น/กองทุน/คริปโต) ที่ระบุต้นทุน เพื่อคำนวณผลตอบแทน</p>
                    @else
                        <div class="space-y-5">
                            @foreach($performers as $perf)
                                @php
                                    $isGain = $perf['gain'] >= 0;
                                    $pctAbs = min(100, abs($perf['pct']));
                                    $color = $isGain ? 'bg-emerald-500' : 'bg-rose-500';
                                    $textColor = $isGain ? 'text-emerald-700' : 'text-rose-700';
                                    $sign = $isGain ? '+' : '';
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex items-baseline justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900">{{ $perf['label'] }}</span>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kindBadge[$perf['kind']] ?? 'bg-slate-100 text-slate-700' }}">
                                                {{ strtoupper($perf['kind']) }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold {{ $textColor }}">{{ $sign }}{{ number_format($perf['pct'], 2) }}%</span>
                                            <span class="text-xs text-slate-500">({{ $sign }}฿{{ $fmtMoney($perf['gain']) }})</span>
                                        </div>
                                    </div>
                                    <div class="relative w-full bg-slate-100 rounded-full h-4 overflow-hidden">
                                        <div class="h-full {{ $color }} transition-all duration-500" style="width: {{ $pctAbs }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-portfolio-layout>
