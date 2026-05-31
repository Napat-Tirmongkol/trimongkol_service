<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.reports') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.financial_reports') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.opening_balances') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.opening_balances_sub') }}</p>
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
        $typeOrder = ['asset', 'liability', 'equity', 'income', 'expense'];
        $openingDisplay = \Illuminate\Support\Carbon::parse($openingDate)->format('d/m/Y');
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            @if ($existing)
                {{-- Already posted — locked. Redoing means reversing the existing journal first. --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-900">{{ __('app.accounting.opening_exists_title') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.accounting.opening_exists_desc') }}</p>
                            <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm ring-1 ring-slate-200">
                                <span class="font-mono font-medium text-slate-800">{{ $existing->no }}</span>
                                <span class="text-slate-400">·</span>
                                <span class="text-slate-600">{{ $existing->date?->format('d/m/Y') }}</span>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('accounting.reports') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">{{ __('app.accounting.view_opening_journal') }} →</a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('accounting.opening-balances.store') }}"
                      x-data="openingForm()" x-init="recompute()"
                      @submit="if ($el.dataset.confirmed === '1') submitting = true"
                      data-confirm="{{ __('app.accounting.opening_confirm') }}">
                    @csrf

                    <div class="rounded-xl border border-brand-100 bg-brand-50/60 px-5 py-4 text-sm text-slate-700">
                        <p>{{ __('app.accounting.opening_intro') }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ __('app.accounting.opening_intro_note') }}</p>
                        <p class="mt-3 text-xs font-medium text-brand-800">{{ __('app.accounting.opening_as_of') }} {{ $openingDisplay }}</p>
                    </div>

                    @error('balances')
                        <div class="mt-4 rounded-lg bg-rose-50 px-4 py-2 text-sm text-rose-700 ring-1 ring-rose-200">{{ $message }}</div>
                    @enderror

                    @foreach ($typeOrder as $type)
                        @continue (! isset($accountsByType[$type]))
                        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 bg-slate-50 px-5 py-2.5">
                                <h3 class="text-sm font-semibold text-slate-800">{{ $typeLabels[$type] }}</h3>
                            </div>
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                                        <th class="px-5 py-2 text-left font-medium">{{ __('app.accounting.col_account') }}</th>
                                        <th class="w-40 px-5 py-2 text-right font-medium">{{ __('app.accounting.col_debit') }}</th>
                                        <th class="w-40 px-5 py-2 text-right font-medium">{{ __('app.accounting.col_credit') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach ($accountsByType[$type] as $account)
                                        <tr>
                                            <td class="px-5 py-1.5 text-slate-700"><span class="font-mono text-xs text-slate-400">{{ $account->code }}</span> {{ $account->name }}</td>
                                            <td class="px-5 py-1.5">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                       name="balances[{{ $account->id }}][debit]"
                                                       value="{{ old('balances.'.$account->id.'.debit') }}"
                                                       @input="recompute()"
                                                       class="js-debit w-full rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                       placeholder="0.00">
                                            </td>
                                            <td class="px-5 py-1.5">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                       name="balances[{{ $account->id }}][credit]"
                                                       value="{{ old('balances.'.$account->id.'.credit') }}"
                                                       @input="recompute()"
                                                       class="js-credit w-full rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                       placeholder="0.00">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </section>
                    @endforeach

                    {{-- Sticky totals + submit --}}
                    <div class="sticky bottom-0 mt-6 rounded-xl border border-slate-200 bg-white/95 px-5 py-3 shadow-lg backdrop-blur">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                                <span class="text-slate-500">{{ __('app.accounting.total_debit') }} <span class="ml-1 font-semibold tabular-nums text-slate-900" x-text="money(debit)"></span></span>
                                <span class="text-slate-500">{{ __('app.accounting.total_credit') }} <span class="ml-1 font-semibold tabular-nums text-slate-900" x-text="money(credit)"></span></span>
                                <span class="text-slate-500">{{ __('app.accounting.difference') }}
                                    <span class="ml-1 font-semibold tabular-nums" :class="balanced ? 'text-emerald-600' : 'text-rose-600'" x-text="money(diff)"></span>
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span x-show="!balanced" x-cloak class="text-xs text-slate-400">{{ __('app.accounting.opening_balanced_hint') }}</span>
                                <button type="submit" :disabled="submitting || !balanced"
                                        class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                                    <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                                    {{ __('app.accounting.opening_post') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        function openingForm() {
            return {
                submitting: false,
                debit: 0,
                credit: 0,
                get diff() {
                    return Math.round((this.debit - this.credit) * 100) / 100;
                },
                get balanced() {
                    return this.debit > 0 && Math.abs(this.diff) < 0.005;
                },
                recompute() {
                    const sum = (sel) => Array.from(this.$root.querySelectorAll(sel))
                        .reduce((t, el) => t + (parseFloat(el.value) || 0), 0);
                    this.debit = Math.round(sum('.js-debit') * 100) / 100;
                    this.credit = Math.round(sum('.js-credit') * 100) / 100;
                },
                money(n) {
                    return (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
            };
        }
    </script>
</x-app-layout>
