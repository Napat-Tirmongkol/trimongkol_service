<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.accounting.login_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">

        <div class="text-center mb-8">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white mb-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-900">{{ __('app.accounting.login_title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.accounting.login_subtitle') }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('accounting.login.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">
                    {{ __('app.accounting.login_email') }}
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100 @error('email') border-rose-400 @enderror">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">
                    {{ __('app.accounting.login_password') }}
                </label>
                <input id="password" type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600">
                {{ __('app.accounting.login_remember') }}
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('app.accounting.login_btn') }}
            </button>
        </form>

    </div>
</body>
</html>
