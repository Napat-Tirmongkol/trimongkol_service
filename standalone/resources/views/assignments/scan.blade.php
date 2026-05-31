<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('app.scan.start') }}: {{ $assignment->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.product-tour')
</head>
<body class="bg-slate-900">
    <livewire:scan-dashboard :assignment="$assignment" />

    <button type="button" id="tour-start"
            class="fixed left-3 top-3 z-40 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/20 backdrop-blur hover:bg-white/20">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        {{ __('app.tour.help') }}
    </button>

    <script>
        (() => {
            const steps = [
                { title: @json(__('app.tour.scan_intro_title')), text: @json(__('app.tour.scan_intro_text')) },
                { el: '[data-tour=scan-start]', title: @json(__('app.tour.scan_start_title')), text: @json(__('app.tour.scan_start_text')), side: 'top' },
            ];
            document.getElementById('tour-start')?.addEventListener('click', () => window.startTour(steps));
            window.addEventListener('load', () => window.maybeAutoTour('scan-page', steps));
        })();
    </script>

    @livewireScripts
</body>
</html>
