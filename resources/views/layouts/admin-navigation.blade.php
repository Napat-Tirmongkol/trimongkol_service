@php
    $u = auth()->user();
    $can = fn ($perm) => $perm === null || (bool) $u?->can($perm);

    $menu = [
        'platform' => [
            'label' => __('app.admin.portalTitle') ?: 'Platform',
            'items' => array_values(array_filter([
                ['route' => 'admin.dashboard', 'label' => __('app.admin.tab_overview'), 'pattern' => 'admin.dashboard', 'perm' => null, 'icon' => 'squares'],
                ['route' => 'admin.leads.index', 'label' => __('app.admin.tab_leads'), 'pattern' => 'admin.leads.*', 'perm' => 'leads.view', 'icon' => 'chat'],
                ['route' => 'admin.users', 'label' => __('app.admin.tab_users'), 'pattern' => 'admin.users*', 'perm' => 'users.view', 'icon' => 'users'],
                ['route' => 'admin.workspaces.index', 'label' => __('app.admin.tab_workspaces'), 'pattern' => 'admin.workspaces.*', 'perm' => 'workspaces.view', 'icon' => 'briefcase'],
                ['route' => 'admin.site-settings.edit', 'label' => __('app.cms.nav'), 'pattern' => 'admin.site-settings.*', 'perm' => 'cms.manage', 'icon' => 'cog'],
            ], fn ($item) => $can($item['perm'])))
        ],
        'system' => [
            'label' => 'System Control',
            'items' => array_values(array_filter([
                ['route' => 'admin.billing', 'label' => __('app.admin.tab_billing'), 'pattern' => 'admin.billing*', 'perm' => 'workspaces.manage', 'icon' => 'credit-card'],
                ['route' => 'admin.notifications.edit', 'label' => __('app.admin.notifications.nav'), 'pattern' => 'admin.notifications.*', 'perm' => 'cms.manage', 'icon' => 'bell'],
                ['route' => 'admin.roles.index', 'label' => __('app.roles.nav'), 'pattern' => 'admin.roles.*', 'perm' => 'roles.manage', 'icon' => 'shield'],
                ['route' => 'admin.security', 'label' => __('app.admin.tab_security'), 'pattern' => 'admin.security', 'perm' => 'security.view', 'icon' => 'key'],
                ['route' => 'admin.logs', 'label' => __('app.admin.tab_logs'), 'pattern' => 'admin.logs', 'perm' => 'audit.view', 'icon' => 'document-text'],
                ['route' => 'admin.system', 'label' => __('app.admin.tab_system'), 'pattern' => 'admin.system*', 'perm' => 'system.manage', 'icon' => 'server'],
            ], fn ($item) => $can($item['perm'])))
        ]
    ];

    $products = $can('products.moderate') ? config('admin-products', []) : [];
    $activeProduct = null;
    foreach ($products as $key => $p) {
        if (request()->routeIs($p['pattern'])) {
            $activeProduct = $key;
            break;
        }
    }
@endphp

{{-- Reusable Navigation Links Block --}}
@php
    $renderLinks = function() use ($menu, $products, $activeProduct) {
        $output = '';
        
        // Render Platform & System Sections
        foreach ($menu as $secKey => $sec) {
            if (empty($sec['items'])) continue;
            
            $output .= '<div class="px-3 mt-6 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">' . e($sec['label']) . '</div>';
            $output .= '<div class="space-y-1">';
            
            foreach ($sec['items'] as $item) {
                $isActive = request()->routeIs($item['pattern']);
                $linkClass = $isActive 
                    ? 'flex items-center gap-3 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white transition' 
                    : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white transition';
                
                $svg = '';
                if ($item['icon'] === 'squares') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>';
                } elseif ($item['icon'] === 'chat') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>';
                } elseif ($item['icon'] === 'users') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
                } elseif ($item['icon'] === 'briefcase') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
                } elseif ($item['icon'] === 'cog') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
                } elseif ($item['icon'] === 'credit-card') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
                } elseif ($item['icon'] === 'bell') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';
                } elseif ($item['icon'] === 'shield') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
                } elseif ($item['icon'] === 'key') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-2 4a5 5 0 110-10 5 5 0 010 10zM19 9h2a2 2 0 012 2v2a2 2 0 01-2 2h-2v2a2 2 0 01-2 2h-2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2h-3.83a5.002 5.002 0 01-1.002-1.002L15 9h4z"/></svg>';
                } elseif ($item['icon'] === 'document-text') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
                } elseif ($item['icon'] === 'server') {
                    $svg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>';
                }
                
                $output .= '<a href="' . route($item['route']) . '" class="' . $linkClass . '">';
                $output .= $svg;
                $output .= '<span>' . e($item['label']) . '</span>';
                $output .= '</a>';
            }
            $output .= '</div>';
        }
        
        // Render Products Section
        if (! empty($products)) {
            $output .= '<div class="px-3 mt-6 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Products (สินค้า)</div>';
            $output .= '<div class="space-y-1">';
            
            foreach ($products as $key => $p) {
                $isProductActive = ($activeProduct === $key);
                $prodLinkClass = $isProductActive
                    ? 'flex items-center justify-between rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white transition'
                    : 'flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-slate-100 transition';
                
                $dotClass = $isProductActive ? 'bg-sky-400' : 'bg-slate-600';
                
                $output .= '<div class="space-y-1">';
                $output .= '<a href="' . route($p['route']) . '" class="' . $prodLinkClass . '">';
                $output .= '<div class="flex items-center gap-2">';
                $output .= '<span class="h-2 w-2 rounded-full ' . $dotClass . '"></span>';
                $output .= '<span>' . e(__($p['label_key'])) . '</span>';
                $output .= '</div>';
                $output .= '<span class="text-xs text-slate-500">→</span>';
                $output .= '</a>';
                
                // If this product is active, render its sub-menus (tabs)
                if ($isProductActive && ! empty($p['tabs'])) {
                    $output .= '<div class="pl-6 space-y-1 border-l border-slate-800 ml-4 mt-1">';
                    foreach ($p['tabs'] as $tab) {
                        $isTabActive = request()->routeIs($tab['pattern']);
                        $tabClass = $isTabActive
                            ? 'block rounded-md px-3 py-1.5 text-xs font-semibold bg-sky-500/10 text-sky-400 transition'
                            : 'block rounded-md px-3 py-1.5 text-xs font-medium text-slate-400 hover:bg-white/5 hover:text-slate-100 transition';
                        
                        $output .= '<a href="' . route($tab['route']) . '" class="' . $tabClass . '">';
                        $output .= e(__($tab['label_key']));
                        $output .= '</a>';
                    }
                    $output .= '</div>';
                }
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        return $output;
    };
@endphp

{{-- 1. Desktop Sidebar (Always Visible on Large Screens) --}}
<aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:z-30 bg-[#0f1012] border-r border-slate-800">
    {{-- Brand Header --}}
    <div class="flex h-16 shrink-0 items-center border-b border-slate-800 px-6 gap-2.5">
        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
        <span class="text-sm font-semibold tracking-tight text-white">{{ __('app.admin.portalTitle') ?: 'Tirmongkol Admin' }}</span>
    </div>
    
    {{-- Sidebar Navigation Links --}}
    <div class="flex flex-1 flex-col overflow-y-auto px-4 py-4 space-y-6">
        {!! $renderLinks() !!}
    </div>
</aside>

{{-- 2. Mobile Sidebar Drawer --}}
<div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" x-cloak>
    {{-- Backdrop overlay --}}
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition-opacity ease-linear duration-300" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" 
         @click="sidebarOpen = false"></div>

    {{-- Sliding Drawer --}}
    <div x-show="sidebarOpen" 
         x-transition:enter="transition ease-in-out duration-300 transform" 
         x-transition:enter-start="-translate-x-full" 
         x-transition:enter-end="translate-x-0" 
         x-transition:leave="transition ease-in-out duration-300 transform" 
         x-transition:leave-start="translate-x-0" 
         x-transition:leave-end="-translate-x-full" 
         class="relative flex w-full max-w-xs flex-col bg-[#0f1012] border-r border-slate-800 min-h-screen">
        
        {{-- Close button --}}
        <div class="absolute right-2 top-2">
            <button type="button" @click="sidebarOpen = false" class="flex h-10 w-10 items-center justify-center rounded-full text-slate-400 hover:bg-slate-800 hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Mobile Brand Header --}}
        <div class="flex h-16 shrink-0 items-center border-b border-slate-800 px-6 gap-2.5">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
            <span class="text-sm font-semibold tracking-tight text-white">{{ __('app.admin.portalTitle') ?: 'Tirmongkol Admin' }}</span>
        </div>

        {{-- Scrollable mobile menu links --}}
        <div class="flex flex-1 flex-col overflow-y-auto px-4 py-4 space-y-6">
            {!! $renderLinks() !!}
        </div>
    </div>
</div>
