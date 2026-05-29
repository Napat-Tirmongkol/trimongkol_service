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

            {{-- Brand panel (desktop only) --}}
            <aside class="relative hidden overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 lg:flex lg:w-1/2 lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                {{-- faint grid texture (white lines for the dark panel) --}}
                <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
                     style="background-image:linear-gradient(to right,#fff 1px,transparent 1px),linear-gradient(to bottom,#fff 1px,transparent 1px);background-size:32px 32px;"></div>
                {{-- decorative blobs --}}
                <div class="pointer-events-none absolute -left-20 top-24 h-72 w-72 rounded-full bg-brand-400/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -right-10 -bottom-10 h-72 w-72 rounded-full bg-brand-500/40 blur-3xl"></div>
                <div class="pointer-events-none absolute right-24 top-1/3 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>

                {{-- logo --}}
                <a href="/" class="relative inline-flex items-center gap-2 self-start">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 text-lg font-bold text-white ring-1 ring-white/30 backdrop-blur">T</span>
                    <span class="text-sm font-semibold tracking-tight text-white">{{ config('app.name') }}</span>
                </a>

                {{-- welcome copy --}}
                <div class="relative max-w-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-200">{{ __('app.auth.brand_eyebrow') }}</p>
                    <h1 class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-tight text-white xl:text-5xl">{{ __('app.auth.brand_welcome') }}</h1>
                    <p class="mt-3 text-lg font-semibold text-brand-100">{{ __('app.auth.brand_tagline') }}</p>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-brand-100/80">{{ __('app.auth.brand_copy') }}</p>

                    <ul class="mt-8 space-y-3">
                        @foreach (['brand_point_1', 'brand_point_2', 'brand_point_3'] as $pt)
                            <li class="flex items-center gap-3 text-sm text-white/90">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-white/15 ring-1 ring-white/20">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                {{ __('app.auth.'.$pt) }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- footer --}}
                <p class="relative text-xs text-white/50">© {{ date('Y') }} {{ config('app.name') }}</p>
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
