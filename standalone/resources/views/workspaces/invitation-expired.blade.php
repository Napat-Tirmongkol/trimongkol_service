<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-rose-50 text-rose-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('app.workspaces.invite_expired_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('app.workspaces.invite_expired_detail', ['workspace' => $invitation->workspace->name]) }}
        </p>
        <p class="mt-3 text-xs text-slate-500">{{ __('app.workspaces.invite_expired_hint') }}</p>
    </div>
</x-guest-layout>
