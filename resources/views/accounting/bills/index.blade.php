<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.bills') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.bills_sub') }}</p>
            </div>
            @if ($workspace)
                <a href="{{ route('accounting.bills.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:shrink-0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('app.accounting.bill_new') }}
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $badge = [
            'draft' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'issued' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'partial' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'void' => 'bg-rose-50 text-rose-600 ring-rose-200',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (! $workspace)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('app.workspaces.no_workspace') }}</div>
            @elseif ($bills->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('app.accounting.bills_empty') }}</h3>
                    <a href="{{ route('accounting.bills.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        {{ __('app.accounting.bill_new') }}
                    </a>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_no') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.vendor') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_date') }}</th>
                                <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($bills as $bill)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('accounting.bills.show', $bill) }}" class="font-mono text-sm font-medium text-slate-900 hover:text-brand-700">{{ $bill->no }}</a>
                                        @if ($bill->bill_ref)<div class="text-xs text-slate-400">{{ $bill->bill_ref }}</div>@endif
                                    </td>
                                    <td class="px-6 py-3 text-slate-700">{{ $bill->partner?->name }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $bill->issue_date?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3 text-right font-medium tabular-nums text-slate-900">฿{{ number_format((float) $bill->total, 2) }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs ring-1 ring-inset {{ $badge[$bill->status] ?? $badge['draft'] }}">
                                            {{ __('app.accounting.status_'.$bill->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $bills->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
