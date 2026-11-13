<x-portfolio-layout>
    @php
        // Tailwind JIT palette for holding kinds
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

        // Group order — debts last so assets are front and center
        $kindOrder = ['stock', 'fund', 'crypto', 'deposit', 'cash', 'gold', 'realestate', 'other', 'debt'];

        $fmtMoney = fn ($n) => number_format((float) $n, 2);
        $fmtQty = fn ($n) => rtrim(rtrim(number_format((float) $n, 8, '.', ','), '0'), '.') ?: '0';

        $totalDebtsCombined = (float) ($totals['debts'] ?? 0) + (float) ($totals['budget_debts'] ?? 0);
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- ── 1. Header row: Title + Financial Cockpit Actions ─────────────────── --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">✨ ศูนย์บัญชาการการเงิน & พอร์ตลงทุน</h1>
                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold text-brand-700">
                            Financial Mission Control
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        ความมั่งคั่งสุทธิ · เงินสดพร้อมสะสมหุ้น DCA · จัดสัดส่วน Core & Satellite · AI วิเคราะห์หุ้น & สัญญาณตลาดสด
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ __('app.portfolio.last_refresh') }}:
                        @if ($lastRefresh)
                            <span class="font-medium text-slate-600">{{ \Carbon\Carbon::parse($lastRefresh)->diffForHumans() }}</span>
                        @else
                            <span class="italic">{{ __('app.portfolio.never_refreshed') }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('portfolio.ai.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:from-brand-500 hover:to-indigo-500 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        เปิด AI วิเคราะห์หุ้น
                    </a>
                    <a href="{{ route('portfolio.budget.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        แผนงบ & หนี้
                    </a>
                    <form method="POST" action="{{ route('portfolio.refresh') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm"
                                title="อัปเดตราคาตลาดสดล่าสุด">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            รีเฟรชราคา
                        </button>
                    </form>
                    <a href="{{ route('portfolio.holdings.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        เพิ่มสินทรัพย์
                    </a>
                </div>
            </div>

            {{-- ── 2. Top Tier: Wealth & Freedom Scoreboard (4 KPI Cards) ────────────── --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Tile 1: ความมั่งคั่งสุทธิ (Net Worth) --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">ความมั่งคั่งสุทธิ (Net Worth)</span>
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">สินทรัพย์ - หนี้สิน</span>
                    </div>
                    <div class="text-3xl font-black {{ $totals['net'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                        ฿{{ $fmtMoney($totals['net']) }}
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 pt-2">
                        <span>สินทรัพย์ ฿{{ $fmtMoney($totals['assets']) }}</span>
                        <span class="text-rose-600">หนี้รวม ฿{{ $fmtMoney($totalDebtsCombined) }}</span>
                    </div>
                </div>

                {{-- Tile 2: เงินสดพร้อมสะสมหุ้น DCA เดือนนี้ --}}
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">งบ DCA สะสมหุ้น (ตามแผนงบ)</span>
                        <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">{{ $activeMonth }}</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-700">
                        ฿{{ $fmtMoney($monthlyDcaCapacity) }}
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-emerald-800/80 border-t border-emerald-200/60 pt-2">
                        <span>รับ ฿{{ $fmtMoney($monthlyIncome) }} − รายจ่าย+หนี้ ฿{{ $fmtMoney($monthlyExpenses) }}</span>
                        <a href="{{ route('portfolio.ai.index') }}#sniper" class="font-bold underline hover:text-emerald-900">วางแผนซื้อ 3 ไม้ →</a>
                    </div>
                </div>

                {{-- Tile 3: ผลตอบแทนพอร์ตลงทุนรวม (Total Gain/Loss) --}}
                @php
                    $gainColor = $totals['gain'] >= 0 ? 'text-emerald-700' : 'text-rose-700';
                    $gainBg = $totals['gain'] >= 0 ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50/60 border-rose-200';
                    $gainSign = $totals['gain'] >= 0 ? '+' : '';
                @endphp
                <div class="rounded-2xl border {{ $gainBg }} p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider {{ $gainColor }}">กำไร/ขาดทุนพอร์ตลงทุนรวม</span>
                        <span class="rounded bg-white/70 px-1.5 py-0.5 text-[10px] font-black {{ $gainColor }}">
                            {{ $gainSign }}{{ number_format($totals['gain_pct'], 2) }}%
                        </span>
                    </div>
                    <div class="text-3xl font-black {{ $gainColor }}">
                        {{ $gainSign }}฿{{ $fmtMoney($totals['gain']) }}
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-200/50 pt-2">
                        <span>ต้นทุนรวม ฿{{ $fmtMoney($totals['cost']) }}</span>
                        <span>มูลค่าตลาด ฿{{ $fmtMoney($totals['assets']) }}</span>
                    </div>
                </div>

                {{-- Tile 4: อิสรภาพทางการเงิน (Passive Income / Freedom Ratio) --}}
                <div class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-800">ปันผลคาดการณ์ (Passive)</span>
                        <span class="rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold text-sky-700">~3.5% ต่อปี</span>
                    </div>
                    <div class="text-3xl font-black text-sky-800">
                        ฿{{ $fmtMoney($estMonthlyDividend) }}<span class="text-xs font-medium text-sky-600"> /ด.</span>
                    </div>
                    <div class="space-y-1 border-t border-sky-200/60 pt-2 text-[11px]">
                        <div class="flex justify-between text-sky-800/80">
                            <span>ครอบคลุมรายจ่าย</span>
                            <span class="font-bold text-sky-900">{{ $freedomRatio }}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-sky-200/70 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-sky-600 transition-all" style="width: {{ min(100, $freedomRatio) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── 3. Tier 2: AI Mission Control & Strategy Radar Banner ───────────── --}}
            <div class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1.5 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-indigo-600 animate-pulse"></span>
                            <h2 class="text-sm font-bold text-slate-900">AI Portfolio Copilot · ระบบเรดาร์เฝ้าระวังพอร์ตและวิเคราะห์หุ้น</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            กลยุทธ์สะสมของคุณ:
                            <span class="font-bold text-emerald-700">Core ชนะเงินเฟ้อ {{ $corePct }}%</span> (฿{{ $fmtMoney($coreValue) }}) :
                            <span class="font-bold text-purple-700">Satellite เกาะกระแส {{ $satellitePct }}%</span> (฿{{ $fmtMoney($satelliteValue) }})
                            @if($corePct >= 65)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 ml-1">สมดุลแข็งแกร่ง</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 ml-1">แนะนำเติม Core เพิ่ม</span>
                            @endif
                            · มีหุ้นในพอร์ต {{ $holdings->whereIn('kind', ['stock', 'fund'])->count() }} ตัว · พอร์ตจำลอง {{ $paperTradeCount }} ออเดอร์
                        </p>
                    </div>

                    {{-- Quick Action Command Pills --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('portfolio.ai.index') }}#prompts"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-50 transition shadow-xs">
                            <span>✨</span> 10 Prompt AI
                        </a>
                        <a href="{{ route('portfolio.ai.index') }}#chart"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-50 transition shadow-xs">
                            <span>📈</span> กราฟ TradingView
                        </a>
                        <a href="{{ route('portfolio.ai.index') }}#sniper"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-brand-200 bg-white px-3 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-50 transition shadow-xs">
                            <span>🎯</span> วางแผนซื้อ 3 ไม้
                        </a>
                        <a href="{{ route('portfolio.ai.index') }}#paper"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50 transition shadow-xs">
                            <span>🧪</span> พอร์ตจำลอง ({{ $paperTradeCount }})
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── 4. Tier 3: Core/Satellite Allocation + Net Worth Trend + Debt Engine ── --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Left Side (Col 1 & 2): Asset Allocation & 90-Day Trend Curve --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Card: สัดส่วนสินทรัพย์ Core vs Satellite และพอร์ตทั้งหมด --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">📊 สัดส่วนพอร์ตสินทรัพย์ (Core & Satellite Asset Allocation)</h3>
                                <p class="text-xs text-slate-500 mt-0.5">กระจายความเสี่ยงหุ้นชนะเงินเฟ้อ หุ้นเกาะกระแส และเงินสดสำรอง</p>
                            </div>
                            <span class="text-xs font-bold text-slate-700">สินทรัพย์รวม ฿{{ $fmtMoney($totals['assets']) }}</span>
                        </div>

                        {{-- Progress bar: Core vs Satellite --}}
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-emerald-700">🛡️ หุ้นชนะเงินเฟ้อ (Core) {{ $corePct }}% (฿{{ $fmtMoney($coreValue) }})</span>
                                <span class="text-purple-700">🚀 หุ้นเกาะกระแส (Satellite) {{ $satellitePct }}% (฿{{ $fmtMoney($satelliteValue) }})</span>
                            </div>
                            <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden flex">
                                <div class="h-3 bg-emerald-500 transition-all" style="width: {{ $corePct }}%"></div>
                                <div class="h-3 bg-purple-500 transition-all" style="width: {{ $satellitePct }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-400">
                                <span>เป้าหมายสมดุล: 70% Core (Defensive & Dividend)</span>
                                <span>30% Satellite (Growth & Trend)</span>
                            </div>
                        </div>

                        {{-- Asset Breakdown Chips --}}
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 pt-2">
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                                <span class="text-[10px] font-semibold text-slate-500 block">หุ้น & กองทุนรวม</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block">฿{{ $fmtMoney($totalInvestedStocks) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $totals['assets'] > 0 ? round(($totalInvestedStocks / $totals['assets']) * 100, 1) : 0 }}% ของพอร์ต</span>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                                <span class="text-[10px] font-semibold text-slate-500 block">เงินสด & เงินฝาก</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block">฿{{ $fmtMoney($cashDepositValue) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $totals['assets'] > 0 ? round(($cashDepositValue / $totals['assets']) * 100, 1) : 0 }}% ของพอร์ต</span>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                                <span class="text-[10px] font-semibold text-slate-500 block">ทองคำ</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block">฿{{ $fmtMoney($goldValue) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $totals['assets'] > 0 ? round(($goldValue / $totals['assets']) * 100, 1) : 0 }}% ของพอร์ต</span>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                                <span class="text-[10px] font-semibold text-slate-500 block">คริปโต & อื่นๆ</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block">฿{{ $fmtMoney($cryptoValue) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $totals['assets'] > 0 ? round(($cryptoValue / $totals['assets']) * 100, 1) : 0 }}% ของพอร์ต</span>
                            </div>
                        </div>
                    </div>

                    {{-- Trend chart (90-day net-worth line) --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex items-baseline justify-between border-b border-slate-100 pb-2.5">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ __('app.portfolio.trend_heading') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('app.portfolio.trend_subtitle') }}</p>
                            </div>
                            <span class="text-xs font-semibold text-slate-400">90 วันล่าสุด</span>
                        </div>

                        @if (count($trend) < 2)
                            <p class="py-8 text-center text-sm italic text-slate-500">{{ __('app.portfolio.trend_empty') }}</p>
                        @else
                            @php
                                $values = array_column($trend, 'net');
                                $min = min($values);
                                $max = max($values);
                                $span = max(1, $max - $min);
                                $width = 600;
                                $height = 140;
                                $count = count($trend);

                                $points = [];
                                foreach ($trend as $i => $row) {
                                    $x = ($i / ($count - 1)) * $width;
                                    $y = $height - (($row['net'] - $min) / $span) * ($height - 20) - 10;
                                    $points[] = round($x, 2) . ',' . round($y, 2);
                                }
                                $polyline = implode(' ', $points);
                                $area = '0,' . $height . ' ' . $polyline . ' ' . $width . ',' . $height;
                            @endphp
                            <div class="mt-2">
                                <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="h-36 w-full">
                                    <defs>
                                        <linearGradient id="netGrad" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.3"/>
                                            <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    <polygon points="{{ $area }}" fill="url(#netGrad)"/>
                                    <polyline points="{{ $polyline }}" fill="none" stroke="#0ea5e9" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div class="mt-2 flex justify-between text-xs text-slate-500">
                                    <span>{{ $trend[0]['d'] }} · ฿{{ $fmtMoney($trend[0]['net']) }}</span>
                                    <span class="font-bold text-slate-800">{{ end($trend)['d'] }} · ฿{{ $fmtMoney(end($trend)['net']) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right Side (Col 3): ท่อส่งเงินสดจาก "ปลดหนี้" สู่ "พอร์ตหุ้น" (Debt-to-Wealth Engine) --}}
                <div class="space-y-6">

                    {{-- กยศ Widget --}}
                    @if(!empty($koyoso))
                        @php
                            $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
                            [$njY, $njM] = explode('-', $koyoso['nextJulyMonth']);
                            $nextJulyLabel = $thMonths[(int)$njM] . ' ' . ((int)$njY + 543);
                        @endphp
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                        <h3 class="text-sm font-bold text-slate-900">{{ $koyoso['debt']->label }}</h3>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">15 งวด · ชำระทุกวันที่ 5 กรกฎาคม</p>
                                </div>
                                <a href="{{ route('portfolio.budget.index') }}"
                                   class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition">
                                    จัดการงบ →
                                </a>
                            </div>

                            {{-- Main stats --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <div class="text-[11px] font-semibold uppercase text-slate-400">หนี้คงเหลือรวม</div>
                                    <div class="text-xl font-black text-rose-700 mt-0.5">
                                        ฿{{ $fmtMoney($koyoso['remainingAmount']) }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">จาก ฿{{ $fmtMoney($koyoso['debt']->total_amount) }}</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <div class="text-[11px] font-semibold uppercase text-slate-400">สะสมสำเร็จ</div>
                                    <div class="text-xl font-black text-emerald-700 mt-0.5">
                                        {{ $koyoso['progressPct'] }}%
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">฿{{ $fmtMoney($koyoso['paidAmount']) }}</div>
                                </div>
                            </div>

                            {{-- Overall Progress bar --}}
                            <div class="space-y-1">
                                <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500"
                                         style="width: {{ $koyoso['progressPct'] }}%"></div>
                                </div>
                            </div>

                            {{-- Current งวด progress --}}
                            <div class="rounded-xl border border-sky-100 bg-sky-50/40 p-3 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-800">งวดที่ {{ $koyoso['installmentNo'] }} ({{ $nextJulyLabel }})</span>
                                    @if($koyoso['curIsPaid'])
                                        <span class="font-bold text-emerald-700">ครบแล้ว ✓</span>
                                    @else
                                        <span class="font-bold text-rose-700">เหลือ ฿{{ $fmtMoney($koyoso['curRemaining']) }}</span>
                                    @endif
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-1.5 rounded-full bg-sky-600" style="width: {{ $koyoso['curPct'] }}%"></div>
                                </div>
                                <div class="flex justify-between text-[10px] text-slate-500">
                                    <span>จ่ายแล้ว ฿{{ $fmtMoney($koyoso['curPaid']) }}</span>
                                    <span>เป้าหมาย ฿{{ $fmtMoney($koyoso['curTarget']) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Debt-to-Wealth Transformation Card --}}
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">💡</span>
                            <h3 class="text-sm font-bold text-slate-900">เครื่องจักรเปลี่ยนหนี้เป็นความมั่งคั่ง</h3>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            หนี้บัตร ค่างวด และ กยศ. ทุกบาทที่คุณผ่อนชำระหมด จะกลายเป็น <strong class="text-emerald-700">กระแสเงินสดสะสมหุ้น DCA อัตโนมัติ</strong>
                            ทำให้พอร์ตโตแบบทบต้นเร็วยิ่งขึ้นในระยะยาว
                        </p>
                        <div class="border-t border-emerald-100 pt-2 flex items-center justify-between text-xs">
                            <span class="text-slate-500">หนี้งบประมาณคงเหลือ:</span>
                            <span class="font-bold text-rose-700">฿{{ $fmtMoney($totals['budget_debts']) }}</span>
                        </div>
                        <a href="{{ route('portfolio.debts.index') }}"
                           class="block w-full rounded-xl border border-emerald-200 bg-white py-2 text-center text-xs font-bold text-emerald-700 hover:bg-emerald-50 transition shadow-xs">
                            ดูรายการหนี้สินทั้งหมด & ลำดับการปลดหนี้ →
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── 5. Tier 4: Live Holdings & Real-time Market Table ────────────────── --}}
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">💼 รายการสินทรัพย์สด & ติดตามราคา Real-time</h2>
                        <p class="text-xs text-slate-500 mt-0.5">อัปเดตราคาตลาดจาก Yahoo Finance อัตโนมัติ พร้อมส่งวิเคราะห์ AI</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">มีทั้งหมด {{ $holdings->count() }} รายการ</span>
                    </div>
                </div>

                @if ($holdings->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                        {{ __('app.portfolio.no_holdings') }}
                    </div>
                @else
                    @foreach ($kindOrder as $kind)
                        @php $group = $byKind->get($kind); @endphp
                        @if (! $group || $group->isEmpty()) @continue @endif

                        @php
                            $groupTotal = $group->sum(fn ($h) => (float) ($h->current_value_thb ?? 0));
                            $isDebtGroup = $kind === \App\Models\Portfolio\Holding::KIND_DEBT;
                        @endphp

                        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 bg-slate-50/50">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $kindBadge[$kind] }}">
                                        {{ __("app.portfolio.kinds.{$kind}") }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $group->count() }} รายการ</span>
                                </div>
                                <div class="text-sm font-bold {{ $isDebtGroup ? 'text-rose-700' : 'text-slate-900' }}">
                                    {{ $isDebtGroup ? '−' : '' }}฿{{ $fmtMoney($groupTotal) }}
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50/60 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-5 py-2.5 text-left font-medium">{{ __('app.portfolio.col_label') }}</th>
                                            <th class="px-5 py-2.5 text-left font-medium">{{ __('app.portfolio.col_symbol') }}</th>
                                            <th class="px-5 py-2.5 text-right font-medium">{{ __('app.portfolio.col_qty') }}</th>
                                            <th class="px-5 py-2.5 text-right font-medium">{{ __('app.portfolio.col_price') }}</th>
                                            <th class="px-5 py-2.5 text-right font-medium">{{ __('app.portfolio.col_value') }}</th>
                                            <th class="px-5 py-2.5 text-right font-medium">{{ __('app.portfolio.col_gain') }}</th>
                                            <th class="px-5 py-2.5 text-right font-medium">อัปเดต</th>
                                            <th class="px-5 py-2.5 text-right font-medium">การกระทำ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($group as $h)
                                            @php
                                                $val = (float) ($h->current_value_thb ?? 0);
                                                $cost = (float) $h->cost_basis;
                                                $gain = $isDebtGroup ? null : ($val - $cost);
                                                $gainPct = ($cost > 0 && $gain !== null) ? ($gain / $cost * 100) : null;
                                            @endphp
                                            <tr class="hover:bg-slate-50/60 transition">
                                                <td class="px-5 py-3">
                                                    <div class="font-bold text-slate-900">{{ $h->label }}</div>
                                                    @if ($h->notes)
                                                        <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($h->notes, 60) }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3 font-mono text-xs text-slate-700">
                                                    @if($h->symbol)
                                                        <span class="rounded bg-slate-100 px-2 py-0.5 font-bold text-slate-800">{{ $h->symbol }}</span>
                                                    @else
                                                        <span class="text-slate-400">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3 text-right tabular-nums text-slate-700 font-medium">{{ $fmtQty($h->quantity) }}</td>
                                                <td class="px-5 py-3 text-right tabular-nums text-slate-700">
                                                    {{ $h->current_price !== null ? $fmtMoney($h->current_price) : '—' }}
                                                    @if ($h->currency && $h->currency !== 'THB')
                                                        <span class="text-xs text-slate-400 ml-0.5">{{ $h->currency }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3 text-right tabular-nums font-bold {{ $isDebtGroup ? 'text-rose-700' : 'text-slate-900' }}">
                                                    {{ $isDebtGroup ? '−' : '' }}฿{{ $fmtMoney($val) }}
                                                </td>
                                                <td class="px-5 py-3 text-right tabular-nums">
                                                    @if ($gain === null)
                                                        <span class="text-slate-400">—</span>
                                                    @else
                                                        <div class="font-bold {{ $gain >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                            {{ $gain >= 0 ? '+' : '' }}฿{{ $fmtMoney($gain) }}
                                                        </div>
                                                        @if ($gainPct !== null)
                                                            <div class="text-xs font-semibold {{ $gain >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                {{ $gain >= 0 ? '+' : '' }}{{ number_format($gainPct, 2) }}%
                                                            </div>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3 text-right text-xs text-slate-500">
                                                    {{ $h->last_priced_at ? $h->last_priced_at->diffForHumans() : '—' }}
                                                </td>
                                                <td class="px-5 py-3 text-right">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        @if($h->symbol && in_array($h->kind, [\App\Models\Portfolio\Holding::KIND_STOCK, \App\Models\Portfolio\Holding::KIND_FUND]))
                                                            <a href="{{ route('portfolio.ai.index') }}"
                                                               class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition"
                                                               title="วิเคราะห์ด้วย AI">
                                                                ✨ วิเคราะห์
                                                            </a>
                                                        @endif
                                                        @if (in_array($h->kind, [\App\Models\Portfolio\Holding::KIND_CASH, \App\Models\Portfolio\Holding::KIND_DEPOSIT, \App\Models\Portfolio\Holding::KIND_DEBT], true))
                                                            <a href="{{ route('portfolio.holdings.transactions.index', $h) }}"
                                                               class="rounded-lg px-2 py-1 text-xs font-medium text-brand-600 transition hover:bg-brand-50 hover:text-brand-700">
                                                                สมุดบัญชี
                                                            </a>
                                                        @endif
                                                        <a href="{{ route('portfolio.holdings.edit', $h) }}"
                                                           class="rounded-lg px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                            แก้ไข
                                                        </a>
                                                        <form method="POST" action="{{ route('portfolio.holdings.destroy', $h) }}"
                                                              data-confirm="{{ __('app.portfolio.form.delete_confirm') }}" data-confirm-danger="1">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                    class="rounded-lg px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                                ลบ
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <p class="text-center text-xs text-slate-400">
                {{ __('app.portfolio.refreshing_hint') }} · ราคาหุ้นและคริปโตอัปเดตอัตโนมัติทุก 5 นาที
            </p>
        </div>
    </div>

    <script>
        // รีเฟรชราคาอัตโนมัติทุกๆ 5 นาที (300,000 มิลลิวินาที)
        setInterval(() => {
            fetch("{{ route('portfolio.refresh') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                window.location.reload();
            }).catch(err => console.error('Auto-refresh failed:', err));
        }, 300000);
    </script>
</x-portfolio-layout>
