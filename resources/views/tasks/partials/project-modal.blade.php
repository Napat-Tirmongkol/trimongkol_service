@php
    $swatch = [
        'brand' => 'bg-brand-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500',
        'violet' => 'bg-violet-500', 'sky' => 'bg-sky-500', 'slate' => 'bg-slate-400',
    ];
@endphp
<template x-teleport="body">
    <div x-show="projectOpen" x-cloak @keydown.escape.window="projectOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" x-transition.opacity>
        <div class="flex min-h-screen items-end justify-center p-0 sm:items-center sm:p-4">
            <div @click.outside="projectOpen = false"
                 class="relative w-full overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-md sm:rounded-2xl"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="translate-y-8 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
                <form method="POST" action="{{ route('tasks.projects.update', $project) }}">
                    @csrf @method('PATCH')
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-900">{{ __('app.tasks.edit_board') }}</h3>
                        <button type="button" @click="projectOpen = false" aria-label="Close"
                                class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-4">
                        <div>
                            <label for="edit-board-name" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_board_name') }} <span class="text-rose-500">*</span></label>
                            <input id="edit-board-name" name="name" type="text" required maxlength="120" value="{{ $project->name }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="edit-board-desc" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_description') }}</label>
                            <textarea id="edit-board-desc" name="description" rows="2" maxlength="1000"
                                      class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $project->description }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_color') }}</label>
                            <div class="mt-1.5 flex flex-wrap gap-2">
                                @foreach (\App\Models\TaskProject::COLORS as $color)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="color" value="{{ $color }}" class="peer sr-only" @checked($project->color === $color)>
                                        <span class="block h-7 w-7 rounded-full {{ $swatch[$color] }} ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-slate-400"></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3">
                        <button type="button" @click="projectOpen = false" class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ __('app.common.cancel') }}</button>
                        <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">{{ __('app.common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
