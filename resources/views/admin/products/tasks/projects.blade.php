<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.tasks.tab_projects') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('app.admin.products.tasks.search_placeholder') }}"
                       class="block w-full max-w-md rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">{{ __('app.admin.search') }}</button>
                @if ($q !== '')
                    <a href="{{ route('admin.tasks.projects') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('app.admin.clear') }}</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.tasks.field_board_name') }}</th>
                                <th class="px-6 py-3">Workspace</th>
                                <th class="px-6 py-3">{{ __('app.admin.classroom_owner') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.admin.products.tasks.stat_tasks') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_joined') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('app.admin.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @forelse ($projects as $p)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-slate-900">
                                        <a href="{{ route('admin.tasks.projects.show', $p) }}" class="hover:text-brand-700">{{ $p->name }}</a>
                                    </td>
                                    <td class="px-6 py-3 text-slate-700">{{ optional($p->workspace)->name ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        @if ($p->user)
                                            <a href="{{ route('admin.users.show', $p->user) }}" class="text-sm text-slate-700 hover:text-brand-700">{{ $p->user->name }}</a>
                                            <div class="text-xs text-slate-500">{{ $p->user->email }}</div>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-slate-700">{{ $p->done_count }} / {{ $p->tasks_count }}</td>
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $p->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.tasks.projects.show', $p) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('app.common.view') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.tasks.projects_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $projects->links() }}</div>
        </div>
    </div>
</x-admin-layout>
