<x-accounting-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.accounts') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.accounts_sub') }}</p>
            </div>
            <a href="{{ route('accounting.accounts.create') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:shrink-0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('app.accounting.account_new') }}
            </a>
        </div>
    </x-slot>

    @php
        $typeLabels = [
            'asset' => __('app.accounting.assets'),
            'liability' => __('app.accounting.liabilities'),
            'equity' => __('app.accounting.equity'),
            'income' => __('app.accounting.revenue'),
            'expense' => __('app.accounting.expenses'),
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @foreach ($types as $type)
                @continue (! isset($accountsByType[$type]))
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-2.5">
                        <h3 class="text-sm font-semibold text-slate-800">{{ $typeLabels[$type] }}</h3>
                        <span class="text-xs text-slate-400">{{ $accountsByType[$type]->count() }}</span>
                    </div>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($accountsByType[$type] as $account)
                                <tr class="{{ $account->is_active ? '' : 'bg-slate-50/60' }}">
                                    <td class="w-28 px-5 py-2.5 align-top">
                                        <span class="font-mono text-xs text-slate-500">{{ $account->code }}</span>
                                    </td>
                                    <td class="px-5 py-2.5">
                                        <div class="font-medium text-slate-800">{{ $account->name }}</div>
                                        @if ($account->name_en)<div class="text-xs text-slate-400">{{ $account->name_en }}</div>@endif
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @if ($account->system_role)
                                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 ring-1 ring-brand-200" title="{{ __('app.accounting.account_role_hint') }}">{{ __('app.accounting.account_system') }}</span>
                                            @endif
                                            @unless ($account->is_active)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ __('app.accounting.account_inactive') }}</span>
                                            @endunless
                                            @unless ($account->is_tax_deductible)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200">{{ __('app.accounting.account_non_deductible') }}</span>
                                            @endunless
                                        </div>
                                    </td>
                                    <td class="w-32 px-5 py-2.5 text-right align-top">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('accounting.accounts.edit', $account) }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.common.edit') }}</a>
                                            @unless ($account->system_role)
                                                <form method="POST" action="{{ route('accounting.accounts.destroy', $account) }}"
                                                      data-confirm="{{ __('app.accounting.account_delete_confirm') }}" data-confirm-danger="1">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">{{ __('app.common.delete') }}</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>
    </div>
</x-accounting-layout>
