<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('classrooms.attendance.index', $classroom) }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('app.attendance.heading') }}
                </a>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">
                    {{ $session->date->translatedFormat('l d M Y') }}
                </h2>
                <p class="text-xs text-slate-500">{{ $classroom->name }} · {{ $students->count() }} {{ __('app.attendance.students_count') }}</p>
            </div>
            <form method="POST" action="{{ route('classrooms.attendance.destroy', [$classroom, $session]) }}"
                  data-confirm="{{ __('app.attendance.delete_confirm') }}" data-confirm-danger="1">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-md border border-rose-200 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                    {{ __('app.attendance.delete_session') }}
                </button>
            </form>
        </div>
    </x-slot>

    @php
        $statuses = [
            'present' => ['label' => __('app.attendance.status_present'), 'tone' => 'emerald', 'icon' => '<polyline points="20 6 9 17 4 12"/>'],
            'late' => ['label' => __('app.attendance.status_late'), 'tone' => 'amber', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
            'absent' => ['label' => __('app.attendance.status_absent'), 'tone' => 'rose', 'icon' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'],
            'excused' => ['label' => __('app.attendance.status_excused'), 'tone' => 'slate', 'icon' => '<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>'],
        ];
        $toneClasses = [
            'emerald' => ['active' => 'bg-emerald-600 text-white', 'idle' => 'text-emerald-700 hover:bg-emerald-50'],
            'amber' => ['active' => 'bg-amber-500 text-white', 'idle' => 'text-amber-700 hover:bg-amber-50'],
            'rose' => ['active' => 'bg-rose-600 text-white', 'idle' => 'text-rose-700 hover:bg-rose-50'],
            'slate' => ['active' => 'bg-slate-700 text-white', 'idle' => 'text-slate-600 hover:bg-slate-100'],
        ];
        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        foreach ($records as $r) {
            if (isset($counts[$r->status])) $counts[$r->status]++;
        }
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            {{-- Quick counts --}}
            <div class="grid grid-cols-4 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                @foreach (['present', 'late', 'absent', 'excused'] as $st)
                    <div class="bg-white px-3 py-3 text-center">
                        <div class="text-[10px] uppercase tracking-wider text-{{ $statuses[$st]['tone'] }}-600">{{ $statuses[$st]['label'] }}</div>
                        <div class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">{{ $counts[$st] }}</div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('classrooms.attendance.update', [$classroom, $session]) }}" class="space-y-4">
                @csrf @method('PATCH')

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($students as $student)
                            @php
                                $rec = $records[$student->id] ?? null;
                                $currentStatus = $rec?->status ?? 'present';
                            @endphp
                            <li class="px-4 py-3 sm:px-5">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-slate-900">
                                            @if ($student->number)<span class="mr-1 text-slate-400">#{{ $student->number }}</span>@endif
                                            {{ $student->name }}
                                        </div>
                                        @if ($rec?->note)
                                            <div class="mt-0.5 truncate text-xs text-slate-500">{{ $rec->note }}</div>
                                        @endif
                                    </div>
                                    <div x-data="{ status: '{{ $currentStatus }}' }"
                                         class="inline-flex shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-50 p-0.5">
                                        @foreach ($statuses as $key => $meta)
                                            <button type="button"
                                                    @click="status = '{{ $key }}'"
                                                    :class="status === '{{ $key }}' ? '{{ $toneClasses[$meta['tone']]['active'] }}' : '{{ $toneClasses[$meta['tone']]['idle'] }}'"
                                                    class="grid h-9 w-9 place-items-center rounded-full transition"
                                                    title="{{ $meta['label'] }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">{!! $meta['icon'] !!}</svg>
                                            </button>
                                        @endforeach
                                        <input type="hidden" name="records[{{ $student->id }}][status]" x-model="status">
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="sticky bottom-3 z-10">
                    <button type="submit"
                            class="w-full rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/30 hover:bg-brand-700">
                        {{ __('app.attendance.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
