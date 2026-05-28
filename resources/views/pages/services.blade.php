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

    {{-- Floating services card --}}
    <section class="relative z-20 mx-auto -mt-24 max-w-7xl px-4 sm:px-6">
        <div class="rounded-3xl bg-white p-6 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50 sm:p-10 md:p-12">
            {{-- Pill tags row --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-6">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ t('home.servicesHeading') }}</span>
                <span class="text-slate-300">·</span>
                @foreach (t('services.items') as $service)
                    <span class="rounded-full bg-brand-50 px-3.5 py-1 text-xs font-medium text-brand-700">
                        {{ $service['title'] }}
                    </span>
                @endforeach
            </div>

            {{-- Service list --}}
            <ul class="mt-2 divide-y divide-slate-100">
                @foreach (t('services.items') as $i => $service)
                    <li x-data="{ open: false }" class="group">
                        <button type="button" @click="open = !open"
                                class="flex w-full items-center justify-between gap-4 py-5 text-left">
                            <div class="flex min-w-0 items-center gap-4">
                                <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-md shadow-brand-500/20">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="truncate text-lg font-semibold text-slate-900 md:text-xl">{{ $service['title'] }}</span>
                            </div>
                            <span :class="open ? 'rotate-45 bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
                                  class="grid h-9 w-9 flex-shrink-0 place-items-center rounded-full transition">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </span>
                        </button>
                        <div x-show="open" x-cloak x-transition class="pl-14 pb-6">
                            <p class="text-sm leading-relaxed text-slate-600 md:text-base">{{ $service['description'] }}</p>
                            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach ($service['features'] as $feature)
                                    <li class="flex items-start gap-2 text-sm text-slate-700">
                                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ul>
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
