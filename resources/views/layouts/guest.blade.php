<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <a href="/" class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-md shadow-brand-500/30">T</span>
                    <span class="text-sm font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </a>

                @php $otherLocale = app()->getLocale() === 'th' ? 'en' : 'th'; @endphp
                <a href="{{ route('locale.switch', $otherLocale) }}"
                   class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 hover:bg-slate-50">
                    {{ $otherLocale }}
                </a>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @include('partials.sweetalert')
    </body>
</html>
