<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1f47e6">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('app.classrooms.heading')) — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.product-tour')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <x-impersonation-banner />
    <x-trial-banner />
    @include('layouts.navigation')

    @isset($header)
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main class="pb-16">
        {{ $slot }}
    </main>

    @include('partials.sweetalert')
    @livewireScripts
</body>
</html>
