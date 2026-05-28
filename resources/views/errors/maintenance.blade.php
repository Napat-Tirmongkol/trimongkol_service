<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.admin.maintenance_title') }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-50 text-slate-900">
    <div class="max-w-md px-6 text-center">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-amber-100 text-amber-700">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="mt-6 text-2xl font-bold">{{ __('app.admin.maintenance_title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $message ?: __('app.admin.maintenance_default') }}</p>
    </div>
</body>
</html>
