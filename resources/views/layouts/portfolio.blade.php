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
            background-color: #141720 !important;
            border-color: #1e2231 !important;
        }

        .portfolio-dark .bg-slate-50,
        .portfolio-dark .bg-slate-50\/50 {
            background-color: #0d0f14 !important;
        }

        .portfolio-dark .bg-slate-100 {
            background-color: #181c28 !important;
        }

        /* Borders & Dividers */
        .portfolio-dark .border,
        .portfolio-dark .border-t,
        .portfolio-dark .border-b,
        .portfolio-dark .border-l,
        .portfolio-dark .border-r,
        .portfolio-dark .border-slate-100,
        .portfolio-dark .border-slate-200,
        .portfolio-dark .border-slate-300,
        .portfolio-dark .divide-y > :not([hidden]) ~ :not([hidden]),
        .portfolio-dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        .portfolio-dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #1e2231 !important;
        }

        /* Shadows — deeper glow for dark surfaces */
        .portfolio-dark .shadow-sm,
        .portfolio-dark .shadow,
        .portfolio-dark .shadow-md {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4), 0 0 1px rgba(0, 0, 0, 0.5) !important;
        }

        /* Rounded cards — subtle border glow */
        .portfolio-dark .rounded-2xl.border {
            border-color: #242a3a !important;
        }

        /* Text Colors */
        .portfolio-dark .text-slate-900,
        .portfolio-dark .text-slate-800 {
            color: #f1f5f9 !important;
        }

        .portfolio-dark .text-slate-700 {
            color: #cbd5e1 !important;
        }

        .portfolio-dark .text-slate-600,
        .portfolio-dark .text-slate-500 {
            color: #8896ab !important;
        }

        .portfolio-dark .text-slate-400 {
            color: #566175 !important;
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
            background-color: #0d0f14 !important;
            border-color: #2a3042 !important;
            color: #f1f5f9 !important;
        }

        .portfolio-dark input::placeholder {
            color: #566175 !important;
        }

        .portfolio-dark input:focus,
        .portfolio-dark select:focus,
        .portfolio-dark textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
        }

        /* Select arrow for dark bg */
        .portfolio-dark select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7f99' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
        }

        /* Checkbox */
        .portfolio-dark input[type="checkbox"] {
            background-color: #0d0f14 !important;
            border-color: #2a3042 !important;
        }

        /* Status Cards — Green */
        .portfolio-dark .bg-emerald-50\/50,
        .portfolio-dark .bg-emerald-50\/60,
        .portfolio-dark .bg-emerald-50 {
            background-color: rgba(6, 95, 70, 0.15) !important;
        }
        .portfolio-dark .border-emerald-200 {
            border-color: rgba(16, 185, 129, 0.25) !important;
        }
        .portfolio-dark .text-emerald-700,
        .portfolio-dark .text-emerald-800,
        .portfolio-dark .text-emerald-900 {
            color: #34d399 !important;
        }
        .portfolio-dark .bg-emerald-500 {
            background-color: #10b981 !important;
        }

        /* Status Cards — Rose / Red */
        .portfolio-dark .bg-rose-50\/50,
        .portfolio-dark .bg-rose-50 {
            background-color: rgba(159, 18, 57, 0.15) !important;
        }
        .portfolio-dark .border-rose-200 {
            border-color: rgba(244, 63, 94, 0.25) !important;
        }
        .portfolio-dark .text-rose-700,
        .portfolio-dark .text-rose-800,
        .portfolio-dark .text-rose-900 {
            color: #fb7185 !important;
        }
        .portfolio-dark .text-rose-400 {
            color: #fb7185 !important;
        }

        /* Amber / Warning */
        .portfolio-dark .bg-amber-50 {
            background-color: rgba(120, 80, 0, 0.15) !important;
        }
        .portfolio-dark .border-amber-200 {
            border-color: rgba(245, 158, 11, 0.25) !important;
        }
        .portfolio-dark .text-amber-700,
        .portfolio-dark .text-amber-800 {
            color: #fbbf24 !important;
        }

        /* Brand Accent */
        .portfolio-dark .bg-brand-100 {
            background-color: rgba(14, 165, 233, 0.15) !important;
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
        .portfolio-dark .bg-brand-600:hover,
        .portfolio-dark .hover\:bg-brand-700:hover {
            background-color: #0284c7 !important;
        }

        /* Progress Bar bg */
        .portfolio-dark .bg-slate-200 {
            background-color: #1e2231 !important;
        }

        /* ── Buttons ───────────────────────────────────── */

        /* Primary dark button (bg-slate-900 text-white) → keep as-is, just lighten slightly */
        .portfolio-dark .bg-slate-900.text-white {
            background-color: #e2e8f0 !important;
            color: #0b0c0e !important;
        }
        .portfolio-dark .bg-slate-900.text-white:hover,
        .portfolio-dark .hover\:bg-slate-800:hover {
            background-color: #f8fafc !important;
            color: #0b0c0e !important;
        }

        /* Cancel/secondary buttons (bg-slate-200 text-slate-700) */
        .portfolio-dark .bg-slate-200.text-slate-700 {
            background-color: #1e2231 !important;
            color: #cbd5e1 !important;
        }

        /* Icon add buttons (+) */
        .portfolio-dark button.rounded-lg.border.border-slate-200 {
            border-color: #2a3042 !important;
            color: #8896ab !important;
        }
        .portfolio-dark button.rounded-lg.border.border-slate-200:hover {
            background-color: #1e2231 !important;
            color: #f1f5f9 !important;
        }

        /* Inline form bg */
        .portfolio-dark .rounded-xl.bg-slate-50,
        .portfolio-dark .bg-slate-50.p-2\.5 {
            background-color: #0d0f14 !important;
            border-color: #1e2231 !important;
        }

        /* Hover states */
        .portfolio-dark .hover\:bg-slate-50:hover {
            background-color: #181c28 !important;
        }
        .portfolio-dark .hover\:bg-slate-100:hover {
            background-color: #1e2231 !important;
        }
        .portfolio-dark .hover\:bg-slate-200:hover {
            background-color: #242a3a !important;
        }

        /* Nav Sub-tabs */
        .portfolio-dark .hover\:border-slate-600:hover {
            border-color: #475569 !important;
        }
        .portfolio-dark .hover\:text-slate-300:hover {
            color: #cbd5e1 !important;
        }

        /* Logout button */
        .portfolio-dark .rounded-full.bg-slate-100 {
            background-color: #1e2231 !important;
            color: #cbd5e1 !important;
        }
        .portfolio-dark .rounded-full.bg-slate-100:hover {
            background-color: #242a3a !important;
        }

        /* Ring on avatar */
        .portfolio-dark .ring-slate-700 {
            --tw-ring-color: #2a3042 !important;
        }

        /* Auto-calc badge */
        .portfolio-dark .bg-slate-100.rounded.border.border-slate-200 {
            background-color: #1e2231 !important;
            border-color: #2a3042 !important;
            color: #8896ab !important;
        }

        /* line-through items */
        .portfolio-dark .line-through {
            color: #3d4657 !important;
        }

        /* Scrollbar */
        .portfolio-dark ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .portfolio-dark ::-webkit-scrollbar-track {
            background: #0a0c10;
        }
        .portfolio-dark ::-webkit-scrollbar-thumb {
            background: #1e2231;
            border-radius: 4px;
        }
        .portfolio-dark ::-webkit-scrollbar-thumb:hover {
            background: #2a3042;
        }
    </style>

    <script>
        // Restore theme before paint to prevent flash
        (function(){
            var d = localStorage.getItem('portfolio_dark');
            if (d === null) d = '1'; // default dark
            if (d === '1') document.documentElement.classList.add('portfolio-dark-pre');
        })();
    </script>
</head>
<body x-data="{
        dark: localStorage.getItem('portfolio_dark') !== '0',
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('portfolio_dark', this.dark ? '1' : '0');
        }
     }"
     x-init="$watch('dark', v => v
         ? document.documentElement.classList.add('portfolio-dark-pre')
         : document.documentElement.classList.remove('portfolio-dark-pre')
     )"
     :class="dark
         ? 'min-h-screen bg-slate-950 text-slate-100 antialiased portfolio-dark'
         : 'min-h-screen bg-slate-50 text-slate-900 antialiased'"
>
    <div class="min-h-screen flex flex-col">
        @auth
            <header :class="dark
                ? 'border-b border-slate-800 bg-[#121418]/90 backdrop-blur'
                : 'border-b border-slate-200 bg-white'">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('portfolio.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
                        <div class="hidden sm:block">
                            <div class="text-sm font-semibold leading-tight" :class="dark ? 'text-slate-200' : 'text-slate-900'">{{ __('app.portfolio.heading') }}</div>
                            <div class="text-xs text-slate-500">{{ __('app.portfolio.subheading') }}</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        {{-- Dark Mode Toggle --}}
                        <button @click="toggle()" type="button"
                                class="relative flex h-8 w-8 items-center justify-center rounded-full transition"
                                :class="dark ? 'text-amber-400 hover:bg-slate-800' : 'text-slate-500 hover:bg-slate-100'"
                                :title="dark ? 'เปลี่ยนเป็น Light Mode' : 'เปลี่ยนเป็น Dark Mode'">
                            {{-- Sun icon (shown in dark mode → click to go light) --}}
                            <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            {{-- Moon icon (shown in light mode → click to go dark) --}}
                            <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="" referrerpolicy="no-referrer"
                                 class="h-8 w-8 rounded-full ring-2" :class="dark ? 'ring-slate-700' : 'ring-slate-100'">
                        @else
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                            </div>
                        @endif
                        <span class="hidden text-sm sm:inline" :class="dark ? 'text-slate-400' : 'text-slate-700'">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('portfolio.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                                    :class="dark
                                        ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                                {{ __('app.portfolio.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
                <div :class="dark
                    ? 'border-t border-slate-800 bg-[#0b0c0e]/50'
                    : 'border-t border-slate-200 bg-slate-50/50'">
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
