<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('site.brand.name') . ' — ' . __('site.brand.tagline'))</title>
    <meta name="description" content="@yield('description', __('site.home.heroDescription'))">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col bg-white text-slate-900">
    @include('partials.marketing-navbar')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.marketing-footer')

    @livewireScripts
</body>
</html>
