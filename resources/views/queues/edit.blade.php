<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('queues.show', $queue) }}" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.queue.settings') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Settings --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('queues.update', $queue) }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">{{ __('app.queue.name_label') }} <span class="text-rose-500">*</span></label>
                        <input id="name" name="name" type="text" required value="{{ old('name', $queue->name) }}"
                               class="mt-1.5 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="prefix" class="block text-sm font-medium text-slate-700">{{ __('app.queue.prefix_label') }}</label>
                        <input id="prefix" name="prefix" type="text" maxlength="8" value="{{ old('prefix', $queue->prefix) }}" placeholder="A"
                               class="mt-1.5 block w-40 rounded-md border-slate-300 uppercase shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.queue.prefix_hint') }}</p>
                    </div>

                    <label class="flex items-center gap-3">
                        <input type="hidden" name="voice_enabled" value="0">
                        <input type="checkbox" name="voice_enabled" value="1" @checked($queue->voice_enabled)
                               class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">{{ __('app.queue.voice_label') }}</span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($queue->is_active)
                               class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">{{ __('app.queue.active_label') }}</span>
                    </label>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.common.save') }}</button>
                    </div>
                </form>
            </div>

            {{-- Counters --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.queue.counters_heading') }}</h3>

                @if ($queue->counters->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">{{ __('app.queue.no_counters') }}</p>
                @else
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($queue->counters as $counter)
                            <li class="flex items-center justify-between py-2.5">
                                <span class="text-sm font-medium text-slate-800">{{ __('app.queue.counter_word') }} {{ $counter->label }}</span>
                                <form method="POST" action="{{ route('queues.counters.destroy', [$queue, $counter]) }}"
                                      data-confirm="{{ __('app.queue.remove_counter_confirm') }}" data-confirm-danger="1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('queues.counters.store', $queue) }}" class="mt-4 flex gap-2">
                    @csrf
                    <input name="label" type="text" required maxlength="40"
                           placeholder="{{ __('app.queue.counter_label_placeholder') }}"
                           class="flex-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('app.queue.add_counter') }}
                    </button>
                </form>
                @error('label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Customer link + QR --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                 x-data="{
                    link: @js(route('queue.public', $queue->public_token)),
                    copy() {
                        navigator.clipboard?.writeText(this.link)
                            .then(() => window.flashToast({ message: @js(__('app.queue.link_copied')), type: 'success' }))
                            .catch(() => {});
                    },
                 }"
                 x-init="window.QRCode && window.QRCode.toCanvas($refs.qr, link, { width: 160, margin: 1 }, () => {})">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.queue.public_link') }}</h3>
                <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                    <canvas x-ref="qr" class="rounded-xl ring-1 ring-slate-200"></canvas>
                    <div class="min-w-0 flex-1">
                        <code class="block truncate rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600" x-text="link"></code>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" @click="copy()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                {{ __('app.queue.copy_link') }}
                            </button>
                            <a href="{{ route('queue.public', $queue->public_token) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                {{ __('app.queue.open_public') }}
                            </a>
                            <a href="{{ route('queues.poster', $queue) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                {{ __('app.queue.poster') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Maintenance: reset + delete --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.queue.reset') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('app.queue.reset_hint') }}</p>
                    </div>
                    <form method="POST" action="{{ route('queues.reset', $queue) }}" data-confirm="{{ __('app.queue.reset_confirm') }}" data-confirm-danger="1">
                        @csrf
                        <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                            {{ __('app.queue.reset') }}
                        </button>
                    </form>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <div>
                        <h3 class="text-sm font-semibold text-rose-700">{{ __('app.queue.delete') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('app.queue.delete_confirm') }}</p>
                    </div>
                    <form method="POST" action="{{ route('queues.destroy', $queue) }}" data-confirm="{{ __('app.queue.delete_confirm') }}" data-confirm-danger="1">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                            {{ __('app.queue.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
