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
                    <a href="{{ route('accounting.bills.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('app.accounting.bills') }}
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
                    <form method="POST" action="{{ route('accounting.setup') }}" class="mt-6" data-confirm="{{ __('app.accounting.setup_confirm') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            {{ __('app.accounting.setup_cta') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_outstanding') }}</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900 tabular-nums">฿{{ number_format((float) $stats['outstanding'], 2) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_invoices') }}</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['invoices']) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.accounting.stat_partners') }}</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['partners']) }}</div>
                    </div>
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
