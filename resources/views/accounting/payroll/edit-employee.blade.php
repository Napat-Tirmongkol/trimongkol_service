<x-accounting-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.payroll.employees') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.employees') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.employee_edit') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ $employee->code }} · {{ $employee->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.payroll.employees.update', $employee) }}"
                  class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                @csrf @method('PATCH')

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $employee->name) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                           placeholder="name@example.com"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.accounting.employee_email_hint') }}</p>
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_salary') }}</label>
                    <input type="number" name="base_salary" step="0.01" min="0" value="{{ old('base_salary', $employee->base_salary) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_position') }}</label>
                    <input type="text" name="position" value="{{ old('position', $employee->position) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_tax_id') }}</label>
                    <input type="text" name="tax_id" value="{{ old('tax_id', $employee->tax_id) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_ss_id') }}</label>
                    <input type="text" name="social_security_id" value="{{ old('social_security_id', $employee->social_security_id) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_bank') }}</label>
                    <input type="text" name="bank_account" value="{{ old('bank_account', $employee->bank_account) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_hired_on') }}</label>
                    <input type="date" name="hired_on" value="{{ old('hired_on', $employee->hired_on?->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="sm:col-span-2 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('accounting.payroll.employees') }}"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('app.accounting.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.accounting.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-accounting-layout>
