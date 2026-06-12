<x-accounting-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.payroll_run') }} {{ $payrollRun->no }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.payroll_period') }}: {{ $payrollRun->period }}</p>
            </div>
            <a href="{{ route('accounting.payroll.runs.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← {{ __('app.accounting.payroll_runs') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Status banner --}}
            @if ($payrollRun->isPosted())
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
                    {{ __('app.accounting.payroll_posted_at', ['at' => $payrollRun->posted_at?->format('d/m/Y H:i')]) }}
                    @if ($payrollRun->journal)
                        — <a href="#" class="font-mono font-semibold">{{ $payrollRun->journal->no }}</a>
                    @endif
                </div>
            @endif

            {{-- Totals --}}
            <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.payroll_gross') }}</div>
                    <div class="mt-1 text-lg font-bold tabular-nums text-slate-800">฿{{ number_format((float) $payrollRun->total_gross, 2) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.payroll_overtime') }} + {{ __('app.accounting.payroll_ot') }}</div>
                    <div class="mt-1 text-lg font-bold tabular-nums text-slate-800">฿{{ number_format((float) $payrollRun->total_overtime + (float) $payrollRun->total_ot, 2) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.payroll_ss') }}</div>
                    <div class="mt-1 text-lg font-bold tabular-nums text-rose-600">฿{{ number_format((float) $payrollRun->total_ss_employee, 2) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.payroll_wht') }}</div>
                    <div class="mt-1 text-lg font-bold tabular-nums text-rose-600">฿{{ number_format((float) $payrollRun->total_wht, 2) }}</div>
                </div>
                <div class="rounded-xl border border-brand-100 bg-brand-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-brand-700">{{ __('app.accounting.payroll_net') }}</div>
                    <div class="mt-1 text-lg font-bold tabular-nums text-brand-800">฿{{ number_format((float) $payrollRun->total_net, 2) }}</div>
                </div>
            </div>

            {{-- Items table --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.payroll_items') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ __('app.accounting.employee_name') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_gross') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_overtime') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_ot') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_ss') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_wht') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_other') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('app.accounting.payroll_net') }}</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($payrollRun->items as $item)
                                <tr class="@if($payrollRun->isPosted()) bg-slate-50/30 @endif">
                                    @if ($payrollRun->isPosted())
                                        <td class="px-3 py-2 font-medium text-slate-900">
                                            <div>{{ $item->employee->name }}</div>
                                            <div class="font-mono text-xs text-slate-400">{{ $item->employee->code }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">฿{{ number_format((float) $item->gross, 2) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">฿{{ number_format((float) $item->overtime, 2) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">฿{{ number_format((float) $item->ot, 2) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums text-rose-600">฿{{ number_format((float) $item->ss_employee, 2) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums text-rose-600">฿{{ number_format((float) $item->wht, 2) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums text-rose-600">฿{{ number_format((float) $item->other_deductions, 2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold tabular-nums">฿{{ number_format((float) $item->net, 2) }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('accounting.payroll.items.payslip', $item) }}" target="_blank"
                                               class="text-xs text-brand-700 hover:text-brand-900">{{ __('app.accounting.payslip') }}</a>
                                        </td>
                                    @else
                                        <form method="POST" action="{{ route('accounting.payroll.items.update', $item) }}" class="contents">
                                            @csrf @method('PATCH')
                                            <td class="px-3 py-2 font-medium text-slate-900">
                                                <div>{{ $item->employee->name }}</div>
                                                <div class="font-mono text-xs text-slate-400">{{ $item->employee->code }}</div>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" name="gross" value="{{ (float) $item->gross }}" step="0.01" min="0"
                                                       class="w-24 rounded border border-slate-300 px-2 py-1 text-right text-sm focus:ring-brand-400">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" name="overtime" value="{{ (float) $item->overtime }}" step="0.01" min="0"
                                                       class="w-20 rounded border border-slate-300 px-2 py-1 text-right text-sm focus:ring-brand-400">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" name="ot" value="{{ (float) $item->ot }}" step="0.01" min="0"
                                                       class="w-20 rounded border border-slate-300 px-2 py-1 text-right text-sm focus:ring-brand-400">
                                            </td>
                                            <td class="px-3 py-2 text-right tabular-nums text-rose-600">฿{{ number_format((float) $item->ss_employee, 2) }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" name="wht" value="{{ (float) $item->wht }}" step="0.01" min="0"
                                                       class="w-20 rounded border border-slate-300 px-2 py-1 text-right text-sm focus:ring-brand-400">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" name="other_deductions" value="{{ (float) $item->other_deductions }}" step="0.01" min="0"
                                                       class="w-20 rounded border border-slate-300 px-2 py-1 text-right text-sm focus:ring-brand-400">
                                            </td>
                                            <td class="px-3 py-2 text-right font-semibold tabular-nums">฿{{ number_format((float) $item->net, 2) }}</td>
                                            <td class="px-3 py-2">
                                                <button type="submit" class="text-xs text-brand-700 hover:text-brand-900">{{ __('app.accounting.save') }}</button>
                                            </td>
                                        </form>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Post --}}
            @if (! $payrollRun->isPosted())
                <form method="POST" action="{{ route('accounting.payroll.runs.post', $payrollRun) }}"
                      data-confirm="{{ __('app.accounting.payroll_post_confirm') }}"
                      class="flex justify-end">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.accounting.payroll_post_btn') }}
                    </button>
                </form>
            @endif

        </div>
    </div>
</x-accounting-layout>
