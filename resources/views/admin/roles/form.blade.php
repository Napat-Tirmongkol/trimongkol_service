<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $role->exists ? __('app.roles.edit') : __('app.roles.create') }}
            </h2>
            <a href="{{ route('admin.roles.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ __('app.roles.nav') }}</a>
        </div>
    </x-slot>

    @php
        $selected = old('permissions', $role->permissions ?? []);
        $isSuper = $role->isSuper();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST"
                  action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
                  class="space-y-6">
                @csrf
                @if ($role->exists) @method('PATCH') @endif

                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">{{ __('app.roles.name') }}</label>
                        <input id="name" name="name" type="text" required maxlength="80"
                               value="{{ old('name', $role->name) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700">{{ __('app.roles.description') }}</label>
                        <input id="description" name="description" type="text" maxlength="255"
                               value="{{ old('description', $role->description) }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.roles.permissions') }}</h3>
                    @if ($isSuper)
                        <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-inset ring-amber-200">{{ __('app.roles.super_locked') }}</p>
                    @endif

                    <div class="mt-4 space-y-5">
                        @foreach ($groups as $group)
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ \App\Support\Permissions::label($group) }}</div>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ($group['permissions'] as $pkey => $perm)
                                        <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm {{ $isSuper ? 'opacity-60' : 'hover:bg-slate-50' }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $pkey }}"
                                                   @checked($isSuper || in_array($pkey, $selected, true))
                                                   @disabled($isSuper)
                                                   class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                            <span>
                                                <span class="font-medium text-slate-800">{{ \App\Support\Permissions::label($perm) }}</span>
                                                <span class="block font-mono text-[11px] text-slate-400">{{ $pkey }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('admin.roles.index') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('app.common.cancel') }}</a>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.roles.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
