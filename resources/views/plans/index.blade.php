<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.plans.heading') }}</h2>
            @if ($workspace)
                <p class="mt-1 text-sm text-slate-500">{{ __('app.plans.subtitle', ['workspace' => $workspace->name]) }}</p>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            @if ($subscription?->isOnTrial())
                <div class="mb-6 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-inset ring-amber-200">
                    {{ __('app.plans.trial_status', [
                        'plan' => \App\Models\Plan::find($subscription->plan_key)->name,
                        'days' => $subscription->trialDaysLeft(),
                    ]) }}
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($plans as $key => $plan)
                    @php
                        $isCurrent = $key === $currentPlanKey;
                        $tone = match ($key) {
                            'free' => 'slate',
                            'basic' => 'sky',
                            'pro' => 'brand',
                            default => 'slate',
                        };
                        $cardClass = $isCurrent
                            ? ($tone === 'brand' ? 'border-brand-500 ring-2 ring-brand-500 bg-white' : ($tone === 'sky' ? 'border-sky-500 ring-2 ring-sky-500 bg-white' : 'border-slate-300 ring-2 ring-slate-300 bg-white'))
                            : 'border-slate-200 bg-white';
                    @endphp
                    <div class="rounded-2xl border {{ $cardClass }} p-6 shadow-sm">
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-lg font-bold {{ $tone === 'brand' ? 'text-brand-700' : ($tone === 'sky' ? 'text-sky-700' : 'text-slate-900') }}">{{ $plan->name }}</h3>
                            @if ($isCurrent)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    {{ __('app.plans.current_plan') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $plan->tagline }}</p>

                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-slate-900">฿{{ number_format($plan->price) }}</span>
                            @if ($plan->price > 0)
                                <span class="text-sm text-slate-500">/ {{ __('app.plans.month') }}</span>
                            @else
                                <span class="text-sm text-slate-500">{{ __('app.plans.forever') }}</span>
                            @endif
                        </div>

                        <ul class="mt-5 space-y-2 text-sm text-slate-700">
                            @foreach ($plan->features as $feature)
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $tone === 'brand' ? 'text-brand-600' : ($tone === 'sky' ? 'text-sky-600' : 'text-emerald-600') }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-6">
                            @if ($isCurrent)
                                <button type="button" disabled class="w-full rounded-md border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-500">
                                    {{ __('app.plans.current_plan') }}
                                </button>
                            @elseif ($plan->key === 'free')
                                <p class="text-center text-xs text-slate-500">{{ __('app.plans.contact_to_downgrade') }}</p>
                            @else
                                <a href="{{ route('contact') }}"
                                   class="block w-full rounded-md {{ $tone === 'brand' ? 'bg-brand-600 hover:bg-brand-700' : 'bg-sky-600 hover:bg-sky-700' }} px-4 py-2 text-center text-sm font-semibold text-white">
                                    {{ __('app.plans.upgrade_cta') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-xs text-slate-500">{{ __('app.plans.contact_note') }}</p>
        </div>
    </div>
</x-app-layout>
