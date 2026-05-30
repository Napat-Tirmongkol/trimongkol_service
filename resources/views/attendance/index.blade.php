<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('classrooms.show', $classroom) }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ $classroom->name }}
                </a>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ __('app.attendance.heading') }}</h2>
            </div>
            <form method="POST" action="{{ route('classrooms.attendance.today', $classroom) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">
                    @if ($todaySession)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ __('app.attendance.continue_today') }}
                    @else
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        {{ __('app.attendance.mark_today') }}
                    @endif
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            {{-- Summary stats --}}
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 sm:grid-cols-3">
                <div class="bg-white px-5 py-4">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.attendance.stat_sessions') }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $summary['session_count'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.attendance.this_month') }}</div>
                </div>
                <div class="bg-white px-5 py-4">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.attendance.stat_rate') }}</div>
                    @php
                        $rate = $summary['overall_rate_percent'];
                        $rateTone = $rate === null ? 'text-slate-900' : ($rate >= 90 ? 'text-emerald-700' : ($rate >= 75 ? 'text-amber-700' : 'text-rose-700'));
                    @endphp
                    <div class="mt-1 text-2xl font-bold tabular-nums {{ $rateTone }}">{{ $rate !== null ? $rate.'%' : '—' }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.attendance.rate_hint') }}</div>
                </div>
                <div class="col-span-2 bg-white px-5 py-4 sm:col-span-1">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ __('app.attendance.stat_top_absent') }}</div>
                    @if (! empty($summary['top_absentees']))
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($summary['top_absentees'] as $row)
                                <a href="{{ route('classrooms.students.attendance', [$classroom, $row['student_id']]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100">
                                    {{ $row['name'] }}
                                    <span class="tabular-nums text-rose-600">×{{ $row['absent_count'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-2 text-xs text-slate-400">{{ __('app.attendance.no_absences') }}</div>
                    @endif
                </div>
            </div>

            {{-- Calendar --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <a href="{{ route('classrooms.attendance.index', [$classroom, 'month' => $summary['prev_month']]) }}"
                       class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h3 class="text-sm font-semibold text-slate-900">{{ $summary['month_label'] }}</h3>
                    <a href="{{ route('classrooms.attendance.index', [$classroom, 'month' => $summary['next_month']]) }}"
                       class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="grid grid-cols-7 gap-px bg-slate-100">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $dow)
                        <div class="bg-slate-50 px-1 py-2 text-center text-[10px] font-medium uppercase tracking-wider {{ $i === 0 || $i === 6 ? 'text-rose-500' : 'text-slate-500' }}">
                            {{ __("app.attendance.dow_{$dow}") }}
                        </div>
                    @endforeach
                    @foreach ($summary['calendar'] as $week)
                        @foreach ($week as $i => $day)
                            @if ($day === null)
                                <div class="aspect-square bg-slate-50/50"></div>
                            @else
                                @php
                                    $hasSession = $day['session_id'] !== null;
                                    $rate = $day['rate'];
                                    $dayTone = ! $hasSession ? 'bg-white' :
                                        ($rate === null ? 'bg-slate-50' :
                                            ($rate >= 90 ? 'bg-emerald-50' : ($rate >= 75 ? 'bg-amber-50' : 'bg-rose-50')));
                                    $dayNum = (int) substr($day['date'], 8, 2);
                                @endphp
                                @if ($hasSession)
                                    <a href="{{ route('classrooms.attendance.show', [$classroom, $day['session_id']]) }}"
                                       class="group flex aspect-square flex-col justify-between p-1.5 {{ $dayTone }} hover:ring-2 hover:ring-inset hover:ring-brand-400">
                                        <span class="text-xs font-semibold {{ $i === 0 || $i === 6 ? 'text-rose-600' : 'text-slate-700' }}">{{ $dayNum }}</span>
                                        @if ($rate !== null)
                                            <span class="text-[10px] font-bold tabular-nums text-slate-600">{{ round($rate) }}%</span>
                                        @endif
                                        @if ($day['absent'] > 0)
                                            <span class="text-[9px] text-rose-600">{{ __('app.attendance.absent_count', ['n' => $day['absent']]) }}</span>
                                        @endif
                                    </a>
                                @else
                                    <div class="flex aspect-square flex-col p-1.5 {{ $dayTone }}">
                                        <span class="text-xs {{ $i === 0 || $i === 6 ? 'text-rose-400' : 'text-slate-400' }}">{{ $dayNum }}</span>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-5 py-3 text-[11px] text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-emerald-100 ring-1 ring-emerald-200"></span>≥ 90%</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-amber-100 ring-1 ring-amber-200"></span>75–89%</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-rose-100 ring-1 ring-rose-200"></span>&lt; 75%</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
