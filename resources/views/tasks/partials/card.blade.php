@php
    $done = $task->isDone();
    $subTotal = $task->items->count();
    $subDone = $task->items->where('is_done', true)->count();
@endphp
<div data-task-card data-task-id="{{ $task->id }}" data-status="{{ $task->status }}" draggable="true"
     @click="openEdit('{{ $task->id }}')"
     class="group cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-brand-300 hover:shadow active:cursor-grabbing">

    @if ($task->labels->isNotEmpty())
        <div class="mb-2 flex flex-wrap gap-1">
            @foreach ($task->labels as $label)
                <span class="rounded px-1.5 py-0.5 text-[11px] font-medium {{ $labelChip[$label->color] ?? $labelChip['slate'] }}">{{ $label->name }}</span>
            @endforeach
        </div>
    @endif

    <div class="flex items-start gap-2">
        <span data-card-check class="mt-0.5 text-emerald-500 {{ $done ? '' : 'hidden' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
        <p data-card-title class="min-w-0 flex-1 text-sm font-medium {{ $done ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $task->title }}</p>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $priorityBadge[$task->priority] }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $priorityDot[$task->priority] }}"></span>
            {{ __('app.tasks.priority.' . $task->priority) }}
        </span>

        @if ($task->due_date)
            <span class="inline-flex items-center gap-1 text-xs {{ $task->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-500' }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ $task->due_date->format('d M') }}
            </span>
        @endif

        <span data-subtask-wrap class="inline-flex items-center gap-1 text-xs text-slate-500 {{ $subTotal === 0 ? 'hidden' : '' }}">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <span data-subtask-progress>{{ $subDone }}/{{ $subTotal }}</span>
        </span>

        @if ($task->assignee)
            <span class="ml-auto grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-700" title="{{ $task->assignee->name }}">{{ $initials($task->assignee->name) }}</span>
        @endif
    </div>
</div>
