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
                <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-900">{{ __('app.gradebook.heading') }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('app.gradebook.subtitle') }}</p>
            </div>
            <a href="{{ route('classrooms.gradebook.export', $classroom) }}"
               class="shrink-0 inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('app.export.button') }}
            </a>
        </div>
    </x-slot>

    @php
        $students = $gradebook['students'];
        $assignments = $gradebook['assignments'];
        $cells = $gradebook['cells'];
        $totals = $gradebook['student_totals'];
        $stats = $gradebook['assignment_stats'];
        $totalWeight = $assignments->sum(fn ($a) => (float) ($a->weight ?? 1));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($assignments->isEmpty() || $students->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-sm text-slate-600">{{ __('app.gradebook.empty') }}</p>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="sticky left-0 z-10 bg-slate-50 px-4 py-3">{{ __('app.students.col_number') }}</th>
                                    <th class="sticky left-12 z-10 bg-slate-50 px-4 py-3">{{ __('app.students.col_name') }}</th>
                                    @foreach ($assignments as $a)
                                        <th class="px-3 py-3 text-center">
                                            <a href="{{ route('classrooms.assignments.show', [$classroom, $a]) }}"
                                               class="block hover:text-brand-700">
                                                <div class="max-w-[10rem] truncate normal-case">{{ $a->name }}</div>
                                                <div class="mt-0.5 text-[10px] font-medium text-slate-400">
                                                    / {{ rtrim(rtrim(number_format($a->effectiveMaxScore(), 2), '0'), '.') }}
                                                    @if ((float) $a->weight !== 1.0)
                                                        · ×{{ rtrim(rtrim(number_format((float) $a->weight, 2), '0'), '.') }}
                                                    @endif
                                                </div>
                                            </a>
                                        </th>
                                    @endforeach
                                    <th class="px-3 py-3 text-center">{{ __('app.gradebook.col_submitted') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('app.gradebook.col_weighted') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($students as $student)
                                    @php
                                        $t = $totals[$student->id];
                                        $pct = $t['weighted_percent'];
                                        $pctClass = $pct === null ? 'text-slate-400'
                                            : ($pct >= 80 ? 'text-emerald-700 bg-emerald-50'
                                            : ($pct >= 50 ? 'text-amber-700 bg-amber-50'
                                            : 'text-rose-700 bg-rose-50'));
                                    @endphp
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="sticky left-0 z-10 bg-white px-4 py-2 tabular-nums text-slate-600">{{ $student->number ?: '—' }}</td>
                                        <td class="sticky left-12 z-10 bg-white px-4 py-2 font-medium">
                                            <a href="{{ route('classrooms.students.show', [$classroom, $student]) }}"
                                               class="text-slate-900 hover:text-brand-700">{{ $student->name }}</a>
                                        </td>
                                        @foreach ($assignments as $a)
                                            @php
                                                $cell = $cells[$student->id][$a->id];
                                                $score = $cell['score'];
                                                $sid = $cell['submission_id'];
                                            @endphp
                                            <td class="px-3 py-2 text-center">
                                                @if ($score === null)
                                                    <span class="text-xs text-slate-300">—</span>
                                                @elseif ($a->scoring_mode === 'check')
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                    </span>
                                                @elseif ($a->scoring_mode === 'custom' && $sid)
                                                    <form method="POST" action="{{ route('classrooms.assignments.submissions.update', [$classroom, $a, $sid]) }}" class="inline-flex">
                                                        @csrf @method('PATCH')
                                                        <input type="number" name="score" min="0" max="1000" step="0.01"
                                                               value="{{ rtrim(rtrim(number_format($score, 2), '0'), '.') }}"
                                                               onchange="this.form.submit()"
                                                               class="w-16 rounded-md border-slate-200 px-2 py-1 text-center text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                                    </form>
                                                @else
                                                    <span class="tabular-nums font-medium text-slate-900">{{ rtrim(rtrim(number_format($score, 2), '0'), '.') }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="px-3 py-2 text-center text-xs text-slate-500 tabular-nums">{{ $t['submitted'] }}/{{ $t['total'] }}</td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($pct !== null)
                                                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums {{ $pctClass }}">{{ rtrim(rtrim(number_format($pct, 1), '0'), '.') }}%</span>
                                            @else
                                                <span class="text-xs text-slate-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 text-xs text-slate-600">
                                <tr>
                                    <td class="sticky left-0 z-10 bg-slate-50 px-4 py-2 font-semibold" colspan="2">{{ __('app.gradebook.row_submitted') }}</td>
                                    @foreach ($assignments as $a)
                                        @php $s = $stats[$a->id]; @endphp
                                        <td class="px-3 py-2 text-center tabular-nums">{{ $s['submitted'] }}/{{ $s['total_students'] }}</td>
                                    @endforeach
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td class="sticky left-0 z-10 bg-slate-50 px-4 py-2 font-semibold" colspan="2">{{ __('app.gradebook.row_avg') }}</td>
                                    @foreach ($assignments as $a)
                                        @php $s = $stats[$a->id]; @endphp
                                        <td class="px-3 py-2 text-center tabular-nums">{{ $s['avg'] !== null ? rtrim(rtrim(number_format($s['avg'], 2), '0'), '.') : '—' }}</td>
                                    @endforeach
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td class="sticky left-0 z-10 bg-slate-50 px-4 py-2 font-semibold" colspan="2">{{ __('app.gradebook.row_minmax') }}</td>
                                    @foreach ($assignments as $a)
                                        @php $s = $stats[$a->id]; @endphp
                                        <td class="px-3 py-2 text-center text-[11px] tabular-nums">
                                            @if ($s['min'] !== null)
                                                {{ rtrim(rtrim(number_format($s['min'], 2), '0'), '.') }} – {{ rtrim(rtrim(number_format($s['max'], 2), '0'), '.') }}
                                            @else — @endif
                                        </td>
                                    @endforeach
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                        <span><span class="font-semibold">{{ __('app.gradebook.total_weight') }}:</span> {{ rtrim(rtrim(number_format($totalWeight, 2), '0'), '.') }}</span>
                        <span><span class="font-semibold">{{ __('app.gradebook.legend') }}:</span></span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> ≥ 80%</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> 50–79%</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> &lt; 50%</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
