<x-guest-layout>
    @php
        $email = $email ?? '';
        $step = $step ?? 'identify';
    @endphp

    @if ($step === 'signin')

        {{-- Step 2a: existing account → ask for password --}}
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('app.auth.welcome_back') }}</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ __('app.auth.signin_with_email', ['email' => $email]) }}
            <a href="{{ route('login') }}" class="text-brand-700 underline underline-offset-2 hover:text-brand-900">
                {{ __('app.auth.change_email') }}
            </a>
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-slate-900">{{ __('app.auth.password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand-700 underline-offset-2 hover:underline">
                            {{ __('app.auth.forgot_short') }}
                        </a>
                    @endif
                </div>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                           placeholder="••••••••••"
                           class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <button type="button" onclick="togglePassword('password', this)"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                        <svg class="eye-off h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <svg class="eye-on hidden h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p role="alert" class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                @error('email')
                    <p role="alert" class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                {{ __('app.auth.remember_me') }}
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                {{ __('app.auth.sign_in') }}
            </button>
        </form>

    @elseif ($step === 'signup')

        {{-- Step 2b: new account → ask for name + password --}}
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('app.auth.create_account_heading') }}</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ __('app.auth.signup_with_email', ['email' => $email]) }}
            <a href="{{ route('login') }}" class="text-brand-700 underline underline-offset-2 hover:text-brand-900">
                {{ __('app.auth.change_email') }}
            </a>
        </p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <label for="name" class="block text-sm font-medium text-slate-900">{{ __('app.auth.name') }}</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input id="name" type="text" name="name" required autofocus autocomplete="name"
                           value="{{ old('name') }}"
                           placeholder="{{ __('app.auth.name_placeholder') }}"
                           class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </div>
                @error('name')
                    <p role="alert" class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-900">{{ __('app.auth.password') }}</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                           placeholder="••••••••••"
                           class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <button type="button" onclick="togglePassword('password', this)"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                        <svg class="eye-off h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <svg class="eye-on hidden h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p role="alert" class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                @error('email')
                    <p role="alert" class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-500">{{ __('app.auth.password_hint') }}</p>
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                {{ __('app.auth.create_account') }}
            </button>
        </form>

        <p class="mt-6 text-pretty text-xs text-slate-500">
            {{ __('app.auth.terms_prefix') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.terms') }}</a>
            {{ __('app.auth.terms_and') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.privacy') }}</a>.
        </p>

    @else

        {{-- Step 1: identify email --}}
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('app.auth.unified_heading') }}</h2>
        <p class="mt-1.5 text-sm text-slate-500">{{ __('app.auth.unified_subtitle') }}</p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.identify') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-900">{{ __('app.auth.email') }}</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </span>
                    <input id="email" type="email" name="email" required autofocus autocomplete="email"
                           value="{{ old('email') }}"
                           placeholder="you@example.com"
                           class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </div>
                @error('email')
                    <p role="alert" class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                {{ __('app.auth.continue') }}
            </button>
        </form>

        <p class="mt-6 text-pretty text-xs text-slate-500">
            {{ __('app.auth.terms_prefix') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.terms') }}</a>
            {{ __('app.auth.terms_and') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.privacy') }}</a>.
        </p>

    @endif

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-off').classList.toggle('hidden', isHidden);
            btn.querySelector('.eye-on').classList.toggle('hidden', !isHidden);
        }
    </script>
</x-guest-layout>
