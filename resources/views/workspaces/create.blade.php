<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('workspaces.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.workspaces.heading') }}</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                {{ __('app.workspaces.create_heading') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('workspaces.store') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">
                        {{ __('app.workspaces.field_name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input id="name" name="name" type="text" required maxlength="80" autofocus
                           value="{{ old('name') }}"
                           placeholder="{{ __('app.workspaces.field_name_placeholder') }}"
                           class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.workspaces.field_name_hint') }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('workspaces.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        {{ __('app.workspaces.create_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
