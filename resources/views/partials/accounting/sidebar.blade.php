@php
    $au = auth('accounting')->user();
    $canManage = $au?->canPost() ?? false;
    $isOwner = $au?->isOwner() ?? false;

    $svg = function (string $name) {
        return match ($name) {
            'dashboard' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
            'invoice' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="14" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>',
            'bill' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
            'partners' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'wht' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg>',
            'journal' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            'recurring' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>',
            'bank' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="21" x2="21" y2="21"/><line x1="3" y1="10" x2="21" y2="10"/><polyline points="5 6 12 3 19 6"/><line x1="4" y1="10" x2="4" y2="21"/><line x1="20" y1="10" x2="20" y2="21"/><line x1="8" y1="14" x2="8" y2="17"/><line x1="12" y1="14" x2="12" y2="17"/><line x1="16" y1="14" x2="16" y2="17"/></svg>',
            'chart' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>',
            'calendar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'opening' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            'inventory' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            'asset' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'payroll' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
            'department' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>',
            'budget' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>',
            'report' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'tax' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
            'approval' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            'users' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'audit' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            default => '',
        };
    };

    $groups = [
        [
            'label' => __('app.accounting.sidebar_overview'),
            'items' => [
                ['route' => 'accounting.dashboard', 'pattern' => 'accounting.dashboard', 'label' => __('app.accounting.sidebar_dashboard'), 'icon' => 'dashboard'],
            ],
        ],
        [
            'label' => __('app.accounting.sidebar_sales'),
            'items' => [
                ['route' => 'accounting.invoices.index', 'pattern' => 'accounting.invoices.*', 'label' => __('app.accounting.invoices'), 'icon' => 'invoice'],
                ['route' => 'accounting.partners.index', 'pattern' => 'accounting.partners.*', 'label' => __('app.accounting.partners'), 'icon' => 'partners'],
                ['route' => 'accounting.wht-certificates.index', 'pattern' => 'accounting.wht-certificates.*', 'label' => __('app.accounting.wht_certificates'), 'icon' => 'wht'],
            ],
        ],
        [
            'label' => __('app.accounting.sidebar_purchases'),
            'items' => [
                ['route' => 'accounting.bills.index', 'pattern' => 'accounting.bills.*', 'label' => __('app.accounting.bills'), 'icon' => 'bill'],
            ],
        ],
        [
            'label' => __('app.accounting.sidebar_books'),
            'items' => [
                ['route' => 'accounting.accounts.index', 'pattern' => 'accounting.accounts.*', 'label' => __('app.accounting.accounts'), 'icon' => 'chart'],
                ['route' => 'accounting.manual-journals.index', 'pattern' => 'accounting.manual-journals.*', 'label' => __('app.accounting.manual_journals'), 'icon' => 'journal'],
                ['route' => 'accounting.recurring-journals.index', 'pattern' => 'accounting.recurring-journals.*', 'label' => __('app.accounting.recurring_journals'), 'icon' => 'recurring'],
                ['route' => 'accounting.bank-reconciliation.index', 'pattern' => 'accounting.bank-reconciliation.*', 'label' => __('app.accounting.bank_recon'), 'icon' => 'bank'],
                ['route' => 'accounting.periods.index', 'pattern' => 'accounting.periods.*', 'label' => __('app.accounting.periods'), 'icon' => 'calendar'],
                ['route' => 'accounting.opening-balances.edit', 'pattern' => 'accounting.opening-balances.*', 'label' => __('app.accounting.opening_balances'), 'icon' => 'opening'],
            ],
        ],
        [
            'label' => __('app.accounting.sidebar_assets'),
            'items' => [
                ['route' => 'accounting.products.index', 'pattern' => 'accounting.products.*', 'label' => __('app.accounting.products'), 'icon' => 'inventory'],
                ['route' => 'accounting.fixed-assets.index', 'pattern' => 'accounting.fixed-assets.*', 'label' => __('app.accounting.fixed_assets'), 'icon' => 'asset'],
                ['route' => 'accounting.payroll.employees', 'pattern' => 'accounting.payroll.*', 'label' => __('app.accounting.payroll_runs'), 'icon' => 'payroll'],
                ['route' => 'accounting.departments.index', 'pattern' => 'accounting.departments.*', 'label' => __('app.accounting.departments'), 'icon' => 'department'],
                ['route' => 'accounting.budgets.index', 'pattern' => 'accounting.budgets.*', 'label' => __('app.accounting.budgets'), 'icon' => 'budget'],
            ],
        ],
        [
            'label' => __('app.accounting.sidebar_reports'),
            'items' => [
                ['route' => 'accounting.reports', 'pattern' => 'accounting.reports', 'label' => __('app.accounting.reports'), 'icon' => 'report'],
                ['route' => 'accounting.reports.tax', 'pattern' => 'accounting.reports.tax', 'label' => __('app.accounting.tax_reports'), 'icon' => 'tax'],
            ],
        ],
        [
            'label' => __('app.accounting.sidebar_admin'),
            'items' => array_values(array_filter([
                $canManage ? ['route' => 'accounting.approvals.index', 'pattern' => 'accounting.approvals.*', 'label' => __('app.accounting.approval_pending_count'), 'icon' => 'approval'] : null,
                $isOwner ? ['route' => 'accounting.users.index', 'pattern' => 'accounting.users.*', 'label' => __('app.accounting.accounting_users'), 'icon' => 'users'] : null,
                $canManage ? ['route' => 'accounting.audit-log.index', 'pattern' => 'accounting.audit-log.*', 'label' => __('app.accounting.audit_log'), 'icon' => 'audit'] : null,
            ])),
        ],
    ];

    $renderLinks = function () use ($groups, $svg) {
        $out = '';
        foreach ($groups as $group) {
            if (empty($group['items'])) continue;
            $out .= '<div class="px-3 pt-5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">' . e($group['label']) . '</div>';
            $out .= '<div class="space-y-0.5">';
            foreach ($group['items'] as $item) {
                $active = request()->routeIs($item['pattern']);
                $cls = $active
                    ? 'flex items-center gap-2.5 rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700'
                    : 'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition';
                $out .= '<a href="' . route($item['route']) . '" class="' . $cls . '">';
                $out .= '<span class="shrink-0 ' . ($active ? 'text-brand-600' : 'text-slate-400') . '">' . $svg($item['icon']) . '</span>';
                $out .= '<span class="truncate">' . e($item['label']) . '</span>';
                $out .= '</a>';
            }
            $out .= '</div>';
        }
        return $out;
    };
@endphp

{{-- Desktop sidebar --}}
<aside class="hidden lg:flex lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:w-64 lg:flex-col border-r border-slate-200 bg-white">
    <div class="flex h-14 shrink-0 items-center gap-2 border-b border-slate-200 px-4">
        <a href="{{ route('accounting.dashboard') }}" class="flex items-center gap-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <span>{{ __('app.accounting.nav') }}</span>
        </a>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-6">
        {!! $renderLinks() !!}
    </nav>
</aside>

{{-- Mobile drawer --}}
<div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" x-cloak>
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
         @click="sidebarOpen = false"></div>

    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-250 transform"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-250 transform"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="relative flex h-full w-72 max-w-[85vw] flex-col bg-white shadow-xl">
        <div class="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-4">
            <a href="{{ route('accounting.dashboard') }}" class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <span>{{ __('app.accounting.nav') }}</span>
            </a>
            <button type="button" @click="sidebarOpen = false" class="grid h-9 w-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            {!! $renderLinks() !!}
        </nav>
    </div>
</div>
