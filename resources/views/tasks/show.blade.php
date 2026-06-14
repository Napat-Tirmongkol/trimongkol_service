@php
    // Literal class strings per token — keep them whole so Tailwind JIT builds them.
    $priorityDot = [
        'urgent' => 'bg-rose-500', 'high' => 'bg-amber-500', 'normal' => 'bg-sky-500', 'low' => 'bg-slate-300',
    ];
    $priorityBadge = [
        'urgent' => 'bg-rose-50 text-rose-700 ring-rose-200', 'high' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'normal' => 'bg-sky-50 text-sky-700 ring-sky-200', 'low' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $labelChip = [
        'slate' => 'bg-slate-100 text-slate-700', 'rose' => 'bg-rose-100 text-rose-700',
        'amber' => 'bg-amber-100 text-amber-700', 'emerald' => 'bg-emerald-100 text-emerald-700',
        'sky' => 'bg-sky-100 text-sky-700', 'violet' => 'bg-violet-100 text-violet-700', 'brand' => 'bg-brand-100 text-brand-700',
    ];
    $statusDot = ['todo' => 'bg-slate-400', 'in_progress' => 'bg-amber-500', 'done' => 'bg-emerald-500'];
    $projectDot = [
        'brand' => 'bg-brand-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500',
        'violet' => 'bg-violet-500', 'sky' => 'bg-sky-500', 'slate' => 'bg-slate-400',
    ];

    // Compact task payload for the JS edit modal, keyed by id.
    $tasksForJs = [];
    foreach ($project->tasks as $t) {
        $tasksForJs[$t->id] = [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'due_date' => optional($t->due_date)->format('Y-m-d'),
            'assignee_id' => $t->assignee_id,
            'label_ids' => $t->labels->pluck('id')->all(),
            'items' => $t->items->map(fn ($i) => ['id' => $i->id, 'title' => $i->title, 'is_done' => (bool) $i->is_done])->values(),
        ];
    }

    $config = [
        'projectId' => $project->id,
        'csrf' => csrf_token(),
        'storeUrl' => route('tasks.store', $project),
        'taskBase' => url('/tasks'),
        'tasksById' => (object) $tasksForJs,
    ];

    $initials = function ($name) {
        return collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?';
    };
@endphp

<x-app-layout>
    <div x-data="taskBoard(@js($config))" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Toolbar --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('tasks.index') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" title="{{ __('app.tasks.heading') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
                <div class="min-w-0">
                    <h2 class="flex items-center gap-2 truncate text-xl font-semibold leading-tight text-gray-800">
                        <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full {{ $projectDot[$project->color] ?? $projectDot['brand'] }}"></span>
                        {{ $project->name }}
                    </h2>
                    @if ($project->description)
                        <p class="mt-0.5 line-clamp-1 text-sm text-slate-500">{{ $project->description }}</p>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                {{-- View toggle --}}
                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
                    <button type="button" @click="view = 'board'"
                            :class="view === 'board' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1.5"/><rect x="14" y="3" width="7" height="11" rx="1.5"/></svg>
                        <span class="hidden sm:inline">{{ __('app.tasks.view_board') }}</span>
                    </button>
                    <button type="button" @click="view = 'list'"
                            :class="view === 'list' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        <span class="hidden sm:inline">{{ __('app.tasks.view_list') }}</span>
                    </button>
                </div>

                {{-- Settings menu --}}
                <div x-data="{ menu: false }" @click.outside="menu = false" class="relative">
                    <button @click="menu = !menu" type="button" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" title="{{ __('app.tasks.board_settings') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                    </button>
                    <div x-show="menu" x-cloak x-transition @click="menu = false"
                         class="absolute right-0 z-20 mt-2 w-52 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg ring-1 ring-slate-900/5">
                        <button type="button" @click="labelOpen = true"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            {{ __('app.tasks.manage_labels') }}
                        </button>
                        <button type="button" @click="openProjectEdit()"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            {{ __('app.tasks.edit_board') }}
                        </button>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('tasks.projects.destroy', $project) }}"
                              data-confirm="{{ __('app.tasks.delete_board_confirm') }}" data-confirm-danger="1">
                            @csrf @method('DELETE')
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                {{ __('app.tasks.delete_board') }}
                            </button>
                        </form>
                    </div>
                </div>

                <button @click="openCreate('todo')" type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span class="hidden sm:inline">{{ __('app.tasks.new_task') }}</span>
                </button>
            </div>
        </div>

        {{-- ───────────── Board (Kanban) view ───────────── --}}
        <div x-show="view === 'board'" class="mt-6 flex gap-4 overflow-x-auto pb-4">
            @foreach (\App\Models\Task::STATUSES as $status)
                <section data-board-column data-status="{{ $status }}"
                         class="flex w-80 shrink-0 flex-col rounded-2xl bg-slate-100/70 transition">
                    <header class="flex items-center justify-between px-3 py-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $statusDot[$status] }}"></span>
                            <h3 class="text-sm font-semibold text-slate-700">{{ __('app.tasks.status.' . $status) }}</h3>
                            <span data-count class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $columns[$status]->count() }}</span>
                        </div>
                        <button type="button" @click="openCreate('{{ $status }}')" class="grid h-6 w-6 place-items-center rounded-md text-slate-400 transition hover:bg-slate-200 hover:text-slate-700" title="{{ __('app.tasks.new_task') }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </header>

                    <div data-column-list class="flex min-h-[60px] flex-col gap-2 px-3 pb-3">
                        @forelse ($columns[$status] as $task)
                            @include('tasks.partials.card', compact('task', 'priorityDot', 'priorityBadge', 'labelChip', 'initials'))
                        @empty
                            <p data-empty class="px-1 py-4 text-center text-xs text-slate-400">{{ __('app.tasks.column_empty') }}</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        {{-- ───────────── List view ───────────── --}}
        <div x-show="view === 'list'" x-cloak class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @php $flat = collect(\App\Models\Task::STATUSES)->flatMap(fn ($s) => $columns[$s]); @endphp
            @if ($flat->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.tasks.column_empty') }}</div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($flat as $task)
                        <li class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50">
                            <form method="POST" action="{{ route('tasks.toggle', $task) }}" class="shrink-0">
                                @csrf
                                <input type="checkbox" onchange="this.form.submit()" @checked($task->isDone())
                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                       title="{{ __('app.tasks.mark_done') }}">
                            </form>
                            <button type="button" @click="openEdit('{{ $task->id }}')" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $priorityDot[$task->priority] }}" title="{{ __('app.tasks.priority.' . $task->priority) }}"></span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium {{ $task->isDone() ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $task->title }}</span>
                                @foreach ($task->labels as $label)
                                    <span class="hidden shrink-0 rounded px-1.5 py-0.5 text-[11px] font-medium sm:inline {{ $labelChip[$label->color] ?? $labelChip['slate'] }}">{{ $label->name }}</span>
                                @endforeach
                                @if ($task->due_date)
                                    <span class="hidden shrink-0 text-xs sm:inline {{ $task->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-500' }}">{{ $task->due_date->format('d M') }}</span>
                                @endif
                                @if ($task->assignee)
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-700" title="{{ $task->assignee->name }}">{{ $initials($task->assignee->name) }}</span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @include('tasks.partials.task-modal', ['members' => $members, 'labels' => $labels])
        @include('tasks.partials.label-modal', ['labels' => $labels])
        @include('tasks.partials.project-modal')
    </div>

    @include('tasks.partials.board-script')
</x-app-layout>
