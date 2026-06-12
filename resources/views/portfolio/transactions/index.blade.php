<x-portfolio-layout>
    @php
        $fmtMoney = fn ($n) => number_format((float) $n, 2);
        $isDebt = $holding->isDebt();
        $ccy = $holding->currency ?: 'THB';
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            {{-- Navigation/Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('portfolio.dashboard') }}" 
                       class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ __('app.portfolio.transactions.back_to_portfolio') }}
                    </a>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                        {{ __('app.portfolio.transactions.title') }} — {{ $holding->label }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('app.portfolio.form.currency') }}: <span class="font-semibold text-slate-700">{{ $ccy }}</span> · 
                        {{ __('app.portfolio.col_value') }}: <span class="font-semibold text-slate-700">฿{{ $fmtMoney($holding->current_value_thb) }}</span>
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm text-right">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('app.portfolio.form.amount') }}</div>
                    <div class="text-2xl font-bold {{ $isDebt ? 'text-rose-700' : 'text-slate-900' }}">
                        {{ $isDebt ? '−' : '' }}{{ $ccy }} {{ $fmtMoney($holding->current_price) }}
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                {{-- Add Transaction Form (Left Column) --}}
                <div class="md:col-span-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">
                        {{ __('app.portfolio.transactions.add_title') }}
                    </h3>
                    <form method="POST" action="{{ route('portfolio.holdings.transactions.store', $holding) }}" class="space-y-4">
                        @csrf
                        
                        {{-- Type --}}
                        <div>
                            <label for="type" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                {{ __('app.portfolio.transactions.type') }}
                            </label>
                            <select id="type" name="type" required
                                    class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @if ($isDebt)
                                    <option value="in" @selected(old('type') === 'in')>{{ __('app.portfolio.transactions.type_in_debt') }}</option>
                                    <option value="out" @selected(old('type') === 'out')>{{ __('app.portfolio.transactions.type_out_debt') }}</option>
                                @else
                                    <option value="in" @selected(old('type') === 'in')>{{ __('app.portfolio.transactions.type_in_asset') }}</option>
                                    <option value="out" @selected(old('type') === 'out')>{{ __('app.portfolio.transactions.type_out_asset') }}</option>
                                @endif
                            </select>
                            @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label for="amount" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                {{ __('app.portfolio.transactions.amount') }} ({{ $ccy }})
                            </label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required
                                   value="{{ old('amount') }}"
                                   class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Date --}}
                        <div>
                            <label for="transaction_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                {{ __('app.portfolio.transactions.date') }}
                            </label>
                            <input id="transaction_date" name="transaction_date" type="date" required
                                   value="{{ old('transaction_date', date('Y-m-d')) }}"
                                   class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('transaction_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                {{ __('app.portfolio.transactions.notes') }}
                            </label>
                            <input id="notes" name="notes" type="text" maxlength="500"
                                   value="{{ old('notes') }}"
                                   class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit"
                                class="mt-2 w-full inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            {{ __('app.portfolio.transactions.submit') }}
                        </button>
                    </form>
                </div>

                {{-- Transactions List (Right Column) --}}
                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col justify-between min-h-[300px]">
                    @if ($transactions->isEmpty())
                        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-sm text-slate-500 italic">
                            {{ __('app.portfolio.transactions.empty') }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3 text-left font-medium">{{ __('app.portfolio.transactions.col_date') }}</th>
                                        <th class="px-5 py-3 text-left font-medium">{{ __('app.portfolio.transactions.col_type') }}</th>
                                        <th class="px-5 py-3 text-right font-medium">{{ __('app.portfolio.transactions.col_amount') }}</th>
                                        <th class="px-5 py-3 text-left font-medium">{{ __('app.portfolio.transactions.col_notes') }}</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 divide-solid">
                                    @foreach ($transactions as $t)
                                        @php
                                            $isIn = $t->type === 'in';
                                            // Badge styles
                                            $badgeClass = $isIn 
                                                ? ($isDebt ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200')
                                                : ($isDebt ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200');
                                            
                                            $badgeText = $isIn 
                                                ? ($isDebt ? __('app.portfolio.transactions.type_in_debt') : __('app.portfolio.transactions.type_in_asset'))
                                                : ($isDebt ? __('app.portfolio.transactions.type_out_debt') : __('app.portfolio.transactions.type_out_asset'));
                                        @endphp
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="px-5 py-3 font-medium text-slate-900 whitespace-nowrap">
                                                {{ $t->transaction_date->format('Y-m-d') }}
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                                    {{ $badgeText }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-right tabular-nums font-semibold text-slate-900 whitespace-nowrap">
                                                {{ $isIn ? '+' : '−' }}{{ $ccy }} {{ $fmtMoney($t->amount) }}
                                            </td>
                                            <td class="px-5 py-3 text-slate-600 max-w-[200px] truncate" title="{{ $t->notes }}">
                                                {{ $t->notes ?: '—' }}
                                            </td>
                                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                                <form method="POST" action="{{ route('portfolio.holdings.transactions.destroy', [$holding, $t]) }}"
                                                      data-confirm="{{ __('app.portfolio.transactions.delete_confirm') }}" data-confirm-danger="1">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" 
                                                            class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                        {{ __('app.portfolio.delete') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-portfolio-layout>
