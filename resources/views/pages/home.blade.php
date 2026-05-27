@extends('layouts.app')

@php
    $heroWords = explode(' ', __('site.home.heroTitle'));
    $lastWord = array_pop($heroWords);
    $heroPrefix = implode(' ', $heroWords);

    $stats = [
        ['value' => '50+', 'label' => __('site.home.statsClients')],
        ['value' => '120+', 'label' => __('site.home.statsProjects')],
        ['value' => '8+', 'label' => __('site.home.statsExperience')],
        ['value' => '24/7', 'label' => __('site.home.statsSupport')],
    ];

    $whyItems = [
        ['title' => __('site.home.why1Title'), 'desc' => __('site.home.why1Desc'), 'icon' => '<path d="M12 6V3M6 12H3M12 18v3M18 12h3M7.05 7.05L4.93 4.93M16.95 7.05l2.12-2.12M16.95 16.95l2.12 2.12M7.05 16.95l-2.12 2.12"/>'],
        ['title' => __('site.home.why2Title'), 'desc' => __('site.home.why2Desc'), 'icon' => '<path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        ['title' => __('site.home.why3Title'), 'desc' => __('site.home.why3Desc'), 'icon' => '<path d="M18 8a6 6 0 11-12 0 6 6 0 0112 0zM12 14v7M9 21h6"/>'],
        ['title' => __('site.home.why4Title'), 'desc' => __('site.home.why4Desc'), 'icon' => '<path d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12V5m0 14v2M5 12H3m18 0h-2"/>'],
    ];
@endphp

@section('content')
    <section class="relative overflow-hidden">
        <div class="bg-grid absolute inset-0 opacity-40" aria-hidden="true"></div>
        <div class="absolute -top-32 right-1/3 h-96 w-96 rounded-full bg-brand-200 opacity-50 blur-3xl" aria-hidden="true"></div>
        <div class="absolute top-40 -left-20 h-72 w-72 rounded-full bg-brand-300 opacity-40 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl px-6 pt-20 pb-24 md:pt-28 md:pb-32">
            <div class="mx-auto max-w-3xl text-center" style="animation: fadeInUp 0.6s ease-out">
                <span class="inline-block rounded-full border border-brand-200 bg-brand-50 px-4 py-1 text-xs font-medium uppercase tracking-wider text-brand-700">
                    {{ __('site.home.heroEyebrow') }}
                </span>
                <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-900 md:text-6xl">
                    {{ $heroPrefix }}
                    <span class="text-gradient">{{ $lastWord }}</span>
                </h1>
                <p class="mt-6 text-lg text-slate-600 md:text-xl">{{ __('site.home.heroDescription') }}</p>
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('services') }}" class="w-full rounded-md bg-brand-600 px-6 py-3 text-base font-medium text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 sm:w-auto">
                        {{ __('site.home.heroPrimary') }}
                    </a>
                    <a href="{{ route('contact') }}" class="w-full rounded-md border border-slate-300 bg-white px-6 py-3 text-base font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 sm:w-auto">
                        {{ __('site.home.heroSecondary') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <dl class="grid grid-cols-2 gap-8 md:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="text-center">
                        <dt class="text-3xl font-bold text-slate-900 md:text-4xl">{{ $stat['value'] }}</dt>
                        <dd class="mt-2 text-sm text-slate-600">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('site.home.servicesHeading') }}</h2>
                <p class="mt-4 text-lg text-slate-600">{{ __('site.home.servicesSubheading') }}</p>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach (__('site.services.items') as $i => $service)
                    <div class="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-100/40">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-600 group-hover:text-white">
                            <span class="text-lg font-bold">{{ $i + 1 }}</span>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $service['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <a href="{{ route('services') }}" class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:text-brand-800">
                    {{ __('site.home.heroPrimary') }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('site.home.whyHeading') }}</h2>
                <p class="mt-4 text-lg text-slate-600">{{ __('site.home.whySubheading') }}</p>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($whyItems as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                {!! $item['icon'] !!}
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 md:py-28">
        <div class="mx-auto max-w-5xl px-6">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-8 py-16 text-center text-white shadow-2xl shadow-brand-900/20 md:px-16">
                <div class="bg-grid absolute inset-0 opacity-10" aria-hidden="true"></div>
                <div class="relative">
                    <h2 class="text-3xl font-bold tracking-tight md:text-4xl">{{ __('site.home.ctaHeading') }}</h2>
                    <p class="mt-4 text-lg text-brand-100">{{ __('site.home.ctaSubheading') }}</p>
                    <a href="{{ route('contact') }}" class="mt-8 inline-block rounded-md bg-white px-8 py-3 text-base font-semibold text-brand-700 shadow-lg transition hover:bg-brand-50">
                        {{ __('site.home.ctaButton') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
