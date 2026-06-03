<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.heading') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.subheading') }}</p>
            </div>
            @if ($workspace && $isSetUp)
                <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                    <a href="{{ route('accounting.reports') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.reports') }}
                    </a>
                    <a href="{{ route('accounting.partners.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.partners') }}
                    </a>
                    <a href="{{ route('accounting.accounts.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.accounts') }}
                    </a>
                    <a href="{{ route('accounting.bills.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.bills') }}
                    </a>
                    <a href="{{ route('accounting.manual-journals.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.manual_journals') }}
                    </a>
                    <a href="{{ route('accounting.wht-certificates.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.wht_certificates') }}
                    </a>
                    <a href="{{ route('accounting.invoices.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('app.accounting.invoice_new') }}
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (! $workspace)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('app.workspaces.no_workspace') }}</div>
            @elseif (! $isSetUp)
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('app.accounting.setup_title') }}</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('app.accounting.setup_desc') }}</p>
                    <a href="{{ route('accounting.onboarding') }}" class="mt-6 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        {{ __('app.accounting.setup_cta') }}
                    </a>
                </div>
            @else
                @php $baht = fn ($v) => ((float) $v < 0 ? '-฿' : '฿').number_format(abs((float) $v), 2); @endphp

                {{-- KPI row: cash, who owes us, who we owe, this month's profit --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_cash') }}</div>
                        <div class="mt-2 text-2xl font-bold text-brand-700 tabular-nums">{{ $baht($finance['cash']) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_outstanding') }}</div>
                        <div class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">{{ $baht($finance['ar']) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_payable') }}</div>
                        <div class="mt-2 text-2xl font-bold text-amber-700 tabular-nums">{{ $baht($finance['ap']) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_profit_month') }}</div>
                        <div class="mt-2 text-2xl font-bold tabular-nums {{ (float) $finance['net_profit'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $baht($finance['net_profit']) }}</div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    {{-- Taxes building up for this month's filing --}}
                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.taxes_due') }} <span class="font-normal text-slate-400">· {{ $finance['month_label'] }}</span></h3>
                            <a href="{{ route('accounting.reports.tax') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.accounting.tax_reports') }} →</a>
                        </div>
                        <dl class="divide-y divide-slate-100 px-6 text-sm">
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">{{ __('app.accounting.vat_net') }}<span class="ml-1 text-xs text-slate-400">({{ __('app.accounting.vat_net_hint') }})</span></dt>
                                <dd class="font-semibold tabular-nums {{ (float) $finance['vat_net'] < 0 ? 'text-emerald-700' : 'text-slate-900' }}">{{ $baht($finance['vat_net']) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">{{ __('app.accounting.wht_pnd3') }}</dt>
                                <dd class="font-semibold tabular-nums text-slate-900">{{ $baht($finance['wht_pnd3']) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">{{ __('app.accounting.wht_pnd53') }}</dt>
                                <dd class="font-semibold tabular-nums text-slate-900">{{ $baht($finance['wht_pnd53']) }}</dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Documents still waiting to be dealt with --}}
                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.action_items') }}</h3>
                        </div>
                        @php $hasTodos = $finance['overdue_count'] || $finance['draft_invoices'] || $finance['draft_bills'] || ($canPost && $pendingApprovalsCount); @endphp
                        @if (! $hasTodos)
                            <div class="flex items-center gap-2 px-6 py-8 text-sm text-slate-500">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-500"><path d="M20 6 9 17l-5-5"/></svg>
                                {{ __('app.accounting.all_clear') }}
                            </div>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @if ($finance['overdue_count'])
                                    <li>
                                        <a href="{{ route('accounting.invoices.index') }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                                            <span class="text-sm text-slate-700">{{ __('app.accounting.overdue_invoices_n') }}</span>
                                            <span class="flex items-center gap-2">
                                                <span class="text-sm font-semibold tabular-nums text-rose-600">{{ $baht($finance['overdue_amount']) }}</span>
                                                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-100 px-1.5 text-xs font-semibold text-rose-700">{{ $finance['overdue_count'] }}</span>
                                            </span>
                                        </a>
                                    </li>
                                @endif
                                @if ($finance['draft_invoices'])
                                    <li>
                                        <a href="{{ route('accounting.invoices.index') }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                                            <span class="text-sm text-slate-700">{{ __('app.accounting.draft_invoices_n') }}</span>
                                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-semibold text-slate-600">{{ $finance['draft_invoices'] }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($finance['draft_bills'])
                                    <li>
                                        <a href="{{ route('accounting.bills.index') }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                                            <span class="text-sm text-slate-700">{{ __('app.accounting.draft_bills_n') }}</span>
                                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-semibold text-slate-600">{{ $finance['draft_bills'] }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($canPost && $pendingApprovalsCount)
                                    <li>
                                        <a href="{{ route('accounting.approvals.index') }}" class="flex items-center justify-between gap-3 px-6 py-3 hover:bg-slate-50">
                                            <span class="text-sm text-slate-700">{{ __('app.accounting.approval_pending_count') }}</span>
                                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-semibold text-amber-700">{{ $pendingApprovalsCount }}</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </section>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.recent_invoices') }}</h3>
                        <a href="{{ route('accounting.invoices.index') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.accounting.view_all') }} →</a>
                    </div>
                    @if ($recent->isEmpty())
                        <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.accounting.invoices_empty') }}</div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recent as $inv)
                                @php $badge = __('app.accounting.status_'.$inv->status); @endphp
                                <li class="flex items-center justify-between gap-3 px-6 py-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('accounting.invoices.show', $inv) }}" class="font-mono text-sm font-medium text-slate-900 hover:text-brand-700">{{ $inv->no }}</a>
                                        <div class="truncate text-xs text-slate-500">{{ $inv->partner?->name }} · {{ $inv->issue_date?->format('d/m/Y') }}</div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-sm font-semibold text-slate-900 tabular-nums">฿{{ number_format((float) $inv->total, 2) }}</div>
                                        <div class="text-xs text-slate-400">{{ $badge }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
