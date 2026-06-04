{{-- Sales/Purchase by partner aggregate table.
     Required vars: $report, $partnerLabel, $totalLabel --}}

@php
    $money = fn ($v) => number_format((float) $v, 2);
@endphp

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    @if (empty($report['rows']))
        <div class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.accounting.no_data_in_period') }}</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-2.5 font-medium">{{ $partnerLabel }}</th>
                        <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.col_docs') }}</th>
                        <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.subtotal') }}</th>
                        <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.vat') }}</th>
                        <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                        <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.paid') }}</th>
                        <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.outstanding') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($report['rows'] as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <a href="{{ route('accounting.reports.partner-statement', $row['partner']) }}?from={{ $report['from'] }}&to={{ $report['to'] }}"
                                   class="font-medium text-slate-900 hover:text-brand-700">{{ $row['partner']?->name }}</a>
                                @if ($row['partner']?->tax_id)
                                    <div class="font-mono text-xs text-slate-400">{{ $row['partner']->tax_id }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-600">{{ $row['count'] }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $money($row['subtotal']) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $money($row['vat']) }}</td>
                            <td class="px-3 py-3 text-right font-medium tabular-nums text-slate-900">{{ $money($row['total']) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-emerald-700">{{ $money($row['paid']) }}</td>
                            <td class="px-6 py-3 text-right font-semibold tabular-nums {{ (float) $row['outstanding'] > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                {{ (float) $row['outstanding'] > 0 ? '฿'.$money($row['outstanding']) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr class="text-sm font-semibold">
                        <td class="px-6 py-3 text-slate-900">{{ $totalLabel }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-slate-900">{{ $report['totals']['count'] }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-slate-900">{{ $money($report['totals']['subtotal']) }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-slate-900">{{ $money($report['totals']['vat']) }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-slate-900">฿{{ $money($report['totals']['total']) }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-emerald-700">฿{{ $money($report['totals']['paid']) }}</td>
                        <td class="px-6 py-3 text-right tabular-nums text-amber-700">฿{{ $money($report['totals']['outstanding']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
