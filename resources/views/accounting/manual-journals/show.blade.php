<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('accounting.manual-journals.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.manual_journals') }}</a>
                <h2 class="font-mono text-xl font-semibold leading-tight text-gray-800">{{ $journal->no }}</h2>
            </div>
            @php
                $isReversed = $journal->reversedBy->isNotEmpty();
                $isReversal = (bool) $journal->reverses_journal_id;
            @endphp
            <div class="flex shrink-0 items-center gap-2">
                @if ($isReversed)
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-sm text-rose-700 ring-1 ring-inset ring-rose-200">{{ __('app.accounting.status_void') }}</span>
                @elseif ($isReversal)
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('app.accounting.reversal_entry') }}</span>
                @else
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-sm text-sky-700 ring-1 ring-inset ring-sky-200">{{ __('app.accounting.status_issued') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-slate-400">{{ __('app.accounting.col_date') }}</div>
                        <div class="mt-0.5 font-medium text-slate-900">{{ $journal->date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <div class="text-xs uppercase tracking-wider text-slate-400">{{ __('app.accounting.col_memo') }}</div>
                        <div class="mt-0.5 text-slate-700">{{ $journal->memo ?: '—' }}</div>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="py-2 pr-3 font-medium">{{ __('app.accounting.col_account') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.accounting.col_debit') }}</th>
                                <th class="py-2 pl-3 text-right font-medium">{{ __('app.accounting.col_credit') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $totalDebit = 0; $totalCredit = 0; @endphp
                            @foreach ($journal->lines as $line)
                                @php $totalDebit += (float) $line->debit; $totalCredit += (float) $line->credit; @endphp
                                <tr>
                                    <td class="py-2.5 pr-3">
                                        <div class="text-slate-800"><span class="font-mono text-xs text-slate-400">{{ $line->account?->code }}</span> {{ $line->account?->name }}</div>
                                        @if ($line->description)<div class="text-xs text-slate-400">{{ $line->description }}</div>@endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-700">{{ (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '' }}</td>
                                    <td class="py-2.5 pl-3 text-right tabular-nums text-slate-700">{{ (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-200 text-sm font-semibold">
                                <td class="py-2.5 pr-3 text-slate-900">{{ __('app.accounting.col_total') }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-slate-900">฿{{ number_format($totalDebit, 2) }}</td>
                                <td class="py-2.5 pl-3 text-right tabular-nums text-slate-900">฿{{ number_format($totalCredit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($journal->reverses)
                <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700 ring-1 ring-slate-200">
                    {{ __('app.accounting.reverses_journal') }}:
                    <a href="{{ route('accounting.manual-journals.show', $journal->reverses) }}" class="font-mono font-medium text-brand-700 hover:text-brand-900">{{ $journal->reverses->no }}</a>
                </div>
            @endif

            @if ($isReversed)
                <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                    {{ __('app.accounting.reversed_by_journal') }}:
                    @foreach ($journal->reversedBy as $rev)
                        <span class="font-mono font-medium">{{ $rev->no }}</span>@if (! $loop->last), @endif
                    @endforeach
                </div>
            @elseif (! $isReversal && $canPost)
                <form method="POST" action="{{ route('accounting.manual-journals.void', $journal) }}"
                      data-confirm="{{ __('app.accounting.manual_journal_void_confirm') }}" data-confirm-danger="1">
                    @csrf
                    <button type="submit" class="rounded-md border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">{{ __('app.accounting.manual_journal_void') }}</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
