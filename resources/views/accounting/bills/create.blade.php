<x-accounting-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.bills.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.bills') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.bill_new') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">{{ __('app.accounting.fix_errors') }}</div>
            @endif

            <form method="POST" action="{{ route('accounting.bills.store') }}" x-data="billForm()" @submit="submitting = true" class="space-y-6">
                @csrf

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="partner_id" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.vendor') }} <span class="text-rose-500">*</span></label>
                            <select id="partner_id" name="partner_id" required class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('partner_id') == $vendor->id)>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="issue_date" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.issue_date') }} <span class="text-rose-500">*</span></label>
                            <input id="issue_date" name="issue_date" type="date" required value="{{ old('issue_date', now()->toDateString()) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="bill_ref" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.bill_ref') }}</label>
                            <input id="bill_ref" name="bill_ref" type="text" maxlength="120" value="{{ old('bill_ref') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-3">
                        <template x-for="(line, i) in lines" :key="i">
                            <div class="rounded-xl border border-slate-200 p-3 sm:p-2.5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <input type="text" x-model="line.description" :name="`lines[${i}][description]`"
                                           placeholder="{{ __('app.accounting.line_desc') }}"
                                           class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:flex-1">
                                    <select x-model="line.account_id" :name="`lines[${i}][account_id]`" required
                                            aria-label="{{ __('app.accounting.line_account') }}"
                                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-44">
                                        <option value="">{{ __('app.accounting.line_expense') }}</option>
                                        @foreach ($expenseAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex gap-2">
                                        <input type="number" step="0.01" min="0" x-model="line.quantity" :name="`lines[${i}][quantity]`" required
                                               placeholder="{{ __('app.accounting.line_qty') }}" aria-label="{{ __('app.accounting.line_qty') }}"
                                               class="block w-20 rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <input type="number" step="0.01" min="0" x-model="line.unit_price" :name="`lines[${i}][unit_price]`" required
                                               placeholder="{{ __('app.accounting.line_price') }}" aria-label="{{ __('app.accounting.line_price') }}"
                                               class="block w-full rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-28">
                                    </div>
                                    <select x-model="line.tax_code_id" :name="`lines[${i}][tax_code_id]`"
                                            aria-label="{{ __('app.accounting.line_tax') }}"
                                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-32">
                                        <option value="">{{ __('app.accounting.no_tax') }}</option>
                                        @foreach ($vatCodes as $code)
                                            <option value="{{ $code->id }}">{{ $code->code }} ({{ rtrim(rtrim(number_format((float) $code->rate, 3), '0'), '.') }}%)</option>
                                        @endforeach
                                    </select>
                                    @if ($departments->isNotEmpty())
                                        <select x-model="line.department_id" :name="`lines[${i}][department_id]`"
                                                aria-label="{{ __('app.accounting.department') }}"
                                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-32">
                                            <option value="">{{ __('app.accounting.no_department') }}</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->code }} {{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-2 sm:justify-end sm:border-0 sm:pt-0">
                                        <span class="text-xs text-slate-400 sm:hidden">{{ __('app.accounting.col_total') }}</span>
                                        <span class="font-medium tabular-nums text-slate-900 sm:w-24 sm:text-right" x-text="money(lineAmount(line))"></span>
                                        <button type="button" @click="removeLine(i)" x-show="lines.length > 1"
                                                class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="ลบรายการ">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addLine()"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-dashed border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('app.accounting.add_line') }}
                    </button>

                    <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                        <div class="w-full max-w-xs space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.subtotal') }}</span><span class="tabular-nums text-slate-800" x-text="'฿'+money(subtotal)"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.vat') }}</span><span class="tabular-nums text-slate-800" x-text="'฿'+money(vat)"></span></div>
                            <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-semibold"><span class="text-slate-900">{{ __('app.accounting.total') }}</span><span class="tabular-nums text-slate-900" x-text="'฿'+money(total)"></span></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('accounting.bills.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                    <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                        {{ __('app.accounting.save_draft') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function billForm() {
            return {
                submitting: false,
                lines: [{ description: '', quantity: 1, unit_price: '', account_id: '', tax_code_id: '', department_id: '' }],
                vatRates: @js($vatCodes->pluck('rate', 'id')),
                addLine() { this.lines.push({ description: '', quantity: 1, unit_price: '', account_id: '', tax_code_id: '', department_id: '' }); },
                removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
                lineAmount(line) {
                    const amount = (parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0);
                    return Math.round(amount * 100) / 100;
                },
                get subtotal() { return this.lines.reduce((s, l) => s + this.lineAmount(l), 0); },
                get vat() {
                    return this.lines.reduce((s, l) => {
                        const rate = parseFloat(this.vatRates[l.tax_code_id]) || 0;
                        return s + Math.round(this.lineAmount(l) * rate) / 100;
                    }, 0);
                },
                get total() { return this.subtotal + this.vat; },
                money(n) { return (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        }
    </script>
</x-accounting-layout>
