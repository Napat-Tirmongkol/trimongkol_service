<x-accounting-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.bank_recon') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.bank_recon_sub') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Filter bar --}}
            <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.bank_account') }}</label>
                    <select name="account_id" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        @foreach ($bankAccounts as $ba)
                            <option value="{{ $ba->id }}" @selected(optional($account)->id === $ba->id)>
                                {{ $ba->code }} {{ $ba->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.period_from') }}</label>
                    <input type="date" name="from" value="{{ $from }}"
                           class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.period_to') }}</label>
                    <input type="date" name="to" value="{{ $to }}"
                           class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <button type="submit"
                        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                    {{ __('app.accounting.apply') }}
                </button>
            </form>

            @if ($summary)
                {{-- Summary cards --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="text-xs uppercase tracking-wider text-emerald-700">{{ __('app.accounting.bank_matched_lines') }}</div>
                        <div class="mt-2 text-2xl font-bold tabular-nums text-emerald-800">{{ $summary['matched'] }}</div>
                        <div class="text-sm text-emerald-600">฿{{ number_format((float) $summary['matched_amount'], 2) }}</div>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <div class="text-xs uppercase tracking-wider text-amber-700">{{ __('app.accounting.bank_unmatched_lines') }}</div>
                        <div class="mt-2 text-2xl font-bold tabular-nums text-amber-800">{{ $summary['unmatched'] }}</div>
                        <div class="text-sm text-amber-600">฿{{ number_format((float) $summary['unmatched_amount'], 2) }}</div>
                    </div>
                </div>

                {{-- Statement lines --}}
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.bank_statement_lines') }}</h3>
                    </div>

                    @if ($summary['statements']->isEmpty())
                        <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.accounting.bank_no_lines') }}</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 text-left">{{ __('app.accounting.col_date') }}</th>
                                        <th class="px-4 py-2 text-left">{{ __('app.accounting.line_desc') }}</th>
                                        <th class="px-4 py-2 text-left">{{ __('app.accounting.bank_ref') }}</th>
                                        <th class="px-4 py-2 text-right">{{ __('app.accounting.col_amount') }}</th>
                                        <th class="px-4 py-2 text-center">{{ __('app.accounting.bank_matched') }}</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($summary['statements'] as $stmt)
                                        <tr class="{{ $stmt->isMatched() ? 'bg-emerald-50/40' : '' }}">
                                            <td class="px-4 py-2 tabular-nums text-slate-600">{{ $stmt->statement_date->format('d/m/Y') }}</td>
                                            <td class="px-4 py-2 text-slate-800">{{ $stmt->description }}</td>
                                            <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $stmt->reference }}</td>
                                            <td class="px-4 py-2 text-right font-semibold tabular-nums {{ (float) $stmt->amount < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                                {{ (float) $stmt->amount < 0 ? '-' : '' }}฿{{ number_format(abs((float) $stmt->amount), 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                @if ($stmt->isMatched())
                                                    <span class="inline-flex items-center gap-1 text-emerald-600">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                        <span class="text-xs font-mono">{{ $stmt->journalLine?->journal?->no }}</span>
                                                    </span>
                                                @else
                                                    {{-- Match dropdown --}}
                                                    @if ($unmatchedLines->isNotEmpty())
                                                        <form method="POST" action="{{ route('accounting.bank-reconciliation.match', $stmt) }}">
                                                            @csrf
                                                            <select name="journal_line_id" onchange="this.form.submit()"
                                                                    class="rounded border border-slate-300 px-2 py-1 text-xs focus:ring-brand-400">
                                                                <option value="">— {{ __('app.accounting.bank_select_line') }} —</option>
                                                                @foreach ($unmatchedLines as $jl)
                                                                    <option value="{{ $jl->id }}">
                                                                        {{ $jl->journal->date->format('d/m') }}
                                                                        {{ $jl->journal->no }}
                                                                        ({{ (float)$jl->debit > 0 ? '+' : '-' }}฿{{ number_format(max((float)$jl->debit,(float)$jl->credit),0) }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    @else
                                                        <span class="text-xs text-slate-400">—</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                @if ($stmt->isMatched())
                                                    <form method="POST" action="{{ route('accounting.bank-reconciliation.unmatch', $stmt) }}">
                                                        @csrf
                                                        <button type="submit" class="text-xs text-slate-400 hover:text-rose-600">{{ __('app.accounting.bank_unmatch') }}</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('accounting.bank-reconciliation.destroy', $stmt) }}"
                                                          data-confirm="{{ __('app.accounting.bank_line_delete_confirm') }}" data-confirm-danger="1">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-xs text-slate-400 hover:text-rose-600">{{ __('app.accounting.delete') }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Add line manually --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.bank_add_line') }}</h3>
                </div>
                <form method="POST" action="{{ route('accounting.bank-reconciliation.lines.store') }}" class="grid gap-4 p-6 sm:grid-cols-5">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ optional($account)->id }}">
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.col_date') }}</label>
                        <input type="date" name="statement_date" value="{{ old('statement_date', now()->toDateString()) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.line_desc') }}</label>
                        <input type="text" name="description" value="{{ old('description') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.col_amount') }} <span class="text-slate-400">(+ รับ / - จ่าย)</span></label>
                        <input type="number" name="amount" value="{{ old('amount') }}" step="0.01"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                            {{ __('app.accounting.add_line') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- CSV import --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.bank_import_csv') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.accounting.bank_import_hint') }}</p>
                </div>
                <form method="POST" action="{{ route('accounting.bank-reconciliation.import') }}" class="space-y-3 p-6">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ optional($account)->id }}">
                    <textarea name="csv" rows="5" placeholder="2569-01-05,ค่าเช่าสำนักงาน,-15000,REF001"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs shadow-sm focus:ring-2 focus:ring-brand-400"></textarea>
                    <button type="submit"
                            class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60">
                        {{ __('app.accounting.bank_import_btn') }}
                    </button>
                </form>
            </section>

        </div>
    </div>
</x-accounting-layout>
