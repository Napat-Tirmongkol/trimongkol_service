<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.reports') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.reports') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.aged_ar') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.aged_ar_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('accounting.reports.aged-ar') }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
                <div>
                    <label for="as_of" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.as_of') }}</label>
                    <input id="as_of" name="as_of" type="date" value="{{ $asOf }}" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.apply') }}</button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm text-slate-600 shadow-sm">
                {{ __('app.accounting.aged_as_of', ['date' => \Carbon\Carbon::parse($report['as_of'])->format('d/m/Y')]) }}
            </div>

            @include('accounting.reports._aging-table', [
                'report' => $report,
                'totalLabel' => __('app.accounting.aged_ar_total'),
            ])
        </div>
    </div>
</x-app-layout>
