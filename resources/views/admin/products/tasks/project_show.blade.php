@php
    $statusBadge = [
        'todo' => 'bg-slate-100 text-slate-600', 'in_progress' => 'bg-amber-50 text-amber-700', 'done' => 'bg-emerald-50 text-emerald-700',
    ];
    $priorityDot = ['urgent' => 'bg-rose-500', 'high' => 'bg-amber-500', 'normal' => 'bg-sky-500', 'low' => 'bg-slate-300'];
@endphp
<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.tasks.projects') }}" class="text-slate-500 hover:text-slate-800">{{ __('app.admin.products.tasks.tab_projects') }}</a>
            <span class="text-slate-300">/</span>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $project->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $project->name }}</h3>
                        @if ($project->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $project->description }}</p>
                        @endif
                        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <dt class="text-slate-500">Workspace</dt>
                            <dd class="text-slate-800">{{ optional($project->workspace)->name ?? '—' }}</dd>
                            <dt class="text-slate-500">{{ __('app.admin.classroom_owner') }}</dt>
                            <dd class="text-slate-800">{{ optional($project->user)->email ?? '—' }}</dd>
                            <dt class="text-slate-500">{{ __('app.admin.products.tasks.stat_tasks') }}</dt>
                            <dd class="text-slate-800">{{ $project->done_count }} / {{ $project->tasks_count }} {{ __('app.tasks.status.done') }}</dd>
                        </dl>
                    </div>
                    <form method="POST" action="{{ route('admin.tasks.projects.destroy', $project) }}"
                          data-confirm="{{ __('app.admin.products.tasks.delete_confirm') }}" data-confirm-danger="1">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
                            {{ __('app.tasks.delete_board') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.tasks.heading') }}</h3>
                </div>
                @if ($tasks->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.tasks.column_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($tasks as $task)
                            <li class="flex items-center gap-3 px-6 py-3">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $priorityDot[$task->priority] ?? $priorityDot['normal'] }}"></span>
                                <span class="min-w-0 flex-1 truncate text-sm {{ $task->isDone() ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $task->title }}</span>
                                @if ($task->assignee)
                                    <span class="shrink-0 text-xs text-slate-500">{{ $task->assignee->name }}</span>
                                @endif
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge[$task->status] ?? $statusBadge['todo'] }}">{{ __('app.tasks.status.' . $task->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
