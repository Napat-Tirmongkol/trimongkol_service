<x-guest-layout>
    @php
        $email = $email ?? '';
        $step = $step ?? 'identify';
    @endphp

    @if ($step === 'signin')

        {{-- Step 2a: existing account → ask for password --}}
        <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
            {{ __('app.auth.welcome_back') }}
        </h2>
        <p class="mt-1 text-center text-sm text-slate-500">
            {{ __('app.auth.signin_with_email', ['email' => $email]) }}
            <a href="{{ route('login') }}" class="ml-1 text-slate-700 underline underline-offset-2 hover:text-slate-900">
                {{ __('app.auth.change_email') }}
            </a>
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-md bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
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
                        <a href="{{ route('password.request') }}" class="text-xs text-slate-500 underline underline-offset-2 hover:text-slate-900">
                            {{ __('app.auth.forgot_short') }}
                        </a>
                    @endif
                </div>
                <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                       placeholder="••••••••••"
                       class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                {{ __('app.auth.remember_me') }}
            </label>

            <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                {{ __('app.auth.sign_in') }}
            </button>
        </form>

    @elseif ($step === 'signup')

        {{-- Step 2b: new account → ask for name + password --}}
        <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
            {{ __('app.auth.create_account_heading') }}
        </h2>
        <p class="mt-1 text-center text-sm text-slate-500">
            {{ __('app.auth.signup_with_email', ['email' => $email]) }}
            <a href="{{ route('login') }}" class="ml-1 text-slate-700 underline underline-offset-2 hover:text-slate-900">
                {{ __('app.auth.change_email') }}
            </a>
        </p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <label for="name" class="block text-sm font-medium text-slate-900">{{ __('app.auth.name') }}</label>
                <input id="name" type="text" name="name" required autofocus autocomplete="name"
                       value="{{ old('name') }}"
                       placeholder="{{ __('app.auth.name_placeholder') }}"
                       class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-900">{{ __('app.auth.password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       placeholder="••••••••••"
                       class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-500">{{ __('app.auth.password_hint') }}</p>
            </div>

            <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                {{ __('app.auth.create_account') }}
            </button>
        </form>

        <p class="mt-6 text-pretty text-center text-xs text-slate-500">
            {{ __('app.auth.terms_prefix') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.terms') }}</a>
            {{ __('app.auth.terms_and') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.privacy') }}</a>.
        </p>

    @else

        {{-- Step 1: identify email --}}
        <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
            {{ __('app.auth.unified_heading') }}
        </h2>
        <p class="mt-1 text-center text-sm text-slate-500">
            {{ __('app.auth.unified_subtitle') }}
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-md bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.identify') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-900">{{ __('app.auth.email') }}</label>
                <input id="email" type="email" name="email" required autofocus autocomplete="email"
                       value="{{ old('email') }}"
                       placeholder="you@example.com"
                       class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                {{ __('app.auth.continue') }}
            </button>
        </form>

        <p class="mt-6 text-pretty text-center text-xs text-slate-500">
            {{ __('app.auth.terms_prefix') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.terms') }}</a>
            {{ __('app.auth.terms_and') }}
            <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.privacy') }}</a>.
        </p>

    @endif
</x-guest-layout>
