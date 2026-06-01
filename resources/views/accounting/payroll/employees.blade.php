<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.employees') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.employees_sub') }}</p>
            </div>
            <a href="{{ route('accounting.payroll.runs.index') }}"
               class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                {{ __('app.accounting.payroll_runs') }} →
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Add employee --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.employee_add') }}</h3>
                </div>
                <form method="POST" action="{{ route('accounting.payroll.employees.store') }}" class="grid gap-4 p-6 sm:grid-cols-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_code') }}</label>
                        <input type="text" name="code" value="{{ old('code') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_salary') }}</label>
                        <input type="number" name="base_salary" step="0.01" min="0" value="{{ old('base_salary') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_tax_id') }}</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_ss_id') }}</label>
                        <input type="text" name="social_security_id" value="{{ old('social_security_id') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_position') }}</label>
                        <input type="text" name="position" value="{{ old('position') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.employee_bank') }}</label>
                        <input type="text" name="bank_account" value="{{ old('bank_account') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="sm:col-span-4">
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                            {{ __('app.accounting.employee_add_btn') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- List --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.employees_list') }}</h3>
                </div>
                @if ($employees->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-400">{{ __('app.accounting.employees_empty') }}</div>
                @else
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.employee_code') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.employee_name') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('app.accounting.employee_position') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('app.accounting.employee_salary') }}</th>
                                <th class="px-4 py-2 text-center">{{ __('app.accounting.col_status') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($employees as $e)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $e->code }}</td>
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $e->name }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $e->position }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">฿{{ number_format((float) $e->base_salary, 2) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @php $badge = $e->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'; @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                            {{ $e->is_active ? __('app.accounting.active') : __('app.accounting.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <form method="POST" action="{{ route('accounting.payroll.employees.toggle', $e) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-slate-500 hover:text-slate-800">
                                                {{ $e->is_active ? __('app.accounting.deactivate') : __('app.accounting.activate') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
