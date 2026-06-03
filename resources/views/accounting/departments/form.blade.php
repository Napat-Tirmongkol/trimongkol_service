<x-app-layout>
    @php
        $isEdit = $department->exists;
        $title = $isEdit ? __('app.accounting.department_edit') : __('app.accounting.department_new');
        $action = $isEdit
            ? route('accounting.departments.update', $department)
            : route('accounting.departments.store');
    @endphp

    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.departments.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.departments') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $title }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ $action }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">
                    @csrf
                    @if ($isEdit) @method('PUT') @endif

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="code" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.department_code') }} <span class="text-rose-500">*</span></label>
                            <input id="code" name="code" type="text" required autofocus maxlength="20" value="{{ old('code', $department->code) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 font-mono shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.department_name') }} <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $department->name) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        {{ __('app.accounting.active') }}
                    </label>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('accounting.departments.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                        <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                            <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                            {{ __('app.common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
