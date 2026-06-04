@php
    $links = [
        ['route' => 'home', 'label' => t('nav.home')],
        ['route' => 'services', 'label' => t('nav.services')],
        ['route' => 'ocr', 'label' => t('ocr.nav')],
        ['route' => 'about', 'label' => t('nav.about')],
        ['route' => 'contact', 'label' => t('nav.contact')],
    ];
    $current = request()->route()->getName();
@endphp

<header x-data="{ open: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 60"
        x-init="scrolled = window.scrollY > 60"
        :class="scrolled ? 'bg-white/90 shadow-sm backdrop-blur' : 'bg-transparent'"
        class="fixed top-0 z-50 w-full transition-all duration-300">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="group flex items-center gap-2">
            <img src="{{ asset('logo.png') }}" alt="{{ t('brand.name') }}" class="h-9 w-auto object-contain">
            <span :class="scrolled ? 'text-slate-900' : 'text-white drop-shadow'"
                  class="text-base font-semibold tracking-tight transition-colors">
                {{ t('brand.name') }}
            </span>
        </a>

        <nav class="hidden items-center gap-1 md:flex">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   :class="scrolled
                       ? '{{ $current === $link['route'] ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:text-slate-900' }}'
                       : '{{ $current === $link['route'] ? 'bg-white/20 text-white' : 'text-white/80 hover:text-white' }}'"
                   class="rounded-full px-4 py-2 text-sm font-medium transition">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-2 md:flex">
            @include('partials.language-toggle')
            @auth
                <a href="{{ route('dashboard') }}"
                   :class="scrolled ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-white text-slate-900 hover:bg-slate-100'"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition">
                    {{ t('nav.dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}"
                   :class="scrolled ? 'text-slate-700 hover:text-slate-900' : 'text-white/90 hover:text-white'"
                   class="rounded-full px-4 py-2 text-sm font-medium transition">
                    {{ t('nav.login') }}
                </a>
                <a href="{{ route('register') }}"
                   :class="scrolled ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-white text-slate-900 hover:bg-slate-100'"
                   class="rounded-full px-5 py-2 text-sm font-semibold transition">
                    {{ t('nav.register') }}
                </a>
            @endauth
        </div>

        <button type="button" @click="open = !open" aria-label="Toggle menu" :aria-expanded="open"
                :class="scrolled ? 'text-slate-700 border-slate-200' : 'text-white border-white/30'"
                class="grid h-10 w-10 place-items-center rounded-full border transition md:hidden">
            <svg x-show="!open" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
            <svg x-show="open" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition class="border-t border-slate-200 bg-white md:hidden">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-4">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="rounded-md px-3 py-2.5 text-base font-medium {{ $current === $link['route'] ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-50' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
            <div class="mt-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                @include('partials.language-toggle')
                @auth
                    <a href="{{ route('dashboard') }}" class="flex-1 rounded-full bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white">
                        {{ t('nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="flex-1 rounded-full bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white">
                        {{ t('nav.register') }}
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
