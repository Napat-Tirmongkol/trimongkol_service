<x-app-layout>
    @php
        $money = fn ($v) => number_format((float) $v, 2);

        // Variance colour depends on account type. For revenue, positive
        // variance (actual > budget) is favourable. For expense, negative
        // variance (actual < budget) is favourable. Profit follows income.
        $varianceClass = function ($type, $variance) {
            $v = (float) $variance;
            if ($v == 0) return 'text-slate-500';
            $favorable = $type === 'expense' ? $v < 0 : $v > 0;
            return $favorable ? 'text-emerald-700' : 'text-rose-700';
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.reports') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.reports') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.budget_vs_actual') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.budget_vs_actual_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('accounting.reports.budget-vs-actual') }}" class="flex flex-wrap items-end gap-2 sm:shrink-0">
                <div>
                    <label for="year" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.fiscal_year') }}</label>
                    <input id="year" name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-1 w-24 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label for="month" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.fiscal_month') }}</label>
                    <select id="month" name="month" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('app.accounting.fiscal_annual') }}</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month === $m)>
                                {{ \Illuminate\Support\Carbon::create(null, $m, 1)?->locale(app()->getLocale())->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="department" class="block text-xs font-medium text-slate-500">{{ __('app.accounting.department') }}</label>
                    <select id="department" name="department" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="all" @selected($departmentSelection === 'all')>{{ __('app.accounting.all_departments') }}</option>
                        <option value="unassigned" @selected($departmentSelection === 'unassigned')>{{ __('app.accounting.no_department') }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected($departmentSelection == (string) $dept->id)>
                                {{ $dept->code }} {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.apply') }}</button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">

            {{-- Summary cards --}}
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['income' => __('app.accounting.income_total'), 'expense' => __('app.accounting.expense_total'), 'profit' => __('app.accounting.profit')] as $type => $label)
                    @php
                        $row = $report['totals'][$type];
                        $vClass = $varianceClass($type, $row['variance']);
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ $label }}</div>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <div class="text-xs text-slate-400">{{ __('app.accounting.budget') }}</div>
                                <div class="font-semibold tabular-nums text-slate-700">{{ $money($row['budget']) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">{{ __('app.accounting.actual') }}</div>
                                <div class="font-semibold tabular-nums text-slate-900">{{ $money($row['actual']) }}</div>
                            </div>
                        </div>
                        <div class="mt-2 border-t border-slate-100 pt-2">
                            <div class="text-xs text-slate-400">{{ __('app.accounting.variance') }}</div>
                            <div class="font-bold tabular-nums {{ $vClass }}">{{ $money($row['variance']) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if (empty($report['rows']))
                    <div class="px-6 py-12 text-center text-sm text-slate-500">{{ __('app.accounting.budget_vs_actual_empty') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_account') }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('app.accounting.department') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.budget') }}</th>
                                    <th class="px-3 py-2.5 text-right font-medium">{{ __('app.accounting.actual') }}</th>
                                    <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.variance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @php $currentType = null; @endphp
                                @foreach ($report['rows'] as $row)
                                    @if ($currentType !== $row['account']->type)
                                        @php $currentType = $row['account']->type; @endphp
                                        <tr class="bg-slate-50/50">
                                            <td colspan="5" class="px-6 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                                {{ $currentType === 'income' ? __('app.accounting.revenue') : __('app.accounting.expenses') }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3">
                                            <span class="font-mono text-xs text-slate-400">{{ $row['account']->code }}</span>
                                            <span class="text-slate-900">{{ $row['account']->name }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-xs text-slate-600">
                                            @if ($row['department'])
                                                <span class="font-mono text-slate-400">{{ $row['department']->code }}</span> {{ $row['department']->name }}
                                            @else
                                                <span class="italic text-slate-400">{{ __('app.accounting.no_department') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums text-slate-700">{{ $money($row['budget']) }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-slate-900">{{ $money($row['actual']) }}</td>
                                        <td class="px-6 py-3 text-right font-semibold tabular-nums {{ $varianceClass($row['account']->type, $row['variance']) }}">
                                            {{ $money($row['variance']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50">
                                @php $profitV = $varianceClass('profit', $report['totals']['profit']['variance']); @endphp
                                <tr class="text-sm font-bold">
                                    <td class="px-6 py-3 text-slate-900" colspan="2">{{ __('app.accounting.profit') }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-900">฿{{ $money($report['totals']['profit']['budget']) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-900">฿{{ $money($report['totals']['profit']['actual']) }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums {{ $profitV }}">฿{{ $money($report['totals']['profit']['variance']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
