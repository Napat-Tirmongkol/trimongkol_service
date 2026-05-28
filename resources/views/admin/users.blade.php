<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.usersHeading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('app.admin.searchPlaceholder') }}"
                       class="block w-full max-w-sm rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ __('app.admin.search') }}
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.users') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('app.admin.clear') }}
                    </a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.admin.col_name') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_email') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.admin.col_classrooms') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_role') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_joined') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('app.admin.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $user->email }}</td>
                                    <td class="px-6 py-3 text-center text-slate-600">{{ $user->classrooms_count }}</td>
                                    <td class="px-6 py-3">
                                        @if ($user->is_admin)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3 6 7 1-5 4 1 7-6-3-6 3 1-7-5-4 7-1z"/></svg>
                                                admin
                                            </span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">teacher</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        @if ($user->id === auth()->id())
                                            <span class="text-xs text-slate-400">{{ __('app.admin.you') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}" class="inline-flex">
                                                @csrf
                                                <button type="submit"
                                                        onclick="return confirm('{{ $user->is_admin ? __('app.admin.confirmDemote', ['name' => $user->name]) : __('app.admin.confirmPromote', ['name' => $user->name]) }}')"
                                                        class="{{ $user->is_admin ? 'text-rose-600 hover:text-rose-700' : 'text-brand-700 hover:text-brand-800' }} text-xs font-medium">
                                                    {{ $user->is_admin ? __('app.admin.demote') : __('app.admin.promote') }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $users->links() }}</div>
        </div>
    </div>
</x-admin-layout>
