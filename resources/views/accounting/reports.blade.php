<x-accounting-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.reports') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.reports_sub') }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-3 text-xs font-medium">
                    <a href="{{ route('accounting.reports.tax') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.tax_reports') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.reports.aged-ar') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.aged_ar') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.reports.aged-ap') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.aged_ap') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.reports.sales-by-partner') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.sales_by_partner') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.reports.purchases-by-partner') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.purchases_by_partner') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.reports.pnl-by-department') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.pnl_by_department') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.reports.budget-vs-actual') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.budget_vs_actual') }} →</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('accounting.opening-balances.edit') }}" class="text-brand-700 hover:text-brand-800">{{ __('app.accounting.opening_balances') }} →</a>
                </div>
            </div>
            <form method="GET" action="{{ route('accounting.reports') }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
                <div>
                    <label for="from" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.period_from') }}</label>
                    <input id="from" name="from" type="date" value="{{ $from }}" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label for="to" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.period_to') }}</label>
                    <input id="to" name="to" type="date" value="{{ $to }}" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.apply') }}</button>
            </form>
        </div>
    </x-slot>

    @php
        $balancedBadge = fn ($ok) => $ok ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200';
        $money = fn ($v) => number_format((float) $v, 2);
    @endphp

    <div class="py-8">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:px-8 lg:grid-cols-2">

            {{-- Profit & Loss --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.profit_loss') }}</h3>
                    <p class="text-xs text-slate-400">{{ $from }} – {{ $to }}</p>
                </div>
                <div class="px-6 py-4 text-sm">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.accounting.revenue') }}</div>
                    @foreach ($pnl['income'] as $row)
                        <div class="flex justify-between py-0.5"><span class="text-slate-600"><span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</span><span class="tabular-nums text-slate-800">{{ $money($row['amount']) }}</span></div>
                    @endforeach
                    <div class="mt-1 flex justify-between border-t border-slate-100 pt-1 font-medium"><span>{{ __('app.accounting.revenue') }}</span><span class="tabular-nums">{{ $money($pnl['income_total']) }}</span></div>

                    <div class="mt-4 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.accounting.expenses') }}</div>
                    @foreach ($pnl['expense'] as $row)
                        <div class="flex justify-between py-0.5"><span class="text-slate-600"><span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</span><span class="tabular-nums text-slate-800">{{ $money($row['amount']) }}</span></div>
                    @endforeach
                    <div class="mt-1 flex justify-between border-t border-slate-100 pt-1 font-medium"><span>{{ __('app.accounting.expenses') }}</span><span class="tabular-nums">{{ $money($pnl['expense_total']) }}</span></div>

                    <div class="mt-3 flex justify-between border-t-2 border-slate-200 pt-2 text-base font-bold {{ (float) $pnl['net_profit'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                        <span>{{ __('app.accounting.net_profit') }}</span><span class="tabular-nums">{{ $money($pnl['net_profit']) }}</span>
                    </div>
                </div>
            </section>

            {{-- Balance Sheet --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.balance_sheet') }}</h3>
                        <p class="text-xs text-slate-400">{{ __('app.accounting.as_of') }} {{ $to }}</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs ring-1 ring-inset {{ $balancedBadge($balanceSheet['balanced']) }}">{{ $balanceSheet['balanced'] ? __('app.accounting.balanced') : __('app.accounting.not_balanced') }}</span>
                </div>
                <div class="px-6 py-4 text-sm">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.accounting.assets') }}</div>
                    @foreach ($balanceSheet['assets'] as $row)
                        <div class="flex justify-between py-0.5"><span class="text-slate-600"><span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</span><span class="tabular-nums text-slate-800">{{ $money($row['amount']) }}</span></div>
                    @endforeach
                    <div class="mt-1 flex justify-between border-t border-slate-100 pt-1 font-semibold"><span>{{ __('app.accounting.assets') }}</span><span class="tabular-nums">{{ $money($balanceSheet['assets_total']) }}</span></div>

                    <div class="mt-4 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.accounting.liabilities') }}</div>
                    @foreach ($balanceSheet['liabilities'] as $row)
                        <div class="flex justify-between py-0.5"><span class="text-slate-600"><span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</span><span class="tabular-nums text-slate-800">{{ $money($row['amount']) }}</span></div>
                    @endforeach

                    <div class="mt-3 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.accounting.equity') }}</div>
                    @foreach ($balanceSheet['equity'] as $row)
                        <div class="flex justify-between py-0.5"><span class="text-slate-600"><span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</span><span class="tabular-nums text-slate-800">{{ $money($row['amount']) }}</span></div>
                    @endforeach
                    <div class="flex justify-between py-0.5"><span class="text-slate-600">{{ __('app.accounting.net_income_period') }}</span><span class="tabular-nums text-slate-800">{{ $money($balanceSheet['net_income']) }}</span></div>

                    <div class="mt-1 flex justify-between border-t border-slate-100 pt-1 font-semibold"><span>{{ __('app.accounting.liab_equity') }}</span><span class="tabular-nums">{{ $money($balanceSheet['liabilities_and_equity']) }}</span></div>
                </div>
            </section>

            {{-- Trial Balance --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.trial_balance') }}</h3>
                        <p class="text-xs text-slate-400">{{ __('app.accounting.as_of') }} {{ $to }}</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs ring-1 ring-inset {{ $balancedBadge($trialBalance['balanced']) }}">{{ $trialBalance['balanced'] ? __('app.accounting.balanced') : __('app.accounting.not_balanced') }}</span>
                </div>
                @if (empty($trialBalance['rows']))
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.accounting.no_data') }}</div>
                @else
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_account') }}</th>
                                <th class="px-6 py-2 text-right font-medium">{{ __('app.accounting.col_debit') }}</th>
                                <th class="px-6 py-2 text-right font-medium">{{ __('app.accounting.col_credit') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($trialBalance['rows'] as $row)
                                <tr>
                                    <td class="px-6 py-2 text-slate-700"><span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-slate-800">{{ (float) $row['debit'] ? $money($row['debit']) : '' }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-slate-800">{{ (float) $row['credit'] ? $money($row['credit']) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-200 font-bold text-slate-900">
                                <td class="px-6 py-2.5">{{ __('app.accounting.report_total') }}</td>
                                <td class="px-6 py-2.5 text-right tabular-nums">{{ $money($trialBalance['total_debit']) }}</td>
                                <td class="px-6 py-2.5 text-right tabular-nums">{{ $money($trialBalance['total_credit']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </section>
        </div>
    </div>
</x-accounting-layout>
