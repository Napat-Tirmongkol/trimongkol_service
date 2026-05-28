@extends('layouts.marketing')

@section('title', t('services.heading') . ' — ' . t('brand.name'))

@section('content')
    @include('partials.hero', [
        'image' => setting('hero_image.services', config('site.hero_images.services')),
        'eyebrow' => t('nav.services'),
        'title' => t('services.heading'),
        'subtitle' => t('services.subheading'),
        'minHeight' => 'min-h-[60vh]',
    ])

    {{-- Floating "Available" product (Homework Scanner) --}}
    <section class="relative z-20 mx-auto -mt-24 max-w-6xl px-4 sm:px-6">
        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50">
            <div class="grid gap-0 md:grid-cols-5">
                {{-- Left visual side --}}
                <div class="relative flex flex-col justify-between bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-8 text-white md:col-span-2 md:p-10">
                    <div class="bg-grid absolute inset-0 opacity-10" aria-hidden="true"></div>
                    <div class="relative">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-200 ring-1 ring-emerald-300/40">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            {{ t('products.available_label') }}
                        </span>
                        <h2 class="mt-5 text-3xl font-extrabold leading-tight md:text-4xl">{{ t('products.scanner.title') }}</h2>
                        <p class="mt-3 text-base text-brand-100">{{ t('products.scanner.tagline') }}</p>
                    </div>

                    <div class="relative mt-8">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur">
                            💸 {{ t('products.free_forever') }}
                        </span>
                    </div>
                </div>

                {{-- Right content side --}}
                <div class="p-8 md:col-span-3 md:p-10">
                    <p class="text-sm leading-relaxed text-slate-600 md:text-base">{{ t('products.scanner.description') }}</p>

                    <ul class="mt-6 grid gap-2 sm:grid-cols-2">
                        @foreach (t('products.scanner.features') as $feature)
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center justify-center gap-2 rounded-full bg-brand-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700">
                                {{ t('products.go_to_app') }}
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center justify-center gap-2 rounded-full bg-brand-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700">
                                {{ t('products.try_free') }}
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </a>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-7 py-3.5 text-base font-medium text-slate-700 hover:bg-slate-50">
                                {{ t('nav.login') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Coming soon: the enterprise services --}}
    <section class="mx-auto mt-20 max-w-7xl px-4 sm:px-6 md:mt-28">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-700">
                {{ t('products.coming_label') }}
            </span>
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ t('home.servicesHeading') }}</h2>
            <p class="mt-3 text-base text-slate-600">{{ t('home.servicesSubheading') }}</p>
        </div>

        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (t('services.items') as $i => $service)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                            <span class="text-sm font-bold">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                            {{ t('products.coming_label') }}
                        </span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $service['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Included card --}}
    <section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6 md:mt-24">
        <div class="rounded-3xl bg-slate-900 p-8 text-white shadow-xl md:p-12">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight md:text-4xl">{{ t('services.includedHeading') }}</h2>
            </div>
            <ul class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (t('services.included') as $item)
                    <li class="flex items-start gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                        <span class="grid h-7 w-7 flex-shrink-0 place-items-center rounded-full bg-brand-500 text-white">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="text-sm">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6 md:py-28">
        <h2 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ t('home.ctaHeading') }}</h2>
        <p class="mt-4 text-base text-slate-600 md:text-lg">{{ t('home.ctaSubheading') }}</p>
        <a href="{{ route('contact') }}"
           class="mt-8 inline-flex items-center gap-2 rounded-full bg-slate-900 px-8 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-slate-800">
            {{ t('home.ctaButton') }}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
    </section>
@endsection
