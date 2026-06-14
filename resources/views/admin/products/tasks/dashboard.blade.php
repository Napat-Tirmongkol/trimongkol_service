<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.tasks.label') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.products.tasks.desc') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.tasks.stat_projects') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['projects'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ $stats['workspaces'] }} {{ __('app.admin.products.tasks.stat_workspaces') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.tasks.stat_tasks') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['tasks'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">+{{ $stats['tasks_today'] }} {{ __('app.admin.products.tasks.today') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.tasks.stat_open') }}</div>
                    <div class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['tasks_open'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.tasks.stat_done') }}</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['tasks_done'] }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.tasks.top_projects') }}</h3>
                    <a href="{{ route('admin.tasks.projects') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.viewAll') }} →</a>
                </div>
                @if ($topProjects->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.tasks.projects_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($topProjects as $p)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.tasks.projects.show', $p) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $p->name }}</a>
                                    <div class="truncate text-xs text-slate-500">
                                        @if ($p->workspace) {{ $p->workspace->name }} @endif
                                        @if ($p->user) · {{ $p->user->email }} @endif
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-2 text-xs text-slate-600">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $p->tasks_count }} {{ __('app.admin.products.tasks.stat_tasks') }}</span>
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700">{{ $p->done_count }} {{ __('app.tasks.status.done') }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
