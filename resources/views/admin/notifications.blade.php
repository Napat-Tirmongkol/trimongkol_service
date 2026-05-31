<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.notifications.heading') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.notifications.subheading') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- LINE (Messaging API) --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.notifications.line_heading') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.notifications.line_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.notifications.line') }}" class="space-y-4 px-6 py-5">
                    @csrf
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="line_enabled" value="0">
                        <input type="checkbox" name="line_enabled" value="1" @checked($lineEnabled)
                               class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">{{ __('app.admin.notifications.line_enabled_label') }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.notifications.line_target') }}</label>
                            <input type="text" name="line_target" value="{{ $lineTarget }}" placeholder="Uxxxxxxxx… / Cxxxxxxxx…"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.notifications.line_target_hint') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ __('app.admin.notifications.line_token') }}
                                @if ($lineTokenSet)
                                    <span class="ml-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.notifications.key_set') }}</span>
                                @endif
                            </label>
                            <input type="password" name="line_token" autocomplete="off"
                                   placeholder="{{ $lineTokenSet ? '••••••••  ('.__('app.admin.notifications.key_keep').')' : 'Channel access token' }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <p class="text-xs text-slate-500">{{ __('app.admin.notifications.line_hint') }}</p>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.notifications.line_save') }}</button>
                </form>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-6 py-4">
                    <form method="POST" action="{{ route('admin.notifications.line-test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            {{ __('app.admin.notifications.line_test') }}
                        </button>
                    </form>
                    <span class="text-xs text-slate-400">{{ __('app.admin.notifications.line_test_hint') }}</span>
                </div>
            </div>

            {{-- Discord (incoming webhook) --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.notifications.discord_heading') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.notifications.discord_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.notifications.discord') }}" class="space-y-4 px-6 py-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.notifications.discord_webhook') }}</label>
                        <input type="url" name="discord_webhook" value="{{ $discordWebhook }}"
                               placeholder="https://discord.com/api/webhooks/…"
                               class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.notifications.discord_hint') }}</p>
                    </div>
                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.notifications.discord_save') }}</button>
                </form>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-6 py-4">
                    <form method="POST" action="{{ route('admin.notifications.discord-test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            {{ __('app.admin.notifications.discord_test') }}
                        </button>
                    </form>
                    <span class="text-xs text-slate-400">{{ __('app.admin.notifications.discord_test_hint') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
