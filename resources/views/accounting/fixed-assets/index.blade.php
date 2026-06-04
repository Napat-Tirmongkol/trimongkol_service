<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.fixed_assets') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.fixed_assets_sub') }}</p>
            </div>
            <a href="{{ route('accounting.fixed-assets.create') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('app.accounting.asset_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if ($assets->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="text-sm text-slate-500">{{ __('app.accounting.assets_empty') }}</p>
                    <a href="{{ route('accounting.fixed-assets.create') }}"
                       class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        {{ __('app.accounting.asset_new') }}
                    </a>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.asset_code') }}</th>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.asset_name') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('app.accounting.asset_cost') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('app.accounting.asset_accumulated') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('app.accounting.asset_book_value') }}</th>
                                    <th class="px-4 py-2 text-center">{{ __('app.accounting.col_status') }}</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($assets as $asset)
                                    @php
                                        $statusBadge = [
                                            'active'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'disposed' => 'bg-slate-100 text-slate-500 ring-slate-200',
                                        ][$asset->status];
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $asset->code }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">
                                            <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="hover:text-brand-700">{{ $asset->name }}</a>
                                            <div class="text-xs font-normal text-slate-400">{{ $asset->purchase_date->format('d/m/Y') }} · {{ $asset->useful_life_months }} {{ __('app.accounting.months') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums">฿{{ number_format((float) $asset->cost, 2) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-rose-600">฿{{ number_format((float) $asset->accumulated, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums">฿{{ number_format((float) $asset->book_value, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusBadge }}">
                                                {{ __('app.accounting.asset_status_'.$asset->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('accounting.fixed-assets.show', $asset) }}"
                                               class="text-xs font-medium text-brand-700 hover:text-brand-900">{{ __('app.accounting.view') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
