@php
    $platformTabs = [
        ['route' => 'admin.dashboard', 'label' => __('app.admin.tab_overview'), 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.leads.index', 'label' => __('app.admin.tab_leads'), 'pattern' => 'admin.leads.*'],
        ['route' => 'admin.users', 'label' => __('app.admin.tab_users'), 'pattern' => 'admin.users*'],
        ['route' => 'admin.security', 'label' => __('app.admin.tab_security'), 'pattern' => 'admin.security'],
        ['route' => 'admin.logs', 'label' => __('app.admin.tab_logs'), 'pattern' => 'admin.logs'],
        ['route' => 'admin.site-settings.edit', 'label' => __('app.cms.nav'), 'pattern' => 'admin.site-settings.*'],
        ['route' => 'admin.system', 'label' => __('app.admin.tab_system'), 'pattern' => 'admin.system*'],
    ];

    $products = config('admin-products', []);

    // Identify which product (if any) the current route belongs to so we can
    // show its second-row sub-nav.
    $activeProduct = null;
    foreach ($products as $key => $p) {
        if (request()->routeIs($p['pattern'])) {
            $activeProduct = $key;
            break;
        }
    }
@endphp

<nav x-data="{ open: false, products: false }" class="border-b border-slate-200 bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold shadow-md shadow-brand-500/30">T</span>
                    <span class="hidden text-sm font-semibold sm:inline">{{ __('app.admin.portalTitle') }}</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    @foreach ($platformTabs as $tab)
                        <a href="{{ route($tab['route']) }}"
                           class="rounded-full px-4 py-1.5 text-sm font-medium transition
                                  {{ request()->routeIs($tab['pattern']) ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden items-center gap-3 md:flex">
                {{-- Products dropdown --}}
                @if (! empty($products))
                    <div class="relative" @click.outside="products = false">
                        <button type="button" @click="products = !products"
                                class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium transition
                                       {{ $activeProduct ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10' }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            {{ __('app.admin.nav_products') }}
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="products" x-cloak x-transition.opacity
                             class="absolute right-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-2 text-slate-900 shadow-lg">
                            @foreach ($products as $key => $p)
                                <a href="{{ route($p['route']) }}"
                                   class="block rounded-lg px-3 py-2 hover:bg-slate-100
                                          {{ $activeProduct === $key ? 'bg-slate-100' : '' }}">
                                    <div class="text-sm font-semibold text-slate-900">{{ __($p['label_key']) }}</div>
                                    @if (! empty($p['desc_key']))
                                        <div class="mt-0.5 text-xs text-slate-500">{{ __($p['desc_key']) }}</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <span class="text-sm text-slate-300">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-white hover:bg-white/20">
                        {{ __('app.admin.logout') }}
                    </button>
                </form>
            </div>

            <button type="button" @click="open = !open" aria-label="Toggle menu"
                    class="grid h-10 w-10 place-items-center rounded-full border border-white/20 text-white md:hidden">
                <svg x-show="!open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
                <svg x-show="open" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div x-show="open" x-cloak class="border-t border-white/10 pb-4 md:hidden">
            <div class="flex flex-col gap-1 pt-3">
                @foreach ($platformTabs as $tab)
                    <a href="{{ route($tab['route']) }}"
                       class="rounded-md px-3 py-2.5 text-sm font-medium
                              {{ request()->routeIs($tab['pattern']) ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach

                @if (! empty($products))
                    <div class="mt-2 border-t border-white/10 pt-2 text-xs uppercase tracking-wider text-slate-400">{{ __('app.admin.nav_products') }}</div>
                    @foreach ($products as $key => $p)
                        <a href="{{ route($p['route']) }}"
                           class="rounded-md px-3 py-2.5 text-sm font-medium
                                  {{ request()->routeIs($p['pattern']) ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10' }}">
                            {{ __($p['label_key']) }}
                        </a>
                    @endforeach
                @endif

                <form method="POST" action="{{ route('admin.logout') }}" class="mt-2 border-t border-white/10 pt-3">
                    @csrf
                    <button type="submit" class="block w-full rounded-md bg-white/10 px-3 py-2.5 text-left text-sm font-medium text-white hover:bg-white/20">
                        {{ __('app.admin.logout') }} — {{ auth()->user()->name }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

{{-- Second row: product-specific tabs, only when inside a product --}}
@if ($activeProduct && ! empty($products[$activeProduct]['tabs']))
    @php $product = $products[$activeProduct]; @endphp
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 py-2 sm:px-6 lg:px-8">
            <span class="mr-3 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __($product['label_key']) }}</span>
            @foreach ($product['tabs'] as $tab)
                <a href="{{ route($tab['route']) }}"
                   class="shrink-0 rounded-full px-3 py-1 text-sm font-medium transition
                          {{ request()->routeIs($tab['pattern']) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ __($tab['label_key']) }}
                </a>
            @endforeach
        </div>
    </div>
@endif
