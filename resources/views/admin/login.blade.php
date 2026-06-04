<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('app.admin.portalTitle') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-950 to-brand-950" aria-hidden="true"></div>
        <div class="bg-grid absolute inset-0 opacity-10" aria-hidden="true"></div>
        <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-brand-600 opacity-20 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-brand-800 opacity-20 blur-3xl" aria-hidden="true"></div>

        <div class="relative flex min-h-screen items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                {{-- Brand --}}
                <div class="mb-8 text-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-white/80 hover:text-white">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto object-contain">
                        <span class="text-lg font-semibold">{{ config('app.name') }}</span>
                    </a>
                </div>

                {{-- Card --}}
                <div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
                    <div class="mb-6 text-center">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/20 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-300 ring-1 ring-amber-300/30">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            {{ __('app.admin.portalLabel') }}
                        </span>
                        <h1 class="mt-4 text-2xl font-bold">{{ __('app.admin.portalTitle') }}</h1>
                        <p class="mt-1 text-sm text-slate-400">{{ __('app.admin.portalSubtitle') }}</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-rose-500/10 px-4 py-3 text-sm text-rose-200 ring-1 ring-rose-500/30">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="block text-xs font-medium uppercase tracking-wider text-slate-400">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                                   value="{{ old('email') }}"
                                   class="mt-1.5 block w-full rounded-lg border border-white/15 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-brand-400 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-medium uppercase tracking-wider text-slate-400">{{ __('Password') }}</label>
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                   class="mt-1.5 block w-full rounded-lg border border-white/15 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-brand-400 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" name="remember"
                                   class="h-4 w-4 rounded border-white/30 bg-white/10 text-brand-500 focus:ring-brand-500/30">
                            {{ __('Remember me') }}
                        </label>

                        <button type="submit"
                                class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700">
                            {{ __('app.admin.signIn') }}
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-500">
                    {{ __('app.admin.notAdmin') }}
                    <a href="{{ route('login') }}" class="text-slate-300 underline hover:text-white">{{ __('app.admin.regularLogin') }}</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
