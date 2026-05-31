<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $queue->name }} — {{ __('app.queue.poster') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 py-12 text-center">
        <div class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-1.5 text-sm font-semibold text-brand-700">
            <span class="grid h-6 w-6 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">T</span>
            {{ $queue->name }}
        </div>

        <h1 class="mt-8 text-4xl font-extrabold tracking-tight md:text-5xl">{{ __('app.queue.poster_title') }}</h1>
        <p class="mt-3 max-w-md text-base text-slate-500">{{ __('app.queue.poster_hint') }}</p>

        <div class="mt-10 rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <canvas id="poster-qr"></canvas>
        </div>

        <p class="mt-6 break-all text-xs text-slate-400">{{ route('queue.public', $queue->public_token) }}</p>

        <button type="button" onclick="window.print()"
                class="mt-10 inline-flex items-center gap-2 rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-slate-800 print:hidden">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            {{ __('app.queue.print') }}
        </button>
    </div>

    <script>
        window.addEventListener('load', () => {
            const render = () => {
                if (!window.QRCode) return setTimeout(render, 50);
                window.QRCode.toCanvas(
                    document.getElementById('poster-qr'),
                    @js(route('queue.public', $queue->public_token)),
                    { width: 320, margin: 1 },
                    () => {}
                );
            };
            render();
        });
    </script>
</body>
</html>
