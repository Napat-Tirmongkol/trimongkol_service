@php
    $workspace = auth()->check() ? \App\Services\CurrentWorkspace::get() : null;
    $sub = $workspace?->subscription;
@endphp

@if ($sub && $sub->isOnTrial() && ! \App\Services\Billing::freeMode())
    <div class="bg-amber-500 text-amber-950">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-2 text-sm">
            <div class="flex items-center gap-2 font-medium">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                {{ __('app.plans.trial_banner', [
                    'plan' => \App\Models\Plan::find($sub->plan_key)->name,
                    'days' => $sub->trialDaysLeft(),
                ]) }}
            </div>
            <a href="{{ route('plans.index') }}" class="rounded-md bg-amber-900 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-950">
                {{ __('app.plans.banner_cta') }}
            </a>
        </div>
    </div>
@endif
