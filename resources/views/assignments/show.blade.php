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
            <a href="{{ route('classrooms.assignments.edit', [$classroom, $assignment]) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('app.common.edit') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @php
                $submittedCount = $assignment->submissions()->count();
                $totalCount = $classroom->students()->count();
                $percent = $totalCount > 0 ? round($submittedCount / $totalCount * 100) : 0;
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
                    <div class="mt-2 text-base font-semibold text-slate-900">{{ __("app.assignments.mode_{$assignment->scoring_mode}_title") }}</div>
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
                                <th class="px-6 py-3">{{ __('app.scan.submitted_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @php $subs = $assignment->submissions()->with('student')->get()->keyBy('student_id'); @endphp
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
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $sub?->submitted_at?->format('H:i') ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
