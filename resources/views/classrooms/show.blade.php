<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('app.classrooms.heading') }}
                </a>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-bold leading-tight text-slate-900">{{ $classroom->name }}</h2>
                    @if ($classroom->grade_level)
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">
                            {{ $classroom->grade_level }}
                        </span>
                    @endif
                </div>
                @if ($classroom->description)
                    <p class="mt-1.5 text-sm text-slate-600">{{ $classroom->description }}</p>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" id="tour-start"
                        class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    {{ __('app.tour.help') }}
                </button>
                <a href="{{ route('classrooms.edit', $classroom) }}"
                   class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('app.common.edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                     x-data="{ show: true }" x-show="show" x-transition>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="mt-0.5 shrink-0 text-emerald-600">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <div class="flex-1">{{ session('status') }}</div>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            @endif

            {{-- Quick stats --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_students') }}</div>
                            <div class="text-2xl font-bold tabular-nums text-slate-900">{{ $classroom->students->count() }}</div>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_assignments') }}</div>
                            <div class="text-2xl font-bold tabular-nums text-slate-900">{{ $classroom->assignments->count() }}</div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('classrooms.gradebook', $classroom) }}" data-tour="gradebook"
                   class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.gradebook.nav') }}</div>
                        <div class="mt-1 text-base font-bold text-slate-900 group-hover:text-brand-700">{{ __('app.gradebook.heading') }} →</div>
                    </div>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                </a>
                <a href="{{ route('classrooms.assignments.create', $classroom) }}" data-tour="assignment"
                   class="group flex items-center justify-between gap-3 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-5 text-white shadow-lg shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-900">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-brand-200">{{ __('app.assignments.add') }}</div>
                        <div class="mt-1 text-base font-bold">{{ __('app.assignments.createHeading') }} →</div>
                    </div>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </span>
                </a>
            </div>

            @if (! empty($insights) && $insights['has_data'])
                @php
                    $avg = $insights['class_average_percent'];
                    $sub = $insights['submission_rate_percent'];
                    $att = $insights['attendance_rate_percent'];
                    $toneFor = fn ($p) => $p === null ? 'slate' : ($p >= 80 ? 'emerald' : ($p >= 60 ? 'amber' : 'rose'));
                    $avgTone = $toneFor($avg);
                    $subTone = $toneFor($sub);
                    $attTone = $toneFor($att);
                    $toneText = ['slate' => 'text-slate-900', 'emerald' => 'text-emerald-700', 'amber' => 'text-amber-700', 'rose' => 'text-rose-700'];
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-brand-50 text-brand-600">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m-8 5h8m-8 5h8M3 17l3.5-3.5L9 16l4-4"/></svg>
                            </span>
                            <h3 class="text-base font-semibold text-slate-900">{{ __('app.insights.heading') }}</h3>
                        </div>
                        <a href="{{ route('classrooms.gradebook', $classroom) }}"
                           class="text-xs font-medium text-brand-600 hover:text-brand-700">
                            {{ __('app.insights.see_gradebook') }} →
                        </a>
                    </div>

                    @php $statCols = $insights['attendance_assignments'] > 0 ? 'sm:grid-cols-4' : 'sm:grid-cols-3'; @endphp
                    <div class="grid grid-cols-2 gap-px bg-slate-100 {{ $statCols }}">
                        <div class="bg-white px-5 py-4">
                            <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.insights.class_average') }}</div>
                            <div class="mt-1 text-2xl font-bold tabular-nums {{ $toneText[$avgTone] }}">
                                {{ $avg !== null ? $avg.'%' : '—' }}
                            </div>
                        </div>
                        <div class="bg-white px-5 py-4">
                            <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.insights.submission_rate') }}</div>
                            <div class="mt-1 text-2xl font-bold tabular-nums {{ $toneText[$subTone] }}">
                                {{ $sub !== null ? $sub.'%' : '—' }}
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500">{{ __('app.insights.submission_count', ['count' => $insights['total_submissions']]) }}</div>
                        </div>
                        @if ($insights['attendance_assignments'] > 0)
                            <div class="bg-white px-5 py-4">
                                <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.insights.attendance_rate') }}</div>
                                <div class="mt-1 text-2xl font-bold tabular-nums {{ $toneText[$attTone] }}">
                                    {{ $att !== null ? $att.'%' : '—' }}
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ __('app.insights.attendance_assignments', ['count' => $insights['attendance_assignments']]) }}</div>
                            </div>
                        @endif
                        <div class="bg-white px-5 py-4">
                            <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.insights.recent_scans') }}</div>
                            <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $insights['recent_scan_count'] }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">{{ __('app.insights.activity_window', ['days' => $insights['recent_window_days']]) }}</div>
                        </div>
                    </div>

                    @if (! empty($insights['top_students']) || ! empty($insights['struggling_students']))
                        <div class="grid gap-px border-t border-slate-100 bg-slate-100 sm:grid-cols-2">
                            <div class="bg-white px-5 py-4">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ __('app.insights.top_students') }}
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($insights['top_students'] as $row)
                                        <a href="{{ route('classrooms.students.show', [$classroom, $row['id']]) }}"
                                           class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100">
                                            {{ $row['name'] }}
                                            <span class="tabular-nums text-emerald-600">{{ round($row['percent']) }}%</span>
                                        </a>
                                    @empty
                                        <span class="text-xs text-slate-400">{{ __('app.insights.no_data') }}</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="bg-white px-5 py-4">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-rose-700">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ __('app.insights.struggling') }}
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($insights['struggling_students'] as $row)
                                        <a href="{{ route('classrooms.students.show', [$classroom, $row['id']]) }}"
                                           class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100">
                                            {{ $row['name'] }}
                                            <span class="tabular-nums text-rose-600">{{ round($row['percent']) }}%</span>
                                        </a>
                                    @empty
                                        <span class="text-xs text-slate-400">{{ __('app.insights.all_doing_well') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Assignments section --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ __('app.assignments.heading') }}
                        <span class="ml-1 text-sm font-normal text-slate-500">({{ $classroom->assignments->count() }})</span>
                    </h3>
                </div>
                @if ($classroom->assignments->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto text-slate-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="mt-3 text-sm text-slate-600">{{ __('app.assignments.empty') }}</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($classroom->assignments as $assignment)
                            <li>
                                <a href="{{ route('classrooms.assignments.show', [$classroom, $assignment]) }}"
                                   class="flex items-center justify-between gap-3 px-6 py-3.5 hover:bg-slate-50">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-600">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-slate-900">{{ $assignment->name }}</div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                                <x-assignment-category-badge :category="$assignment->category" />
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                                                    {{ __("app.assignments.mode_{$assignment->scoring_mode}_title") }}
                                                </span>
                                                @if ($assignment->due_date)
                                                    <span>· {{ __('app.assignments.due') }} {{ $assignment->due_date->format('d M Y') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Students section --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ __('app.students.heading') }}
                        <span class="ml-1 text-sm font-normal text-slate-500">({{ $classroom->students->count() }})</span>
                    </h3>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($classroom->students->isNotEmpty())
                            <a href="{{ route('classrooms.students.print', $classroom) }}" data-tour="print"
                               target="_blank"
                               class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                {{ __('app.students.print_button') }}
                            </a>
                        @endif
                        <a href="{{ route('classrooms.students.bulk', $classroom) }}"
                           class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            {{ __('app.students.bulk_button') }}
                        </a>
                        <a href="{{ route('classrooms.students.create', $classroom) }}" data-tour="add-student"
                           class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                            + {{ __('app.students.add') }}
                        </a>
                    </div>
                </div>

                @if ($classroom->students->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto text-slate-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="mt-3 text-sm text-slate-600">{{ __('app.students.empty') }}</p>
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <a href="{{ route('classrooms.students.bulk', $classroom) }}"
                               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                {{ __('app.students.bulk_button') }}
                            </a>
                            <a href="{{ route('classrooms.students.create', $classroom) }}" data-tour="add-student"
                               class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                                + {{ __('app.students.add') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-3">{{ __('app.students.col_number') }}</th>
                                    <th class="px-6 py-3">{{ __('app.students.col_name') }}</th>
                                    <th class="px-6 py-3">{{ __('app.students.col_code') }}</th>
                                    <th class="px-6 py-3 text-right">{{ __('app.students.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($classroom->students as $student)
                                    <tr class="text-sm hover:bg-slate-50/50">
                                        <td class="px-6 py-3 tabular-nums text-slate-600">{{ $student->number ?: '—' }}</td>
                                        <td class="px-6 py-3 font-medium text-slate-900">
                                            <a href="{{ route('classrooms.students.show', [$classroom, $student]) }}"
                                               class="hover:text-brand-700">{{ $student->name }}</a>
                                        </td>
                                        <td class="px-6 py-3"><span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $student->code }}</span></td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('classrooms.students.qr', [$classroom, $student]) }}"
                                               target="_blank"
                                               class="text-sm font-medium text-slate-500 hover:text-slate-700">QR</a>
                                            <span class="text-slate-300">·</span>
                                            <a href="{{ route('classrooms.students.show', [$classroom, $student]) }}"
                                               class="text-sm font-medium text-slate-500 hover:text-slate-700">{{ __('app.common.view') }}</a>
                                            <span class="text-slate-300">·</span>
                                            <a href="{{ route('classrooms.students.edit', [$classroom, $student]) }}"
                                               class="text-sm font-medium text-brand-700 hover:text-brand-800">{{ __('app.common.edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        (() => {
            const steps = [
                { el: '[data-tour=add-student]', title: @json(__('app.tour.room_students_title')), text: @json(__('app.tour.room_students_text')), side: 'bottom' },
                { el: '[data-tour=print]', title: @json(__('app.tour.room_print_title')), text: @json(__('app.tour.room_print_text')), side: 'bottom' },
                { el: '[data-tour=assignment]', title: @json(__('app.tour.room_assign_title')), text: @json(__('app.tour.room_assign_text')), side: 'top' },
                { el: '[data-tour=gradebook]', title: @json(__('app.tour.room_grades_title')), text: @json(__('app.tour.room_grades_text')), side: 'top' },
            ];
            document.getElementById('tour-start')?.addEventListener('click', () => window.startTour(steps));
            window.addEventListener('load', () => window.maybeAutoTour('classroom', steps));
        })();
    </script>
</x-app-layout>
