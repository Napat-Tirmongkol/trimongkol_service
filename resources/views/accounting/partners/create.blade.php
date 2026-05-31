<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.partner_new') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('accounting.partners.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.partner_name') }} <span class="text-rose-500">*</span></label>
                        <input id="name" name="name" type="text" required autofocus value="{{ old('name') }}"
                               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="code" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.partner_code') }}</label>
                            <input id="code" name="code" type="text" maxlength="30" value="{{ old('code') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="credit_days" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.partner_credit_days') }}</label>
                            <input id="credit_days" name="credit_days" type="number" min="0" max="365" value="{{ old('credit_days', 0) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="tax_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.partner_tax_id') }}</label>
                            <input id="tax_id" name="tax_id" type="text" maxlength="13" value="{{ old('tax_id') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 font-mono shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('tax_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="branch_code" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.partner_branch') }}</label>
                            <input id="branch_code" name="branch_code" type="text" maxlength="10" value="{{ old('branch_code', '00000') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 font-mono shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_customer" value="1" {{ old('is_customer', true) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('app.accounting.partner_is_customer') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_vendor" value="1" {{ old('is_vendor') ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('app.accounting.partner_is_vendor') }}
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('accounting.partners.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                        <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
