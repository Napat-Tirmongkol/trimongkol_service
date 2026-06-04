<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('app.admin.heading') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Dark Theme Global Overrides for Admin Panel --}}
    <style>
        body.admin-mode {
            background-color: #0b0c0e !important;
            color: #f1f5f9 !important;
        }

        /* Card & Container Backgrounds */
        body.admin-mode .bg-white,
        body.admin-mode .bg-slate-50,
        body.admin-mode .bg-gray-50,
        body.admin-mode .bg-slate-50\/50,
        body.admin-mode .bg-gray-50\/50 {
            background-color: #121418 !important;
        }

        body.admin-mode .bg-slate-100,
        body.admin-mode .bg-gray-100 {
            background-color: #0b0c0e !important;
        }

        /* Borders & Dividers */
        body.admin-mode .border,
        body.admin-mode .border-t,
        body.admin-mode .border-b,
        body.admin-mode .border-l,
        body.admin-mode .border-r,
        body.admin-mode .border-slate-100,
        body.admin-mode .border-slate-200,
        body.admin-mode .border-gray-100,
        body.admin-mode .border-gray-200,
        body.admin-mode .divide-y > :not([hidden]) ~ :not([hidden]),
        body.admin-mode .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        body.admin-mode .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #1f2226 !important;
        }

        /* Shadow Adjustments for Dark Mode */
        body.admin-mode .shadow-sm,
        body.admin-mode .shadow,
        body.admin-mode .shadow-md,
        body.admin-mode .shadow-lg {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3) !important;
        }

        /* Text Colors */
        body.admin-mode .text-slate-900,
        body.admin-mode .text-slate-800,
        body.admin-mode .text-gray-900,
        body.admin-mode .text-gray-800,
        body.admin-mode .text-slate-700,
        body.admin-mode .text-gray-700 {
            color: #f1f5f9 !important;
        }

        body.admin-mode .text-slate-600,
        body.admin-mode .text-slate-500,
        body.admin-mode .text-gray-600,
        body.admin-mode .text-gray-500 {
            color: #94a3b8 !important;
        }

        body.admin-mode .text-slate-400,
        body.admin-mode .text-gray-400 {
            color: #475569 !important;
        }

        /* Input Controls */
        body.admin-mode input[type="text"],
        body.admin-mode input[type="email"],
        body.admin-mode input[type="password"],
        body.admin-mode input[type="search"],
        body.admin-mode input[type="date"],
        body.admin-mode select,
        body.admin-mode textarea {
            background-color: #16191d !important;
            border-color: #24282e !important;
            color: #f1f5f9 !important;
        }

        body.admin-mode input::placeholder {
            color: #475569 !important;
        }

        body.admin-mode input:focus,
        body.admin-mode select:focus,
        body.admin-mode textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        }

        /* Tables */
        body.admin-mode thead tr,
        body.admin-mode thead th {
            background-color: #181c22 !important;
            color: #94a3b8 !important;
        }

        body.admin-mode tbody tr:hover {
            background-color: #171b20 !important;
        }

        /* Status Cards (Green/Blue/Red/Purple) */
        body.admin-mode .bg-emerald-50\/50,
        body.admin-mode .bg-emerald-50 {
            background-color: rgba(6, 78, 59, 0.2) !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        body.admin-mode .text-emerald-700,
        body.admin-mode .text-emerald-800 {
            color: #34d399 !important;
        }

        body.admin-mode .bg-sky-50\/50,
        body.admin-mode .bg-sky-50 {
            background-color: rgba(12, 74, 110, 0.2) !important;
            border-color: rgba(14, 165, 233, 0.3) !important;
        }
        body.admin-mode .text-sky-700,
        body.admin-mode .text-sky-800 {
            color: #38bdf8 !important;
        }

        body.admin-mode .bg-rose-50\/50,
        body.admin-mode .bg-rose-50 {
            background-color: rgba(159, 18, 57, 0.2) !important;
            border-color: rgba(244, 63, 94, 0.3) !important;
        }
        body.admin-mode .text-rose-700,
        body.admin-mode .text-rose-800 {
            color: #f43f5e !important;
        }

        body.admin-mode .bg-gradient-to-br.from-violet-50.to-violet-100,
        body.admin-mode .bg-violet-50 {
            background-image: none !important;
            background-color: rgba(88, 28, 135, 0.2) !important;
            border-color: rgba(139, 92, 246, 0.3) !important;
        }
        body.admin-mode .text-violet-700,
        body.admin-mode .text-violet-800,
        body.admin-mode .text-violet-900 {
            color: #a78bfa !important;
        }

        /* Primary Accent Text / Brand colors */
        body.admin-mode .bg-brand-50\/50,
        body.admin-mode .bg-brand-50 {
            background-color: rgba(14, 165, 233, 0.2) !important;
            border-color: rgba(14, 165, 233, 0.3) !important;
        }

        body.admin-mode .text-brand-700,
        body.admin-mode .text-brand-800 {
            color: #38bdf8 !important;
        }

        /* Buttons overrides */
        body.admin-mode .bg-slate-100.text-slate-700,
        body.admin-mode .bg-gray-100.text-gray-700 {
            background-color: #1f2226 !important;
            color: #cbd5e1 !important;
        }
        body.admin-mode .bg-slate-100.text-slate-700:hover,
        body.admin-mode .bg-gray-100.text-gray-700:hover {
            background-color: #2b2f35 !important;
            color: #f1f5f9 !important;
        }

        /* Custom scrollbar */
        body.admin-mode ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        body.admin-mode ::-webkit-scrollbar-track {
            background: #0b0c0e;
        }
        body.admin-mode ::-webkit-scrollbar-thumb {
            background: #24282e;
            border-radius: 4px;
        }
        body.admin-mode ::-webkit-scrollbar-thumb:hover {
            background: #2d323a;
        }

        /* Toggle switches / badges */
        body.admin-mode .bg-slate-200 {
            background-color: #24282e !important;
        }
    </style>
    @livewireStyles
</head>
<body x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-950 text-slate-100 antialiased admin-mode">

    <div class="flex min-h-screen">
        {{-- Sidebar Nav --}}
        @include('layouts.admin-navigation')

        {{-- Main Area --}}
        <div class="flex flex-1 flex-col lg:pl-64">
            
            {{-- Top Header Bar --}}
            <header class="sticky top-0 z-20 flex min-h-16 py-3 shrink-0 items-center justify-between border-b border-slate-800 bg-[#121418]/90 backdrop-blur px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    {{-- Hamburger menu for mobile --}}
                    <button type="button" @click="sidebarOpen = true" class="text-slate-400 hover:text-white lg:hidden">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <div class="text-base font-semibold text-slate-200">
                        @isset($header)
                            {{ $header }}
                        @else
                            {{ __('app.admin.heading') }}
                        @endisset
                    </div>
                </div>

                {{-- Quick links & Profile --}}
                <div class="flex items-center gap-4">
                    {{-- Link to main web --}}
                    <a href="{{ route('home') }}" title="{{ __('app.nav.website_home') }}"
                       class="rounded-full p-2 text-slate-400 hover:bg-slate-800 hover:text-white transition">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </a>
                    
                    <span class="hidden text-sm text-slate-300 sm:inline">{{ auth()->user()->name }}</span>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-full bg-slate-800 px-4 py-1.5 text-xs font-semibold text-white hover:bg-slate-700 transition">
                            {{ __('app.admin.logout') }}
                        </button>
                    </form>
                </div>
            </header>

            {{-- Main Page Slot --}}
            <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('partials.sweetalert')
    @livewireScripts
</body>
</html>
