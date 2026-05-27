@php
    $links = [
        ['route' => 'home', 'label' => __('site.nav.home')],
        ['route' => 'services', 'label' => __('site.nav.services')],
        ['route' => 'about', 'label' => __('site.nav.about')],
        ['route' => 'contact', 'label' => __('site.nav.contact')],
    ];
    $current = request()->route()->getName();
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-50 w-full border-b border-slate-200/60 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="group flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-md shadow-brand-500/20 transition group-hover:shadow-brand-500/40">T</span>
            <span class="text-lg font-semibold tracking-tight text-slate-900">{{ __('site.brand.name') }}</span>
        </a>

        <nav class="hidden items-center gap-1 md:flex">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="rounded-md px-4 py-2 text-sm font-medium transition {{ $current === $link['route'] ? 'text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            @include('partials.language-toggle')
            <a href="{{ route('contact') }}" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">
                {{ __('site.nav.cta') }}
            </a>
        </div>

        <button type="button" @click="open = !open" aria-label="Toggle menu" :aria-expanded="open"
                class="grid h-10 w-10 place-items-center rounded-md border border-slate-200 text-slate-600 md:hidden">
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
            <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="border-t border-slate-200 bg-white md:hidden">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-6 py-4">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="rounded-md px-3 py-2 text-base font-medium {{ $current === $link['route'] ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-50' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
            <div class="mt-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                @include('partials.language-toggle')
                <a href="{{ route('contact') }}" class="flex-1 rounded-md bg-brand-600 px-4 py-2 text-center text-sm font-medium text-white">
                    {{ __('site.nav.cta') }}
                </a>
            </div>
        </div>
    </div>
</header>
