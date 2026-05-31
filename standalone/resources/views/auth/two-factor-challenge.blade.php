<x-guest-layout>
    <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
        {{ __('app.two_factor.challenge_heading') }}
    </h2>
    <p class="mt-1 text-center text-sm text-slate-500">
        {{ __('app.two_factor.challenge_intro') }}
    </p>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-slate-900">{{ __('app.two_factor.challenge_label') }}</label>
            <input id="code" name="code" type="text" required autofocus inputmode="numeric"
                   autocomplete="one-time-code" maxlength="20"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-center font-mono text-lg tracking-widest shadow-sm focus:border-slate-900 focus:ring-slate-900">
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('app.two_factor.challenge_submit') }}
        </button>

        <details class="text-center">
            <summary class="cursor-pointer text-xs text-slate-500 hover:text-slate-700">
                {{ __('app.two_factor.use_recovery_code') }}
            </summary>
            <p class="mt-2 text-xs text-slate-500">{{ __('app.two_factor.recovery_login_hint') }}</p>
        </details>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm text-slate-500 hover:text-slate-900 hover:underline">
            {{ __('app.two_factor.sign_out') }}
        </button>
    </form>
</x-guest-layout>
