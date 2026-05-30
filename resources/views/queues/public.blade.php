<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $queue->name }} — {{ __('app.queue.get_ticket') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950">
    <div class="bg-grid pointer-events-none fixed inset-0 opacity-10" aria-hidden="true"></div>

    <main class="relative flex min-h-screen flex-col items-center justify-center py-8">
        <livewire:queue-public :queue="$queue" />

        <p class="mt-6 text-center text-xs text-white/40">{{ config('app.name') }}</p>
    </main>

    @include('partials.sweetalert')
    @livewireScripts
</body>
</html>
