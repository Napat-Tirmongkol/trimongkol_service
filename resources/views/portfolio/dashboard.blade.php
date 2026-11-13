<x-portfolio-layout>
    @php
        $fmtMoney = fn ($n) => number_format((float) $n, 2);
        $totalDebtsCombined = (float) ($totals['debts'] ?? 0) + (float) ($totals['budget_debts'] ?? 0);
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- ── 1. Hero Header & Mission Control Status ─────────────────────────── --}}
            <div class="rounded-2xl border border-slate-800 bg-[#121418] p-5 sm:p-6 shadow-xl">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-500/10 px-3 py-1 text-xs font-semibold text-brand-400 border border-brand-500/20">
                                <span class="h-2 w-2 rounded-full bg-brand-400 animate-pulse"></span>
                                Life Mission Control
                            </span>
                            <span class="rounded-full bg-slate-800 px-2.5 py-1 text-[11px] font-medium text-slate-300">
                                📅 {{ now()->locale('th')->isoFormat('D MMMM GGGG') }}
                            </span>
                        </div>
                        <h1 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">
                            ศูนย์บัญชาการชีวิตครบวงจร
                        </h1>
                        <p class="mt-1 text-sm text-slate-400">
                            มอนิเตอร์ภาพรวม 5 มิติ: ความมั่งคั่ง · เป้าหมายชีวิต · Second Brain · ความทรงจำคู่รัก · ทีม AI อัตโนมัติ
                        </p>
                    </div>

                    {{-- Fleet Quick Switcher & Actions --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('portfolio.finance.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:from-emerald-500 hover:to-teal-500 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            ศูนย์การเงิน & พอร์ต
                        </a>
                        <a href="{{ route('portfolio.command-center.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:from-brand-500 hover:to-indigo-500 transition">
                            <span class="text-sm">🕹️</span>
                            ห้อง AI Room 8-Bit
                        </a>
                    </div>
                </div>

                {{-- AI Agents Live Pill Bar --}}
                <div class="mt-5 border-t border-slate-800/80 pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 font-medium text-slate-300">
                            <span class="text-slate-400">สถานะทีม AI:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($aiAgents as $agent)
                                    <div class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800/80 px-2.5 py-1 text-slate-200 border border-slate-700/50">
                                        <span class="h-2 w-2 rounded-full {{ $agent['status'] === 'online' ? 'bg-emerald-400 animate-pulse' : ($agent['status'] === 'active' ? 'bg-indigo-400 animate-pulse' : 'bg-purple-400') }}"></span>
                                        <span class="font-semibold">{{ $agent['name'] }}</span>
                                        <span class="text-[10px] text-slate-400 hidden sm:inline">({{ $agent['role'] }})</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <a href="{{ route('portfolio.command-center.index') }}" class="text-brand-400 hover:text-brand-300 font-semibold inline-flex items-center gap-1">
                            ดูระบบตรวจการเรียลไทม์ &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── 2. Top 5 Pillars Executive Scoreboard ───────────────────────────── --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                {{-- Card 1: ความมั่งคั่งสุทธิ --}}
                <a href="{{ route('portfolio.finance.index') }}"
                   class="group rounded-2xl border border-slate-800 bg-[#141720] p-4 transition hover:border-emerald-500/50 hover:bg-slate-900/60 shadow-lg">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-400">
                        <span>💎 ความมั่งคั่งสุทธิ</span>
                        <span class="text-emerald-400 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="mt-2 text-xl font-black text-white group-hover:text-emerald-400 transition">
                        ฿{{ $fmtMoney($totals['net']) }}
                    </div>
                    <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                        <span>ทรัพย์ ฿{{ number_format($totals['assets'] / 1000, 1) }}k</span>
                        <span class="text-rose-400">หนี้ ฿{{ number_format($totals['debts'] / 1000, 1) }}k</span>
                    </div>
                </a>

                {{-- Card 2: เป้าหมายชีวิต & Roadmap --}}
                <a href="{{ route('portfolio.life-goals.index') }}"
                   class="group rounded-2xl border border-slate-800 bg-[#141720] p-4 transition hover:border-amber-500/50 hover:bg-slate-900/60 shadow-lg">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-400">
                        <span>🎯 เป้าหมายชีวิต</span>
                        <span class="text-amber-400 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="mt-2 text-xl font-black text-white group-hover:text-amber-400 transition">
                        {{ $lifeGoals['overallProgress'] }}% <span class="text-xs font-normal text-slate-400">เฉลี่ย</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-400">
                        กำลังลุย <span class="font-bold text-amber-400">{{ $lifeGoals['activeCount'] }}</span> ข้อ (สำเร็จ {{ $lifeGoals['completedCount'] }})
                    </div>
                </a>

                {{-- Card 3: Second Brain --}}
                <a href="{{ route('portfolio.brain.index') }}"
                   class="group rounded-2xl border border-slate-800 bg-[#141720] p-4 transition hover:border-purple-500/50 hover:bg-slate-900/60 shadow-lg">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-400">
                        <span>🧠 Second Brain</span>
                        <span class="text-purple-400 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="mt-2 text-xl font-black text-white group-hover:text-purple-400 transition">
                        {{ $brain['totalCount'] }} <span class="text-xs font-normal text-slate-400">บันทึก</span>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-400">
                        Inbox: <span class="text-purple-300 font-semibold">{{ $brain['inboxCount'] }}</span> · Projects: <span class="text-purple-300 font-semibold">{{ $brain['projectsCount'] }}</span>
                    </div>
                </a>

                {{-- Card 4: Couple Space --}}
                <a href="{{ route('portfolio.couple.index') }}"
                   class="group rounded-2xl border border-slate-800 bg-[#141720] p-4 transition hover:border-rose-500/50 hover:bg-slate-900/60 shadow-lg">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-400">
                        <span>💕 Couple Space</span>
                        <span class="text-rose-400 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="mt-2 text-xl font-black text-white group-hover:text-rose-400 transition">
                        ฿{{ $fmtMoney($couple['tripFundSaved']) }}
                    </div>
                    <div class="mt-1 text-[11px] text-slate-400">
                        กองกลางทริปคู่ · รอทำ <span class="text-rose-300 font-semibold">{{ $couple['pendingCount'] }}</span> ข้อ
                    </div>
                </a>

                {{-- Card 5: ค่าน้ำมัน & เดินทาง --}}
                <a href="{{ route('portfolio.fuel.index') }}"
                   class="group rounded-2xl border border-slate-800 bg-[#141720] p-4 transition hover:border-sky-500/50 hover:bg-slate-900/60 shadow-lg">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-400">
                        <span>⛽ ค่าน้ำมันเดือนนี้</span>
                        <span class="text-sky-400 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="mt-2 text-xl font-black text-white group-hover:text-sky-400 transition">
                        ฿{{ $fmtMoney($fuelCostThisMonth) }}
                    </div>
                    <div class="mt-1 text-[11px] text-slate-400">
                        บันทึกการเติมและประหยัดน้ำมัน &rarr;
                    </div>
                </a>

            </div>

            {{-- ── 3. Main Bento Grid: Deep Telemetry Across Dimensions ───────────── --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- ── Bento 1: Financial Mission Control Summary ──────────────────── --}}
                <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                                💰
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">การเงิน & พอร์ตการลงทุน</h3>
                                <p class="text-xs text-slate-400">Financial Cockpit & Asset Allocation</p>
                            </div>
                        </div>
                        <a href="{{ route('portfolio.finance.index') }}"
                           class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition">
                            ดูทั้งหมด &rarr;
                        </a>
                    </div>

                    <div class="mt-4 space-y-4">
                        {{-- Assets vs Debts Ratio bar --}}
                        <div>
                            @php
                                $totalVolume = $totals['assets'] + $totals['debts'];
                                $assetBarPct = $totalVolume > 0 ? round(($totals['assets'] / $totalVolume) * 100) : 100;
                                $debtBarPct  = 100 - $assetBarPct;
                            @endphp
                            <div class="flex items-center justify-between text-xs text-slate-300 mb-1.5 font-medium">
                                <span>สินทรัพย์ {{ $assetBarPct }}% (฿{{ $fmtMoney($totals['assets']) }})</span>
                                <span class="text-rose-400">หนี้สิน {{ $debtBarPct }}% (฿{{ $fmtMoney($totals['debts']) }})</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-slate-800 overflow-hidden flex">
                                <div class="bg-emerald-500 transition-all duration-500" style="width: {{ $assetBarPct }}%;"></div>
                                <div class="bg-rose-500 transition-all duration-500" style="width: {{ $debtBarPct }}%;"></div>
                            </div>
                        </div>

                        {{-- Core vs Satellite & Freedom Ratio stats --}}
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="rounded-xl bg-slate-900/80 p-3 border border-slate-800/60">
                                <div class="text-[11px] text-slate-400">พอร์ตหุ้น Core & Satellite</div>
                                <div class="text-sm font-bold text-slate-200 mt-1">
                                    Core <span class="text-sky-400">{{ $corePct }}%</span> · Sat <span class="text-indigo-400">{{ $satellitePct }}%</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">รวม ฿{{ $fmtMoney($totalInvestedStocks) }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 p-3 border border-slate-800/60">
                                <div class="text-[11px] text-slate-400">Financial Freedom Ratio</div>
                                <div class="text-sm font-bold text-emerald-400 mt-1">
                                    {{ $freedomRatio }}% อิสรภาพ
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">ปันผล ~฿{{ $fmtMoney($estMonthlyDividend) }}/ด.</div>
                            </div>
                        </div>

                        {{-- Quick Financial Action Buttons --}}
                        <div class="flex flex-wrap gap-2 pt-2">
                            <a href="{{ route('portfolio.ai.index') }}" class="flex-1 text-center rounded-xl bg-slate-800/90 hover:bg-slate-800 p-2 text-xs font-semibold text-slate-200 border border-slate-700/60 transition">
                                🤖 AI วิเคราะห์หุ้น
                            </a>
                            <a href="{{ route('portfolio.budget.index') }}" class="flex-1 text-center rounded-xl bg-slate-800/90 hover:bg-slate-800 p-2 text-xs font-semibold text-slate-200 border border-slate-700/60 transition">
                                📋 แผนงบประมาณ
                            </a>
                            <a href="{{ route('portfolio.finance.index') }}" class="flex-1 text-center rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 p-2 text-xs font-semibold text-emerald-300 border border-emerald-500/30 transition">
                                📊 พอร์ตเต็มรูปแบบ
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ── Bento 2: Life Goals & Roadmap (เป้าหมายชีวิต) ────────────────── --}}
                <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400">
                                🎯
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">เป้าหมายชีวิต & Roadmap</h3>
                                <p class="text-xs text-slate-400">Short, Mid & Long Term Milestones</p>
                            </div>
                        </div>
                        <a href="{{ route('portfolio.life-goals.index') }}"
                           class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                            ดู Roadmap &rarr;
                        </a>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($lifeGoals['focusGoals'] as $goal)
                            <div class="rounded-xl bg-slate-900/70 p-3 border border-slate-800/60 flex flex-col gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $goal->horizon === 'short' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($goal->horizon === 'medium' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20') }}">
                                            {{ $goal->horizon === 'short' ? 'ระยะสั้น' : ($goal->horizon === 'medium' ? 'ระยะกลาง' : 'ระยะยาว') }}
                                        </span>
                                        <div class="text-xs font-semibold text-slate-200 truncate">{{ $goal->title }}</div>
                                    </div>
                                    <span class="text-xs font-bold text-amber-400 shrink-0">{{ $goal->progress_percent }}%</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500 rounded-full" style="width: {{ $goal->progress_percent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-slate-400">
                                ยังไม่มีเป้าหมายที่กำลังโฟกัส
                            </div>
                        @endforelse

                        <a href="{{ route('portfolio.life-goals.index') }}" class="block text-center rounded-xl bg-slate-800/70 hover:bg-slate-800 p-2.5 text-xs font-medium text-slate-300 border border-slate-700/50 transition">
                            + วางแผนเป้าหมายชีวิตและ Roadmap เพิ่มเติม
                        </a>
                    </div>
                </div>

                {{-- ── Bento 3: Second Brain Quick Capture & Recent Notes ──────────── --}}
                <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/10 text-purple-400">
                                🧠
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">Second Brain (P.A.R.A.)</h3>
                                <p class="text-xs text-slate-400">สมองที่สอง จัดการไอเดียและโปรเจกต์</p>
                            </div>
                        </div>
                        <a href="{{ route('portfolio.brain.index') }}"
                           class="text-xs font-semibold text-purple-400 hover:text-purple-300 transition">
                            เปิดสมองที่สอง &rarr;
                        </a>
                    </div>

                    {{-- Quick Note Capture Input --}}
                    <form method="POST" action="{{ route('portfolio.brain.store') }}" class="mt-4 flex gap-2">
                        @csrf
                        <input type="hidden" name="type" value="inbox">
                        <input type="text" name="title" required placeholder="⚡ บันทึกไอเดีย/โน้ตด่วนลง Inbox..."
                               class="flex-1 rounded-xl border border-slate-700 bg-slate-900/90 px-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:border-purple-500 focus:outline-none">
                        <button type="submit" class="rounded-xl bg-purple-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-purple-500 transition">
                            จดด่วน
                        </button>
                    </form>

                    {{-- Recent Notes List --}}
                    <div class="mt-3 space-y-2">
                        @forelse ($brain['recentNotes'] as $note)
                            <div class="rounded-xl bg-slate-900/70 p-2.5 border border-slate-800/60 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $note->type === 'inbox' ? 'bg-sky-500/10 text-sky-400' : ($note->type === 'project' ? 'bg-purple-500/10 text-purple-400' : 'bg-slate-700 text-slate-300') }}">
                                        {{ $note->type }}
                                    </span>
                                    <span class="text-xs text-slate-200 truncate">{{ $note->title }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400 shrink-0">{{ $note->updated_at?->diffForHumans() }}</span>
                            </div>
                        @empty
                            <div class="py-4 text-center text-xs text-slate-400">
                                ไม่มีโน้ตล่าสุดในระบบ
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ── Bento 4: Couple Space (พื้นที่เราสองคน) ──────────────────────── --}}
                <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/10 text-rose-400">
                                💕
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">Couple Space & ความทรงจำคู่</h3>
                                <p class="text-xs text-slate-400">Wishlist, แผนเที่ยว และกองกลาง</p>
                            </div>
                        </div>
                        <a href="{{ route('portfolio.couple.index') }}"
                           class="text-xs font-semibold text-rose-400 hover:text-rose-300 transition">
                            เปิดพื้นที่คู่รัก &rarr;
                        </a>
                    </div>

                    <div class="mt-4 space-y-3">
                        {{-- Trip Fund Progress --}}
                        @php
                            $fundPct = $couple['tripFundTarget'] > 0 ? min(100, round(($couple['tripFundSaved'] / $couple['tripFundTarget']) * 100)) : 0;
                        @endphp
                        <div class="rounded-xl bg-gradient-to-r from-rose-950/30 to-pink-950/20 p-3.5 border border-rose-900/30">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-rose-200">🏖️ กระปุกกองกลางทริปเที่ยวคู่</span>
                                <span class="font-bold text-rose-400">{{ $fundPct }}%</span>
                            </div>
                            <div class="mt-2 text-lg font-black text-white">
                                ฿{{ $fmtMoney($couple['tripFundSaved']) }} <span class="text-xs font-normal text-slate-400">/ ฿{{ $fmtMoney($couple['tripFundTarget']) }}</span>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-slate-800 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-rose-500 to-pink-500 rounded-full" style="width: {{ $fundPct }}%;"></div>
                            </div>
                        </div>

                        {{-- Pending wishlist / restaurants --}}
                        <div class="space-y-1.5">
                            @forelse ($couple['recentItems'] as $item)
                                <div class="flex items-center justify-between text-xs bg-slate-900/70 p-2.5 rounded-lg border border-slate-800/60">
                                    <div class="flex items-center gap-2 truncate">
                                        <span>{{ $item->type === 'food' ? '🍜' : ($item->type === 'trip' ? '🚗' : ($item->type === 'movie' ? '🎬' : '🎁')) }}</span>
                                        <span class="text-slate-200 truncate">{{ $item->title }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 shrink-0">{{ $item->partner_name }}</span>
                                </div>
                            @empty
                                <div class="py-2 text-center text-xs text-slate-400">ยังไม่มีรายการค้าง</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── 4. AI Autonomous Command Center Fleet Room ─────────────────────── --}}
            <div class="rounded-2xl border border-slate-800 bg-[#121418] p-5 shadow-xl">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">🕹️</span>
                        <div>
                            <h3 class="font-bold text-white text-base">สถานีทำงาน AI Agent ทีมรักษาการ (Autonomous Office)</h3>
                            <p class="text-xs text-slate-400">ทีมผู้ช่วยอัจฉริยะที่คอยดูแลเซิร์ฟเวอร์ พัฒนาโค้ด และตรวจสอบระบบตลอดเวลา</p>
                        </div>
                    </div>
                    <a href="{{ route('portfolio.command-center.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">
                        เข้าห้องบัญชาการ 8-Bit เต็มจอ &rarr;
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($aiAgents as $ag)
                        <div class="rounded-xl bg-slate-900/80 p-3.5 border border-slate-800/70 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-bold text-slate-400">
                                    <span>{{ $ag['station'] }}</span>
                                    <span class="inline-flex items-center gap-1 text-[10px] {{ $ag['status'] === 'online' ? 'text-emerald-400' : ($ag['status'] === 'active' ? 'text-indigo-400' : 'text-purple-400') }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $ag['status'] === 'online' ? 'bg-emerald-400 animate-pulse' : ($ag['status'] === 'active' ? 'bg-indigo-400 animate-pulse' : 'bg-purple-400') }}"></span>
                                        {{ strtoupper($ag['status']) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-sm font-bold text-white">{{ $ag['name'] }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ $ag['role'] }}</div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-800/60 text-[11px] text-slate-300">
                                {{ $ag['status_text'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-portfolio-layout>
