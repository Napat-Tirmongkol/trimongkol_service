<x-app-layout>
    @php $editing = $account->exists; @endphp
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.accounts.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.accounts') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $editing ? __('app.accounting.account_edit') : __('app.accounting.account_new') }}</h2>
        </div>
    </x-slot>

    @php
        $typeLabels = [
            'asset' => __('app.accounting.assets'),
            'liability' => __('app.accounting.liabilities'),
            'equity' => __('app.accounting.equity'),
            'income' => __('app.accounting.revenue'),
            'expense' => __('app.accounting.expenses'),
        ];
        $locked = $editing && $account->system_role;
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @if ($locked)
                    <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">
                        {{ __('app.accounting.account_role_locked') }}
                    </div>
                @endif

                <form method="POST"
                      action="{{ $editing ? route('accounting.accounts.update', $account) : route('accounting.accounts.store') }}"
                      x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">
                    @csrf
                    @if ($editing) @method('PATCH') @endif

                    <div class="grid gap-4 sm:grid-cols-[8rem_1fr]">
                        <div>
                            <label for="code" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.account_code') }} <span class="text-rose-500">*</span></label>
                            <input id="code" name="code" type="text" required maxlength="20" value="{{ old('code', $account->code) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 font-mono shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.account_name') }} <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" required autofocus maxlength="255" value="{{ old('name', $account->name) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="name_en" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.account_name_en') }}</label>
                        <input id="name_en" name="name_en" type="text" maxlength="255" value="{{ old('name_en', $account->name_en) }}"
                               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('name_en') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.account_type') }} <span class="text-rose-500">*</span></label>
                        <select id="type" name="type" {{ $locked ? 'disabled' : '' }}
                                class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-500">
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $account->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($locked)
                            <input type="hidden" name="type" value="{{ $account->type }}">
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.accounting.account_type_locked') }}</p>
                        @endif
                        @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-3 rounded-lg bg-slate-50 px-4 py-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('app.accounting.account_active') }}
                            <span class="text-xs text-slate-400">— {{ __('app.accounting.account_active_hint') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="is_tax_deductible" value="0">
                            <input type="checkbox" name="is_tax_deductible" value="1" {{ old('is_tax_deductible', $account->is_tax_deductible ?? true) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('app.accounting.account_deductible') }}
                            <span class="text-xs text-slate-400">— {{ __('app.accounting.account_deductible_hint') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('accounting.accounts.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
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
