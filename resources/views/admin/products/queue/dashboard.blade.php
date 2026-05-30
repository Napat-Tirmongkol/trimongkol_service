<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.queue.label') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.products.queue.desc') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.queue.stat_queues') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['queues'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">+{{ $stats['queues_week'] }} {{ __('app.admin.products.queue.this_week') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.queue.stat_tickets') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['tickets'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.queue.stat_tickets_today') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['tickets_today'] }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.queue.recent') }}</h3>
                    <a href="{{ route('admin.queue.index') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.viewAll') }} →</a>
                </div>
                @if ($recent->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.queue.queues_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recent as $queue)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-slate-900">{{ $queue->name }}</div>
                                    <div class="truncate text-xs text-slate-500">
                                        @if ($queue->workspace) {{ $queue->workspace->name }} · @endif
                                        @if ($queue->user) {{ $queue->user->email }} @endif
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">{{ $queue->waiting_count }} {{ __('app.queue.waiting') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
