<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('queues.index') }}" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.queue.billing.heading') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.queue.billing.subheading') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <div class="flex-1 font-medium">{{ session('status') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div class="flex-1 font-medium">{{ session('error') }}</div>
                </div>
            @endif

            {{-- Current plan --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.queue.billing.current_plan') }}</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="text-lg font-bold text-slate-900">{{ config("queue-plans.{$currentKey}.name") }}</span>
                            @if ($isExpired)
                                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">{{ __('app.queue.billing.expired') }}</span>
                            @elseif ($expiresAt)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.queue.billing.until', ['date' => $expiresAt->format('d M Y')]) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($pending)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ __('app.queue.billing.pending_notice', ['plan' => config("queue-plans.{$pending->plan_key}.name")]) }}
                </div>
            @endif

            @unless ($billingEnabled)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    {{ __('app.queue.billing.disabled_notice') }}
                </div>
            @endunless

            {{-- Plans --}}
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($plans as $key => $plan)
                    <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-lg font-bold text-slate-900">{{ $plan['name'] }}</h3>
                            <div class="text-right">
                                <span class="text-2xl font-extrabold text-slate-900">฿{{ number_format($plan['price']) }}</span>
                                <span class="text-sm text-slate-500">/{{ __('app.plans.month') }}</span>
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ app()->getLocale() === 'en' ? ($plan['tagline_en'] ?? $plan['tagline']) : $plan['tagline'] }}</p>

                        <ul class="mt-4 flex-1 space-y-2">
                            @foreach ((app()->getLocale() === 'en' ? ($plan['features_en'] ?? $plan['features']) : $plan['features']) as $feature)
                                <li class="flex items-start gap-2 text-sm text-slate-700">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        @if ($billingEnabled)
                            <a href="{{ route('queues.billing.pay', $key) }}"
                               class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                                {{ $currentKey === $key && ! $isExpired ? __('app.queue.billing.renew') : __('app.queue.billing.choose') }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="text-center text-xs text-slate-400">{{ __('app.queue.billing.enterprise_note') }}</p>
        </div>
    </div>
</x-app-layout>
