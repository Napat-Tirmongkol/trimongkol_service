<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
        @php $otherLocale = app()->getLocale() === 'th' ? 'en' : 'th'; @endphp

        <div class="flex min-h-screen flex-col lg:flex-row">

            {{-- Brand panel (desktop only) — mirrors the marketing hero: photo + slate overlay --}}
            <aside class="relative hidden overflow-hidden lg:flex lg:w-1/2 lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                <img src="{{ setting('hero_image.login', config('site.hero_images.login') ?? config('site.hero_images.home')) }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager">
                <div class="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-900/55 to-slate-900/85"></div>

                {{-- logo --}}
                <a href="/" class="relative inline-flex items-center gap-2 self-start">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 text-lg font-bold text-white ring-1 ring-white/30 backdrop-blur">T</span>
                    <span class="text-sm font-semibold tracking-tight text-white drop-shadow">{{ config('app.name') }}</span>
                </a>

                {{-- welcome copy --}}
                <div class="relative max-w-md">
                    <span class="inline-block rounded-full border border-white/30 bg-white/10 px-4 py-1 text-xs font-medium uppercase tracking-[0.18em] text-white/90 backdrop-blur">{{ __('app.auth.brand_eyebrow') }}</span>
                    <h1 class="mt-6 text-4xl font-extrabold leading-[1.05] tracking-tight text-white drop-shadow-md xl:text-5xl">{{ __('app.auth.brand_welcome') }}</h1>
                    <p class="mt-3 text-lg font-semibold">
                        <span class="bg-gradient-to-r from-brand-300 via-brand-200 to-white bg-clip-text text-transparent">{{ __('app.auth.brand_tagline') }}</span>
                    </p>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/85">{{ __('app.auth.brand_copy') }}</p>

                    <ul class="mt-8 space-y-3">
                        @foreach (['brand_point_1', 'brand_point_2', 'brand_point_3'] as $pt)
                            <li class="flex items-center gap-3 text-sm text-white/90">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-white/15 ring-1 ring-white/25 backdrop-blur">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                {{ __('app.auth.'.$pt) }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- footer --}}
                <p class="relative text-xs text-white/60">© {{ date('Y') }} {{ config('app.name') }}</p>
            </aside>

            {{-- Form panel --}}
            <main class="flex flex-1 flex-col px-5 py-6 sm:px-8 lg:w-1/2">
                <div class="flex items-center justify-between">
                    {{-- mobile logo (hidden on desktop where the brand panel shows it) --}}
                    <a href="/" class="inline-flex items-center gap-2 lg:invisible">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-md shadow-brand-500/30">T</span>
                        <span class="text-sm font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                    </a>
                    <a href="{{ route('locale.switch', $otherLocale) }}"
                       class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 hover:bg-slate-50">
                        {{ $otherLocale }}
                    </a>
                </div>

                <div class="flex flex-1 items-center justify-center py-8">
                    <div class="w-full max-w-sm">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>

        @include('partials.sweetalert')
    </body>
</html>
