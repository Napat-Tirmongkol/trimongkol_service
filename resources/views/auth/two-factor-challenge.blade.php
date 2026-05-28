<x-guest-layout>
    <div class="mb-4 text-center">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('app.two_factor.challenge_heading') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('app.two_factor.challenge_intro') }}</p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-gray-700">{{ __('app.two_factor.challenge_label') }}</label>
            <input id="code" name="code" type="text" required autofocus inputmode="numeric"
                   autocomplete="one-time-code" maxlength="20"
                   class="mt-1 block w-full rounded-md border-gray-300 text-center font-mono text-lg tracking-widest shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
            {{ __('app.two_factor.challenge_submit') }}
        </button>

        <details class="text-center">
            <summary class="cursor-pointer text-xs text-slate-500 hover:text-slate-700">
                {{ __('app.two_factor.use_recovery_code') }}
            </summary>
            <p class="mt-2 text-xs text-slate-500">{{ __('app.two_factor.recovery_login_hint') }}</p>
        </details>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs text-slate-500 hover:text-slate-700 underline">
            {{ __('app.two_factor.sign_out') }}
        </button>
    </form>
</x-guest-layout>
