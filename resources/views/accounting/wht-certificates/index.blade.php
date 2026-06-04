<x-accounting-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.wht_certificates') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.wht_certificates_sub') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (! $workspace)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('app.workspaces.no_workspace') }}</div>
            @elseif ($certificates->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-slate-500">
                    {{ __('app.accounting.wht_certificates_empty') }}
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_no') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_payee') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_pnd') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_paid_on') }}</th>
                                <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.col_base') }}</th>
                                <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.col_wht') }}</th>
                                <th class="px-6 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($certificates as $cert)
                                <tr>
                                    <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ $cert->no }}</td>
                                    <td class="px-6 py-3 text-slate-800">{{ $cert->partner?->name }}</td>
                                    <td class="px-6 py-3"><span class="rounded border border-slate-300 px-1.5 py-0.5 text-xs text-slate-600">{{ $cert->pnd_type === 'PND3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53' }}</span></td>
                                    <td class="px-6 py-3 text-slate-600">{{ $cert->paid_on?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums text-slate-600">{{ number_format((float) $cert->base_amount, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-medium tabular-nums text-slate-900">{{ number_format((float) $cert->wht_amount, 2) }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('accounting.wht-certificates.print', $cert) }}" target="_blank" rel="noopener"
                                           class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.accounting.print') }} ↗</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $certificates->links() }}</div>
            @endif
        </div>
    </div>
</x-accounting-layout>
