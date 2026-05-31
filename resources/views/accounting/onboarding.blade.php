<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.onboarding_title') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.onboarding_sub') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.onboarding.store') }}"
                  x-data="{ submitting: false }" @submit="submitting = true" class="space-y-6">
                @csrf

                {{-- Step 1: company profile (printed on documents) --}}
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.onboarding_company') }}</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.company_name') }} <span class="text-rose-500">*</span></label>
                            <input id="company_name" name="company_name" type="text" required autofocus maxlength="255"
                                   value="{{ old('company_name', $workspace->company_name ?: $workspace->name) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('company_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tax_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.company_tax_id') }}</label>
                                <input id="tax_id" name="tax_id" type="text" maxlength="13" inputmode="numeric" value="{{ old('tax_id') }}"
                                       placeholder="0105xxxxxxxxx"
                                       class="mt-1.5 block w-full rounded-md border-slate-300 font-mono shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @error('tax_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="branch" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.company_branch') }}</label>
                                <input id="branch" name="branch" type="text" maxlength="30" value="{{ old('branch', 'สำนักงานใหญ่') }}"
                                       class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.company_phone') }}</label>
                            <input id="phone" name="phone" type="text" maxlength="30" value="{{ old('phone') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label for="company_address" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.company_address') }}</label>
                            <textarea id="company_address" name="company_address" rows="2" maxlength="500"
                                      class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('company_address') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Step 2: chart-of-accounts template --}}
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ chosen: '{{ old('chart_template', array_key_first($templates)) }}' }">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">2</span>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.onboarding_chart') }}</h3>
                    </div>
                    <p class="mb-3 text-xs text-slate-500">{{ __('app.accounting.onboarding_chart_hint') }}</p>

                    <div class="space-y-2">
                        @foreach ($templates as $key => $tpl)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                                   :class="chosen === '{{ $key }}' ? 'border-brand-400 bg-brand-50 ring-1 ring-brand-200' : 'border-slate-200 hover:bg-slate-50'">
                                <input type="radio" name="chart_template" value="{{ $key }}" x-model="chosen"
                                       class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span>
                                    <span class="block text-sm font-medium text-slate-800">{{ $tpl['name'] }}</span>
                                    <span class="block text-xs text-slate-500">{{ $tpl['name_en'] }} · {{ trans_choice(':count บัญชี|:count บัญชี', count($tpl['accounts']), ['count' => count($tpl['accounts'])]) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('chart_template') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    <p class="mt-3 text-xs text-slate-400">{{ __('app.accounting.onboarding_chart_editable') }}</p>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" :disabled="submitting"
                            class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                        {{ __('app.accounting.onboarding_cta') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
