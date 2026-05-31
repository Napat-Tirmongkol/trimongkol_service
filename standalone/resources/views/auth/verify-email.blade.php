<x-guest-layout>
    <h2 class="text-balance text-center text-xl font-semibold text-slate-900">
        {{ __('app.auth.verify_heading') }}
    </h2>
    <p class="mt-1 text-center text-sm text-slate-500">
        {{ __('app.auth.verify_subtitle') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-md bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
            {{ __('app.auth.verify_resent') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('app.auth.verify_resend') }}
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-slate-500 hover:text-slate-900 hover:underline">
            {{ __('app.auth.sign_out') }}
        </button>
    </form>
</x-guest-layout>
