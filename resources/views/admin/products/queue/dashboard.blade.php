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

            {{-- Voice / Text-to-Speech --}}
            @php
                $ttsProvider = setting('tts.provider', 'browser');
                $ttsVoice = setting('tts.voice', 'th-TH-Neural2-C');
                $ttsKeySet = filled(setting('tts.google_key')) || filled(config('services.google_tts.key'));
                $ttsVoices = [
                    'th-TH-Neural2-C' => 'th-TH-Neural2-C — หญิง (Neural2)',
                    'th-TH-Standard-A' => 'th-TH-Standard-A — หญิง (Standard)',
                ];
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.queue.tts_heading') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.products.queue.tts_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.queue.settings') }}" class="space-y-4 px-6 py-5">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.tts_provider') }}</label>
                            <select name="provider" class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="browser" @selected($ttsProvider !== 'google')>{{ __('app.admin.products.queue.tts_provider_browser') }}</option>
                                <option value="google" @selected($ttsProvider === 'google')>{{ __('app.admin.products.queue.tts_provider_google') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.tts_voice') }}</label>
                            <select name="voice" class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($ttsVoices as $val => $label)
                                    <option value="{{ $val }}" @selected($ttsVoice === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            {{ __('app.admin.products.queue.tts_key') }}
                            @if ($ttsKeySet)
                                <span class="ml-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.products.queue.tts_key_set') }}</span>
                            @endif
                        </label>
                        <input type="password" name="google_key" autocomplete="off"
                               placeholder="{{ $ttsKeySet ? '••••••••••  ('.__('app.admin.products.queue.tts_key_keep').')' : 'AIza…' }}"
                               class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.products.queue.tts_key_hint') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.products.queue.tts_save') }}</button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-6 py-4">
                    <form method="POST" action="{{ route('admin.queue.tts-test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                            {{ __('app.admin.products.queue.tts_test') }}
                        </button>
                    </form>
                    <span class="text-xs text-slate-400">{{ __('app.admin.products.queue.tts_test_hint') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
