<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.tab_billing') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.billing.heading') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.billing.subheading') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Overview --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.tab_workspaces') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['workspaces'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.billing.queue_mrr') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">฿{{ number_format($stats['queue_mrr']) }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.billing.mrr_hint') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.billing.free_mode') }}</div>
                    <div class="mt-2 text-2xl font-bold {{ $stats['free_mode'] ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $stats['free_mode'] ? __('app.admin.billing.free_mode_on') : __('app.admin.billing.free_mode_off') }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.billing.free_mode_scope') }}</div>
                </div>
            </div>

            {{-- Free-launch toggle (Scanner) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.billing.free_mode') }}</h3>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.billing.free_mode_desc') }}</p>
                <form method="POST" action="{{ route('admin.billing.update') }}" class="mt-4 flex flex-wrap items-center gap-3">
                    @csrf
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="free_mode" value="0">
                        <input type="checkbox" name="free_mode" value="1" @checked($stats['free_mode'])
                               class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">{{ __('app.admin.billing.free_mode_label') }}</span>
                    </label>
                    <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.common.save') }}</button>
                    <a href="{{ route('admin.site-settings.edit') }}" class="text-xs text-slate-500 hover:text-slate-700">{{ __('app.admin.billing.launch_caps_link') }} →</a>
                </form>
            </div>

            {{-- Queue plan distribution --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.billing.queue_plans') }}</h3>
                    <a href="{{ route('admin.workspaces.index') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.billing.manage_workspaces') }} →</a>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($queuePlans as $key => $plan)
                        <li class="flex items-center justify-between px-6 py-3">
                            <div>
                                <span class="text-sm font-semibold text-slate-900">{{ $plan['name'] }}</span>
                                <span class="ml-2 text-xs text-slate-500">
                                    {{ $plan['price'] === null ? __('app.admin.billing.custom_price') : '฿'.number_format($plan['price']).'/'.__('app.admin.billing.per_month') }}
                                </span>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-sm font-semibold text-slate-700">
                                {{ $stats['queue_by_plan'][$key] ?? 0 }} {{ __('app.admin.billing.workspaces_unit') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Scanner plan distribution --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.billing.scanner_plans') }}</h3>
                </div>
                @if (empty($stats['scanner_by_plan']))
                    <div class="px-6 py-6 text-center text-sm text-slate-500">{{ __('app.admin.billing.no_subscriptions') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($stats['scanner_by_plan'] as $key => $count)
                            <li class="flex items-center justify-between px-6 py-3">
                                <span class="text-sm font-medium text-slate-800">{{ ucfirst($key) }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-sm font-semibold text-slate-700">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <p class="text-center text-xs text-slate-400">{{ __('app.admin.billing.manual_note') }}</p>
        </div>
    </div>
</x-admin-layout>
