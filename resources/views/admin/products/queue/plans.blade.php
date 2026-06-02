<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.queue.label') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.queue.tab_plans') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.products.queue.plans_desc') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('admin.queue.dashboard') }}" class="text-brand-700 hover:text-brand-800">← {{ __('app.admin.products.queue.tab_overview') }}</a>
            </div>

            <form method="POST" action="{{ route('admin.queue.plans.update') }}">
                @csrf

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.queue.plans_limits_heading') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.products.queue.plans_limits_hint') }}</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50">
                                    <th class="w-1/3 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        {{ __('app.admin.products.queue.plans_limit_name') }}
                                    </th>
                                    @foreach ($plans as $key => $plan)
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            {{ $plan['name'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">

                                {{-- ราคา --}}
                                <tr class="bg-amber-50/40 hover:bg-amber-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ __('app.admin.products.queue.plans_price') }}</div>
                                        <div class="text-xs text-slate-400">{{ __('app.admin.products.queue.plans_price_hint') }}</div>
                                    </td>
                                    @foreach ($plans as $key => $plan)
                                        <td class="px-4 py-4 text-center">
                                            @if ($key === 'free')
                                                <span class="text-sm font-semibold text-emerald-600">{{ __('app.admin.products.queue.plans_free_label') }}</span>
                                                <input type="hidden" name="plans[free][price]" value="0">
                                            @else
                                                <div class="relative inline-block">
                                                    <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-slate-400 text-sm">฿</span>
                                                    <input
                                                        type="number"
                                                        name="plans[{{ $key }}][price]"
                                                        value="{{ $plan['price'] ?? '' }}"
                                                        min="1"
                                                        max="999999"
                                                        placeholder="—"
                                                        class="w-28 rounded-md border-slate-300 pl-6 text-center text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                    >
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- ขีดจำกัด --}}
                                @php
                                    $rows = [
                                        'max_queues'          => __('app.admin.products.queue.plans_limit_queues'),
                                        'max_counters'        => __('app.admin.products.queue.plans_limit_counters'),
                                        'max_tickets_per_day' => __('app.admin.products.queue.plans_limit_tickets'),
                                    ];
                                @endphp
                                @foreach ($rows as $limitName => $label)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-slate-900">{{ $label }}</div>
                                            <div class="text-xs text-slate-400">{{ __('app.admin.products.queue.plans_blank_unlimited') }}</div>
                                        </td>
                                        @foreach ($plans as $key => $plan)
                                            <td class="px-4 py-4 text-center">
                                                <input
                                                    type="number"
                                                    name="plans[{{ $key }}][{{ $limitName }}]"
                                                    value="{{ $plan['limits'][$limitName] ?? '' }}"
                                                    min="1"
                                                    max="99999"
                                                    placeholder="{{ __('app.admin.products.queue.plans_unlimited') }}"
                                                    class="w-28 rounded-md border-slate-300 text-center text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                >
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 px-6 py-4">
                        <p class="text-xs text-slate-400">{{ __('app.admin.products.queue.plans_price_note') }}</p>
                        <button type="submit"
                                class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                            {{ __('app.admin.products.queue.plans_save') }}
                        </button>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </form>

        </div>
    </div>
</x-admin-layout>
