@extends('layouts.app')

@section('title', __('site.services.heading') . ' — ' . __('site.brand.name'))

@section('content')
    <section class="relative overflow-hidden border-b border-slate-200 bg-slate-50">
        <div class="bg-grid absolute inset-0 opacity-30" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-4xl px-6 py-20 text-center md:py-24">
            <h1 class="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">{{ __('site.services.heading') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">{{ __('site.services.subheading') }}</p>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid gap-8 md:grid-cols-2">
                @foreach (__('site.services.items') as $i => $service)
                    <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-8 transition hover:border-brand-300 hover:shadow-xl hover:shadow-brand-100/40">
                        <div class="absolute right-0 top-0 -mt-12 -mr-12 h-32 w-32 rounded-full bg-brand-100 opacity-0 transition group-hover:opacity-60" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-600 font-bold text-white">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <h2 class="text-xl font-semibold text-slate-900">{{ $service['title'] }}</h2>
                            </div>
                            <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                            <ul class="mt-6 grid gap-2 sm:grid-cols-2">
                                @foreach ($service['features'] as $feature)
                                    <li class="flex items-start gap-2 text-sm text-slate-700">
                                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('site.services.includedHeading') }}</h2>
            <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (__('site.services.included') as $item)
                    <li class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4">
                        <span class="grid h-8 w-8 flex-shrink-0 place-items-center rounded-lg bg-brand-100 text-brand-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="text-sm text-slate-700">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('site.home.ctaHeading') }}</h2>
            <p class="mt-4 text-lg text-slate-600">{{ __('site.home.ctaSubheading') }}</p>
            <a href="{{ route('contact') }}" class="mt-8 inline-block rounded-md bg-brand-600 px-8 py-3 text-base font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                {{ __('site.home.ctaButton') }}
            </a>
        </div>
    </section>
@endsection
