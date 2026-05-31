<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ config('brand.color') }}">
    <title>{{ config('app.name') }} — {{ config('brand.tagline') }}</title>
    <meta name="description" content="{{ __('app.landing.hero_sub') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    @php $otherLocale = app()->getLocale() === 'th' ? 'en' : 'th'; @endphp

    {{-- Top bar --}}
    <header class="sticky top-0 z-30 border-b border-slate-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-brand-logo />
                <span class="text-sm font-bold tracking-tight text-slate-900">{{ config('app.name') }}</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('locale.switch', $otherLocale) }}"
                   class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 hover:bg-slate-50">{{ $otherLocale }}</a>
                <a href="{{ route('login') }}" class="rounded-full px-4 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ __('app.landing.sign_in') }}</a>
                <a href="{{ route('register') }}" class="rounded-full bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">{{ __('app.landing.get_started') }}</a>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-50/70 to-white"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-20 text-center sm:px-6 lg:px-8 lg:py-28">
            <span class="inline-block rounded-full border border-brand-200 bg-white px-4 py-1 text-xs font-medium uppercase tracking-[0.18em] text-brand-700">{{ __('app.landing.eyebrow') }}</span>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">{{ __('app.landing.hero_title') }}</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-slate-600">{{ __('app.landing.hero_sub') }}</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('app.landing.cta_primary') }}</a>
                <a href="#pricing" class="rounded-full border border-slate-300 bg-white px-7 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('app.landing.cta_pricing') }}</a>
            </div>
            <p class="mt-4 text-xs text-slate-400">{{ __('app.landing.hero_note') }}</p>
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    ['t' => 'feat_docs_title', 'd' => 'feat_docs_desc'],
                    ['t' => 'feat_arap_title', 'd' => 'feat_arap_desc'],
                    ['t' => 'feat_tax_title', 'd' => 'feat_tax_desc'],
                    ['t' => 'feat_reports_title', 'd' => 'feat_reports_desc'],
                    ['t' => 'feat_handoff_title', 'd' => 'feat_handoff_desc'],
                    ['t' => 'feat_multi_title', 'd' => 'feat_multi_desc'],
                ];
            @endphp
            @foreach ($features as $f)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-600">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('app.landing.'.$f['t']) }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ __('app.landing.'.$f['d']) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-slate-50 py-16">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('app.landing.pricing_title') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">{{ __('app.landing.pricing_sub') }}</p>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-3">
                @foreach ($plans as $key => $plan)
                    @php $featured = $key === \App\Models\Plan::PRO; @endphp
                    <div class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm {{ $featured ? 'border-brand-400 ring-1 ring-brand-200' : 'border-slate-200' }}">
                        @if ($featured)
                            <span class="mb-3 inline-block self-start rounded-full bg-brand-600 px-2.5 py-0.5 text-xs font-semibold text-white">{{ __('app.landing.popular') }}</span>
                        @endif
                        <h3 class="text-base font-semibold text-slate-900">{{ $plan->name }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $plan->tagline }}</p>
                        <div class="mt-4">
                            <span class="text-3xl font-bold tabular-nums text-slate-900">฿{{ number_format($plan->price) }}</span>
                            <span class="text-sm text-slate-500">/ {{ __('app.billing.per_month') }}</span>
                        </div>
                        <ul class="mt-4 flex-1 space-y-2 text-sm text-slate-600">
                            @foreach ($plan->features as $feature)
                                <li class="flex items-start gap-2">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-500"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('register') }}"
                           class="mt-6 w-full rounded-full px-4 py-2 text-center text-sm font-semibold transition {{ $featured ? 'bg-brand-600 text-white hover:bg-brand-700' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                            {{ __('app.landing.get_started') }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('app.landing.final_title') }}</h2>
        <p class="mx-auto mt-3 max-w-xl text-slate-600">{{ __('app.landing.final_sub') }}</p>
        <a href="{{ route('register') }}" class="mt-7 inline-block rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('app.landing.cta_primary') }}</a>
    </section>

    <footer class="border-t border-slate-100 py-8">
        <div class="mx-auto max-w-6xl px-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
            © {{ date('Y') }} {{ config('app.name') }}. {{ __('app.landing.rights') }}
        </div>
    </footer>
</body>
</html>
