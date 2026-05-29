<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('app.admin.usersHeading') }}
            </h2>
            <a href="{{ route('admin.users.export', request()->query()) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('app.admin.export_csv') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" class="flex flex-wrap items-end gap-2">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.search') }}</label>
                    <input type="text" name="q" value="{{ $q }}"
                           placeholder="{{ __('app.admin.searchPlaceholder') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.filter_role') }}</label>
                    <select name="role" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('app.admin.filter_all') }}</option>
                        <option value="staff" @selected($role === 'staff')>{{ __('app.roles.filter_staff') }}</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->key }}" @selected($role === $r->key)>{{ $r->name }}</option>
                        @endforeach
                        <option value="teacher" @selected($role === 'teacher')>{{ __('app.admin.role_teacher') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.filter_status') }}</label>
                    <select name="status" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('app.admin.filter_all') }}</option>
                        <option value="active" @selected($status === 'active')>{{ __('app.admin.status_active') }}</option>
                        <option value="suspended" @selected($status === 'suspended')>{{ __('app.admin.status_suspended') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">{{ __('app.admin.sort') }}</label>
                    <select name="sort" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="recent" @selected($sort === 'recent')>{{ __('app.admin.sort_recent') }}</option>
                        <option value="classrooms" @selected($sort === 'classrooms')>{{ __('app.admin.sort_classrooms') }}</option>
                        <option value="last_login" @selected($sort === 'last_login')>{{ __('app.admin.sort_last_login') }}</option>
                        <option value="name" @selected($sort === 'name')>{{ __('app.admin.sort_name') }}</option>
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ __('app.admin.apply') }}
                </button>
                @if ($q !== '' || $role || $status || $sort !== 'recent')
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
                                <th class="px-6 py-3">{{ __('app.admin.col_status') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_last_login') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.col_joined') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('app.admin.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @foreach ($users as $user)
                                <tr class="{{ $user->is_active ? '' : 'bg-rose-50/30' }}">
                                    <td class="px-6 py-3 font-medium text-slate-900">
                                        <a href="{{ route('admin.users.show', $user) }}" class="hover:text-brand-700">{{ $user->name }}</a>
                                    </td>
                                    <td class="px-6 py-3 text-slate-600">{{ $user->email }}</td>
                                    <td class="px-6 py-3 text-center text-slate-600">{{ $user->classrooms_count }}</td>
                                    <td class="px-6 py-3">
                                        @if ($user->role)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3 6 7 1-5 4 1 7-6-3-6 3 1-7-5-4 7-1z"/></svg>
                                                {{ $user->role->name }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ __('app.roles.user_badge') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($user->is_active)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.status_active') }}</span>
                                        @else
                                            <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700">{{ __('app.admin.status_suspended') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-xs text-slate-500">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-xs text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('app.common.view') }}</a>
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
