<x-app-layout>
    @php
        $money = fn ($v) => number_format((float) $v, 2);
        $isVendor = $report['role'] === 'vendor';
        $docLabel = $isVendor ? __('app.accounting.col_bill_no') : __('app.accounting.col_invoice_no');
        $statusBadge = [
            'issued'  => 'bg-sky-50 text-sky-700 ring-sky-200',
            'partial' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'paid'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
        $detailRoute = $isVendor ? 'accounting.bills.show' : 'accounting.invoices.show';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.partners.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.partners') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.partner_statement') }}</h2>
                <p class="mt-0.5 text-sm text-slate-700">{{ $partner->name }}
                    @if ($partner->tax_id)<span class="font-mono text-xs text-slate-400">· {{ $partner->tax_id }}</span>@endif
                </p>
            </div>
            <form method="GET" action="{{ route('accounting.reports.partner-statement', $partner) }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
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

            {{-- Summary --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.col_total') }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">฿{{ $money($report['total']) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.paid') }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums text-emerald-700">฿{{ $money($report['paid']) }}</div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-5">
                    <div class="text-xs uppercase tracking-wider text-amber-700">{{ __('app.accounting.outstanding') }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums text-amber-800">฿{{ $money($report['outstanding']) }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if (empty($report['entries']))
                    <div class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.accounting.no_data_in_period') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_date') }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ $docLabel }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('app.accounting.col_ref') }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('app.accounting.due_date') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.paid') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.outstanding') }}</th>
                                    <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($report['entries'] as $entry)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3 text-slate-600">{{ $entry['date']?->format('d/m/Y') }}</td>
                                        <td class="px-3 py-3 font-mono text-xs text-slate-900">{{ $entry['no'] }}</td>
                                        <td class="px-3 py-3 text-xs text-slate-500">{{ $entry['ref'] ?: '—' }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $entry['due_date']?->format('d/m/Y') ?: '—' }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-slate-900">{{ $money($entry['total']) }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-emerald-700">{{ $money($entry['paid']) }}</td>
                                        <td class="px-3 py-3 text-right font-semibold tabular-nums {{ (float) $entry['outstanding'] > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                            {{ (float) $entry['outstanding'] > 0 ? $money($entry['outstanding']) : '—' }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="rounded-full px-2 py-0.5 text-xs ring-1 ring-inset {{ $statusBadge[$entry['status']] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                                {{ __('app.accounting.status_'.$entry['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr class="text-sm font-semibold">
                                    <td class="px-6 py-3 text-slate-900" colspan="4">{{ __('app.accounting.col_total') }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-900">฿{{ $money($report['total']) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-emerald-700">฿{{ $money($report['paid']) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-amber-700">฿{{ $money($report['outstanding']) }}</td>
                                    <td class="px-6 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
