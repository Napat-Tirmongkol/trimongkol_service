<x-app-layout>
    @php
        $money = fn ($v) => number_format((float) $v, 2);
        $monthLabel = function ($m) {
            return $m
                ? \Illuminate\Support\Carbon::create(null, (int) $m, 1)?->locale(app()->getLocale())->translatedFormat('F')
                : __('app.accounting.fiscal_annual');
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.budgets') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.budgets_sub') }}</p>
            </div>
            <div class="flex flex-wrap items-end gap-2 sm:shrink-0">
                <form method="GET" action="{{ route('accounting.budgets.index') }}" class="flex items-end gap-2">
                    <div>
                        <label for="year" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.fiscal_year') }}</label>
                        <select id="year" name="year" onchange="this.form.submit()" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($years as $y)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <a href="{{ route('accounting.budgets.create') }}?year={{ $year }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('app.accounting.budget_new') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if ($budgets->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.accounting.budgets_empty') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_account') }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('app.accounting.department') }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('app.accounting.fiscal_period') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.budget_amount') }}</th>
                                    <th class="px-6 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($budgets as $b)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3">
                                            <span class="font-mono text-xs text-slate-400">{{ $b->account?->code }}</span>
                                            <span class="text-slate-900">{{ $b->account?->name }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-slate-600">
                                            @if ($b->department)
                                                <span class="font-mono text-xs text-slate-400">{{ $b->department->code }}</span> {{ $b->department->name }}
                                            @else
                                                <span class="italic text-slate-400">{{ __('app.accounting.all_departments') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-sm text-slate-700">{{ $monthLabel($b->fiscal_month) }}</td>
                                        <td class="px-3 py-3 text-right font-medium tabular-nums text-slate-900">฿{{ $money($b->amount) }}</td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="inline-flex items-center gap-3">
                                                <a href="{{ route('accounting.budgets.edit', $b) }}" class="text-xs text-slate-500 hover:text-slate-800">{{ __('app.common.edit') }}</a>
                                                <form method="POST" action="{{ route('accounting.budgets.destroy', $b) }}"
                                                      data-confirm="{{ __('app.accounting.budget_delete_confirm') }}" data-confirm-danger="1">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-rose-500 hover:text-rose-700">{{ __('app.common.delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
