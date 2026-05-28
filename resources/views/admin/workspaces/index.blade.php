<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.workspaces.heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('app.admin.workspaces.search') }}"
                       class="block w-full max-w-md rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ __('app.admin.search') }}
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.workspaces.index') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('app.admin.clear') }}</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.admin.workspaces.col_name') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.workspaces.col_owner') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.admin.workspaces.col_members') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.admin.workspaces.col_classrooms') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_joined') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @forelse ($workspaces as $w)
                                @php $ownerMembership = $w->memberships->first(); @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('admin.workspaces.show', $w) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $w->name }}</a>
                                        <div class="text-xs text-slate-400">/{{ $w->slug }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($ownerMembership && $ownerMembership->user)
                                            <a href="{{ route('admin.users.show', $ownerMembership->user) }}" class="text-sm text-slate-700 hover:text-brand-700">{{ $ownerMembership->user->name }}</a>
                                            <div class="text-xs text-slate-500">{{ $ownerMembership->user->email }}</div>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-slate-700">{{ $w->members_count }}</td>
                                    <td class="px-6 py-3 text-center text-slate-700">{{ $w->classrooms_count }}</td>
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $w->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.workspaces.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $workspaces->links() }}</div>
        </div>
    </div>
</x-admin-layout>
