<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.audit_log') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.audit_log_sub') }}</p>
        </div>
    </x-slot>

    @php
        $badgeClass = [
            'invoice.created'          => 'bg-sky-50 text-sky-700 ring-sky-200',
            'invoice.issued'           => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'invoice.receipt_recorded' => 'bg-teal-50 text-teal-700 ring-teal-200',
            'bill.created'             => 'bg-sky-50 text-sky-700 ring-sky-200',
            'bill.posted'              => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'bill.payment_recorded'    => 'bg-teal-50 text-teal-700 ring-teal-200',
            'account.created'          => 'bg-sky-50 text-sky-700 ring-sky-200',
            'account.updated'          => 'bg-amber-50 text-amber-700 ring-amber-200',
            'account.deleted'          => 'bg-rose-50 text-rose-700 ring-rose-200',
            'partner.created'          => 'bg-sky-50 text-sky-700 ring-sky-200',
            'asset.registered'         => 'bg-sky-50 text-sky-700 ring-sky-200',
            'asset.depreciated'        => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'asset.disposed'           => 'bg-rose-50 text-rose-700 ring-rose-200',
            'employee.added'           => 'bg-sky-50 text-sky-700 ring-sky-200',
            'employee.toggled'         => 'bg-amber-50 text-amber-700 ring-amber-200',
            'payroll_run.created'      => 'bg-sky-50 text-sky-700 ring-sky-200',
            'payroll_run.posted'       => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'recurring.created'        => 'bg-sky-50 text-sky-700 ring-sky-200',
            'recurring.run'            => 'bg-amber-50 text-amber-700 ring-amber-200',
            'recurring.toggled'        => 'bg-amber-50 text-amber-700 ring-amber-200',
            'recurring.deleted'        => 'bg-rose-50 text-rose-700 ring-rose-200',
            'journal.posted'           => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'period.closed'            => 'bg-slate-100 text-slate-700 ring-slate-200',
            'period.reopened'          => 'bg-amber-50 text-amber-700 ring-amber-200',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            @if ($logs->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-slate-500">
                    {{ __('app.accounting.audit_no_logs') }}
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-3 text-left">{{ __('app.accounting.col_date') }}</th>
                                <th class="px-5 py-3 text-left">{{ __('app.accounting.audit_by') }}</th>
                                <th class="px-5 py-3 text-left">การดำเนินการ</th>
                                <th class="px-5 py-3 text-left">รายการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($logs as $log)
                                @php
                                    $badge = $badgeClass[$log->action] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                                    $actionLabel = __('app.accounting.audit_action.' . $log->action, [], 'th');
                                    if ($actionLabel === 'app.accounting.audit_action.' . $log->action) {
                                        $actionLabel = $log->action;
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                        {{ $log->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-700">
                                        {{ $log->accountingUser?->name ?? __('app.accounting.audit_system') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $badge }}">
                                            {{ $actionLabel }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">
                                        {{ $log->subject_label ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
