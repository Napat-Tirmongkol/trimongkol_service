<x-accounting-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.accounting_users') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- User list --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.accounting_users') }}</h3>
                </div>
                <div class="overflow-x-auto">
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
                            @forelse ($users as $u)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $u->name }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $u->email }}</td>
                                    <td class="px-4 py-2">
                                        @php $roleColors = ['owner' => 'bg-brand-50 text-brand-700 ring-brand-200', 'admin' => 'bg-amber-50 text-amber-700 ring-amber-200', 'staff' => 'bg-slate-100 text-slate-600 ring-slate-200']; @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $roleColors[$u->role] }}">
                                            {{ __('app.accounting.role_' . $u->role) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($u->is_active)
                                            <span class="text-emerald-600 text-xs font-medium">{{ __('app.accounting.status_active') }}</span>
                                        @else
                                            <span class="text-slate-400 text-xs font-medium">{{ __('app.accounting.status_inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right space-x-3">
                                        @if (auth('accounting')->user()->isOwner())
                                            <button type="button"
                                                    onclick="document.getElementById('edit-{{ $u->id }}').classList.toggle('hidden')"
                                                    class="text-xs text-brand-700 hover:text-brand-900">{{ __('app.accounting.edit') }}</button>

                                            @if ($u->id !== auth('accounting')->id())
                                                <form method="POST" action="{{ route('accounting.users.destroy', $u) }}"
                                                      data-confirm="{{ __('app.accounting.user_delete_confirm') }}"
                                                      data-confirm-danger="1" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-800">{{ __('app.accounting.delete') }}</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                {{-- Inline edit row --}}
                                @if (auth('accounting')->user()->isOwner())
                                    <tr id="edit-{{ $u->id }}" class="hidden bg-slate-50">
                                        <td colspan="5" class="px-4 py-3">
                                            <form method="POST" action="{{ route('accounting.users.update', $u) }}" class="flex flex-wrap gap-3 items-end">
                                                @csrf @method('PATCH')
                                                <div>
                                                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.accounting.employee_name') }}</label>
                                                    <input type="text" name="name" value="{{ $u->name }}" required
                                                           class="rounded border border-slate-300 px-2 py-1 text-sm w-36">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.accounting.user_role') }}</label>
                                                    <select name="role" class="rounded border border-slate-300 px-2 py-1 text-sm">
                                                        <option value="owner" @selected($u->role === 'owner')>{{ __('app.accounting.role_owner') }}</option>
                                                        <option value="admin" @selected($u->role === 'admin')>{{ __('app.accounting.role_admin') }}</option>
                                                        <option value="staff" @selected($u->role === 'staff')>{{ __('app.accounting.role_staff') }}</option>
                                                    </select>
                                                </div>
                                                <label class="flex items-center gap-1.5 text-sm text-slate-600">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" @checked($u->is_active) class="rounded border-slate-300">
                                                    {{ __('app.accounting.user_active') }}
                                                </label>
                                                <button type="submit" class="rounded bg-brand-600 px-3 py-1 text-xs font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.save') }}</button>
                                            </form>
                                            {{-- Reset password --}}
                                            <form method="POST" action="{{ route('accounting.users.reset-password', $u) }}" class="mt-2 flex flex-wrap gap-3 items-end border-t border-slate-200 pt-2">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.accounting.new_password') }}</label>
                                                    <input type="password" name="password" required minlength="8"
                                                           class="rounded border border-slate-300 px-2 py-1 text-sm w-36">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.accounting.confirm_password') }}</label>
                                                    <input type="password" name="password_confirmation" required
                                                           class="rounded border border-slate-300 px-2 py-1 text-sm w-36">
                                                </div>
                                                <button type="submit" class="rounded bg-slate-600 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-700">{{ __('app.accounting.reset_password') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">{{ __('app.accounting.no_users_yet') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Create new user --}}
            @if (auth('accounting')->user()->isOwner())
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('app.accounting.add_user') }}</h3>
                    <form method="POST" action="{{ route('accounting.users.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.employee_name') }}</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none @error('name') border-rose-400 @enderror">
                                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none @error('email') border-rose-400 @enderror">
                                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.login_password') }}</label>
                                <input type="password" name="password" required minlength="8"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.confirm_password') }}</label>
                                <input type="password" name="password_confirmation" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('app.accounting.user_role') }}</label>
                                <select name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
                                    <option value="staff">{{ __('app.accounting.role_staff') }}</option>
                                    <option value="admin">{{ __('app.accounting.role_admin') }}</option>
                                    <option value="owner">{{ __('app.accounting.role_owner') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            {{ __('app.accounting.add_user') }}
                        </button>
                    </form>
                </section>
            @endif

        </div>
    </div>
</x-accounting-layout>
