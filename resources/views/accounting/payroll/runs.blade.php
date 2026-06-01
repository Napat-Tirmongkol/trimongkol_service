<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.payroll_runs') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.payroll_runs_sub') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('accounting.payroll.employees') }}"
                   class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    {{ __('app.accounting.employees') }}
                </a>
                <a href="{{ route('accounting.payroll.runs.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('app.accounting.payroll_run_new') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($runs->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="text-sm text-slate-500">{{ __('app.accounting.payroll_runs_empty') }}</p>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.col_no') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.payroll_period') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.payment_date') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('app.accounting.payroll_gross') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('app.accounting.payroll_net') }}</th>
                                <th class="px-4 py-2 text-center">{{ __('app.accounting.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($runs as $r)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 font-mono text-xs">
                                        <a href="{{ route('accounting.payroll.runs.show', $r) }}" class="text-brand-700 hover:text-brand-900">{{ $r->no }}</a>
                                    </td>
                                    <td class="px-4 py-2">{{ $r->period }}</td>
                                    <td class="px-4 py-2 tabular-nums text-slate-600">{{ $r->payment_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">฿{{ number_format((float) $r->total_gross, 2) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-semibold">฿{{ number_format((float) $r->total_net, 2) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @php $badge = $r->isPosted() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'; @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                            {{ __('app.accounting.status_'.$r->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
