<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.accounting.onboarding_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">

<div class="w-full max-w-md" x-data="{
    type: '{{ old('business_type', '') }}',
    name: '{{ old('company_name', '') }}',
    get ready() { return this.type !== '' && this.name.trim() !== '' }
}">

    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">

        {{-- Title --}}
        <div class="px-8 pt-8 pb-5 text-center">
            <h1 class="text-lg font-semibold text-slate-900">{{ __('app.accounting.onboarding_title') }}</h1>
        </div>
        <hr class="border-slate-200">

        <form method="POST" action="{{ route('accounting.onboarding.store') }}" class="px-8 py-6 space-y-5">
            @csrf

            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Business type --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2.5">
                    {{ __('app.accounting.biz_type_label') }} <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition select-none has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="business_type" value="company" x-model="type"
                               class="sr-only">
                        <span class="text-sm font-semibold text-slate-900">{{ __('app.accounting.biz_type_company') }}</span>
                    </label>
                    <label class="relative flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition select-none has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="business_type" value="individual" x-model="type"
                               class="sr-only">
                        <span class="text-sm font-semibold text-slate-900">{{ __('app.accounting.biz_type_individual') }}</span>
                    </label>
                </div>
                @error('business_type')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Business name --}}
            <div>
                <label for="company_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    {{ __('app.accounting.company_name') }}
                </label>
                <input id="company_name" name="company_name" type="text" maxlength="255"
                       x-model="name"
                       value="{{ old('company_name', $workspace->company_name ?: $workspace->name) }}"
                       placeholder="{{ __('app.accounting.company_name_placeholder') }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder:text-slate-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100 @error('company_name') border-rose-400 @enderror">
            </div>

            {{-- Address --}}
            <div>
                <label for="company_address" class="block text-sm font-medium text-slate-700 mb-1.5">
                    {{ __('app.accounting.company_address') }}
                </label>
                <textarea id="company_address" name="company_address" rows="3" maxlength="500"
                          placeholder="{{ __('app.accounting.address_placeholder') }}"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder:text-slate-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100 resize-none">{{ old('company_address', $workspace->company_address) }}</textarea>
            </div>

            {{-- Tax ID --}}
            <div>
                <label for="tax_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                    {{ __('app.accounting.company_tax_id') }}
                    <span class="ml-1 inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-200 text-slate-500 text-[10px] cursor-help"
                          title="{{ __('app.accounting.tax_id_hint') }}">i</span>
                </label>
                <input id="tax_id" name="tax_id" type="text" maxlength="13" inputmode="numeric"
                       value="{{ old('tax_id', $workspace->tax_id) }}"
                       placeholder="000 000 000 000 0"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono tracking-widest placeholder:text-slate-400 placeholder:tracking-widest focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100">
                @error('tax_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- VAT registered --}}
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_vat" value="0">
                    <input type="checkbox" name="is_vat" value="1" @checked(old('is_vat'))
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-slate-700">
                        {{ __('app.accounting.is_vat_label') }}
                        <span class="ml-1 inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-200 text-slate-500 text-[10px] cursor-help"
                              title="{{ __('app.accounting.is_vat_hint') }}">i</span>
                    </span>
                </label>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end pt-1">
                <button type="submit"
                        :disabled="!ready"
                        class="rounded-full px-6 py-2.5 text-sm font-semibold transition
                               disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed
                               bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-100">
                    {{ __('app.accounting.onboarding_cta') }} →
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
