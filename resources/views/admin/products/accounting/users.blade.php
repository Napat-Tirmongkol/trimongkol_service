<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.accounting_users') }}</h2>
            <a href="{{ route('admin.accounting.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800">← Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Create user form --}}
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('app.accounting.add_user') }}</h3>
                <form method="POST" action="{{ route('admin.accounting.users.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Workspace</label>
                            <select name="workspace_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                                <option value="">— เลือก workspace —</option>
                                @foreach ($workspaces as $ws)
                                    <option value="{{ $ws->id }}">{{ $ws->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('app.accounting.employee_name') }}</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('app.accounting.login_password') }}</label>
                            <input type="password" name="password" required minlength="8"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('app.accounting.user_role') }}</label>
                            <select name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                                <option value="owner">{{ __('app.accounting.role_owner') }}</option>
                                <option value="admin">{{ __('app.accounting.role_admin') }}</option>
                                <option value="staff">{{ __('app.accounting.role_staff') }}</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        {{ __('app.accounting.add_user') }}
                    </button>
                </form>
            </section>

            {{-- Users list by workspace --}}
            @foreach ($workspaces as $ws)
                @if ($ws->accountingUsers->isNotEmpty())
                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $ws->name }}</h3>
                        </div>
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.employee_name') }}</th>
                                    <th class="px-4 py-2 text-left">Email</th>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.user_role') }}</th>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.user_active') }}</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($ws->accountingUsers as $u)
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-slate-900">{{ $u->name }}</td>
                                        <td class="px-4 py-2 text-slate-600">{{ $u->email }}</td>
                                        <td class="px-4 py-2 text-slate-600 capitalize">{{ $u->role }}</td>
                                        <td class="px-4 py-2">
                                            @if ($u->is_active)
                                                <span class="text-emerald-600 text-xs font-medium">{{ __('app.accounting.status_active') }}</span>
                                            @else
                                                <span class="text-slate-400 text-xs font-medium">{{ __('app.accounting.status_inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <form method="POST" action="{{ route('admin.accounting.users.destroy', $u) }}"
                                                  data-confirm="{{ __('app.accounting.user_delete_confirm') }}"
                                                  data-confirm-danger="1" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800">{{ __('app.accounting.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif
            @endforeach

        </div>
    </div>
</x-app-layout>
