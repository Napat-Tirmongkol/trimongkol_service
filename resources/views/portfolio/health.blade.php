<x-portfolio-layout>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">

    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-300 text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-3">
                <span class="text-xl">✅</span>
                <span>{{ session('status') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white text-xs font-mono">✕ ปิด</button>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-rose-300 text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-3">
                <span class="text-xl">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-white text-xs font-mono">✕ ปิด</button>
        </div>
    @endif

    {{-- ── 1. Top Cockpit Banner ────────────────────────────────────────── --}}
    <div class="relative rounded-3xl border border-cyan-500/30 bg-gradient-to-br from-[#0b1320] via-[#0f172a] to-[#0a101d] p-6 sm:p-8 shadow-2xl overflow-hidden">
        {{-- Ambient Glow --}}
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="flex items-start sm:items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500/20 to-indigo-500/20 text-cyan-400 border border-cyan-500/30 text-3xl shadow-inner shadow-cyan-950/40">
                    🏃‍♂️
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            Health & Bio-Hacker Cockpit
                        </h1>
                        <span class="rounded-lg bg-cyan-500/20 px-2.5 py-0.5 text-xs font-bold text-cyan-300 border border-cyan-500/30 font-mono">
                            GALAXY WATCH 7 LIVE
                        </span>
                    </div>
                    <p class="mt-1 text-xs sm:text-sm text-slate-300">
                        ศูนย์ควบคุมชีวภาพ สถิติสุขภาพ ก้าวเดิน หัวใจ และคุณภาพการนอน ย้อนหลัง 30 วัน จาก Samsung Health Connect
                    </p>
                </div>
            </div>

            {{-- Readiness & Sync Badge --}}
            <div class="flex flex-wrap items-center gap-3">
                @php
                    $rBadge = $readinessMeta['badgeClass'] ?? 'bg-emerald-500/20 text-emerald-400';
                    $rText  = $readinessMeta['textClass'] ?? 'text-emerald-300';
                    $rLabel = $readinessMeta['label'] ?? 'สมดุลดี พร้อมทำงานปกติ';
                    $rParts = $readinessMeta['breakdown'] ?? [];
                @endphp
                <div class="rounded-2xl bg-slate-900/90 border border-slate-800 p-3 flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $rBadge }} font-black text-sm">
                        {{ $readinessScore }}
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider font-mono">Energy / Readiness Score</div>
                        <div class="text-xs font-bold {{ $rText }}">{{ $rLabel }}</div>
                        @if (!empty($rParts))
                            <div class="mt-0.5 text-[10px] font-mono text-slate-400">
                                นอน {{ $rParts['sleep']['score'] ?? 0 }}/{{ $rParts['sleep']['max'] ?? 40 }}
                                · หัวใจ {{ $rParts['recovery']['score'] ?? 0 }}/{{ $rParts['recovery']['max'] ?? 30 }}
                                · ก้าว {{ $rParts['activity']['score'] ?? 0 }}/{{ $rParts['activity']['max'] ?? 20 }}
                                · กะ {{ $rParts['shift']['score'] ?? 0 }}/{{ $rParts['shift']['max'] ?? 10 }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-900/90 border border-slate-800 px-3.5 py-2.5 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs font-mono text-slate-300">
                        {{ $totalDays }} วันที่บันทึก
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2. Today's Key Telemetry Bento Grid ──────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- 1. Steps Today & 30D Avg --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg relative overflow-hidden group hover:border-cyan-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 text-xs">
                <span class="flex items-center gap-1.5 font-medium">
                    <span>👟</span>
                    <span>ก้าวเดินวันนี้</span>
                </span>
                <span class="text-[11px] font-mono text-cyan-400">เฉลี่ย {{ number_format($avgSteps) }}</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <div class="text-3xl font-black text-white">
                    {{ $latest && $latest->steps ? number_format($latest->steps) : '—' }}
                </div>
                <span class="text-xs text-slate-400">ก้าว</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>ระยะทางสะสม:</span>
                <span class="font-bold text-slate-200">{{ $totalDistanceKm }} กม.</span>
            </div>
            <div class="mt-2 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                @php $stepGoalPct = $latest && $latest->steps ? min(100, round(($latest->steps / 6000) * 100)) : 10; @endphp
                <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-emerald-400 transition-all duration-700"
                     style="width: {{ $stepGoalPct }}%"></div>
            </div>
        </div>

        {{-- 2. Sleep Quality & Stages --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg relative overflow-hidden group hover:border-purple-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 text-xs">
                <span class="flex items-center gap-1.5 font-medium">
                    <span>🌙</span>
                    <span>การนอนหลับ</span>
                </span>
                <span class="text-[11px] font-mono text-purple-400">เฉลี่ย {{ round($avgSleepMin / 60, 1) }} ชม.</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <div class="text-3xl font-black text-purple-300">
                    {{ $latest && ($latest->sleep_minutes ?? 0) > 15 ? round($latest->sleep_minutes / 60, 1) : '—' }}
                </div>
                <span class="text-xs text-slate-400">ชั่วโมง</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>หลับลึก (Deep):</span>
                <span class="font-bold text-purple-200">{{ $latest && $latest->deep_sleep_minutes ? $latest->deep_sleep_minutes : round($avgDeepMin) }} นาที</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>หลับฝัน (REM):</span>
                <span class="font-bold text-indigo-300">{{ $latest && $latest->rem_sleep_minutes ? $latest->rem_sleep_minutes : round($avgRemMin) }} นาที</span>
            </div>
        </div>

        {{-- 3. Heart Rate (Resting & Avg) --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg relative overflow-hidden group hover:border-rose-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 text-xs">
                <span class="flex items-center gap-1.5 font-medium">
                    <span>❤️</span>
                    <span>อัตราเต้นหัวใจ</span>
                </span>
                <span class="text-[11px] font-mono text-rose-400">Resting {{ $avgRestingHr ?: '—' }} bpm</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <div class="text-3xl font-black text-rose-400">
                    {{ $latest && $latest->heart_rate_avg ? $latest->heart_rate_avg : ($avgHr ?: '—') }}
                </div>
                <span class="text-xs text-slate-400">bpm (เฉลี่ย)</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>ช่วงการเต้น (Min - Max):</span>
                <span class="font-bold text-slate-200">
                    {{ $latest && $latest->heart_rate_min ? $latest->heart_rate_min . ' - ' . $latest->heart_rate_max : '—' }}
                </span>
            </div>
            <div class="mt-2 text-[10px] text-emerald-400 flex items-center gap-1">
                <span>●</span>
                <span>หัวใจแข็งแรง อยู่ในเกณฑ์ปกติ</span>
            </div>
        </div>

        {{-- 4. Calories & Metabolism --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg relative overflow-hidden group hover:border-amber-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 text-xs">
                <span class="flex items-center gap-1.5 font-medium">
                    <span>🔥</span>
                    <span>เผาผลาญแคลอรี</span>
                </span>
                <span class="text-[11px] font-mono text-amber-400">เฉลี่ย {{ number_format($avgCalories) }}</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <div class="text-3xl font-black text-amber-400">
                    {{ $latest && $latest->active_calories ? number_format($latest->active_calories) : '—' }}
                </div>
                <span class="text-xs text-slate-400">kcal (Active)</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>รวม 30 วัน:</span>
                <span class="font-bold text-slate-200">{{ number_format($totalCalories) }} kcal</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>SpO2 เฉลี่ย:</span>
                <span class="font-bold text-sky-400">{{ $latest && $latest->spo2_avg ? $latest->spo2_avg : '96.5' }}%</span>
            </div>
        </div>

    </div>

    {{-- ── 2.5 SOC Shift & Bio-Rhythm Tactical Radar (Google Calendar Sync) ── --}}
    <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-br from-[#121624] via-[#151a2e] to-[#0e121d] p-6 sm:p-8 shadow-2xl relative overflow-hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 text-indigo-400 border border-indigo-500/30 text-2xl shadow-inner">
                    📅
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg sm:text-xl font-extrabold text-white">SOC Shift & Bio-Rhythm Tactical Radar</h2>
                        @if ($calendarSummary['is_connected'])
                            <span class="rounded bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-300 border border-emerald-500/30 font-mono flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                GOOGLE CALENDAR LINKED
                            </span>
                        @else
                            <span class="rounded bg-slate-500/20 px-2 py-0.5 text-[10px] font-bold text-slate-300 border border-slate-500/30 font-mono">
                                READY TO SYNC
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300">
                        ดึงตารางเวรจาก Google Calendar API เทียบกับอัตราการพักผ่อนจาก Galaxy Watch 7 คำนวณความพร้อมก่อนเข้ากะ
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if ($calendarSummary['is_connected'])
                    <form method="POST" action="{{ route('portfolio.calendar.sync') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/30 px-3.5 py-2 text-xs font-semibold transition shadow-sm">
                            <span>🔄</span>
                            <span>ซิงก์ตารางเวร</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('portfolio.calendar.disconnect') }}" onsubmit="return confirm('ยืนยันยกเลิกการเชื่อมต่อ Google Calendar หรือไม่?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-slate-800/80 hover:bg-rose-500/20 hover:text-rose-300 hover:border-rose-500/30 text-slate-400 border border-slate-700 px-3 py-2 text-xs font-semibold transition">
                            <span>✕</span>
                            <span>ตัดการเชื่อมต่อ</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('portfolio.calendar.connect') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold text-xs px-4 py-2.5 transition shadow-lg shadow-indigo-950/40 border border-indigo-400/30">
                        <span>🔗</span>
                        <span>เชื่อมต่อ Google Calendar</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Shift Fatigue / Readiness Alert (If Triggered) --}}
        @if ($shiftFatigueAlert)
            <div class="rounded-2xl p-4 border flex items-start gap-3.5 {{ $shiftFatigueAlert['level'] === 'warning' ? 'border-amber-500/30 bg-amber-500/10 text-amber-200' : ($shiftFatigueAlert['level'] === 'success' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-cyan-500/30 bg-cyan-500/10 text-cyan-200') }}">
                <div class="text-2xl shrink-0">
                    {{ $shiftFatigueAlert['level'] === 'warning' ? '⚠️' : ($shiftFatigueAlert['level'] === 'success' ? '⚡' : '🏖️') }}
                </div>
                <div class="text-xs sm:text-sm">
                    <div class="font-bold text-white">{{ $shiftFatigueAlert['title'] }}</div>
                    <div class="mt-0.5 opacity-90 leading-relaxed">{{ $shiftFatigueAlert['message'] }}</div>
                </div>
            </div>
        @endif

        @if ($calendarSummary['is_connected'])
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Today's Shift Card --}}
                <div class="rounded-2xl border border-slate-800 bg-[#0d111a] p-5 shadow-inner space-y-3">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="font-mono uppercase tracking-wider text-[11px] text-indigo-400 font-bold">1. เวรวันนี้ (Today)</span>
                        <span class="text-[11px] text-slate-500">{{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}</span>
                    </div>

                    @if ($todayShift)
                        <div class="flex items-center gap-3">
                            <div class="text-3xl">{{ $todayShift['icon'] ?? '📌' }}</div>
                            <div>
                                <div class="text-base font-extrabold text-white">{{ $todayShift['title'] ?? 'เวรวันนี้' }}</div>
                                <span class="inline-block mt-1 rounded px-2 py-0.5 text-[10px] font-bold border {{ $todayShift['badge_color'] ?? 'bg-slate-500/20 text-slate-300 border-slate-500/30' }}">
                                    {{ $todayShift['label'] ?? 'ภารกิจ' }}
                                </span>
                            </div>
                        </div>
                        @if (count($calendarSummary['today_shifts'] ?? []) > 1)
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach (array_slice($calendarSummary['today_shifts'], 1) as $secShift)
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold border {{ $secShift['badge_color'] ?? 'bg-slate-500/20 text-slate-300 border-slate-500/30' }}">
                                        {{ $secShift['icon'] ?? '📌' }} {{ $secShift['title'] ?? '' }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-400">ช่วงเวลาเข้ากะ:</span>
                            <span class="text-indigo-300 font-bold">{{ $todayShift['start_formatted'] ?? '08:00' }} – {{ $todayShift['end_formatted'] ?? '20:00' }} น.</span>
                        </div>
                    @else
                        <div class="py-4 text-center">
                            <div class="text-2xl mb-1">🏖️</div>
                            <div class="text-sm font-bold text-slate-300">ไม่มีตารางเวรที่ระบุวันนี้</div>
                            <div class="text-xs text-slate-500 mt-1">วันพักผ่อน หรือไม่มีนัดหมายในปฏิทิน</div>
                        </div>
                    @endif
                </div>

                {{-- Tomorrow's Shift Card --}}
                <div class="rounded-2xl border border-slate-800 bg-[#0d111a] p-5 shadow-inner space-y-3">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="font-mono uppercase tracking-wider text-[11px] text-purple-400 font-bold">2. เวรวันพรุ่งนี้ (Tomorrow)</span>
                        <span class="text-[11px] text-slate-500">{{ \Carbon\Carbon::now()->addDay()->translatedFormat('d M Y') }}</span>
                    </div>

                    @if ($tomorrowShift)
                        <div class="flex items-center gap-3">
                            <div class="text-3xl">{{ $tomorrowShift['icon'] ?? '📌' }}</div>
                            <div>
                                <div class="text-base font-extrabold text-white">{{ $tomorrowShift['title'] ?? 'เวรพรุ่งนี้' }}</div>
                                <span class="inline-block mt-1 rounded px-2 py-0.5 text-[10px] font-bold border {{ $tomorrowShift['badge_color'] ?? 'bg-slate-500/20 text-slate-300 border-slate-500/30' }}">
                                    {{ $tomorrowShift['label'] ?? 'ภารกิจ' }}
                                </span>
                            </div>
                        </div>
                        @if (count($calendarSummary['tomorrow_shifts'] ?? []) > 1)
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach (array_slice($calendarSummary['tomorrow_shifts'], 1) as $secShift)
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold border {{ $secShift['badge_color'] ?? 'bg-slate-500/20 text-slate-300 border-slate-500/30' }}">
                                        {{ $secShift['icon'] ?? '📌' }} {{ $secShift['title'] ?? '' }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-400">ช่วงเวลาเข้ากะ:</span>
                            <span class="text-purple-300 font-bold">{{ $tomorrowShift['start_formatted'] ?? '08:00' }} – {{ $tomorrowShift['end_formatted'] ?? '20:00' }} น.</span>
                        </div>
                    @else
                        <div class="py-4 text-center">
                            <div class="text-2xl mb-1">🏖️</div>
                            <div class="text-sm font-bold text-slate-300">ไม่มีตารางเวรพรุ่งนี้</div>
                            <div class="text-xs text-slate-500 mt-1">วันหยุดพัก หรือยังไม่ได้ลงนัดหมาย</div>
                        </div>
                    @endif
                </div>

                {{-- Upcoming Roster List --}}
                <div class="rounded-2xl border border-slate-800 bg-[#0d111a] p-5 shadow-inner space-y-3 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="font-mono uppercase tracking-wider text-[11px] text-cyan-400 font-bold">3. คิวเวรและนัดหมายถัดไป</span>
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                            <span class="font-mono text-cyan-300 font-bold">{{ count($calendarSummary['upcoming'] ?? []) }} รายการ</span>
                            <span class="text-slate-500">(เลื่อนเมาส์ดูได้ ↕)</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 pr-1.5 scrollbar-thin" style="max-height: 150px; overflow-y: auto;">
                        @forelse ($calendarSummary['upcoming'] as $shift)
                            <div class="flex items-center justify-between rounded-xl bg-slate-900/60 border border-slate-800/80 p-2 text-xs hover:border-slate-700 transition">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-base shrink-0">{{ $shift['icon'] ?? '📌' }}</span>
                                    <div class="min-w-0">
                                        <div class="font-bold text-white truncate max-w-[130px] sm:max-w-[150px]">{{ $shift['title'] ?? '' }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $shift['day_formatted'] ?? '' }} ({{ $shift['start_formatted'] ?? '' }} น.)</div>
                                    </div>
                                </div>
                                <span class="rounded px-1.5 py-0.5 text-[9px] font-bold border shrink-0 {{ $shift['badge_color'] ?? 'bg-slate-500/20 text-slate-300 border-slate-500/30' }}">
                                    {{ $shift['label'] ?? 'ทั่วไป' }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-xs text-slate-500">
                                ไม่พบคิวเวรถัดไป
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        @else
            {{-- Connect Callout --}}
            <div class="rounded-2xl border border-dashed border-indigo-500/40 bg-indigo-500/5 p-6 text-center space-y-4">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-3xl">
                    🛰️
                </div>
                <div class="max-w-xl mx-auto space-y-1.5">
                    <h3 class="text-base font-bold text-white">ปลดผนึกเรดาร์เชื่อมต่อ Google Calendar API</h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        เชื่อมต่อปฏิทินของมึงเพียงคลิกเดียว ระบบจะดึงตารางเวร SOC (กะเช้า / กะบ่าย / กะดึก / Day Off) มาประเมินร่วมกับคุณภาพการนอนหลับจาก Galaxy Watch 7 ทันที เพื่อป้องกันอาการ Fatigue ก่อนเข้าเวรจริง
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('portfolio.calendar.connect') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-extrabold text-xs sm:text-sm px-6 py-3 transition shadow-xl shadow-indigo-950/50 border border-indigo-400/40">
                        <span>🔗</span>
                        <span>เชื่อมต่อ Google Calendar ของโฟล์ก (คลิกเดียวผ่าน OAuth)</span>
                    </a>
                </div>
            </div>
        @endif

    </div>

    {{-- ── 3. Visual 30-Day Step Count Chart ─────────────────────────────── --}}
    <div class="rounded-3xl border border-slate-800 bg-[#141720] p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-800/80 pb-3">
            <div>
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <span>📊</span>
                    <span>กราฟสถิติก้าวเดินรายวัน ย้อนหลัง 30 วัน</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    ก้าวเดินรวม {{ number_format($totalSteps) }} ก้าว · วันที่เดินเยอะที่สุด:
                    <strong class="text-cyan-400">{{ $maxStepDay ? \Carbon\Carbon::parse($maxStepDay->date)->format('d/m/Y') . ' (' . number_format($maxStepDay->steps) . ' ก้าว)' : '—' }}</strong>
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs font-mono">
                <span class="flex items-center gap-1.5 text-slate-400">
                    <span class="h-3 w-3 rounded bg-cyan-500 inline-block"></span>
                    <span>≥ 4,000 ก้าว</span>
                </span>
                <span class="flex items-center gap-1.5 text-slate-400">
                    <span class="h-3 w-3 rounded bg-slate-700 inline-block"></span>
                    <span>< 4,000 ก้าว</span>
                </span>
            </div>
        </div>

        {{-- High Contrast Responsive CSS Bar Chart --}}
        @php
            $maxStepInSet = max(1, count($chartSteps) ? max($chartSteps) : 1000);
        @endphp
        <div class="relative pt-6 pb-2">
            <div class="h-48 w-full flex items-end gap-1 sm:gap-2 border-b border-slate-800 pb-1">
                @foreach ($chartSteps as $idx => $stepVal)
                    @php
                        $barHeightPct = max(8, round(($stepVal / $maxStepInSet) * 100));
                        $isHigh = $stepVal >= 4000;
                        $label = $chartLabels[$idx] ?? '';
                    @endphp
                    <div class="flex-1 flex flex-col items-center group relative h-full justify-end">
                        {{-- Tooltip hover --}}
                        <div class="absolute -top-10 hidden group-hover:flex flex-col items-center z-20 pointer-events-none">
                            <span class="rounded-lg bg-slate-900 border border-slate-700 px-2 py-1 text-[10px] font-mono text-cyan-300 shadow-xl whitespace-nowrap">
                                {{ $label }}: {{ number_format($stepVal) }} ก้าว
                            </span>
                        </div>
                        <div class="w-full rounded-t-md transition-all duration-300 {{ $isHigh ? 'bg-gradient-to-t from-cyan-600 to-cyan-400 group-hover:brightness-125' : 'bg-slate-700 group-hover:bg-slate-600' }}"
                             style="height: {{ $barHeightPct }}%;"></div>
                    </div>
                @endforeach
            </div>

            {{-- X-Axis Date Labels --}}
            <div class="flex justify-between text-[10px] font-mono text-slate-500 mt-2 px-1">
                <span>{{ $chartLabels[0] ?? '' }}</span>
                <span>{{ $chartLabels[round(count($chartLabels) / 2)] ?? '' }}</span>
                <span>{{ end($chartLabels) ?: '' }} (วันนี้)</span>
            </div>
        </div>
    </div>

    {{-- ── 4. Bio-Hacker AI Intelligence & Insights ────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Insight 1: Sleep Architecture --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg space-y-3">
            <div class="flex items-center gap-2 text-purple-400 font-bold text-sm">
                <span>🧬</span>
                <span>สถาปัตยกรรมการนอน (Sleep Architecture)</span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                เฉลี่ยการนอนอยู่ที่ <strong>{{ round($avgSleepMin / 60, 1) }} ชม./คืน</strong> โดยมีช่วงหลับลึก (Deep Sleep) เฉลี่ย <strong>{{ round($avgDeepMin) }} นาที</strong> และ REM <strong>{{ round($avgRemMin) }} นาที</strong> ซึ่งช่วยฟื้นฟูสมองและการซ่อมแซมกล้ามเนื้อได้อย่างมีประสิทธิภาพ
            </p>
            <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400">
                💡 คำแนะนำ: พยายามเข้านอนให้ตรงเวลาเพื่อรักษา Circadian Rhythm ให้เสถียร
            </div>
        </div>

        {{-- Insight 2: Cardiovascular Efficiency --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg space-y-3">
            <div class="flex items-center gap-2 text-rose-400 font-bold text-sm">
                <span>💓</span>
                <span>สุขภาพหัวใจ (Cardiovascular Metric)</span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                Resting Heart Rate อยู่ที่ <strong>{{ $avgRestingHr ?: 70 }} bpm</strong> แสดงถึงประสิทธิภาพของกล้ามเนื้อหัวใจในระดับปกติ สมบูรณ์พร้อมสำหรับการขับขี่มอเตอร์ไซค์เดินทางไกลและทำงานใช้ความคิดเข้มข้น
            </p>
            <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400">
                💡 คำแนะนำ: ดื่มน้ำเปล่าเพิ่ม 500ml ในวันที่ขี่รถตากแดดเพื่อรักษาความดันเลือด
            </div>
        </div>

        {{-- Insight 3: Bio-Hacker Mission --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720] p-5 shadow-lg space-y-3">
            <div class="flex items-center gap-2 text-cyan-400 font-bold text-sm">
                <span>🎯</span>
                <span>ภารกิจเพิ่มความฟิตประจำสัปดาห์</span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                ทำก้าวเดินสะสมให้แตะ <strong>5,000 ก้าว</strong> ต่อวัน อย่างน้อย 4 วันในสัปดาห์นี้ เพื่อกระตุ้นการเผาผลาญไขมันและลดความเมื่อยล้าจากการนั่งหน้าจอเขียนโค้ดนานๆ
            </p>
            <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400">
                💡 เชื่อมต่ออัตโนมัติ: ดึงข้อมูลผ่าน Tailscale MCP & Health Connect 24/7
            </div>
        </div>

    </div>

    {{-- ── 5. Detailed 30-Day Historical Data Table ─────────────────────── --}}
    <div class="rounded-3xl border border-slate-800 bg-[#141720] p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <span>📋</span>
                    <span>ตารางประวัติสุขภาพย้อนหลัง 30 วัน</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    บันทึกจาก Samsung Galaxy Watch 7 • เรียงจากวันล่าสุด
                </p>
            </div>
            <a href="{{ route('portfolio.command-center.index') }}"
               class="rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 text-cyan-300 px-3 py-1.5 text-xs font-semibold transition">
                เปิดห้องสั่งการ AI &rarr;
            </a>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-mono text-[11px] uppercase border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">วันที่</th>
                        <th class="py-3 px-4">ก้าวเดิน</th>
                        <th class="py-3 px-4">ระยะทาง</th>
                        <th class="py-3 px-4">แคลอรี (Active)</th>
                        <th class="py-3 px-4">หัวใจเฉลี่ย</th>
                        <th class="py-3 px-4">เวลานอนหลับ</th>
                        <th class="py-3 px-4">หลับลึก (Deep)</th>
                        <th class="py-3 px-4">SpO2</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                    @forelse ($recordsDesc as $row)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-bold text-white font-sans">
                                {{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}
                                @if (\Carbon\Carbon::parse($row->date)->isToday())
                                    <span class="ml-1.5 rounded bg-cyan-500/20 px-1.5 py-0.5 text-[9px] text-cyan-300 font-mono">วันนี้</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold {{ ($row->steps ?? 0) >= 4000 ? 'text-cyan-400' : 'text-slate-300' }}">
                                    {{ $row->steps ? number_format($row->steps) : '—' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-400">
                                {{ $row->distance_meters ? round($row->distance_meters / 1000, 2) . ' กม.' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-amber-400 font-semibold">
                                {{ $row->active_calories ? number_format($row->active_calories) . ' kcal' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-rose-400">
                                {{ $row->heart_rate_avg ? $row->heart_rate_avg . ' bpm' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-purple-300 font-semibold">
                                {{ $row->sleep_minutes ? round($row->sleep_minutes / 60, 1) . ' ชม.' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-purple-400">
                                {{ $row->deep_sleep_minutes ? $row->deep_sleep_minutes . ' นาที' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-sky-400">
                                {{ $row->spo2_avg ? $row->spo2_avg . '%' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-500">
                                ยังไม่มีข้อมูลบันทึกสุขภาพ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</x-portfolio-layout>
