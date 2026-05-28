@php
    $tabs = [
        ['route' => 'admin.dashboard', 'label' => __('app.admin.tab_overview'), 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.users', 'label' => __('app.admin.tab_users'), 'pattern' => 'admin.users*'],
        ['route' => 'admin.logs', 'label' => __('app.admin.tab_logs'), 'pattern' => 'admin.logs'],
        ['route' => 'admin.site-settings.edit', 'label' => __('app.cms.nav'), 'pattern' => 'admin.site-settings.*'],
    ];
@endphp
<nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="rounded-full px-4 py-1.5 text-sm font-medium transition
                  {{ request()->routeIs($tab['pattern']) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
