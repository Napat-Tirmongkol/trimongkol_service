<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('classrooms.show', $classroom) }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ $classroom->name }}</a>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ $assignment->name }}</h2>
                @if ($assignment->due_date)
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.assignments.due') }}: {{ $assignment->due_date->format('d M Y') }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('classrooms.assignments.export', [$classroom, $assignment]) }}"
                   class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('app.export.button') }}
                </a>
                <a href="{{ route('classrooms.assignments.edit', [$classroom, $assignment]) }}"
                   class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('app.common.edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @php
                $subs = $assignment->submissions()->with('student')->get()->keyBy('student_id');
                $submittedCount = $subs->count();
                $totalCount = $classroom->students->count();
                $percent = $totalCount > 0 ? round($submittedCount / $totalCount * 100) : 0;
                $isGraded = $assignment->scoring_mode !== 'check';
                $scores = $subs->filter(fn ($s) => $s->score !== null)->pluck('score');
                $avgScore = $scores->count() > 0 ? round($scores->avg(), 1) : null;
            @endphp

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.scan.submitted_label') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $submittedCount }} <span class="text-base font-normal text-slate-500">/ {{ $totalCount }}</span></div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full bg-brand-600" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.assignments.mode_label') }}</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">
                        {{ __("app.assignments.mode_{$assignment->scoring_mode}_title") }}
                        @if ($assignment->scoring_mode === 'fixed' && $assignment->default_score !== null)
                            · {{ $assignment->default_score }}
                        @endif
                    </div>
                    @if ($isGraded && $avgScore !== null)
                        <div class="mt-2 text-xs text-slate-500">{{ __('app.export.avg') }}: <span class="font-semibold text-slate-700">{{ $avgScore }}</span></div>
                    @endif
                </div>
                <a href="{{ route('classrooms.assignments.scan', [$classroom, $assignment]) }}"
                   class="flex flex-col justify-center rounded-xl bg-brand-600 p-5 text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-brand-100">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/>
                        </svg>
                        {{ __('app.scan.action_label') }}
                    </div>
                    <div class="mt-1 text-xl font-bold">{{ __('app.scan.start') }} →</div>
                </a>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('app.students.heading') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.students.col_number') }}</th>
                                <th class="px-6 py-3">{{ __('app.students.col_name') }}</th>
                                <th class="px-6 py-3">{{ __('app.scan.status') }}</th>
                                @if ($isGraded)
                                    <th class="px-6 py-3">{{ __('app.export.score') }}</th>
                                @endif
                                <th class="px-6 py-3">{{ __('app.scan.submitted_at') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('app.students.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @foreach ($classroom->students as $student)
                                @php $sub = $subs->get($student->id); @endphp
                                <tr>
                                    <td class="px-6 py-3 text-slate-600">{{ $student->number ?: '—' }}</td>
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $student->name }}</td>
                                    <td class="px-6 py-3">
                                        @if ($sub)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                {{ __('app.scan.submitted_short') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('app.scan.pending') }}</span>
                                        @endif
                                    </td>
                                    @if ($isGraded)
                                        <td class="px-6 py-3">
                                            @if ($sub)
                                                @if ($assignment->scoring_mode === 'custom')
                                                    <form method="POST" action="{{ route('classrooms.assignments.submissions.update', [$classroom, $assignment, $sub]) }}" class="flex items-center gap-1">
                                                        @csrf @method('PATCH')
                                                        <input type="number" name="score" min="0" max="100" step="1"
                                                               value="{{ $sub->score }}"
                                                               class="w-16 rounded-md border-slate-300 px-2 py-1 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                                        <button type="submit" class="text-xs text-brand-700 hover:text-brand-900">{{ __('app.common.save') }}</button>
                                                    </form>
                                                @else
                                                    <span class="text-sm font-semibold text-slate-900">{{ $sub->score ?? '—' }}</span>
                                                @endif
                                            @else
                                                <span class="text-xs text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $sub?->submitted_at?->format('H:i') ?: '—' }}</td>
                                    <td class="px-6 py-3 text-right">
                                        @if ($sub)
                                            <form method="POST" action="{{ route('classrooms.assignments.submissions.destroy', [$classroom, $assignment, $sub]) }}"
                                                  onsubmit="return confirm('{{ __('app.scan.undo_confirm') }}')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-700">{{ __('app.scan.undo') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('classrooms.assignments.submissions.store', [$classroom, $assignment]) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <button type="submit" class="text-xs text-brand-700 hover:text-brand-900">{{ __('app.scan.mark_submitted') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
