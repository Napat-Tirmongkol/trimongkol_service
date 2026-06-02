<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }}</div>
                <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.accounting.label') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.products.accounting.desc') }}</p>
            </div>
            <a href="{{ route('admin.accounting.users') }}"
               class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                {{ __('app.accounting.accounting_users') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                @foreach ([
                    'stat_accounts' => $stats['accounts'],
                    'stat_workspaces' => $stats['workspaces'],
                    'stat_journals' => $stats['journals'],
                    'stat_posted' => $stats['posted'],
                    'stat_drafts' => $stats['drafts'],
                ] as $key => $value)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.accounting.'.$key) }}</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($value) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.accounting.recent') }}</h3>
                </div>
                @if ($recent->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.accounting.journals_empty') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-2 font-medium">{{ __('app.admin.products.accounting.col_no') }}</th>
                                    <th class="px-6 py-2 font-medium">{{ __('app.admin.products.accounting.col_date') }}</th>
                                    <th class="px-6 py-2 font-medium">{{ __('app.admin.products.accounting.col_memo') }}</th>
                                    <th class="px-6 py-2 text-right font-medium">{{ __('app.admin.products.accounting.col_total') }}</th>
                                    <th class="px-6 py-2 font-medium">{{ __('app.admin.products.accounting.col_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($recent as $journal)
                                    @php
                                        $badge = [
                                            'posted' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'draft' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            'void' => 'bg-slate-100 text-slate-500 ring-slate-200',
                                        ][$journal->status] ?? 'bg-slate-100 text-slate-500 ring-slate-200';
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ $journal->no }}</td>
                                        <td class="px-6 py-3 text-slate-700">{{ $journal->date?->format('d/m/Y') }}</td>
                                        <td class="px-6 py-3 text-slate-700">
                                            {{ $journal->memo ?: '—' }}
                                            @if ($journal->workspace)
                                                <span class="text-xs text-slate-400">· {{ $journal->workspace->name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right font-medium text-slate-900">{{ number_format((float) $journal->total, 2) }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs ring-1 ring-inset {{ $badge }}">
                                                {{ __('app.admin.products.accounting.status_'.$journal->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
