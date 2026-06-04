<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }} / {{ __('app.admin.products.social.label') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.social.tab_settings') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.social.settings.update') }}" class="space-y-6">
                @csrf @method('PATCH')

                {{-- Facebook settings --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('app.admin.products.social.facebook_heading') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">{{ __('app.admin.products.social.facebook_page_id') }}</label>
                            <input type="text" name="facebook_page_id" maxlength="50"
                                   placeholder="{{ $pageIdSet ? __('app.admin.products.social.already_set') : 'e.g. 123456789' }}"
                                   value="{{ old('facebook_page_id', $pageId) }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">{{ __('app.admin.products.social.facebook_token') }}</label>
                            <input type="password" name="facebook_page_token" maxlength="1000"
                                   placeholder="{{ $tokenSet ? __('app.admin.products.social.keep_existing') : __('app.admin.products.social.paste_token') }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.admin.products.social.facebook_token_hint') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Discord Webhook --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('app.admin.products.social.discord_heading') }}</h3>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-700">{{ __('app.admin.products.social.discord_webhook') }}</label>
                        <input type="url" name="discord_webhook" maxlength="500"
                               value="{{ old('discord_webhook', $discordWebhook) }}"
                               placeholder="https://discord.com/api/webhooks/..."
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">{{ __('app.admin.products.social.discord_webhook_hint') }}</p>
                    </div>
                </div>

                {{-- Gemini API key --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('app.admin.products.social.ai_heading') }}</h3>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-700">{{ __('app.admin.products.social.gemini_key') }}</label>
                        <input type="password" name="gemini_key" maxlength="200"
                               placeholder="{{ $geminiSet ? __('app.admin.products.social.keep_existing') : 'AIza...' }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">{{ __('app.admin.products.social.gemini_key_hint') }}</p>
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-brand-600 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60">
                    {{ __('app.admin.products.social.settings_save') }}
                </button>
            </form>

        </div>
    </div>
</x-admin-layout>
