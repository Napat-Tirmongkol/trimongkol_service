<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.roles.heading') }}</h2>
            <a href="{{ route('admin.roles.create') }}"
               class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                + {{ __('app.roles.create') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.roles.name') }}</th>
                                <th class="px-6 py-3">{{ __('app.roles.permissions') }}</th>
                                <th class="px-6 py-3 text-center">{{ __('app.roles.members') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('app.admin.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($roles as $role)
                                <tr>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-900">{{ $role->name }}</span>
                                            @if ($role->is_system)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">{{ __('app.roles.system') }}</span>
                                            @endif
                                        </div>
                                        @if ($role->description)
                                            <div class="mt-0.5 text-xs text-slate-500">{{ $role->description }}</div>
                                        @endif
                                        <div class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $role->key }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-slate-600">
                                        @if ($role->isSuper())
                                            <span class="font-medium text-brand-700">{{ __('app.roles.all_permissions') }}</span>
                                        @else
                                            {{ count($role->permissions ?? []) }} {{ __('app.roles.permissions') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-slate-600">{{ $role->users_count }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.roles.edit', $role) }}"
                                               class="text-xs font-medium text-slate-600 hover:text-slate-900">{{ __('app.common.edit') }}</a>
                                            @unless ($role->is_system)
                                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                                      data-confirm="{{ __('app.roles.confirm_delete', ['name' => $role->name]) }}" data-confirm-danger="1">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800">{{ __('app.common.delete') }}</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-xs text-slate-500">{{ __('app.roles.index_hint') }}</p>
        </div>
    </div>
</x-admin-layout>
