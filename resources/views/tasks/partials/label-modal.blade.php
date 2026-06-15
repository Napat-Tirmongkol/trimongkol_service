@php
    $labelChipFull = [
        'slate' => 'bg-slate-100 text-slate-700', 'rose' => 'bg-rose-100 text-rose-700',
        'amber' => 'bg-amber-100 text-amber-700', 'emerald' => 'bg-emerald-100 text-emerald-700',
        'sky' => 'bg-sky-100 text-sky-700', 'violet' => 'bg-violet-100 text-violet-700', 'brand' => 'bg-brand-100 text-brand-700',
    ];
    $labelSwatch = [
        'slate' => 'bg-slate-400', 'rose' => 'bg-rose-400', 'amber' => 'bg-amber-400', 'emerald' => 'bg-emerald-400',
        'sky' => 'bg-sky-400', 'violet' => 'bg-violet-400', 'brand' => 'bg-brand-400',
    ];
@endphp
<template x-teleport="body">
    <div x-show="labelOpen" x-cloak @keydown.escape.window="labelOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" x-transition.opacity>
        <div class="flex min-h-screen items-end justify-center p-0 sm:items-center sm:p-4">
            <div @click.outside="labelOpen = false"
                 class="relative w-full overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-md sm:rounded-2xl"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="translate-y-8 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('app.tasks.manage_labels') }}</h3>
                    <button type="button" @click="labelOpen = false" aria-label="Close"
                            class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div class="space-y-1.5">
                        @forelse ($labels as $label)
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2">
                                <span class="rounded px-2 py-0.5 text-xs font-medium {{ $labelChipFull[$label->color] ?? $labelChipFull['slate'] }}">{{ $label->name }}</span>
                                <form method="POST" action="{{ route('tasks.labels.destroy', $label) }}"
                                      data-confirm="{{ __('app.tasks.delete_label_confirm') }}" data-confirm-danger="1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded p-1 text-slate-300 hover:bg-rose-50 hover:text-rose-500" title="{{ __('app.common.delete') }}">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="rounded-lg bg-slate-50 px-3 py-3 text-center text-xs text-slate-400">{{ __('app.tasks.no_labels') }}</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('tasks.labels.store') }}" class="border-t border-slate-100 pt-4">
                        @csrf
                        <label for="label-name" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.new_label') }}</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input id="label-name" name="name" type="text" required maxlength="50"
                                   placeholder="{{ __('app.tasks.field_label_name_placeholder') }}"
                                   class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <button type="submit" class="shrink-0 rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.common.add') }}</button>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (\App\Models\TaskLabel::COLORS as $i => $color)
                                <label class="cursor-pointer">
                                    <input type="radio" name="color" value="{{ $color }}" class="peer sr-only" @checked($i === 0)>
                                    <span class="block h-6 w-6 rounded-full {{ $labelSwatch[$color] }} ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-slate-400"></span>
                                </label>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
