<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.manual-journals.index') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.manual_journals') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.manual_journal_new') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">{{ __('app.accounting.fix_errors') }}</div>
            @endif

            <form method="POST" action="{{ route('accounting.manual-journals.store') }}" x-data="manualJournalForm()" @submit.prevent="submit($el)" class="space-y-6">
                @csrf

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="date" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.col_date') }} <span class="text-rose-500">*</span></label>
                            <input id="date" name="date" type="date" required value="{{ old('date', now()->toDateString()) }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="memo" class="block text-sm font-medium text-slate-700">{{ __('app.accounting.col_memo') }}</label>
                            <input id="memo" name="memo" type="text" maxlength="255" value="{{ old('memo') }}"
                                   placeholder="{{ __('app.accounting.manual_journal_memo_placeholder') }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-2">
                        <template x-for="(line, i) in lines" :key="i">
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-12 sm:col-span-5">
                                    <select x-model="line.account_id" :name="`lines[${i}][account_id]`" required
                                            aria-label="{{ __('app.accounting.col_account') }}"
                                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">— {{ __('app.accounting.col_account') }} —</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <input type="number" step="0.01" min="0" x-model="line.debit" :name="`lines[${i}][debit]`"
                                           placeholder="{{ __('app.accounting.col_debit') }}" aria-label="{{ __('app.accounting.col_debit') }}"
                                           @input="if (parseFloat(line.debit) > 0) line.credit = ''"
                                           class="block w-full rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <input type="number" step="0.01" min="0" x-model="line.credit" :name="`lines[${i}][credit]`"
                                           placeholder="{{ __('app.accounting.col_credit') }}" aria-label="{{ __('app.accounting.col_credit') }}"
                                           @input="if (parseFloat(line.credit) > 0) line.debit = ''"
                                           class="block w-full rounded-md border-slate-300 text-right text-sm tabular-nums shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div class="col-span-12 sm:col-span-1 flex items-center sm:justify-center">
                                    <button type="button" @click="removeLine(i)" x-show="lines.length > 2"
                                            class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="ลบรายการ">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>
                                <div class="col-span-12">
                                    <input type="text" x-model="line.description" :name="`lines[${i}][description]`" maxlength="255"
                                           placeholder="{{ __('app.accounting.line_desc') }}"
                                           class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500">
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
                        <div class="w-full max-w-sm space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.col_debit') }}</span><span class="tabular-nums text-slate-800" x-text="'฿'+money(totalDebit)"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.accounting.col_credit') }}</span><span class="tabular-nums text-slate-800" x-text="'฿'+money(totalCredit)"></span></div>
                            <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-semibold"
                                 :class="isBalanced ? 'text-emerald-700' : 'text-rose-600'">
                                <span x-text="isBalanced ? '{{ __('app.accounting.balanced') }}' : '{{ __('app.accounting.unbalanced') }}'"></span>
                                <span class="tabular-nums" x-text="'฿'+money(Math.abs(totalDebit - totalCredit))"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('accounting.manual-journals.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('app.common.cancel') }}</a>
                    <button type="submit" :disabled="submitting || !isBalanced" class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                        {{ __('app.accounting.post_journal') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function manualJournalForm() {
            return {
                submitting: false,
                lines: [
                    { account_id: '', debit: '', credit: '', description: '' },
                    { account_id: '', debit: '', credit: '', description: '' },
                ],
                addLine() { this.lines.push({ account_id: '', debit: '', credit: '', description: '' }); },
                removeLine(i) { if (this.lines.length > 2) this.lines.splice(i, 1); },
                get totalDebit() { return this.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0); },
                get totalCredit() { return this.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0); },
                get isBalanced() {
                    return this.totalDebit > 0 && Math.round(this.totalDebit * 100) === Math.round(this.totalCredit * 100);
                },
                money(n) { return (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                submit(form) { this.submitting = true; form.submit(); },
            };
        }
    </script>
</x-app-layout>
