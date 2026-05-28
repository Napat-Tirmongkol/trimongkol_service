<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.classrooms_heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('app.admin.classrooms_search') }}"
                       class="block w-full max-w-md rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ __('app.admin.search') }}
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.classrooms') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('app.admin.clear') }}</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.classrooms.col_name') ?? __('app.admin.col_name') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.classroom_owner') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.students.heading') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.assignments.heading') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_joined') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('app.admin.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @forelse ($classrooms as $c)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-slate-900">
                                        <a href="{{ route('admin.classrooms.show', $c) }}" class="hover:text-brand-700">{{ $c->name }}</a>
                                        @if ($c->grade_level)
                                            <span class="ml-2 text-xs text-slate-500">{{ $c->grade_level }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($c->user)
                                            <a href="{{ route('admin.users.show', $c->user) }}" class="text-sm text-slate-700 hover:text-brand-700">{{ $c->user->name }}</a>
                                            <div class="text-xs text-slate-500">{{ $c->user->email }}</div>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-slate-700">{{ $c->students_count }}</td>
                                    <td class="px-6 py-3 text-center text-slate-700">{{ $c->assignments_count }}</td>
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $c->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.classrooms.show', $c) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('app.common.view') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.classrooms_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $classrooms->links() }}</div>
        </div>
    </div>
</x-admin-layout>
