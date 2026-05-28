<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.workspaces.invite_landing') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-md px-4">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-center">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-50 text-brand-600">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $invitation->workspace->name }}</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('app.workspaces.invite_intro_v2', [
                            'inviter' => $invitation->inviter?->name ?: __('app.workspaces.invite_unknown_inviter'),
                            'role' => __("app.workspaces.role_{$invitation->role}"),
                        ]) }}
                    </p>
                </div>

                @if ($mismatchedEmail)
                    <div class="mt-6 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-inset ring-rose-200">
                        {{ __('app.workspaces.invite_email_mismatch_detail', [
                            'invited' => $invitation->email,
                            'current' => auth()->user()->email,
                        ]) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('app.workspaces.invite_signout_and_continue') }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('workspace-invitations.accept', $invitation->token) }}" class="mt-6">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            {{ __('app.workspaces.invite_accept_button') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
