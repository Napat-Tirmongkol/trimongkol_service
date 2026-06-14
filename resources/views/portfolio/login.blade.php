<x-portfolio-layout>
    <div class="flex min-h-[calc(100vh-1px)] items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
                <div class="flex flex-col items-center text-center">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto">
                    <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">
                        {{ __('app.portfolio.login.title') }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('app.portfolio.login.subtitle') }}
                    </p>
                </div>

                @if (session('error'))
                    <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                <a href="{{ route('portfolio.auth.google') }}"
                   class="mt-8 inline-flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                        <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C16.318 4 9.656 8.337 6.306 14.691z"/>
                        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                    </svg>
                    {{ __('app.portfolio.login.google_button') }}
                </a>

                <p class="mt-6 text-center text-xs text-slate-500">
                    {{ __('app.portfolio.login.allowlist_hint') }}
                </p>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">
                <a href="{{ url('/') }}" class="hover:text-slate-600">← {{ __('app.portfolio.login.back_to_site') }}</a>
            </p>
        </div>
    </div>
</x-portfolio-layout>
