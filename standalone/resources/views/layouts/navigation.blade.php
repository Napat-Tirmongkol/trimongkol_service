@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('') ?: '·';
    $otherLocale = app()->getLocale() === 'th' ? 'en' : 'th';
    $currentWorkspace = \App\Services\CurrentWorkspace::get($user);
    $userWorkspaces = $user ? $user->workspaces()->orderBy('name')->get() : collect();
@endphp
<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">
            <div class="flex items-center gap-6">
                <a href="{{ route('accounting.dashboard') }}" class="flex items-center gap-2">
                    <x-brand-logo class="shadow-md shadow-brand-500/30" />
                    <span class="hidden text-sm font-semibold tracking-tight text-slate-900 sm:inline">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('accounting.dashboard') }}"
                       class="rounded-full px-4 py-1.5 text-sm font-medium transition
                              {{ request()->routeIs('accounting.*')
                                  ? 'bg-slate-900 text-white'
                                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        {{ __('app.accounting.nav') }}
                    </a>
                </div>
            </div>

            <div class="hidden items-center gap-2 md:flex">
                {{-- Workspace switcher: only when the user belongs to more than one --}}
                @if ($currentWorkspace && $userWorkspaces->count() > 1)
                    <div x-data="{ ws: false }" @click.outside="ws = false" class="relative">
                        <button @click="ws = !ws" type="button"
                                class="flex max-w-[180px] items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-sm hover:bg-slate-50">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400">
                                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            <span class="truncate font-medium text-slate-700">{{ $currentWorkspace->name }}</span>
                            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="shrink-0 text-slate-400">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="ws" x-cloak x-transition
                             class="absolute right-0 mt-2 w-64 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg ring-1 ring-slate-900/5">
                            @foreach ($userWorkspaces as $w)
                                <form method="POST" action="{{ route('workspaces.switch', $w) }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-100 {{ $currentWorkspace->id === $w->id ? 'bg-slate-100 font-semibold' : '' }}">
                                        <span class="truncate text-slate-800">{{ $w->name }}</span>
                                        @if ($currentWorkspace->id === $w->id)
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-emerald-600"><polyline points="20 6 9 17 4 12"/></svg>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                            <div class="my-1 border-t border-slate-100"></div>
                            <a href="{{ route('workspaces.index') }}" class="block rounded-lg px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">
                                {{ __('app.workspaces.manage') }} →
                            </a>
                        </div>
                    </div>
                @endif

                <a href="{{ route('locale.switch', $otherLocale) }}"
                   class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 hover:bg-slate-100">
                    {{ $otherLocale }}
                </a>

                <div x-data="{ menu: false }" @click.outside="menu = false" class="relative">
                    <button @click="menu = !menu" type="button"
                            class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-3 text-sm hover:bg-slate-50">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">{{ $initials }}</span>
                        <span class="font-medium text-slate-800">{{ $user?->name }}</span>
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" class="text-slate-400">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="menu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-56 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-slate-900/5">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <div class="text-sm font-medium text-slate-900">{{ $user?->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $user?->email }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('Profile') }}
                        </a>
                        <a href="{{ route('workspaces.index') }}"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('app.workspaces.manage') }}
                        </a>
                        <a href="{{ route('billing.index') }}"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('app.billing.nav') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full border-t border-slate-100 px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <button type="button" @click="open = !open" aria-label="Menu"
                    class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-slate-700 md:hidden">
                <svg x-show="!open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
                <svg x-show="open" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div x-show="open" x-cloak class="border-t border-slate-200 py-3 md:hidden">
            <div class="flex items-center gap-3 px-1 pb-3">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">{{ $initials }}</span>
                <div>
                    <div class="text-sm font-medium text-slate-900">{{ $user?->name }}</div>
                    <div class="text-xs text-slate-500">{{ $user?->email }}</div>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <a href="{{ route('accounting.dashboard') }}"
                   class="rounded-md px-3 py-2 text-sm font-medium
                          {{ request()->routeIs('accounting.*')
                              ? 'bg-slate-900 text-white'
                              : 'text-slate-700 hover:bg-slate-100' }}">
                    {{ __('app.accounting.nav') }}
                </a>
                <a href="{{ route('profile.edit') }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    {{ __('Profile') }}
                </a>
                <a href="{{ route('workspaces.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    {{ __('app.workspaces.manage') }}
                </a>
                <a href="{{ route('locale.switch', $otherLocale) }}"
                   class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    {{ __('Language') }} · <span class="font-bold uppercase">{{ $otherLocale }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-slate-200 pt-2">
                    @csrf
                    <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
