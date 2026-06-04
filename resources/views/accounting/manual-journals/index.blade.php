<x-accounting-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.manual_journals') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.manual_journals_sub') }}</p>
            </div>
            @if ($workspace)
                <a href="{{ route('accounting.manual-journals.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:shrink-0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('app.accounting.manual_journal_new') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if ($journals->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('app.accounting.manual_journals_empty') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.accounting.manual_journals_sub') }}</p>
                    <a href="{{ route('accounting.manual-journals.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        {{ __('app.accounting.manual_journal_new') }}
                    </a>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_no') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_date') }}</th>
                                <th class="px-6 py-2.5 font-medium">{{ __('app.accounting.col_memo') }}</th>
                                <th class="px-6 py-2.5 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($journals as $j)
                                @php $total = $j->lines->sum(fn ($l) => (float) $l->debit); @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('accounting.manual-journals.show', $j) }}" class="font-mono text-sm font-medium text-slate-900 hover:text-brand-700">{{ $j->no }}</a>
                                    </td>
                                    <td class="px-6 py-3 text-slate-600">{{ $j->date?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $j->memo ?: '—' }}</td>
                                    <td class="px-6 py-3 text-right font-medium tabular-nums text-slate-900">฿{{ number_format($total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $journals->links() }}</div>
            @endif
        </div>
    </div>
</x-accounting-layout>
