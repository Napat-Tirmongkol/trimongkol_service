<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <a href="{{ route('classrooms.students.show', [$classroom, $student]) }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ $student->name }}
            </a>
            <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ __('app.attendance.student_history_heading') }}</h2>
            <p class="text-xs text-slate-500">{{ $classroom->name }} · {{ $month->translatedFormat('F Y') }}</p>
        </div>
    </x-slot>

    @php
        $statusMeta = [
            'present' => ['label' => __('app.attendance.status_present'), 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'late' => ['label' => __('app.attendance.status_late'), 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'absent' => ['label' => __('app.attendance.status_absent'), 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            'excused' => ['label' => __('app.attendance.status_excused'), 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'],
        ];
        $rateTone = $summary['rate_percent'] === null ? 'text-slate-900' :
            ($summary['rate_percent'] >= 90 ? 'text-emerald-700' :
                ($summary['rate_percent'] >= 75 ? 'text-amber-700' : 'text-rose-700'));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            {{-- Summary --}}
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 sm:grid-cols-5">
                <div class="bg-white px-5 py-4 sm:col-span-2">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.attendance.stat_rate') }}</div>
                    <div class="mt-1 text-3xl font-bold tabular-nums {{ $rateTone }}">
                        {{ $summary['rate_percent'] !== null ? $summary['rate_percent'].'%' : '—' }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.attendance.of_n_sessions', ['n' => $summary['total_sessions']]) }}</div>
                </div>
                @foreach (['present', 'late', 'absent', 'excused'] as $st)
                    <div class="bg-white px-3 py-3 text-center">
                        <div class="text-[10px] uppercase tracking-wider text-slate-500">{{ $statusMeta[$st]['label'] }}</div>
                        <div class="mt-0.5 text-xl font-bold tabular-nums text-slate-900">{{ $summary[$st] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Log --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.attendance.history_log') }}</h3>
                    <span class="text-xs text-slate-500">{{ $records->count() }}</span>
                </div>
                @if ($records->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-slate-500">{{ __('app.attendance.no_records_month') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($records as $r)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $r->session->date->translatedFormat('l d M Y') }}</div>
                                    @if ($r->note)
                                        <div class="mt-0.5 text-xs text-slate-500">{{ $r->note }}</div>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $statusMeta[$r->status]['class'] ?? 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                                    {{ $statusMeta[$r->status]['label'] ?? $r->status }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
