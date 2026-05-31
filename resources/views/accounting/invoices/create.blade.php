<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.invoice_new') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                    {{ __('app.accounting.fix_errors') }}
                </div>
            @endif

            <form method="POST" action="{{ route('accounting.invoices.store') }}" x-data="invoiceForm()" class="space-y-6">
                @csrf

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="partner_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.col_partner') }} <span class="text-rose-500">*</span></label>
                            <select id="partner_id" name="partner_id" required class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($partners as $partner)
                                    <option value="{{ $partner->id }}" @selected(old('partner_id') == $partner->id)>{{ $partner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="issue_date" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.issue_date') }} <span class="text-rose-500">*</span></label>
                            <input id="issue_date" name="issue_date" type="date" required value="{{ old('issue_date', now()->toDateString()) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="pb-2 pr-2 font-medium">{{ __('app.accounting.line_desc') }}</th>
                                    <th class="px-2 pb-2 font-medium">{{ __('app.accounting.line_account') }}</th>
                                    <th class="px-2 pb-2 text-right font-medium">{{ __('app.accounting.line_qty') }}</th>
                                    <th class="px-2 pb-2 text-right font-medium">{{ __('app.accounting.line_price') }}</th>
                                    <th class="px-2 pb-2 font-medium">{{ __('app.accounting.line_tax') }}</th>
                                    <th class="px-2 pb-2 text-right font-medium">{{ __('app.accounting.col_total') }}</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, i) in lines" :key="i">
                                    <tr class="align-top">
                                        <td class="py-1.5 pr-2">
                                            <input type="text" x-model="line.description" :name="`lines[${i}][description]`"
                                                   class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <select x-model="line.account_id" :name="`lines[${i}][account_id]`" required
                                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                                <option value="">—</option>
                                                @foreach ($revenueAccounts as $acc)
                                                    <option value="{{ $acc->id }}">{{ $acc->code }} {{ $acc->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <input type="number" step="0.01" min="0" x-model="line.quantity" :name="`lines[${i}][quantity]`" required
                                                   class="block w-20 rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <input type="number" step="0.01" min="0" x-model="line.unit_price" :name="`lines[${i}][unit_price]`" required
                                                   class="block w-28 rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <select x-model="line.tax_code_id" :name="`lines[${i}][tax_code_id]`"
                                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                                <option value="">{{ __('app.accounting.no_tax') }}</option>
                                                @foreach ($vatCodes as $code)
                                                    <option value="{{ $code->id }}">{{ $code->code }} ({{ rtrim(rtrim(number_format((float) $code->rate, 3), '0'), '.') }}%)</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-1.5 text-right align-middle font-medium tabular-nums text-slate-900" x-text="money(lineAmount(line))"></td>
                                        <td class="py-1.5 pl-1 align-middle">
                                            <button type="button" @click="removeLine(i)" x-show="lines.length > 1"
                                                    class="grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="remove">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" @click="addLine()"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-dashed border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('app.accounting.add_line') }}
                    </button>

                    <div class="mt-5 flex flex-col gap-4 border-t border-slate-100 pt-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="sm:max-w-xs">
                            <label for="wht_tax_code_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.wht_code') }}</label>
                            <select id="wht_tax_code_id" name="wht_tax_code_id" x-model="whtCode"
                                    class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">{{ __('app.accounting.no_wht') }}</option>
                                @foreach ($whtCodes as $code)
                                    <option value="{{ $code->id }}">{{ $code->code }} — {{ $code->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">{{ __('app.accounting.wht_hint') }}</p>
                        </div>
                        <div class="ml-auto w-full max-w-xs space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.subtotal') }}</span><span class="tabular-nums text-slate-800" x-text="'฿'+money(subtotal)"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.vat') }}</span><span class="tabular-nums text-slate-800" x-text="'฿'+money(vat)"></span></div>
                            <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-semibold"><span class="text-slate-900">{{ __('app.accounting.total') }}</span><span class="tabular-nums text-slate-900" x-text="'฿'+money(total)"></span></div>
                            <div class="flex justify-between text-xs" x-show="wht > 0"><span class="text-slate-400">{{ __('app.accounting.wht_expected') }}</span><span class="tabular-nums text-slate-500" x-text="'฿'+money(wht)"></span></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('accounting.invoices.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.accounting.save_draft') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function invoiceForm() {
            return {
                lines: [{ description: '', quantity: 1, unit_price: '', account_id: '', tax_code_id: '' }],
                whtCode: '',
                vatRates: @js($vatCodes->pluck('rate', 'id')),
                whtRates: @js($whtCodes->pluck('rate', 'id')),
                addLine() {
                    this.lines.push({ description: '', quantity: 1, unit_price: '', account_id: '', tax_code_id: '' });
                },
                removeLine(i) {
                    if (this.lines.length > 1) this.lines.splice(i, 1);
                },
                lineAmount(line) {
                    const amount = (parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0);
                    return Math.round(amount * 100) / 100;
                },
                get subtotal() {
                    return this.lines.reduce((sum, line) => sum + this.lineAmount(line), 0);
                },
                get vat() {
                    return this.lines.reduce((sum, line) => {
                        const rate = parseFloat(this.vatRates[line.tax_code_id]) || 0;
                        return sum + Math.round(this.lineAmount(line) * rate) / 100;
                    }, 0);
                },
                get wht() {
                    const rate = parseFloat(this.whtRates[this.whtCode]) || 0;
                    return Math.round(this.subtotal * rate) / 100;
                },
                get total() {
                    return this.subtotal + this.vat;
                },
                money(n) {
                    return (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
            };
        }
    </script>
</x-app-layout>
