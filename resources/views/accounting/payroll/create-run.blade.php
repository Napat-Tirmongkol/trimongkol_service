<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.payroll_run_new') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.payroll.runs.store') }}"
                  class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.payroll_period') }}</label>
                        <input type="month" name="period" value="{{ old('period', now()->format('Y-m')) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.accounting.payment_date') }}</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->endOfMonth()->toDateString()) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <hr class="border-slate-200">

                <div class="space-y-3">
                    <p class="text-sm font-medium text-slate-700">{{ __('app.accounting.payroll_accounts') }}</p>

                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.payroll_salary_expense') }}</label>
                        <select name="salary_expense_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($expenseAccounts as $a)
                                <option value="{{ $a->id }}" @selected(str_contains($a->name, 'เงินเดือน'))>{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.payroll_ss_expense') }}</label>
                        <select name="ss_expense_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($expenseAccounts as $a)
                                <option value="{{ $a->id }}" @selected(str_contains($a->name, 'สวัสดิการ'))>{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.payroll_ss_payable') }}</label>
                        <select name="ss_payable_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($liabilityAccounts as $a)
                                <option value="{{ $a->id }}" @selected(str_contains($a->name, 'ค้างจ่าย'))>{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.payroll_wht_payable') }}</label>
                        <select name="wht_payable_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($liabilityAccounts as $a)
                                <option value="{{ $a->id }}" @selected($a->system_role === 'wht_payable_pnd1' || str_contains($a->name, 'ภงด.1'))>{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600">{{ __('app.accounting.payroll_cash') }}</label>
                        <select name="cash_account_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">—</option>
                            @foreach ($assetAccounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="text-xs text-slate-500">{{ __('app.accounting.payroll_run_hint') }}</p>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.payroll.runs.index') }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('app.accounting.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.accounting.payroll_run_create_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
