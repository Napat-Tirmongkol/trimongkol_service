@php
    $labelDot = [
        'slate' => 'bg-slate-400', 'rose' => 'bg-rose-400', 'amber' => 'bg-amber-400', 'emerald' => 'bg-emerald-400',
        'sky' => 'bg-sky-400', 'violet' => 'bg-violet-400', 'brand' => 'bg-brand-400',
    ];
@endphp
<template x-teleport="body">
    <div x-show="taskOpen" x-cloak @keydown.escape.window="taskOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" x-transition.opacity>
        <div class="flex min-h-screen items-end justify-center p-0 sm:items-center sm:p-4">
            <div @click.outside="taskOpen = false"
                 class="relative flex w-full max-h-[92vh] flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="translate-y-8 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900" x-text="form.mode === 'edit' ? @js(__('app.tasks.edit_task')) : @js(__('app.tasks.new_task'))"></h3>
                    <button type="button" @click="taskOpen = false" aria-label="Close"
                            class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <form id="taskForm" method="POST" :action="form.action">
                        @csrf
                        <input type="hidden" name="_method" :value="form.method">

                        <div>
                            <label for="task-title" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_title') }} <span class="text-rose-500">*</span></label>
                            <input id="task-title" name="title" type="text" required maxlength="200" x-model="form.title"
                                   placeholder="{{ __('app.tasks.field_title_placeholder') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div class="mt-4">
                            <label for="task-desc" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_description') }}</label>
                            <textarea id="task-desc" name="description" rows="3" maxlength="5000" x-model="form.description"
                                      class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div>
                                <label for="task-status" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_status') }}</label>
                                <select id="task-status" name="status" x-model="form.status"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach (\App\Models\Task::STATUSES as $status)
                                        <option value="{{ $status }}">{{ __('app.tasks.status.' . $status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="task-priority" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_priority') }}</label>
                                <select id="task-priority" name="priority" x-model="form.priority"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach (\App\Models\Task::PRIORITIES as $priority)
                                        <option value="{{ $priority }}">{{ __('app.tasks.priority.' . $priority) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div>
                                <label for="task-due" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_due') }}</label>
                                <input id="task-due" name="due_date" type="date" x-model="form.due_date"
                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label for="task-assignee" class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_assignee') }}</label>
                                <select id="task-assignee" name="assignee_id" x-model="form.assignee_id"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">{{ __('app.tasks.unassigned') }}</option>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if ($labels->isNotEmpty())
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-slate-600">{{ __('app.tasks.field_labels') }}</label>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($labels as $label)
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition"
                                               :class="form.labels.includes('{{ $label->id }}') ? 'border-slate-400 bg-slate-50 text-slate-800' : 'border-slate-200 text-slate-500 hover:bg-slate-50'">
                                            <input type="checkbox" name="labels[]" value="{{ $label->id }}" x-model="form.labels" class="sr-only">
                                            <span class="h-2 w-2 rounded-full {{ $labelDot[$label->color] ?? $labelDot['slate'] }}"></span>
                                            {{ $label->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </form>

                    {{-- Sub-tasks (edit only) — managed live via fetch, outside the main form. --}}
                    <template x-if="form.mode === 'edit'">
                        <div class="border-t border-slate-100 pt-4">
                            <label class="block text-xs font-medium text-slate-600">{{ __('app.tasks.subtasks') }}</label>
                            <div class="mt-1.5 space-y-1">
                                <template x-for="item in form.items" :key="item.id">
                                    <div class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                                        <input type="checkbox" :checked="item.is_done" @change="toggleItem(item)"
                                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span class="min-w-0 flex-1 text-sm" :class="item.is_done ? 'text-slate-400 line-through' : 'text-slate-700'" x-text="item.title"></span>
                                        <button type="button" @click="deleteItem(item)" class="shrink-0 rounded p-1 text-slate-300 hover:bg-rose-50 hover:text-rose-500" title="{{ __('app.common.delete') }}">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <p x-show="form.items.length === 0" class="px-2 py-1 text-xs text-slate-400">{{ __('app.tasks.no_subtasks') }}</p>
                            </div>
                            <div class="mt-1.5 flex items-center gap-2">
                                <input type="text" x-ref="newItem" maxlength="200" @keydown.enter.prevent="addItem($refs.newItem.value)"
                                       placeholder="{{ __('app.tasks.add_subtask') }}"
                                       class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <button type="button" @click="addItem($refs.newItem.value)"
                                        class="shrink-0 rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">{{ __('app.common.add') }}</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3">
                    <template x-if="form.mode === 'edit'">
                        <form method="POST" :action="form.action" data-confirm="{{ __('app.tasks.delete_task_confirm') }}" data-confirm-danger="1">
                            @csrf
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">{{ __('app.common.delete') }}</button>
                        </form>
                    </template>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" @click="taskOpen = false" class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ __('app.common.cancel') }}</button>
                        <button type="submit" form="taskForm" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">{{ __('app.common.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
