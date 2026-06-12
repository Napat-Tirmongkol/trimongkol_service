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

    <style>
        /* ── Portfolio Dark Mode (mirrors admin theme) ────────────── */

        /* Cards & Surfaces */
        .portfolio-dark .bg-white {
            background-color: #121418 !important;
        }

        .portfolio-dark .bg-slate-50,
        .portfolio-dark .bg-slate-50\/50 {
            background-color: #0b0c0e !important;
        }

        .portfolio-dark .bg-slate-100 {
            background-color: #16191d !important;
        }

        /* Borders & Dividers */
        .portfolio-dark .border,
        .portfolio-dark .border-t,
        .portfolio-dark .border-b,
        .portfolio-dark .border-l,
        .portfolio-dark .border-r,
        .portfolio-dark .border-slate-100,
        .portfolio-dark .border-slate-200,
        .portfolio-dark .border-slate-150,
        .portfolio-dark .divide-y > :not([hidden]) ~ :not([hidden]),
        .portfolio-dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        .portfolio-dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #1f2226 !important;
        }

        /* Shadows */
        .portfolio-dark .shadow-sm,
        .portfolio-dark .shadow,
        .portfolio-dark .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3) !important;
        }

        /* Text Colors */
        .portfolio-dark .text-slate-900,
        .portfolio-dark .text-slate-800 {
            color: #f1f5f9 !important;
        }

        .portfolio-dark .text-slate-700 {
            color: #e2e8f0 !important;
        }

        .portfolio-dark .text-slate-600,
        .portfolio-dark .text-slate-500 {
            color: #94a3b8 !important;
        }

        .portfolio-dark .text-slate-400 {
            color: #475569 !important;
        }

        /* Input Controls */
        .portfolio-dark input[type="text"],
        .portfolio-dark input[type="email"],
        .portfolio-dark input[type="number"],
        .portfolio-dark input[type="password"],
        .portfolio-dark input[type="search"],
        .portfolio-dark input[type="date"],
        .portfolio-dark select,
        .portfolio-dark textarea {
            background-color: #16191d !important;
            border-color: #24282e !important;
            color: #f1f5f9 !important;
        }

        .portfolio-dark input::placeholder {
            color: #475569 !important;
        }

        .portfolio-dark input:focus,
        .portfolio-dark select:focus,
        .portfolio-dark textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        }

        /* Checkbox */
        .portfolio-dark input[type="checkbox"] {
            background-color: #16191d !important;
            border-color: #374151 !important;
        }

        /* Status Cards — Green */
        .portfolio-dark .bg-emerald-50\/50,
        .portfolio-dark .bg-emerald-50\/60,
        .portfolio-dark .bg-emerald-50 {
            background-color: rgba(6, 78, 59, 0.2) !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        .portfolio-dark .border-emerald-200 {
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        .portfolio-dark .text-emerald-700,
        .portfolio-dark .text-emerald-800,
        .portfolio-dark .text-emerald-900 {
            color: #34d399 !important;
        }

        /* Status Cards — Rose / Red */
        .portfolio-dark .bg-rose-50\/50,
        .portfolio-dark .bg-rose-50 {
            background-color: rgba(159, 18, 57, 0.2) !important;
            border-color: rgba(244, 63, 94, 0.3) !important;
        }
        .portfolio-dark .border-rose-200 {
            border-color: rgba(244, 63, 94, 0.3) !important;
        }
        .portfolio-dark .text-rose-700,
        .portfolio-dark .text-rose-800,
        .portfolio-dark .text-rose-900 {
            color: #f43f5e !important;
        }
        .portfolio-dark .text-rose-400 {
            color: #fb7185 !important;
        }

        /* Brand Accent */
        .portfolio-dark .bg-brand-100 {
            background-color: rgba(14, 165, 233, 0.2) !important;
        }
        .portfolio-dark .text-brand-700 {
            color: #38bdf8 !important;
        }
        .portfolio-dark .text-brand-600 {
            color: #38bdf8 !important;
        }
        .portfolio-dark .border-brand-600 {
            border-color: #38bdf8 !important;
        }
        .portfolio-dark .bg-brand-600 {
            background-color: #0ea5e9 !important;
        }
        .portfolio-dark .bg-brand-600:hover {
            background-color: #0284c7 !important;
        }

        /* Progress Bar */
        .portfolio-dark .bg-slate-200 {
            background-color: #24282e !important;
        }

        /* Nav Sub-tabs */
        .portfolio-dark .hover\:border-slate-300:hover {
            border-color: #475569 !important;
        }
        .portfolio-dark .hover\:text-slate-700:hover {
            color: #e2e8f0 !important;
        }
        .portfolio-dark .hover\:bg-slate-50:hover {
            background-color: #1f2226 !important;
        }
        .portfolio-dark .hover\:bg-slate-100:hover {
            background-color: #24282e !important;
        }
        .portfolio-dark .hover\:bg-slate-200:hover {
            background-color: #2b2f35 !important;
        }

        /* Dark Button overrides */
        .portfolio-dark .bg-slate-900 {
            background-color: #e2e8f0 !important;
            color: #0b0c0e !important;
        }
        .portfolio-dark .bg-slate-900:hover {
            background-color: #f1f5f9 !important;
        }

        /* Inline edit bg */
        .portfolio-dark .bg-slate-50\/50 {
            background-color: #0e1014 !important;
        }

        /* Logout button */
        .portfolio-dark .rounded-full.bg-slate-100 {
            background-color: #1f2226 !important;
            color: #cbd5e1 !important;
        }
        .portfolio-dark .rounded-full.bg-slate-100:hover {
            background-color: #2b2f35 !important;
        }

        /* Ring on avatar */
        .portfolio-dark .ring-slate-100 {
            --tw-ring-color: #1f2226 !important;
        }

        /* Scrollbar */
        .portfolio-dark ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .portfolio-dark ::-webkit-scrollbar-track {
            background: #0b0c0e;
        }
        .portfolio-dark ::-webkit-scrollbar-thumb {
            background: #24282e;
            border-radius: 4px;
        }
        .portfolio-dark ::-webkit-scrollbar-thumb:hover {
            background: #2d323a;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased portfolio-dark">
    <div class="min-h-screen flex flex-col">
        @auth
            <header class="border-b border-slate-800 bg-[#121418]/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('portfolio.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
                        <div class="hidden sm:block">
                            <div class="text-sm font-semibold leading-tight text-slate-200">{{ __('app.portfolio.heading') }}</div>
                            <div class="text-xs text-slate-500">{{ __('app.portfolio.subheading') }}</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="" referrerpolicy="no-referrer"
                                 class="h-8 w-8 rounded-full ring-2 ring-slate-700">
                        @else
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                            </div>
                        @endif
                        <span class="hidden text-sm text-slate-400 sm:inline">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('portfolio.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                {{ __('app.portfolio.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
                <div class="border-t border-slate-800 bg-[#0b0c0e]/50">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <a href="{{ route('portfolio.dashboard') }}" 
                               class="border-b-2 px-1 py-3 text-sm font-medium transition {{ request()->routeIs('portfolio.dashboard') ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-600 hover:text-slate-300' }}">
                                {{ __('app.portfolio.nav.dashboard') }}
                            </a>
                            <a href="{{ route('portfolio.budget.index') }}" 
                               class="border-b-2 px-1 py-3 text-sm font-medium transition {{ request()->routeIs('portfolio.budget.*') ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-600 hover:text-slate-300' }}">
                                {{ __('app.portfolio.nav.budget') }}
                            </a>
                            <a href="{{ route('portfolio.planner') }}" 
                               class="border-b-2 px-1 py-3 text-sm font-medium transition {{ request()->routeIs('portfolio.planner') ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-600 hover:text-slate-300' }}">
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
