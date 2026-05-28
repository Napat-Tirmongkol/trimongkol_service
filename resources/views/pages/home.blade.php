@extends('layouts.marketing')

@php
    $heroWords = explode(' ', t('home.heroTitle'));
    $lastWord = array_pop($heroWords);
    $heroPrefix = implode(' ', $heroWords);

    $stats = [
        ['value' => setting('home.stat1Value', '50+'),  'label' => t('home.statsClients')],
        ['value' => setting('home.stat2Value', '120+'), 'label' => t('home.statsProjects')],
        ['value' => setting('home.stat3Value', '8+'),   'label' => t('home.statsExperience')],
        ['value' => setting('home.stat4Value', '24/7'), 'label' => t('home.statsSupport')],
    ];

    $whyItems = [
        ['title' => t('home.why1Title'), 'desc' => t('home.why1Desc')],
        ['title' => t('home.why2Title'), 'desc' => t('home.why2Desc')],
        ['title' => t('home.why3Title'), 'desc' => t('home.why3Desc')],
        ['title' => t('home.why4Title'), 'desc' => t('home.why4Desc')],
    ];
@endphp

@section('content')
    @include('partials.hero', [
        'image' => setting('hero_image.home', config('site.hero_images.home')),
        'eyebrow' => t('home.heroEyebrow'),
        'title' => $heroPrefix,
        'titleHighlight' => $lastWord,
        'subtitle' => t('home.heroDescription'),
        'primaryAction' => ['href' => route('services'), 'label' => t('home.heroPrimary')],
        'secondaryAction' => ['href' => route('contact'), 'label' => t('home.heroSecondary')],
        'minHeight' => 'min-h-[90vh]',
    ])

    {{-- Floating stats card overlapping the hero --}}
    <section class="relative z-20 mx-auto -mt-24 max-w-6xl px-4 sm:px-6">
        <div class="grid gap-8 rounded-3xl bg-white p-8 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50 md:grid-cols-5 md:gap-12 md:p-12">
            <div class="md:col-span-2">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ t('brand.tagline') }}</span>
                <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 md:text-3xl">
                    {{ t('home.servicesSubheading') }}
                </h2>
            </div>
            <dl class="grid grid-cols-2 gap-6 md:col-span-3 md:grid-cols-4">
                @foreach ($stats as $stat)
                    <div>
                        <dt class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">{{ $stat['value'] }}</dt>
                        <dd class="mt-1 text-xs leading-snug text-slate-600">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Services preview --}}
    <section class="mx-auto mt-24 max-w-7xl px-4 sm:px-6 md:mt-32">
        <div class="mx-auto max-w-2xl text-center">
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ t('home.servicesHeading') }}</span>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-5xl">{{ t('home.servicesSubheading') }}</h2>
        </div>

        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (t('services.items') as $i => $service)
                <div class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-xl hover:ring-brand-300">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/20">
                        <span class="text-sm font-bold">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $service['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('services') }}"
               class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100">
                {{ t('home.heroPrimary') }}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
    </section>

    {{-- Why us --}}
    <section class="mt-24 bg-slate-100 py-20 md:mt-32 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ t('home.whyHeading') }}</span>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-5xl">{{ t('home.whySubheading') }}</h2>
            </div>

            <div class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($whyItems as $i => $item)
                    <div class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-200/50">
                        <div class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600">0{{ $i + 1 }}</div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA card --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6 md:py-28">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-8 py-16 text-center text-white shadow-2xl shadow-brand-900/30 md:px-16 md:py-20">
            <div class="bg-grid absolute inset-0 opacity-10" aria-hidden="true"></div>
            <div class="relative">
                <h2 class="mx-auto max-w-3xl text-3xl font-bold tracking-tight md:text-5xl">{{ t('home.ctaHeading') }}</h2>
                <p class="mx-auto mt-5 max-w-xl text-base text-brand-100 md:text-lg">{{ t('home.ctaSubheading') }}</p>
                <a href="{{ route('contact') }}"
                   class="mt-10 inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 text-base font-semibold text-brand-700 shadow-lg transition hover:bg-brand-50">
                    {{ t('home.ctaButton') }}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
@endsection
