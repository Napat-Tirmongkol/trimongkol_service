<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.products') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.products_sub') }}</p>
            </div>
            <a href="{{ route('accounting.products.create') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('app.accounting.product_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-brand-100 bg-brand-50 p-5">
                <div class="text-xs uppercase tracking-wider text-brand-700">{{ __('app.accounting.inventory_total_value') }}</div>
                <div class="mt-2 text-2xl font-bold tabular-nums text-brand-800">฿{{ number_format((float) $totalValue, 2) }}</div>
            </div>

            @if ($products->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="text-sm text-slate-500">{{ __('app.accounting.products_empty') }}</p>
                    <a href="{{ route('accounting.products.create') }}"
                       class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        {{ __('app.accounting.product_new') }}
                    </a>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.product_sku') }}</th>
                                    <th class="px-4 py-2 text-left">{{ __('app.accounting.product_name') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('app.accounting.product_on_hand') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('app.accounting.product_unit_cost') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('app.accounting.product_total_value') }}</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($products as $p)
                                    @php $value = (float) $p->on_hand * (float) $p->unit_cost; @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $p->sku }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">
                                            <a href="{{ route('accounting.products.show', $p) }}" class="hover:text-brand-700">{{ $p->name }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums">
                                            {{ number_format((float) $p->on_hand, 3) }} <span class="text-xs text-slate-400">{{ $p->unit }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-600">฿{{ number_format((float) $p->unit_cost, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums">฿{{ number_format($value, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('accounting.products.show', $p) }}"
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
