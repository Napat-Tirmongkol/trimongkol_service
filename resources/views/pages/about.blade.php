@extends('layouts.app')

@section('title', __('site.about.heading') . ' — ' . __('site.brand.name'))

@php
    $values = [
        ['title' => __('site.about.value1Title'), 'desc' => __('site.about.value1Desc')],
        ['title' => __('site.about.value2Title'), 'desc' => __('site.about.value2Desc')],
        ['title' => __('site.about.value3Title'), 'desc' => __('site.about.value3Desc')],
        ['title' => __('site.about.value4Title'), 'desc' => __('site.about.value4Desc')],
    ];
    $statBoxes = [
        ['n' => '50+',  'l' => __('site.home.statsClients')],
        ['n' => '120+', 'l' => __('site.home.statsProjects')],
        ['n' => '8+',   'l' => __('site.home.statsExperience')],
        ['n' => '24/7', 'l' => __('site.home.statsSupport')],
    ];
@endphp

@section('content')
    <section class="relative overflow-hidden border-b border-slate-200 bg-slate-50">
        <div class="bg-grid absolute inset-0 opacity-30" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-4xl px-6 py-20 text-center md:py-24">
            <h1 class="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">{{ __('site.about.heading') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">{{ __('site.about.subheading') }}</p>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto grid max-w-6xl gap-12 px-6 md:grid-cols-2 md:items-center">
            <div>
                <span class="text-sm font-semibold uppercase tracking-wider text-brand-700">{{ __('site.about.story') }}</span>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ __('site.brand.name') }}</h2>
                <p class="mt-6 text-base leading-relaxed text-slate-600">{{ __('site.about.storyText') }}</p>
            </div>
            <div class="relative">
                <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-brand-100 to-brand-300 opacity-50 blur-2xl" aria-hidden="true"></div>
                <div class="relative rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
                    <div class="grid grid-cols-2 gap-6">
                        @foreach ($statBoxes as $s)
                            <div class="rounded-xl bg-brand-50 p-5">
                                <div class="text-3xl font-bold text-brand-700">{{ $s['n'] }}</div>
                                <div class="mt-1 text-xs text-slate-600">{{ $s['l'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-20">
        <div class="mx-auto grid max-w-6xl gap-8 px-6 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-8">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-xl font-semibold text-slate-900">{{ __('site.about.missionTitle') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('site.about.missionText') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-8">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-xl font-semibold text-slate-900">{{ __('site.about.visionTitle') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('site.about.visionText') }}</p>
            </div>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('site.about.valuesHeading') }}</h2>
            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($values as $i => $value)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <span class="text-sm font-semibold text-brand-700">0{{ $i + 1 }}</span>
                        <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $value['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $value['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('site.home.ctaHeading') }}</h2>
            <p class="mt-4 text-lg text-slate-600">{{ __('site.home.ctaSubheading') }}</p>
            <a href="{{ route('contact') }}" class="mt-8 inline-block rounded-md bg-brand-600 px-8 py-3 text-base font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                {{ __('site.home.ctaButton') }}
            </a>
        </div>
    </section>
@endsection
