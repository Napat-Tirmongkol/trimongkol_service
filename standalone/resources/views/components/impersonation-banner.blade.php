@if (session('impersonator_id') && auth()->check())
    <div class="sticky top-0 z-40 bg-amber-500 text-amber-950 shadow">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2 text-sm">
            <div class="flex items-center gap-2 font-medium">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.84 5a2 2 0 00-3.68 0L3.16 16.25A2 2 0 005 19z"/>
                </svg>
                {{ __('app.admin.impersonating_as', ['name' => auth()->user()->name]) }}
            </div>
            <form method="POST" action="{{ route('impersonate.stop') }}">
                @csrf
                <button type="submit"
                        class="rounded-md bg-amber-900 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-950">
                    {{ __('app.admin.stop_impersonating') }}
                </button>
            </form>
        </div>
    </div>
@endif
