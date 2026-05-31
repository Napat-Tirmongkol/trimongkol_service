<x-guest-layout>
    <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
        {{ __('app.auth.register_heading') }}
    </h2>
    <p class="mt-1 text-center text-sm text-slate-500">
        {{ __('app.auth.register_subtitle') }}
    </p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-900">{{ __('app.auth.name') }}</label>
            <input id="name" type="text" name="name" required autofocus autocomplete="name"
                   value="{{ old('name') }}"
                   placeholder="{{ __('app.auth.name_placeholder') }}"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-900">{{ __('app.auth.email') }}</label>
            <input id="email" type="email" name="email" required autocomplete="email"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-900">{{ __('app.auth.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   placeholder="••••••••••"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-900">{{ __('app.auth.confirm_password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   placeholder="••••••••••"
                   class="mt-2 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-900 focus:ring-slate-900">
            @error('password_confirmation') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="mt-2 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('app.auth.create_account') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-slate-900 hover:underline">{{ __('app.auth.sign_in') }}</a>
    </p>

    <p class="mt-6 text-pretty text-center text-xs text-slate-500">
        {{ __('app.auth.terms_prefix') }}
        <a href="#" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.terms') }}</a>
        {{ __('app.auth.terms_and') }}
        <a href="#" class="underline underline-offset-2 hover:text-slate-700">{{ __('app.auth.privacy') }}</a>.
    </p>
</x-guest-layout>
