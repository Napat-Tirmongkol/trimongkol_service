@php
    // Full literal class strings per colour token — Tailwind JIT only sees these,
    // never an interpolated `bg-{{ $color }}-50`, so the classes always build.
    $boardAccent = [
        'brand' => 'bg-brand-50 text-brand-700', 'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700', 'rose' => 'bg-rose-50 text-rose-700',
        'violet' => 'bg-violet-50 text-violet-700', 'sky' => 'bg-sky-50 text-sky-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
    $boardBar = [
        'brand' => 'bg-brand-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500', 'violet' => 'bg-violet-500', 'sky' => 'bg-sky-500', 'slate' => 'bg-slate-400',
    ];
    $swatch = [
        'brand' => 'bg-brand-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500', 'violet' => 'bg-violet-500', 'sky' => 'bg-sky-500', 'slate' => 'bg-slate-400',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.tasks.heading') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.tasks.subheading') }}</p>
            </div>
            @if ($workspace)
                <div x-data="{ open: false }" class="sm:shrink-0">
                    <button @click="open = true" type="button"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:w-auto">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('app.tasks.new_board') }}
                    </button>

                    @include('tasks.partials.board-form-modal', ['swatch' => $swatch])
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (! $workspace)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    {{ __('app.workspaces.no_workspace') }}
                </div>
            @elseif ($projects->isEmpty())
                <div x-data="{ open: false }">
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1.5"/><rect x="14" y="3" width="7" height="11" rx="1.5"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('app.tasks.empty_title') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.tasks.empty_desc') }}</p>
                        <button @click="open = true" type="button"
                                class="mt-6 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            {{ __('app.tasks.new_board') }}
                        </button>
                    </div>
                    @include('tasks.partials.board-form-modal', ['swatch' => $swatch])
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($projects as $project)
                        @php
                            $pct = $project->tasks_count > 0 ? round($project->done_count / $project->tasks_count * 100) : 0;
                            $accent = $boardAccent[$project->color] ?? $boardAccent['brand'];
                            $bar = $boardBar[$project->color] ?? $boardBar['brand'];
                        @endphp
                        <a href="{{ route('tasks.projects.show', $project) }}"
                           class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $accent }}">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1.5"/><rect x="14" y="3" width="7" height="11" rx="1.5"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-lg font-semibold text-slate-900 group-hover:text-brand-700">{{ $project->name }}</div>
                                    @if ($project->description)
                                        <p class="mt-0.5 line-clamp-1 text-sm text-slate-500">{{ $project->description }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <span>{{ __('app.tasks.progress', ['done' => $project->done_count, 'total' => $project->tasks_count]) }}</span>
                                    <span class="font-semibold text-slate-700">{{ $pct }}%</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
