<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('app.portfolio.heading') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen flex flex-col">
        @auth
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('portfolio.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
                        <div class="hidden sm:block">
                            <div class="text-sm font-semibold leading-tight text-slate-900">{{ __('app.portfolio.heading') }}</div>
                            <div class="text-xs text-slate-500">{{ __('app.portfolio.subheading') }}</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="" referrerpolicy="no-referrer"
                                 class="h-8 w-8 rounded-full ring-2 ring-slate-100">
                        @else
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                            </div>
                        @endif
                        <span class="hidden text-sm text-slate-700 sm:inline">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('portfolio.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                {{ __('app.portfolio.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
                <div class="border-t border-slate-200 bg-slate-50/50">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <a href="{{ route('portfolio.dashboard') }}" 
                               class="border-b-2 px-1 py-3 text-sm font-medium transition {{ request()->routeIs('portfolio.dashboard') ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                                {{ __('app.portfolio.nav.dashboard') }}
                            </a>
                            <a href="{{ route('portfolio.planner') }}" 
                               class="border-b-2 px-1 py-3 text-sm font-medium transition {{ request()->routeIs('portfolio.planner') ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                                {{ __('app.portfolio.nav.planner') }}
                            </a>
                        </nav>
                    </div>
                </div>
            </header>
        @endauth

        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>

    @include('partials.sweetalert')
    @livewireScripts
</body>
</html>
