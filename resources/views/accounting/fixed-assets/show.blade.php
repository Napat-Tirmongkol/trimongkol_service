<x-accounting-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $fixedAsset->name }}</h2>
                <p class="mt-0.5 font-mono text-sm text-slate-500">{{ $fixedAsset->code }}</p>
            </div>
            <a href="{{ route('accounting.fixed-assets.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← {{ __('app.accounting.fixed_assets') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Summary --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.asset_cost') }}</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-slate-800">฿{{ number_format((float) $fixedAsset->cost, 2) }}</div>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50 p-5">
                    <div class="text-xs uppercase tracking-wider text-rose-600">{{ __('app.accounting.asset_accumulated') }}</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-rose-700">฿{{ number_format((float) $accumulated, 2) }}</div>
                </div>
                <div class="rounded-xl border border-brand-100 bg-brand-50 p-5">
                    <div class="text-xs uppercase tracking-wider text-brand-700">{{ __('app.accounting.asset_book_value') }}</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-brand-800">฿{{ number_format((float) $bookValue, 2) }}</div>
                </div>
            </div>

            {{-- Asset details --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.asset_details') }}</h3>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 p-6 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('app.accounting.asset_purchase_date') }}</dt>
                        <dd class="mt-0.5 font-medium">{{ $fixedAsset->purchase_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('app.accounting.asset_life') }}</dt>
                        <dd class="mt-0.5 font-medium">{{ $fixedAsset->useful_life_months }} {{ __('app.accounting.months') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('app.accounting.asset_monthly_dep') }}</dt>
                        <dd class="mt-0.5 font-medium">฿{{ number_format((float) $monthlyAmount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('app.accounting.asset_salvage') }}</dt>
                        <dd class="mt-0.5 font-medium">฿{{ number_format((float) $fixedAsset->salvage_value, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('app.accounting.col_status') }}</dt>
                        <dd class="mt-0.5">
                            @php $badge = $fixedAsset->isActive() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'; @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                {{ __('app.accounting.asset_status_'.$fixedAsset->status) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- Run depreciation --}}
            @if ($fixedAsset->isActive())
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.run_depreciation') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('accounting.fixed-assets.depreciate', $fixedAsset) }}"
                          class="flex items-end gap-3 p-6">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.for_month') }}</label>
                            <input type="month" name="for_month" value="{{ now()->format('Y-m') }}"
                                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                            {{ __('app.accounting.post_depreciation') }}
                        </button>
                    </form>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.asset_dispose') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('accounting.fixed-assets.dispose', $fixedAsset) }}"
                          data-confirm="{{ __('app.accounting.asset_dispose_confirm') }}" data-confirm-danger="1"
                          class="flex items-end gap-3 p-6">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.asset_disposed_on') }}</label>
                            <input type="date" name="disposed_on" value="{{ now()->toDateString() }}"
                                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <button type="submit"
                                class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100 disabled:opacity-60">
                            {{ __('app.accounting.asset_dispose_btn') }}
                        </button>
                    </form>
                </section>
            @endif

            {{-- Depreciation history --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.depreciation_history') }}</h3>
                </div>
                @if ($journals->isEmpty())
                    <div class="px-6 py-6 text-center text-sm text-slate-400">{{ __('app.accounting.no_data') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($journals as $j)
                            <li class="flex items-center justify-between gap-3 px-6 py-3">
                                <div>
                                    <span class="font-mono text-sm text-slate-800">{{ $j->no }}</span>
                                    <span class="ml-2 text-xs text-slate-400">{{ $j->date->format('d/m/Y') }}</span>
                                </div>
                                <span class="text-sm font-semibold tabular-nums text-rose-600">
                                    ฿{{ number_format((float) ($j->lines->where('account_id', $fixedAsset->accumulated_account_id)->first()?->credit ?? 0), 2) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

        </div>
    </div>
</x-accounting-layout>
