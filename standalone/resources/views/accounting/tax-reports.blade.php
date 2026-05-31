<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('accounting.reports') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.financial_reports') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.tax_reports') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.tax_reports_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('accounting.reports.tax') }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
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

    @php $money = fn ($v) => number_format((float) $v, 2); @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-3 shadow-sm">
                <div class="text-sm text-slate-600">{{ __('app.accounting.export_desc') }}</div>
                <a href="{{ route('accounting.reports.export', ['from' => $from, 'to' => $to]) }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('app.accounting.export_journal') }}
                </a>
            </div>

            {{-- VAT sales / purchases --}}
            @foreach ([['t' => 'vat_sales', 'd' => $vatSales], ['t' => 'vat_purchases', 'd' => $vatPurchases]] as $report)
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.'.$report['t']) }}</h3>
                    </div>
                    @if (count($report['d']['rows']) === 0)
                        <div class="px-6 py-6 text-center text-sm text-slate-500">{{ __('app.accounting.no_data') }}</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                        <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_date') }}</th>
                                        <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_no') }}</th>
                                        <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_partner') }}</th>
                                        <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_tax_id') }}</th>
                                        <th class="px-6 py-2 text-right font-medium">{{ __('app.accounting.col_net') }}</th>
                                        <th class="px-6 py-2 text-right font-medium">{{ __('app.accounting.col_vat') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($report['d']['rows'] as $row)
                                        <tr>
                                            <td class="px-6 py-2 text-slate-600">{{ $row['date']?->format('d/m/Y') }}</td>
                                            <td class="px-6 py-2 font-mono text-xs text-slate-700">{{ $row['tax_no'] ?: $row['no'] }}</td>
                                            <td class="px-6 py-2 text-slate-700">{{ $row['partner'] }}</td>
                                            <td class="px-6 py-2 font-mono text-xs text-slate-500">{{ $row['tax_id'] ?: '—' }}</td>
                                            <td class="px-6 py-2 text-right tabular-nums text-slate-700">{{ $money($row['base']) }}</td>
                                            <td class="px-6 py-2 text-right tabular-nums text-slate-700">{{ $money($row['vat']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-slate-200 font-bold text-slate-900">
                                        <td class="px-6 py-2.5" colspan="4">{{ __('app.accounting.report_total') }}</td>
                                        <td class="px-6 py-2.5 text-right tabular-nums">{{ $money($report['d']['base_total']) }}</td>
                                        <td class="px-6 py-2.5 text-right tabular-nums">{{ $money($report['d']['vat_total']) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </section>
            @endforeach

            {{-- WHT summary --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.wht_report') }}</h3>
                    <div class="text-xs text-slate-500">ภ.ง.ด.3 {{ $money($wht['pnd3_total']) }} · ภ.ง.ด.53 {{ $money($wht['pnd53_total']) }}</div>
                </div>
                @if ($wht['rows']->isEmpty())
                    <div class="px-6 py-6 text-center text-sm text-slate-500">{{ __('app.accounting.no_data') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_paid_on') }}</th>
                                    <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_payee') }}</th>
                                    <th class="px-6 py-2 font-medium">{{ __('app.accounting.col_pnd') }}</th>
                                    <th class="px-6 py-2 text-right font-medium">{{ __('app.accounting.col_base') }}</th>
                                    <th class="px-6 py-2 text-right font-medium">{{ __('app.accounting.col_wht') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($wht['rows'] as $cert)
                                    <tr>
                                        <td class="px-6 py-2 text-slate-600">{{ $cert->paid_on?->format('d/m/Y') }}</td>
                                        <td class="px-6 py-2 text-slate-700">{{ $cert->partner?->name }}</td>
                                        <td class="px-6 py-2 text-xs text-slate-600">{{ $cert->pnd_type === 'PND3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53' }}</td>
                                        <td class="px-6 py-2 text-right tabular-nums text-slate-700">{{ $money($cert->base_amount) }}</td>
                                        <td class="px-6 py-2 text-right tabular-nums text-slate-700">{{ $money($cert->wht_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-200 font-bold text-slate-900">
                                    <td class="px-6 py-2.5" colspan="3">{{ __('app.accounting.report_total') }}</td>
                                    <td class="px-6 py-2.5 text-right tabular-nums">{{ $money($wht['base_total']) }}</td>
                                    <td class="px-6 py-2.5 text-right tabular-nums">{{ $money($wht['wht_total']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
