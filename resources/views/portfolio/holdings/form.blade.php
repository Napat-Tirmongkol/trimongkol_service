<x-portfolio-layout>
    @php
        $isEdit = $holding->exists;
        $action = $isEdit
            ? route('portfolio.holdings.update', $holding)
            : route('portfolio.holdings.store');

        $kinds = [
            'stock', 'fund', 'crypto',
            'deposit', 'cash', 'gold', 'realestate', 'debt', 'other',
        ];

        // For Alpine's initial state — string-cast everything so empty
        // values don't render as the literal "null".
        $initialKind = old('kind', $holding->kind) ?: 'stock';
        $initialQty = (string) old('quantity', $holding->quantity ?? '1');
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                {{ $isEdit ? __('app.portfolio.form.edit_title') : __('app.portfolio.form.create_title') }}
            </h1>

            <form method="POST" action="{{ $action }}"
                  x-data="{
                      kind: @js($initialKind),
                      quantity: @js($initialQty),
                      isMoney() { return ['cash', 'deposit', 'debt'].includes(this.kind); },
                      isInvestment() { return ['stock', 'fund', 'crypto'].includes(this.kind); },
                      init() {
                          this.$watch('kind', () => {
                              // Money kinds collapse to a single amount field; the hidden
                              // quantity input still needs a sane value so validation
                              // (`required|numeric|min:0`) passes on submit.
                              if (this.isMoney()) this.quantity = '1';
                          });
                      }
                  }"
                  class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @if ($isEdit) @method('PATCH') @endif

                {{-- Kind --}}
                <div>
                    <label for="kind" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.kind') }}</label>
                    <select id="kind" name="kind" required x-model="kind"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @foreach ($kinds as $k)
                            <option value="{{ $k }}" @selected(old('kind', $holding->kind) === $k)>
                                {{ __("app.portfolio.kinds.{$k}") }}
                            </option>
                        @endforeach
                    </select>
                    @error('kind') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Label --}}
                <div>
                    <label for="label" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.label') }}</label>
                    <input id="label" name="label" type="text" required maxlength="120"
                           value="{{ old('label', $holding->label) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.portfolio.form.label_hint') }}</p>
                    @error('label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Symbol — only for investment kinds with a real price feed --}}
                <div x-show="isInvestment()" x-cloak>
                    <label for="symbol" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.symbol') }}</label>
                    <input id="symbol" name="symbol" type="text" maxlength="32"
                           value="{{ old('symbol', $holding->symbol) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <p class="mt-1 text-xs text-slate-500">
                        <span x-show="kind === 'stock'" x-cloak>{{ __('app.portfolio.form.symbol_hint_stock') }}</span>
                        <span x-show="kind === 'fund'" x-cloak>{{ __('app.portfolio.form.symbol_hint_fund') }}</span>
                        <span x-show="kind === 'crypto'" x-cloak>{{ __('app.portfolio.form.symbol_hint_crypto') }}</span>
                    </p>
                    @error('symbol') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Quantity + currency row.  Quantity stays in the DOM (hidden
                     via x-show) so its bound value still submits, but in money
                     mode the column collapses and currency stretches full. --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <div x-show="!isMoney()" x-cloak>
                        <label for="quantity" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.quantity') }}</label>
                        {{-- `required` would still be enforced when hidden, blocking submit in
                             cash/deposit/debt mode — drop it and rely on server validation
                             instead.  x-model keeps the value in sync (defaults to 1 for
                             money kinds via the form's init watcher). --}}
                        <input id="quantity" name="quantity" type="number" step="0.00000001" min="0"
                               x-model="quantity"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('quantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div :class="isMoney() ? 'sm:col-span-2' : ''">
                        <label for="currency" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.currency') }}</label>
                        <select id="currency" name="currency" required
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach (['THB', 'USD', 'EUR', 'JPY', 'GBP', 'SGD', 'HKD', 'CNY'] as $c)
                                <option value="{{ $c }}" @selected(old('currency', $holding->currency ?? 'THB') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('currency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Price / amount + cost basis row.  Money mode hides cost_basis
                     and the price input fills the row, relabeled as "amount". --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <div :class="isMoney() ? 'sm:col-span-2' : ''">
                        <label for="current_price" class="block text-sm font-medium text-slate-700">
                            <span x-show="isMoney()" x-cloak>{{ __('app.portfolio.form.amount') }}</span>
                            <span x-show="!isMoney()" x-cloak>{{ __('app.portfolio.form.current_price') }}</span>
                        </label>
                        <input id="current_price" name="current_price" type="number" step="0.00000001" min="0"
                               value="{{ old('current_price', $holding->current_price) }}"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">
                            <span x-show="isMoney()" x-cloak>{{ __('app.portfolio.form.amount_hint') }}</span>
                            <span x-show="!isMoney()" x-cloak>{{ __('app.portfolio.form.current_price_hint') }}</span>
                        </p>
                        @error('current_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div x-show="!isMoney()" x-cloak>
                        <label for="cost_basis" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.cost_basis') }}</label>
                        <input id="cost_basis" name="cost_basis" type="number" step="0.01" min="0"
                               value="{{ old('cost_basis', $holding->cost_basis) }}"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.portfolio.form.cost_basis_hint') }}</p>
                        @error('cost_basis') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.notes') }}</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="1000"
                              class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('notes', $holding->notes) }}</textarea>
                    @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="max-w-[8rem]">
                    <label for="sort_order" class="block text-sm font-medium text-slate-700">{{ __('app.portfolio.form.sort_order') }}</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="9999"
                           value="{{ old('sort_order', $holding->sort_order ?? 0) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('portfolio.dashboard') }}"
                       class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        {{ __('app.portfolio.form.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.portfolio.form.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-portfolio-layout>
