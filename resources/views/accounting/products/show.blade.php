<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $product->name }}</h2>
                <p class="mt-0.5 font-mono text-sm text-slate-500">{{ $product->sku }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('accounting.products.edit', $product) }}" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">{{ __('app.common.edit') }}</a>
                <form method="POST" action="{{ route('accounting.products.destroy', $product) }}"
                      data-confirm="{{ __('app.accounting.product_delete_confirm') }}" data-confirm-danger="1">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">{{ __('app.common.delete') }}</button>
                </form>
                <a href="{{ route('accounting.products.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← {{ __('app.accounting.products') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Summary --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.product_on_hand') }}</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-slate-800">{{ number_format((float) $product->on_hand, 3) }} <span class="text-sm font-normal text-slate-400">{{ $product->unit }}</span></div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.product_unit_cost') }}</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-slate-800">฿{{ number_format((float) $product->unit_cost, 2) }}</div>
                </div>
                <div class="rounded-xl border border-brand-100 bg-brand-50 p-5">
                    <div class="text-xs uppercase tracking-wider text-brand-700">{{ __('app.accounting.product_total_value') }}</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-brand-800">฿{{ number_format((float) $product->on_hand * (float) $product->unit_cost, 2) }}</div>
                </div>
            </div>

            {{-- Stock IN --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.stock_in') }}</h3>
                </div>
                <form method="POST" action="{{ route('accounting.products.receive', $product) }}" class="grid gap-3 p-6 sm:grid-cols-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.col_date') }}</label>
                        <input type="date" name="movement_date" value="{{ now()->toDateString() }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.line_qty') }}</label>
                        <input type="number" name="quantity" step="0.001" min="0.001"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.product_unit_cost') }}</label>
                        <input type="number" name="unit_cost" step="0.01" min="0" value="{{ $product->unit_cost }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.stock_clearing_account') }}</label>
                        <select name="clearing_account_id"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($bankAccounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-5">
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60">
                            {{ __('app.accounting.stock_receive_btn') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Stock OUT --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.stock_out') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.accounting.stock_out_hint') }}</p>
                </div>
                <form method="POST" action="{{ route('accounting.products.issue', $product) }}" class="grid gap-3 p-6 sm:grid-cols-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.col_date') }}</label>
                        <input type="date" name="movement_date" value="{{ now()->toDateString() }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.line_qty') }}</label>
                        <input type="number" name="quantity" step="0.001" min="0.001"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.bank_ref') }}</label>
                        <input type="text" name="reference"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-60">
                            {{ __('app.accounting.stock_issue_btn') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Movements history --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.stock_history') }}</h3>
                </div>
                @if ($product->movements->isEmpty())
                    <div class="px-6 py-6 text-center text-sm text-slate-400">{{ __('app.accounting.no_data') }}</div>
                @else
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.col_date') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.stock_type') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('app.accounting.line_qty') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('app.accounting.product_unit_cost') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('app.accounting.col_amount') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.bank_ref') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($product->movements as $m)
                                @php
                                    $badge = ['in'=>'bg-emerald-50 text-emerald-700','out'=>'bg-rose-50 text-rose-700','adjust'=>'bg-amber-50 text-amber-700'][$m->type] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 tabular-nums text-slate-600">{{ $m->movement_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">
                                            {{ __('app.accounting.stock_type_'.$m->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $m->quantity, 3) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums text-slate-600">฿{{ number_format((float) $m->unit_cost, 2) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-semibold">฿{{ number_format((float) $m->total_value, 2) }}</td>
                                    <td class="px-4 py-2 text-xs text-slate-500">{{ $m->reference }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
