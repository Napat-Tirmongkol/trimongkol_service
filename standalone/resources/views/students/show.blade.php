<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('classrooms.show', $classroom) }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ $classroom->name }}
                </a>
                <div class="mt-1 flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-lg font-bold text-white shadow-md shadow-brand-500/20">
                        {{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}
                    </span>
                    <div>
                        <h2 class="text-xl font-bold leading-tight text-slate-900">
                            @if ($student->number)<span class="text-slate-400">#{{ $student->number }}</span> @endif
                            {{ $student->name }}
                        </h2>
                        <span class="mt-0.5 inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $student->code }}</span>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('classrooms.students.attendance', [$classroom, $student->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    {{ __('app.attendance.nav') }}
                </a>
                <a href="{{ route('classrooms.students.qr', [$classroom, $student]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/>
                    </svg>
                    {{ __('app.students.print_qr_button') }}
                </a>
                <a href="{{ route('classrooms.students.edit', [$classroom, $student]) }}"
                   class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('app.common.edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $assignments = $gradebook['assignments'];
        $cells = $gradebook['cells'][$student->id] ?? [];
        $t = $gradebook['student_totals'][$student->id] ?? ['submitted' => 0, 'total' => 0, 'weighted_percent' => null];
        $pct = $t['weighted_percent'];
        $pctClass = $pct === null ? 'text-slate-500 bg-slate-100'
            : ($pct >= 80 ? 'text-emerald-700 bg-emerald-100'
            : ($pct >= 50 ? 'text-amber-700 bg-amber-100'
            : 'text-rose-700 bg-rose-100'));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            {{-- Summary stats --}}
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.gradebook.col_submitted') }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $t['submitted'] }} <span class="text-base font-normal text-slate-500">/ {{ $t['total'] }}</span></div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.gradebook.col_weighted') }}</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        @if ($pct !== null)
                            <span class="inline-block rounded-full px-3 py-1 text-lg font-bold tabular-nums {{ $pctClass }}">{{ rtrim(rtrim(number_format($pct, 1), '0'), '.') }}%</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.gradebook.col_avg_percent') }}</div>
                    @php
                        $percents = collect($cells)->filter(fn ($c) => $c['percent'] !== null)->pluck('percent');
                        $avgP = $percents->count() > 0 ? round($percents->avg(), 1) : null;
                    @endphp
                    <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
                        {{ $avgP !== null ? $avgP . '%' : '—' }}
                    </div>
                </div>
            </div>

            {{-- Per-assignment list --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('app.gradebook.per_assignment') }}</h3>
                </div>
                @if ($assignments->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-600">{{ __('app.assignments.empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($assignments as $a)
                            @php
                                $cell = $cells[$a->id];
                                $p = $cell['percent'];
                                $cellPctClass = $p === null ? 'text-slate-400 bg-slate-50'
                                    : ($p >= 80 ? 'text-emerald-700 bg-emerald-50'
                                    : ($p >= 50 ? 'text-amber-700 bg-amber-50'
                                    : 'text-rose-700 bg-rose-50'));
                            @endphp
                            <li>
                                <a href="{{ route('classrooms.assignments.show', [$classroom, $a]) }}"
                                   class="flex items-center justify-between gap-3 px-6 py-3.5 hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-slate-900">{{ $a->name }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                                                {{ __("app.assignments.mode_{$a->scoring_mode}_title") }}
                                            </span>
                                            @if ((float) $a->weight !== 1.0)
                                                <span>· ×{{ rtrim(rtrim(number_format((float) $a->weight, 2), '0'), '.') }}</span>
                                            @endif
                                            @if ($a->due_date)
                                                <span>· {{ $a->due_date->format('d M Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        @if ($cell['score'] === null)
                                            <span class="text-xs text-slate-400">{{ __('app.scan.not_yet') }}</span>
                                        @else
                                            <div class="font-semibold tabular-nums text-slate-900">
                                                {{ rtrim(rtrim(number_format($cell['score'], 2), '0'), '.') }}
                                                <span class="text-xs font-normal text-slate-400">/ {{ rtrim(rtrim(number_format($cell['max_score'], 2), '0'), '.') }}</span>
                                            </div>
                                            @if ($p !== null)
                                                <div class="mt-0.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums {{ $cellPctClass }}">{{ rtrim(rtrim(number_format($p, 1), '0'), '.') }}%</div>
                                            @endif
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
