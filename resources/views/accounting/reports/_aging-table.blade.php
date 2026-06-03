{{-- Aged AR/AP table partial.
     Required vars: $report, $totalLabel (e.g. "Total outstanding") --}}

@php
    $bucketLabels = [
        'current' => __('app.accounting.aging_current'),
        '1_30'    => __('app.accounting.aging_1_30'),
        '31_60'   => __('app.accounting.aging_31_60'),
        '61_90'   => __('app.accounting.aging_61_90'),
        '90_plus' => __('app.accounting.aging_90_plus'),
    ];
    $bucketColors = [
        'current' => 'text-slate-700',
        '1_30'    => 'text-amber-700',
        '31_60'   => 'text-orange-700',
        '61_90'   => 'text-rose-700',
        '90_plus' => 'text-rose-900 font-semibold',
    ];
    $money = fn ($v) => number_format((float) $v, 2);
@endphp

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    @if (empty($report['rows']))
        <div class="px-6 py-12 text-center text-sm text-slate-500">
            {{ __('app.accounting.aging_empty') }}
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_partner') }}</th>
                        @foreach ($bucketLabels as $key => $label)
                            <th class="px-3 py-2.5 text-right font-medium">{{ $label }}</th>
                        @endforeach
                        <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($report['rows'] as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <div class="font-medium text-slate-900">{{ $row['partner']?->name }}</div>
                                @if ($row['partner']?->tax_id)
                                    <div class="font-mono text-xs text-slate-400">{{ $row['partner']->tax_id }}</div>
                                @endif
                            </td>
                            @foreach ($bucketLabels as $key => $label)
                                @php $amount = (float) $row['amounts'][$key]; @endphp
                                <td class="px-3 py-3 text-right tabular-nums {{ $amount > 0 ? $bucketColors[$key] : 'text-slate-300' }}">
                                    {{ $amount > 0 ? $money($amount) : '—' }}
                                </td>
                            @endforeach
                            <td class="px-6 py-3 text-right font-semibold tabular-nums text-slate-900">
                                ฿{{ $money($row['total']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr class="text-sm font-semibold">
                        <td class="px-6 py-3 text-slate-900">{{ $totalLabel }}</td>
                        @foreach ($bucketLabels as $key => $label)
                            <td class="px-3 py-3 text-right tabular-nums text-slate-900">
                                {{ $money($report['totals'][$key]) }}
                            </td>
                        @endforeach
                        <td class="px-6 py-3 text-right tabular-nums text-slate-900">
                            ฿{{ $money($report['grand_total']) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
