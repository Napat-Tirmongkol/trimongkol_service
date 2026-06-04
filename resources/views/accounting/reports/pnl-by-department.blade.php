<x-accounting-layout>
    @php $money = fn ($v) => number_format((float) $v, 2); @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.reports') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.reports') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.pnl_by_department') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.pnl_by_department_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('accounting.reports.pnl-by-department') }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
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

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if (empty($report['rows']))
                    <div class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.accounting.no_data_in_period') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.department') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.income_total') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.expense_total') }}</th>
                                    <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.profit') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($report['rows'] as $row)
                                    @php $profit = (float) $row['profit']; @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3">
                                            @if ($row['department'])
                                                <span class="font-mono text-xs text-slate-400">{{ $row['department']->code }}</span>
                                                <span class="font-medium text-slate-900">{{ $row['department']->name }}</span>
                                            @else
                                                <span class="italic text-slate-500">{{ __('app.accounting.no_department') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $money($row['revenue']) }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $money($row['expense']) }}</td>
                                        <td class="px-6 py-3 text-right font-semibold tabular-nums {{ $profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                            ฿{{ $money($profit) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50">
                                @php $totalProfit = (float) $report['totals']['profit']; @endphp
                                <tr class="text-sm font-semibold">
                                    <td class="px-6 py-3 text-slate-900">{{ __('app.accounting.col_total') }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-900">฿{{ $money($report['totals']['revenue']) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-900">฿{{ $money($report['totals']['expense']) }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums {{ $totalProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">฿{{ $money($totalProfit) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-accounting-layout>
