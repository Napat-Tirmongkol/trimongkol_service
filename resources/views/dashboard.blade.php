<x-app-layout>
    @php
        $totalStudents = $classrooms->sum('students_count');
        $totalAssignments = $classrooms->sum('assignments_count');
        $firstName = trim(explode(' ', (string) auth()->user()->name)[0] ?? '');
    @endphp

    <div class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-b from-brand-50 to-white">
        {{-- soft brand accents on the light hero --}}
        <div class="pointer-events-none absolute inset-0 bg-grid opacity-50"></div>
        <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-brand-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 top-8 h-40 w-40 rounded-full bg-brand-100/50 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('app.dashboard.greeting_label') }}</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        {{ __('app.dashboard.greeting', ['name' => $firstName]) }}
                    </h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-500">{{ __('app.dashboard.subtitle') }}</p>
                </div>
                @if ($classrooms->isNotEmpty())
                    <a href="{{ route('classrooms.create') }}"
                       class="inline-flex items-center gap-2 self-start rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 sm:self-auto">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        {{ __('app.classrooms.add') }}
                    </a>
                @endif
            </div>

            @if ($classrooms->isNotEmpty())
                <div class="mt-7 grid grid-cols-3 gap-3 sm:max-w-xl">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_classrooms') }}</div>
                        <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $classrooms->count() }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_students') }}</div>
                        <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalStudents }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_assignments') }}</div>
                        <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $totalAssignments }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
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

        @if ($classrooms->isEmpty())
            <div class="overflow-hidden rounded-3xl border border-dashed border-slate-300 bg-white">
                <div class="grid items-center gap-6 p-8 sm:grid-cols-[1fr_auto] sm:gap-10 sm:p-12">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                            {{ __('app.dashboard.start_here') }}
                        </div>
                        <h2 class="mt-3 text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('app.classrooms.emptyTitle') }}</h2>
                        <p class="mt-2 max-w-md text-sm text-slate-600">{{ __('app.classrooms.emptyDesc') }}</p>

                        <a href="{{ route('classrooms.create') }}"
                           class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            {{ __('app.classrooms.add') }}
                        </a>

                        <ul class="mt-6 grid gap-2 text-sm text-slate-600 sm:max-w-md">
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-700">1</span>
                                {{ __('app.dashboard.step_1') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-700">2</span>
                                {{ __('app.dashboard.step_2') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-700">3</span>
                                {{ __('app.dashboard.step_3') }}
                            </li>
                        </ul>
                    </div>
                    <div class="hidden sm:block">
                        <div class="grid h-40 w-40 place-items-center rounded-3xl bg-gradient-to-br from-brand-100 via-brand-50 to-white shadow-inner">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-brand-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/>
                                <rect x="7" y="7" width="10" height="10" rx="1"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('app.dashboard.your_classrooms') }}</h2>
                <span class="text-xs text-slate-500">{{ $classrooms->count() }} {{ __('app.dashboard.count_suffix') }}</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($classrooms as $classroom)
                    @php $latest = $classroom->assignments->first(); @endphp
                    <a href="{{ route('classrooms.show', $classroom) }}"
                       class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5">
                        <span class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-brand-50 opacity-0 transition group-hover:opacity-100"></span>

                        <div class="relative flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-lg font-semibold text-slate-900 group-hover:text-brand-700">
                                    {{ $classroom->name }}
                                </h3>
                                @if ($classroom->description)
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $classroom->description }}</p>
                                @endif
                            </div>
                            @if ($classroom->grade_level)
                                <span class="shrink-0 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700">
                                    {{ $classroom->grade_level }}
                                </span>
                            @endif
                        </div>

                        <div class="relative mt-5 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_students') }}</div>
                                <div class="mt-0.5 text-lg font-bold tabular-nums text-slate-900">{{ $classroom->students_count }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">{{ __('app.dashboard.stat_assignments') }}</div>
                                <div class="mt-0.5 text-lg font-bold tabular-nums text-slate-900">{{ $classroom->assignments_count }}</div>
                            </div>
                        </div>

                        <div class="relative mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                            @if ($latest)
                                <span class="truncate">
                                    <span class="text-slate-400">{{ __('app.dashboard.latest') }}:</span>
                                    <span class="font-medium text-slate-700">{{ $latest->name }}</span>
                                </span>
                            @else
                                <span class="text-slate-400">{{ __('app.assignments.empty_short') }}</span>
                            @endif
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 class="shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @endforeach

                <a href="{{ route('classrooms.create') }}"
                   class="group flex min-h-[180px] flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/50 p-5 text-center text-slate-500 transition hover:border-brand-400 hover:bg-brand-50/50 hover:text-brand-700">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-white ring-1 ring-slate-200 transition group-hover:ring-brand-200">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </span>
                    <span class="text-sm font-medium">{{ __('app.classrooms.add') }}</span>
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
