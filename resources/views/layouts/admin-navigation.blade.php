@php
    $tabs = [
        ['route' => 'admin.dashboard', 'label' => __('app.admin.tab_overview'), 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.users', 'label' => __('app.admin.tab_users'), 'pattern' => 'admin.users*'],
        ['route' => 'admin.classrooms', 'label' => __('app.admin.tab_classrooms'), 'pattern' => 'admin.classrooms*'],
        ['route' => 'admin.logs', 'label' => __('app.admin.tab_logs'), 'pattern' => 'admin.logs'],
        ['route' => 'admin.site-settings.edit', 'label' => __('app.cms.nav'), 'pattern' => 'admin.site-settings.*'],
    ];
@endphp
<nav x-data="{ open: false }" class="border-b border-slate-200 bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold shadow-md shadow-brand-500/30">T</span>
                    <span class="hidden text-sm font-semibold sm:inline">{{ __('app.admin.portalTitle') }}</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    @foreach ($tabs as $tab)
                        <a href="{{ route($tab['route']) }}"
                           class="rounded-full px-4 py-1.5 text-sm font-medium transition
                                  {{ request()->routeIs($tab['pattern']) ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden items-center gap-3 md:flex">
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
                @foreach ($tabs as $tab)
                    <a href="{{ route($tab['route']) }}"
                       class="rounded-md px-3 py-2.5 text-sm font-medium
                              {{ request()->routeIs($tab['pattern']) ? 'bg-white text-slate-900' : 'text-slate-300 hover:bg-white/10' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
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
