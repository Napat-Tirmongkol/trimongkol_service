<x-accounting-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.products.show', $product) }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ $product->name }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.product_edit') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">{{ __('app.accounting.fix_errors') }}</div>
            @endif

            <form method="POST" action="{{ route('accounting.products.update', $product) }}"
                  class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_sku') }}</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_unit') }}</label>
                        <input type="text" name="unit" value="{{ old('unit', $product->unit) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>

                    @if ($hasMovements)
                        <div class="sm:col-span-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">
                            {{ __('app.accounting.product_accounts_locked') }}
                        </div>
                        <div class="sm:col-span-2 text-sm text-slate-600">
                            <span class="text-slate-400">{{ __('app.accounting.product_inventory_account') }}:</span>
                            <span class="font-mono">{{ $product->inventoryAccount?->code }}</span> {{ $product->inventoryAccount?->name }}
                        </div>
                        <div class="sm:col-span-2 text-sm text-slate-600">
                            <span class="text-slate-400">{{ __('app.accounting.product_cogs_account') }}:</span>
                            <span class="font-mono">{{ $product->cogsAccount?->code }}</span> {{ $product->cogsAccount?->name }}
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_inventory_account') }}</label>
                            <select name="inventory_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">—</option>
                                @foreach ($assetAccounts as $a)
                                    <option value="{{ $a->id }}" @selected(old('inventory_account_id', $product->inventory_account_id) == $a->id)>{{ $a->code }} {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.product_cogs_account') }}</label>
                            <select name="cogs_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">—</option>
                                @foreach ($expenseAccounts as $a)
                                    <option value="{{ $a->id }}" @selected(old('cogs_account_id', $product->cogs_account_id) == $a->id)>{{ $a->code }} {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.products.show', $product) }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('app.common.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-accounting-layout>
