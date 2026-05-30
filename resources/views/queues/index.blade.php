<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.queue.heading') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.queue.subheading') }}</p>
            </div>
            @if ($workspace)
                <a href="{{ route('queues.create') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('app.queue.create') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (! $workspace)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    {{ __('app.workspaces.no_workspace') }}
                </div>
            @elseif ($queues->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('app.queue.empty_title') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.queue.empty_desc') }}</p>
                    <a href="{{ route('queues.create') }}"
                       class="mt-6 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('app.queue.create') }}
                    </a>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($queues as $queue)
                        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('queues.show', $queue) }}" class="block truncate text-lg font-semibold text-slate-900 hover:text-brand-700">{{ $queue->name }}</a>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                        @if ($queue->prefix)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono font-semibold text-slate-600">{{ $queue->prefix }}001</span>
                                        @endif
                                        @unless ($queue->is_active)
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 font-medium text-rose-600">{{ __('app.queue.closed') }}</span>
                                        @endunless
                                    </div>
                                </div>
                                <a href="{{ route('queues.edit', $queue) }}" class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="{{ __('app.queue.settings') }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                </a>
                            </div>

                            <div class="mt-4 flex items-center gap-4 text-sm">
                                <div>
                                    <span class="text-2xl font-bold text-amber-600 tabular-nums">{{ $queue->waiting_count }}</span>
                                    <span class="text-slate-500">{{ __('app.queue.waiting') }}</span>
                                </div>
                                <div class="text-slate-300">·</div>
                                <div>
                                    <span class="text-2xl font-bold text-slate-900 tabular-nums">{{ $queue->counters_count }}</span>
                                    <span class="text-slate-500">{{ __('app.queue.counters') }}</span>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-4">
                                <a href="{{ route('queues.show', $queue) }}"
                                   class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-800">
                                    {{ __('app.queue.open_control') }}
                                </a>
                                <a href="{{ route('queue.public', $queue->public_token) }}" target="_blank" rel="noopener"
                                   class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                    {{ __('app.queue.open_public') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
