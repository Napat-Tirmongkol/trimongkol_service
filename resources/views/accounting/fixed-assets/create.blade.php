<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.fixed-assets.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.fixed_assets') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.asset_new') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.fixed-assets.store') }}"
                  class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.asset_code') }}</label>
                        <input type="text" name="code" value="{{ old('code') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.asset_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.asset_purchase_date') }}</label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.asset_cost') }}</label>
                        <input type="number" name="cost" value="{{ old('cost') }}" step="0.01" min="0"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.asset_salvage') }} <span class="text-slate-400">({{ __('app.accounting.optional') }})</span></label>
                        <input type="number" name="salvage_value" value="{{ old('salvage_value', 0) }}" step="0.01" min="0"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.asset_life') }}</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input type="number" name="useful_life_months" value="{{ old('useful_life_months', 60) }}" min="1"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <span class="text-sm text-slate-500">{{ __('app.accounting.months') }}</span>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-200">

                <div class="space-y-3">
                    <p class="text-sm font-medium text-slate-700">{{ __('app.accounting.asset_accounts') }}</p>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.asset_account') }}</label>
                        <select name="asset_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('asset_account_id') == $acc->id)>{{ $acc->code }} {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.asset_accumulated_account') }}</label>
                        <select name="accumulated_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('accumulated_account_id') == $acc->id || str_contains($acc->name, 'เสื่อม'))>{{ $acc->code }} {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.asset_depreciation_account') }}</label>
                        <select name="depreciation_expense_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('depreciation_expense_account_id') == $acc->id || str_contains($acc->name, 'เสื่อม'))>{{ $acc->code }} {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.fixed-assets.index') }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('app.accounting.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.accounting.asset_register') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
