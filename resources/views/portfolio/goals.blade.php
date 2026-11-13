<x-portfolio-layout>

@php
    $categories = [
        'all'      => ['label' => 'ทุกหมวดหมู่', 'icon' => 'sparkles', 'color' => 'slate'],
        'finance'  => ['label' => 'การเงิน & พอร์ต', 'icon' => 'wallet', 'color' => 'emerald'],
        'health'   => ['label' => 'สุขภาพ & กายภาพ', 'icon' => 'heart', 'color' => 'rose'],
        'career'   => ['label' => 'การงาน & ธุรกิจ', 'icon' => 'briefcase', 'color' => 'blue'],
        'travel'   => ['label' => 'ท่องเที่ยว & ทริป', 'icon' => 'map', 'color' => 'amber'],
        'learning' => ['label' => 'การเรียนรู้ & ทักษะ', 'icon' => 'book', 'color' => 'purple'],
        'personal' => ['label' => 'พัฒนาตัวเอง & วินัย', 'icon' => 'star', 'color' => 'cyan'],
    ];

    $categoryBadge = function (string $cat) {
        return match ($cat) {
            'finance'  => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            'health'   => 'bg-rose-500/15 text-rose-400 border-rose-500/30',
            'career'   => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
            'travel'   => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            'learning' => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
            'personal' => 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
            default    => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        };
    };

    $formatThaiDate = function ($date): ?string {
        if (!$date) return null;
        $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $c = \Carbon\Carbon::parse($date);
        return $c->day . ' ' . $thaiMonths[$c->month] . ' ' . ($c->year + 543);
    };
@endphp

<div x-data="goalRoadmap()" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash status notification --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center justify-between rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300 backdrop-blur">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-400 hover:text-emerald-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Top Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 shadow-inner">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="12" cy="12" r="5" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="12" cy="12" r="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-100">Goal Year & Life Roadmap</h1>
                        <span class="rounded-lg bg-indigo-500/20 px-2 py-0.5 text-[11px] font-semibold text-indigo-300 border border-indigo-500/30">
                            เป้าหมายชีวิต & เช็คลิสต์
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400">
                        วางแผนสิ่งที่อยากทำในปีหน้าและอนาคต แบ่งตามระยะสั้น (1–3 เดือน), ระยะกลาง (6 ด.–1 ปี), และระยะไกล (1–3 ปีขึ้นไป)
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Filter Year --}}
            <form method="GET" action="{{ route('portfolio.life-goals.index') }}" class="flex items-center">
                <input type="hidden" name="horizon" value="{{ $activeHorizon }}">
                <input type="hidden" name="category" value="{{ $activeCategory }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                <select name="year" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-700 bg-slate-800/90 px-3 py-2 text-xs sm:text-sm font-semibold text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all" @selected($activeYear === 'all')>ทุกปีเป้าหมาย</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected((string)$activeYear === (string)$y)>
                            ปี {{ $y + 543 }} ({{ $y }})
                        </option>
                    @endforeach
                </select>
            </form>

            {{-- New Goal Button --}}
            <button type="button" @click="openAddModal()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-400 hover:to-indigo-500 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-md shadow-indigo-950/40 transition active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>เพิ่มเป้าหมายใหม่</span>
            </button>
        </div>
    </div>

    {{-- Scoreboard Summary Cards (4 Cards) --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        {{-- Card 1: Total Overall --}}
        <div class="rounded-2xl border border-slate-800 bg-[#141720]/90 p-4 shadow-sm backdrop-blur relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">ความสำเร็จรวม</span>
                <span class="rounded-md bg-indigo-500/10 px-2 py-0.5 text-[11px] font-bold text-indigo-400">
                    {{ $completedGoals }}/{{ $totalGoals }}
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-100">{{ $overallPercent }}%</span>
                <span class="text-xs text-slate-400">สำเร็จแล้ว</span>
            </div>
            <div class="mt-3 h-2 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-400 transition-all duration-500"
                     style="width: {{ $overallPercent }}%"></div>
            </div>
        </div>

        {{-- Card 2: Short-Term --}}
        <div class="rounded-2xl border border-emerald-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span class="text-xs font-semibold text-emerald-400">ระยะสั้น</span>
                </div>
                <span class="text-[11px] font-medium text-slate-400">1–3 เดือน</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-100">{{ $shortCompleted }}<span class="text-sm font-normal text-slate-400">/{{ $shortCount }}</span></span>
                <span class="text-xs font-medium text-emerald-400">({{ $shortPercent }}%)</span>
            </div>
            <div class="mt-3 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-400 transition-all duration-500"
                     style="width: {{ $shortPercent }}%"></div>
            </div>
        </div>

        {{-- Card 3: Medium-Term --}}
        <div class="rounded-2xl border border-amber-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                    <span class="text-xs font-semibold text-amber-400">ระยะกลาง</span>
                </div>
                <span class="text-[11px] font-medium text-slate-400">6 ด.–1 ปี</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-100">{{ $mediumCompleted }}<span class="text-sm font-normal text-slate-400">/{{ $mediumCount }}</span></span>
                <span class="text-xs font-medium text-amber-400">({{ $mediumPercent }}%)</span>
            </div>
            <div class="mt-3 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-amber-400 transition-all duration-500"
                     style="width: {{ $mediumPercent }}%"></div>
            </div>
        </div>

        {{-- Card 4: Long-Term --}}
        <div class="rounded-2xl border border-indigo-500/20 bg-[#141720]/90 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                    <span class="text-xs font-semibold text-indigo-400">ระยะไกล</span>
                </div>
                <span class="text-[11px] font-medium text-slate-400">1–3 ปีขึ้นไป</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-100">{{ $longCompleted }}<span class="text-sm font-normal text-slate-400">/{{ $longCount }}</span></span>
                <span class="text-xs font-medium text-indigo-400">({{ $longPercent }}%)</span>
            </div>
            <div class="mt-3 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-indigo-400 transition-all duration-500"
                     style="width: {{ $longPercent }}%"></div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar: Horizon Tabs & Categories --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-3">
        {{-- Horizon Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-1 sm:pb-0">
            <a href="{{ route('portfolio.life-goals.index', ['horizon' => 'all', 'category' => $activeCategory, 'status' => $activeStatus, 'year' => $activeYear]) }}"
               class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border {{ $activeHorizon === 'all' && $activeStatus !== 'completed' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                ทั้งหมด ({{ $totalGoals }})
            </a>
            <a href="{{ route('portfolio.life-goals.index', ['horizon' => 'short', 'category' => $activeCategory, 'status' => $activeStatus, 'year' => $activeYear]) }}"
               class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border flex items-center gap-1.5 {{ $activeHorizon === 'short' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span>ระยะสั้น ({{ $shortCount }})</span>
            </a>
            <a href="{{ route('portfolio.life-goals.index', ['horizon' => 'medium', 'category' => $activeCategory, 'status' => $activeStatus, 'year' => $activeYear]) }}"
               class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border flex items-center gap-1.5 {{ $activeHorizon === 'medium' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                <span>ระยะกลาง ({{ $mediumCount }})</span>
            </a>
            <a href="{{ route('portfolio.life-goals.index', ['horizon' => 'long', 'category' => $activeCategory, 'status' => $activeStatus, 'year' => $activeYear]) }}"
               class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border flex items-center gap-1.5 {{ $activeHorizon === 'long' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                <span>ระยะไกล ({{ $longCount }})</span>
            </a>
            <a href="{{ route('portfolio.life-goals.index', ['status' => 'completed', 'category' => $activeCategory, 'horizon' => $activeHorizon, 'year' => $activeYear]) }}"
               class="whitespace-nowrap rounded-xl px-3.5 py-1.5 text-xs font-semibold transition border flex items-center gap-1.5 {{ $activeStatus === 'completed' ? 'bg-emerald-500/25 text-emerald-300 border-emerald-500/50' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-slate-200' }}">
                <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <span>สำเร็จแล้ว ({{ $completedGoals }})</span>
            </a>
        </div>

        {{-- Category Dropdown --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 whitespace-nowrap">หมวดหมู่:</span>
            <form method="GET" action="{{ route('portfolio.life-goals.index') }}">
                <input type="hidden" name="horizon" value="{{ $activeHorizon }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                <input type="hidden" name="year" value="{{ $activeYear }}">
                <select name="category" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-700 bg-slate-800/90 px-3 py-1.5 text-xs font-semibold text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($categories as $catKey => $catInfo)
                        <option value="{{ $catKey }}" @selected($activeCategory === $catKey)>
                            {{ $catInfo['label'] }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Checklist Items List --}}
    @if ($filteredGoals->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-[#141720]/50 p-12 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-4">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-200">ยังไม่มีเป้าหมายในหมวดนี้</h3>
            <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                เริ่มต้นเขียนเป้าหมายปีหน้า หรือสิ่งที่อยากทำให้สำเร็จในระยะสั้น กลาง หรือระยะไกลได้เลยครับ
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <button type="button" @click="openAddModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 hover:bg-indigo-400 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>สร้างเป้าหมายข้อแรก</span>
                </button>
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($filteredGoals as $goal)
                @php
                    $colors = $goal->horizonBadgeColor();
                @endphp
                <div id="goal-row-{{ $goal->id }}"
                     class="group relative rounded-2xl border transition-all duration-300 p-4 sm:p-5 {{ $goal->is_completed ? 'border-emerald-500/25 bg-[#141720]/50 opacity-80' : 'border-slate-800 hover:border-slate-700 bg-[#141720]/90' }} shadow-sm backdrop-blur">
                    <div class="flex items-start gap-3.5 sm:gap-4">
                        {{-- Interactive Checkbox --}}
                        <button type="button"
                                @click="toggleGoal({{ $goal->id }})"
                                class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border transition-all duration-200 {{ $goal->is_completed ? 'bg-emerald-500 border-emerald-500 text-slate-950 shadow-sm shadow-emerald-500/40' : 'border-slate-600 bg-slate-800/80 hover:border-indigo-400 text-transparent hover:text-slate-400' }}">
                            <svg class="h-4 w-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                {{-- Horizon Badge --}}
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-0.5 text-[11px] font-semibold {{ $colors['bg'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $colors['dot'] }}"></span>
                                    <span>{{ $goal->horizonLabel() }}</span>
                                </span>

                                {{-- Category Badge --}}
                                <span class="inline-flex items-center rounded-lg border px-2.5 py-0.5 text-[11px] font-medium {{ $categoryBadge($goal->category) }}">
                                    {{ $goal->categoryLabel() }}
                                </span>

                                {{-- Target Year / Target Date --}}
                                @if ($goal->target_year)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                                        <svg class="h-3 w-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                                        <span>เป้าหมายปี {{ $goal->target_year + 543 }}</span>
                                    </span>
                                @endif

                                @if ($goal->target_date)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                                        <span>(ภายใน {{ $formatThaiDate($goal->target_date) }})</span>
                                    </span>
                                @endif

                                @if ($goal->is_completed && $goal->completed_at)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400">
                                        <span>✓ สำเร็จเมื่อ {{ $formatThaiDate($goal->completed_at) }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Title & Strikethrough --}}
                            <h3 class="text-base sm:text-lg font-bold leading-snug {{ $goal->is_completed ? 'line-through text-slate-400' : 'text-slate-100' }}">
                                {{ $goal->title }}
                            </h3>

                            @if ($goal->description)
                                <p class="mt-1 text-xs sm:text-sm text-slate-400 leading-relaxed">
                                    {{ $goal->description }}
                                </p>
                            @endif

                            {{-- Progress Slider Bar --}}
                            <div class="mt-3.5 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-[11px] font-medium mb-1">
                                        <span class="text-slate-400">ความคืบหน้า</span>
                                        <span id="progress-val-{{ $goal->id }}" class="font-bold {{ $goal->is_completed ? 'text-emerald-400' : 'text-slate-300' }}">
                                            {{ $goal->progress_percent }}%
                                        </span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-800 overflow-hidden relative">
                                        <div id="progress-bar-{{ $goal->id }}"
                                             class="h-full rounded-full transition-all duration-300 {{ $goal->is_completed ? 'bg-emerald-400' : ($goal->horizon === 'medium' ? 'bg-amber-400' : 'bg-indigo-500') }}"
                                             style="width: {{ $goal->progress_percent }}%"></div>
                                    </div>
                                </div>

                                {{-- Quick % Step Buttons --}}
                                <div class="flex items-center gap-1 self-end sm:self-auto">
                                    <button type="button" @click="setGoalProgress({{ $goal->id }}, 25)"
                                            class="rounded-md bg-slate-800/80 hover:bg-slate-700 px-2 py-0.5 text-[10px] font-semibold text-slate-300 transition">
                                        25%
                                    </button>
                                    <button type="button" @click="setGoalProgress({{ $goal->id }}, 50)"
                                            class="rounded-md bg-slate-800/80 hover:bg-slate-700 px-2 py-0.5 text-[10px] font-semibold text-slate-300 transition">
                                        50%
                                    </button>
                                    <button type="button" @click="setGoalProgress({{ $goal->id }}, 75)"
                                            class="rounded-md bg-slate-800/80 hover:bg-slate-700 px-2 py-0.5 text-[10px] font-semibold text-slate-300 transition">
                                        75%
                                    </button>
                                    <button type="button" @click="setGoalProgress({{ $goal->id }}, 100)"
                                            class="rounded-md bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 px-2 py-0.5 text-[10px] font-bold transition">
                                        100%
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-1 self-start sm:self-center ml-2">
                            <button type="button" @click="openEditModal({{ json_encode($goal) }})"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition"
                                    title="แก้ไข">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                            </button>

                            <form method="POST" action="{{ route('portfolio.life-goals.destroy', $goal) }}"
                                  onsubmit="return confirm('ยืนยันที่จะลบเป้าหมายนี้หรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-500/10 hover:text-rose-400 transition"
                                        title="ลบ">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Inspiration Presets Box --}}
    <div x-data="{ openPresets: false }" class="rounded-2xl border border-slate-800 bg-[#141720]/80 p-5 shadow-sm backdrop-blur">
        <div class="flex items-center justify-between cursor-pointer" @click="openPresets = !openPresets">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-200">💡 ไอเดียเป้าหมายยอดนิยม (คลิกเลือกใส่ได้ทันที)</h4>
                    <p class="text-xs text-slate-400">ตัวอย่างเป้าหมายที่ช่วยให้ชีวิตก้าวหน้าทั้งสุขภาพ การเงิน และการท่องเที่ยว</p>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-200 transition">
                <svg class="h-5 w-5 transition-transform" :class="openPresets ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </div>

        <div x-show="openPresets" x-collapse class="mt-4 pt-4 border-t border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {{-- Preset 1 --}}
            <button type="button" @click="fillPreset('วิ่งออกกำลังกายสัปดาห์ละ 3 วัน', 'สร้างสุขภาพที่แข็งแรง ลดไขมัน คุม HR', 'short', 'health', 2027)"
                    class="flex flex-col items-start p-3 rounded-xl border border-slate-700/60 bg-slate-800/50 hover:border-emerald-500/50 hover:bg-slate-800 text-left transition group">
                <span class="rounded bg-emerald-500/15 text-emerald-400 text-[10px] font-semibold px-2 py-0.5 mb-1.5">🟢 ระยะสั้น • สุขภาพ</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-emerald-300">วิ่งออกกำลังกายสัปดาห์ละ 3 วัน</span>
                <span class="text-[11px] text-slate-400 mt-1">+ คลิกเพื่อสร้าง</span>
            </button>

            {{-- Preset 2 --}}
            <button type="button" @click="fillPreset('อ่านหนังสือพัฒนาตัวเองให้จบ 6 เล่ม', 'สรุปเนื้อหาสำคัญและนำมาใช้จริง', 'short', 'learning', 2027)"
                    class="flex flex-col items-start p-3 rounded-xl border border-slate-700/60 bg-slate-800/50 hover:border-purple-500/50 hover:bg-slate-800 text-left transition group">
                <span class="rounded bg-purple-500/15 text-purple-400 text-[10px] font-semibold px-2 py-0.5 mb-1.5">🟢 ระยะสั้น • การเรียนรู้</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-purple-300">อ่านหนังสือพัฒนาตัวเอง 6 เล่ม</span>
                <span class="text-[11px] text-slate-400 mt-1">+ คลิกเพื่อสร้าง</span>
            </button>

            {{-- Preset 3 --}}
            <button type="button" @click="fillPreset('สำรองเงินฉุกเฉินให้ครบ 6 เดือนของค่าใช้จ่าย', 'เก็บในบัญชีดอกเบี้ยสูงเพื่อความอุ่นใจ', 'medium', 'finance', 2027)"
                    class="flex flex-col items-start p-3 rounded-xl border border-slate-700/60 bg-slate-800/50 hover:border-amber-500/50 hover:bg-slate-800 text-left transition group">
                <span class="rounded bg-amber-500/15 text-amber-400 text-[10px] font-semibold px-2 py-0.5 mb-1.5">🟡 ระยะกลาง • การเงิน</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-amber-300">สำรองเงินฉุกเฉินครบ 6 เดือน</span>
                <span class="text-[11px] text-slate-400 mt-1">+ คลิกเพื่อสร้าง</span>
            </button>

            {{-- Preset 4 --}}
            <button type="button" @click="fillPreset('ขี่มอเตอร์ไซค์เที่ยวทริปภาคเหนือ 1,000 กม.', 'เก็บบันทึก GPS และภาพถ่ายลง Life Journey', 'medium', 'travel', 2027)"
                    class="flex flex-col items-start p-3 rounded-xl border border-slate-700/60 bg-slate-800/50 hover:border-amber-500/50 hover:bg-slate-800 text-left transition group">
                <span class="rounded bg-amber-500/15 text-amber-400 text-[10px] font-semibold px-2 py-0.5 mb-1.5">🟡 ระยะกลาง • ท่องเที่ยว</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-amber-300">ทริปมอเตอร์ไซค์ภาคเหนือ 1,000 กม.</span>
                <span class="text-[11px] text-slate-400 mt-1">+ คลิกเพื่อสร้าง</span>
            </button>

            {{-- Preset 5 --}}
            <button type="button" @click="fillPreset('พอร์ตการลงทุนแตะเป้าหมาย 1,000,000 บาท', 'สะสมหุ้นคุณค่าและ DCA อย่างมีวินัย', 'long', 'finance', 2028)"
                    class="flex flex-col items-start p-3 rounded-xl border border-slate-700/60 bg-slate-800/50 hover:border-indigo-500/50 hover:bg-slate-800 text-left transition group">
                <span class="rounded bg-indigo-500/15 text-indigo-400 text-[10px] font-semibold px-2 py-0.5 mb-1.5">🔵 ระยะไกล • การเงิน</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-indigo-300">พอร์ตลงทุนแตะ 1,000,000 บาท</span>
                <span class="text-[11px] text-slate-400 mt-1">+ คลิกเพื่อสร้าง</span>
            </button>

            {{-- Preset 6 --}}
            <button type="button" @click="fillPreset('สร้างระบบ AI Automation ในงานประจำสำเร็จ', 'ช่วยทุ่นเวลาทำงานลง 50% ด้วยระบบอัตโนมัติ', 'medium', 'career', 2027)"
                    class="flex flex-col items-start p-3 rounded-xl border border-slate-700/60 bg-slate-800/50 hover:border-blue-500/50 hover:bg-slate-800 text-left transition group">
                <span class="rounded bg-blue-500/15 text-blue-400 text-[10px] font-semibold px-2 py-0.5 mb-1.5">🟡 ระยะกลาง • การงาน</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-blue-300">ระบบ AI Automation ในงาน</span>
                <span class="text-[11px] text-slate-400 mt-1">+ คลิกเพื่อสร้าง</span>
            </button>
        </div>
    </div>

    {{-- Add / Edit Modal --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         @keydown.escape.window="closeModal()">
        <div class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-[#141720] p-6 shadow-2xl transition-all"
             @click.outside="closeModal()">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-slate-100" x-text="isEditMode ? 'แก้ไขเป้าหมาย' : 'เพิ่มเป้าหมายใหม่'"></h3>
                <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-200 hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="formAction" method="POST" class="mt-4 space-y-4">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">ชื่อเป้าหมาย <span class="text-rose-400">*</span></label>
                    <input type="text" name="title" x-model="form.title" required
                           placeholder="เช่น วิ่งออกกำลังกายสัปดาห์ละ 3 วัน, เก็บเงิน 100,000 บาท"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Horizon selection --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">ระยะเวลาเป้าหมาย <span class="text-rose-400">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer rounded-xl border p-2.5 text-center transition flex flex-col items-center justify-center"
                               :class="form.horizon === 'short' ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="horizon" value="short" x-model="form.horizon" class="sr-only">
                            <span class="text-xs font-bold">🟢 ระยะสั้น</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">1–3 เดือน</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2.5 text-center transition flex flex-col items-center justify-center"
                               :class="form.horizon === 'medium' ? 'border-amber-500 bg-amber-500/15 text-amber-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="horizon" value="medium" x-model="form.horizon" class="sr-only">
                            <span class="text-xs font-bold">🟡 ระยะกลาง</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">6 ด.–1 ปี</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-2.5 text-center transition flex flex-col items-center justify-center"
                               :class="form.horizon === 'long' ? 'border-indigo-500 bg-indigo-500/15 text-indigo-300' : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-600'">
                            <input type="radio" name="horizon" value="long" x-model="form.horizon" class="sr-only">
                            <span class="text-xs font-bold">🔵 ระยะไกล</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">1–3 ปีขึ้นไป</span>
                        </label>
                    </div>
                </div>

                {{-- Category & Target Year --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">หมวดหมู่</label>
                        <select name="category" x-model="form.category"
                                class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="personal">🌟 พัฒนาตัวเอง & วินัย</option>
                            <option value="finance">💰 การเงิน & การลงทุน</option>
                            <option value="health">🏃 สุขภาพ & กายภาพ</option>
                            <option value="career">💼 การงาน & ธุรกิจ</option>
                            <option value="travel">🗺️ ท่องเที่ยว & ทริป</option>
                            <option value="learning">📚 การเรียนรู้ & ทักษะใหม่</option>
                            <option value="other">📌 ทั่วไป</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">ปีเป้าหมาย (พ.ศ.)</label>
                        <select name="target_year" x-model="form.target_year"
                                class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="2026">2569 (ปีนี้)</option>
                            <option value="2027">2570 (ปีหน้า)</option>
                            <option value="2028">2571</option>
                            <option value="2029">2572</option>
                            <option value="2030">2573</option>
                        </select>
                    </div>
                </div>

                {{-- Target Date (Optional) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">กำหนดวันเสร็จสิ้น (ถ้ามี)</label>
                    <input type="date" name="target_date" x-model="form.target_date"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Description / Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">รายละเอียด / หมายเหตุ</label>
                    <textarea name="description" x-model="form.description" rows="2"
                              placeholder="ระบุแนวทางหรือบันทึกเพิ่มเติมเพื่อช่วยให้ทำสำเร็จได้ง่ายขึ้น"
                              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                {{-- Progress Slider --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-300">ความคืบหน้า (%)</label>
                        <span class="text-xs font-bold text-indigo-400" x-text="form.progress_percent + '%'"></span>
                    </div>
                    <input type="range" name="progress_percent" min="0" max="100" step="5" x-model="form.progress_percent"
                           class="w-full accent-indigo-500 cursor-pointer">
                </div>

                {{-- Completed Checkbox --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_completed" id="modal_is_completed" value="1"
                           x-model="form.is_completed"
                           class="h-4 w-4 rounded border-slate-700 bg-slate-800 text-emerald-500 focus:ring-emerald-500">
                    <label for="modal_is_completed" class="text-xs font-semibold text-slate-300 cursor-pointer">
                        เป้าหมายนี้ทำสำเร็จแล้ว 🎉
                    </label>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="closeModal()"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-300 hover:bg-slate-800">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-indigo-500 hover:bg-indigo-400 px-5 py-2 text-xs sm:text-sm font-semibold text-white shadow-md transition">
                        <span x-text="isEditMode ? 'บันทึกการแก้ไข' : 'เพิ่มเป้าหมาย'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function goalRoadmap() {
    return {
        modalOpen: false,
        isEditMode: false,
        formAction: '{{ route("portfolio.life-goals.store") }}',
        form: {
            id: null,
            title: '',
            description: '',
            horizon: 'short',
            category: 'personal',
            target_year: 2027,
            target_date: '',
            progress_percent: 0,
            is_completed: false
        },

        openAddModal() {
            this.isEditMode = false;
            this.formAction = '{{ route("portfolio.life-goals.store") }}';
            this.form = {
                id: null,
                title: '',
                description: '',
                horizon: 'short',
                category: 'personal',
                target_year: 2027,
                target_date: '',
                progress_percent: 0,
                is_completed: false
            };
            this.modalOpen = true;
        },

        openEditModal(goal) {
            this.isEditMode = true;
            this.formAction = `/portfolio/life-goals/${goal.id}`;
            this.form = {
                id: goal.id,
                title: goal.title || '',
                description: goal.description || '',
                horizon: goal.horizon || 'short',
                category: goal.category || 'personal',
                target_year: goal.target_year || 2027,
                target_date: goal.target_date ? goal.target_date.substring(0, 10) : '',
                progress_percent: goal.progress_percent || 0,
                is_completed: Boolean(goal.is_completed)
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        fillPreset(title, desc, horizon, category, year) {
            this.openAddModal();
            this.form.title = title;
            this.form.description = desc;
            this.form.horizon = horizon;
            this.form.category = category;
            this.form.target_year = year;
        },

        async toggleGoal(goalId) {
            try {
                const res = await fetch(`/portfolio/life-goals/${goalId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
                window.location.reload();
            }
        },

        async setGoalProgress(goalId, percent) {
            try {
                const res = await fetch(`/portfolio/life-goals/${goalId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ progress_percent: percent })
                });
                const data = await res.json();
                if (data.success) {
                    const valEl = document.getElementById(`progress-val-${goalId}`);
                    const barEl = document.getElementById(`progress-bar-${goalId}`);
                    if (valEl) valEl.innerText = `${percent}%`;
                    if (barEl) barEl.style.width = `${percent}%`;
                    if (percent === 100) {
                        window.location.reload();
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }
    };
}
</script>

</x-portfolio-layout>
