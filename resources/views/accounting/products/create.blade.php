<x-accounting-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.products.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.products') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.product_new') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.products.store') }}"
                  class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_sku') }}</label>
                        <input type="text" name="sku" value="{{ old('sku') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_unit') }}</label>
                        <input type="text" name="unit" value="{{ old('unit', 'ชิ้น') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_inventory_account') }}</label>
                        <select name="inventory_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($assetAccounts as $a)
                                <option value="{{ $a->id }}" @selected(old('inventory_account_id') == $a->id || str_contains($a->name, 'สินค้า'))>{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_cogs_account') }}</label>
                        <select name="cogs_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($expenseAccounts as $a)
                                <option value="{{ $a->id }}" @selected(old('cogs_account_id') == $a->id || str_contains($a->name, 'ซื้อสินค้า'))>{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.products.index') }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('app.accounting.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.accounting.product_register') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-accounting-layout>
