<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('accounting.invoices.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.invoices') }}</a>
                <h2 class="font-mono text-xl font-semibold leading-tight text-gray-800">{{ $invoice->no }}</h2>
            </div>
            @php
                $badge = [
                    'draft' => 'bg-slate-100 text-slate-600 ring-slate-200',
                    'issued' => 'bg-sky-50 text-sky-700 ring-sky-200',
                    'partial' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'void' => 'bg-rose-50 text-rose-600 ring-rose-200',
                ][$invoice->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
            @endphp
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" rel="noopener"
                   class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">{{ __('app.accounting.print') }} ↗</a>
                <span class="rounded-full px-3 py-1 text-sm ring-1 ring-inset {{ $badge }}">{{ __('app.accounting.status_'.$invoice->status) }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-slate-400">{{ __('app.accounting.col_partner') }}</div>
                        <div class="mt-0.5 font-medium text-slate-900">{{ $invoice->partner?->name }}</div>
                        @if ($invoice->partner?->tax_id)<div class="font-mono text-xs text-slate-500">{{ $invoice->partner->tax_id }}</div>@endif
                    </div>
                    <div class="sm:text-right">
                        <div class="text-xs uppercase tracking-wider text-slate-400">{{ __('app.accounting.issue_date') }} · {{ __('app.accounting.due_date') }}</div>
                        <div class="mt-0.5 text-sm text-slate-700">{{ $invoice->issue_date?->format('d/m/Y') }} · {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}</div>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="py-2 pr-3 font-medium">{{ __('app.accounting.line_desc') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.accounting.line_qty') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.accounting.line_price') }}</th>
                                <th class="py-2 pl-3 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoice->lines as $line)
                                <tr>
                                    <td class="py-2.5 pr-3">
                                        <div class="text-slate-800">{{ $line->description ?: $line->account?->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $line->account?->code }} {{ $line->account?->name }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ rtrim(rtrim(number_format((float) $line->quantity, 4), '0'), '.') }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ number_format((float) $line->unit_price, 2) }}</td>
                                    <td class="py-2.5 pl-3 text-right font-medium tabular-nums text-slate-900">{{ number_format((float) $line->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 ml-auto max-w-xs space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.subtotal') }}</span><span class="tabular-nums text-slate-800">฿{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.vat') }}</span><span class="tabular-nums text-slate-800">฿{{ number_format((float) $invoice->vat_amount, 2) }}</span></div>
                    <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-semibold"><span class="text-slate-900">{{ __('app.accounting.total') }}</span><span class="tabular-nums text-slate-900">฿{{ number_format((float) $invoice->total, 2) }}</span></div>
                    @if ((float) $invoice->wht_amount > 0)
                        <div class="flex justify-between text-xs"><span class="text-slate-400">{{ __('app.accounting.wht_expected') }}</span><span class="tabular-nums text-slate-500">฿{{ number_format((float) $invoice->wht_amount, 2) }}</span></div>
                    @endif
                    @if (in_array($invoice->status, ['issued', 'partial'], true))
                        <div class="flex justify-between border-t border-slate-200 pt-1.5 font-semibold text-amber-700"><span>{{ __('app.accounting.outstanding') }}</span><span class="tabular-nums">฿{{ number_format((float) $outstanding, 2) }}</span></div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            @if ($invoice->status === 'draft')
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.issue_heading') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.accounting.issue_desc') }}</p>
                    @if ($canPost)
                        <form method="POST" action="{{ route('accounting.invoices.issue', $invoice) }}" class="mt-4"
                              data-confirm="{{ __('app.accounting.issue_confirm') }}" data-confirm-yes="{{ __('app.accounting.issue_cta') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.issue_cta') }}</button>
                        </form>
                    @else
                        <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">{{ __('app.accounting.awaiting_poster') }}</p>
                    @endif
                </div>
            @elseif (in_array($invoice->status, ['issued', 'partial'], true) && $canPost)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.record_payment') }}</h3>
                    <form method="POST" action="{{ route('accounting.invoices.receipts.store', $invoice) }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="account_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.bank_account') }} <span class="text-rose-500">*</span></label>
                                <select id="account_id" name="account_id" required class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach ($bankAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="payment_date" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.payment_date') }} <span class="text-rose-500">*</span></label>
                                <input id="payment_date" name="payment_date" type="date" required value="{{ now()->toDateString() }}"
                                       class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.amount_received') }} <span class="text-rose-500">*</span></label>
                                <input id="amount" name="amount" type="number" step="0.01" min="0" required value="{{ $cashDefault }}"
                                       class="mt-1.5 block w-full rounded-md border-slate-300 text-right tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label for="wht_amount" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.wht_withheld') }}</label>
                                <input id="wht_amount" name="wht_amount" type="number" step="0.01" min="0" value="{{ $whtDefault }}"
                                       class="mt-1.5 block w-full rounded-md border-slate-300 text-right tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>
                        <div>
                            <label for="slip_ref" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.slip_ref') }}</label>
                            <input id="slip_ref" name="slip_ref" type="text" maxlength="120" value="{{ old('slip_ref') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <p class="mt-1 text-xs text-slate-500">{{ __('app.accounting.receipt_hint') }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                                {{ __('app.accounting.record_payment') }}
                            </button>
                        </div>
                    </form>
                </div>
            @elseif ($invoice->status === 'paid')
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">{{ __('app.accounting.paid_full') }}</div>
            @elseif (in_array($invoice->status, ['issued', 'partial'], true) && ! $canPost)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('app.accounting.awaiting_poster') }}</div>
            @endif

            {{-- The journal this invoice posted to the ledger --}}
            @if ($invoice->journal)
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.gl_entry') }} · <span class="font-mono text-slate-500">{{ $invoice->journal->no }}</span></h3>
                    </div>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoice->journal->lines as $line)
                                <tr>
                                    <td class="px-6 py-2.5 text-slate-700">
                                        <span class="font-mono text-xs text-slate-400">{{ $line->account?->code }}</span> {{ $line->account?->name }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-700">{{ (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '' }}</td>
                                    <td class="px-6 py-2.5 text-right tabular-nums text-slate-700">{{ (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
