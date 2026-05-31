<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.billing.heading') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('app.billing.sub') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            {{-- Current plan banner --}}
            @php $current = $plans[$currentKey] ?? null; @endphp
            <div class="mb-6 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.billing.current_plan') }}</div>
                    <div class="mt-0.5 flex items-center gap-2">
                        <span class="text-lg font-semibold text-slate-900">{{ $current?->name }}</span>
                        @if ($subscription?->isOnTrial())
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                {{ __('app.billing.trial_days', ['days' => $subscription->trialDaysLeft()]) }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-sm text-slate-500">{{ __('app.billing.contact_note') }}</div>
            </div>

            {{-- Plan cards --}}
            <div class="grid gap-5 sm:grid-cols-3">
                @foreach ($plans as $key => $plan)
                    @php $isCurrent = $key === $currentKey; @endphp
                    <div class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm {{ $isCurrent ? 'border-brand-400 ring-1 ring-brand-200' : 'border-slate-200' }}">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900">{{ $plan->name }}</h3>
                            @if ($isCurrent)
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-200">{{ __('app.billing.current_badge') }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $plan->tagline }}</p>

                        <div class="mt-4">
                            <span class="text-3xl font-bold tabular-nums text-slate-900">฿{{ number_format($plan->price) }}</span>
                            <span class="text-sm text-slate-500">/ {{ __('app.billing.per_month') }}</span>
                        </div>

                        <ul class="mt-4 flex-1 space-y-2 text-sm text-slate-600">
                            @foreach ($plan->features as $feature)
                                <li class="flex items-start gap-2">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-500"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-6">
                            @if ($isCurrent)
                                <button type="button" disabled class="w-full rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">
                                    {{ __('app.billing.current_badge') }}
                                </button>
                            @else
                                <form method="POST" action="{{ route('billing.subscribe') }}"
                                      data-confirm="{{ __('app.billing.confirm', ['plan' => $plan->name]) }}">
                                    @csrf
                                    <input type="hidden" name="plan_key" value="{{ $key }}">
                                    <button type="submit"
                                            class="w-full rounded-full px-4 py-2 text-sm font-semibold transition {{ $plan->isFree() ? 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                                        {{ $plan->isFree() ? __('app.billing.switch_free') : __('app.billing.choose', ['plan' => $plan->name]) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">{{ __('app.billing.gateway_note') }}</p>
        </div>
    </div>
</x-app-layout>
