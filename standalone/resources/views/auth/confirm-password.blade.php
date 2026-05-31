<x-guest-layout>
    <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
        {{ __('app.auth.confirm_heading') }}
    </h2>
    <p class="mt-1 text-center text-sm text-slate-500">
        {{ __('app.auth.confirm_subtitle') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-slate-900">{{ __('app.auth.password') }}</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                   placeholder="••••••••••"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="mt-2 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('app.auth.confirm_submit') }}
        </button>
    </form>
</x-guest-layout>
