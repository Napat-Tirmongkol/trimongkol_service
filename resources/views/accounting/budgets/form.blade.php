<x-accounting-layout>
    @php
        $isEdit = $budget->exists;
        $title = $isEdit ? __('app.accounting.budget_edit') : __('app.accounting.budget_new');
        $action = $isEdit
            ? route('accounting.budgets.update', $budget)
            : route('accounting.budgets.store');
        $defaultYear = old('fiscal_year', $budget->fiscal_year ?? request('year', now()->year));
    @endphp

    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.budgets.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.budgets') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $title }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">{{ __('app.accounting.fix_errors') }}</div>
            @endif

            <form method="POST" action="{{ $action }}" x-data="{ submitting: false }" @submit="submitting = true"
                  class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div>
                    <label for="account_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.col_account') }} <span class="text-rose-500">*</span></label>
                    <select id="account_id" name="account_id" required class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">— {{ __('app.accounting.col_account') }} —</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('account_id', $budget->account_id) == $acc->id)>
                                {{ $acc->code }} {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.accounting.budget_account_hint') }}</p>
                </div>

                <div>
                    <label for="department_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.department') }}</label>
                    <select id="department_id" name="department_id" class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('app.accounting.all_departments') }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id', $budget->department_id) == $dept->id)>
                                {{ $dept->code }} {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="fiscal_year" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.fiscal_year') }} <span class="text-rose-500">*</span></label>
                        <input id="fiscal_year" name="fiscal_year" type="number" min="2000" max="2100" required value="{{ $defaultYear }}"
                               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="fiscal_month" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.fiscal_month') }}</label>
                        <select id="fiscal_month" name="fiscal_month" class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">{{ __('app.accounting.fiscal_annual') }}</option>
                            @foreach ($months as $m)
                                <option value="{{ $m }}" @selected(old('fiscal_month', $budget->fiscal_month) == $m)>
                                    {{ \Illuminate\Support\Carbon::create(null, (int) $m, 1)?->locale(app()->getLocale())->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.budget_amount') }} <span class="text-rose-500">*</span></label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0" required value="{{ old('amount', $budget->amount) }}"
                           class="mt-1.5 block w-full rounded-md border-slate-300 text-right tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('accounting.budgets.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                    <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                        {{ __('app.common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-accounting-layout>
