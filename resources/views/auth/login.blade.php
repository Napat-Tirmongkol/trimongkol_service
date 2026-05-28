<x-guest-layout>
    <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
        {{ __('app.auth.login_heading') }}
    </h2>

    @if (session('status'))
        <div class="mt-4 rounded-md bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-900">{{ __('app.auth.email') }}</label>
            <input id="email" type="email" name="email" required autofocus autocomplete="email"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-900">{{ __('app.auth.password') }}</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-slate-500 underline underline-offset-2 hover:text-slate-900">
                        {{ __('app.auth.forgot_short') }}
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••••"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
            {{ __('app.auth.remember_me') }}
        </label>

        <button type="submit" class="mt-2 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('app.auth.sign_in') }}
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-600">
            {{ __('app.auth.no_account') }}
            <a href="{{ route('register') }}" class="font-semibold text-slate-900 hover:underline">{{ __('app.auth.sign_up') }}</a>
        </p>
    @endif

    <p class="mt-6 text-pretty text-center text-xs text-slate-500">
        {{ __('app.auth.terms_prefix') }}
        <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.terms') }}</a>
        {{ __('app.auth.terms_and') }}
        <a href="{{ route('about') }}" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.privacy') }}</a>.
    </p>
</x-guest-layout>
