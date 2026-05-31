<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-50 text-brand-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $invitation->workspace->name }}</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('app.workspaces.invite_intro_v2', [
                'inviter' => $invitation->inviter?->name ?: __('app.workspaces.invite_unknown_inviter'),
                'role' => __("app.workspaces.role_{$invitation->role}"),
            ]) }}
        </p>
        <p class="mt-2 text-sm text-slate-500">
            {{ __('app.workspaces.invite_email_target', ['email' => $invitation->email]) }}
        </p>
    </div>

    <div class="mt-6 space-y-2">
        <a href="{{ route('register') }}"
           class="block w-full rounded-md bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-brand-700">
            {{ __('app.workspaces.invite_register_button') }}
        </a>
        <a href="{{ route('login') }}"
           class="block w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
            {{ __('app.workspaces.invite_login_button') }}
        </a>
        <p class="mt-3 text-center text-xs text-slate-500">{{ __('app.workspaces.invite_guest_hint') }}</p>
    </div>
</x-guest-layout>
