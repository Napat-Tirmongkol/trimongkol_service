<x-guest-layout>
    <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
        {{ __('app.auth.forgot_heading') }}
    </h2>
    <p class="mt-1 text-center text-sm text-slate-500">
        {{ __('app.auth.forgot_subtitle') }}
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-md bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-900">{{ __('app.auth.email') }}</label>
            <input id="email" type="email" name="email" required autofocus autocomplete="email"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="mt-2 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('app.auth.send_reset_link') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-900 hover:underline">← {{ __('app.auth.back_to_login') }}</a>
    </p>
</x-guest-layout>
