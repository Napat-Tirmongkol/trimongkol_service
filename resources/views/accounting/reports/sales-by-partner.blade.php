<x-accounting-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.reports') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.reports') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.sales_by_partner') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.sales_by_partner_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('accounting.reports.sales-by-partner') }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
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
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            @include('accounting.reports._by-partner-table', [
                'report' => $report,
                'partnerLabel' => __('app.accounting.col_partner'),
                'totalLabel' => __('app.accounting.sales_total'),
            ])
        </div>
    </div>
</x-accounting-layout>
