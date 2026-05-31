@extends('layouts.marketing')

@section('title', t('about.heading') . ' — ' . t('brand.name'))

@php
    $values = [
        ['title' => t('about.value1Title'), 'desc' => t('about.value1Desc')],
        ['title' => t('about.value2Title'), 'desc' => t('about.value2Desc')],
        ['title' => t('about.value3Title'), 'desc' => t('about.value3Desc')],
        ['title' => t('about.value4Title'), 'desc' => t('about.value4Desc')],
    ];
    $statBoxes = [
        ['n' => '50+',  'l' => t('home.statsClients')],
        ['n' => '120+', 'l' => t('home.statsProjects')],
        ['n' => '8+',   'l' => t('home.statsExperience')],
        ['n' => '24/7', 'l' => t('home.statsSupport')],
    ];
@endphp

@section('content')
    @include('partials.hero', [
        'image' => setting('hero_image.about', config('site.hero_images.about')),
        'eyebrow' => t('nav.about'),
        'title' => t('about.heading'),
        'subtitle' => t('about.subheading'),
        'minHeight' => 'min-h-[60vh]',
    ])

    {{-- Floating "Our story" card --}}
    <section class="relative z-20 mx-auto -mt-24 max-w-6xl px-4 sm:px-6">
        <div class="rounded-3xl bg-white p-8 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50 md:p-12">
            <div class="grid gap-10 md:grid-cols-5 md:gap-12">
                <div class="md:col-span-3">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ t('about.story') }}</span>
                    <h2 class="mt-3 text-3xl font-bold leading-tight tracking-tight text-slate-900 md:text-4xl">{{ t('brand.name') }}</h2>
                    <p class="mt-6 text-base leading-relaxed text-slate-600 md:text-lg">{{ t('about.storyText') }}</p>
                </div>
                <div class="md:col-span-2">
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($statBoxes as $s)
                            <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 p-5">
                                <div class="text-3xl font-extrabold tracking-tight text-brand-700">{{ $s['n'] }}</div>
                                <div class="mt-1 text-xs text-slate-600">{{ $s['l'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mission + Vision --}}
    <section class="mx-auto mt-16 max-w-6xl px-4 sm:px-6 md:mt-24">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200/50 md:p-10">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/20">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                </div>
                <h3 class="mt-6 text-xl font-bold text-slate-900">{{ t('about.missionTitle') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 md:text-base">{{ t('about.missionText') }}</p>
            </div>
            <div class="rounded-3xl bg-slate-900 p-8 text-white shadow-sm md:p-10">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 backdrop-blur ring-1 ring-white/20">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                <h3 class="mt-6 text-xl font-bold">{{ t('about.visionTitle') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-300 md:text-base">{{ t('about.visionText') }}</p>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="mx-auto mt-16 max-w-6xl px-4 sm:px-6 md:mt-24">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ t('about.valuesHeading') }}</h2>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($values as $i => $value)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/50">
                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600">0{{ $i + 1 }}</span>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $value['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $value['desc'] }}</p>
                </div>
            @endforeach
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
