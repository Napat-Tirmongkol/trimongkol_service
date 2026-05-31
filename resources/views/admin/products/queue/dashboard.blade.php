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

            {{-- Billing / SlipOK settings --}}
            @php
                $billEnabled = setting('queue_billing.enabled') === '1';
                $billMethod = setting('queue_billing.method') ?: 'promptpay_phone';
                $promptpay = setting('queue_billing.promptpay');
                $bankName = setting('queue_billing.bank_name');
                $accountNo = setting('queue_billing.account_no');
                $accountName = setting('queue_billing.account_name');
                $slipBranch = setting('queue_billing.slipok_branch');
                $slipKeySet = filled(setting('queue_billing.slipok_key')) || filled(config('services.slipok.key'));
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.queue.billing_heading') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.products.queue.billing_desc') }}</p>
                    </div>
                    <a href="{{ route('admin.queue.payments') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.products.queue.tab_payments') }} →</a>
                </div>

                <form method="POST" action="{{ route('admin.queue.billing-settings') }}" class="space-y-4 px-6 py-5"
                      x-data="{ method: @js($billMethod) }">
                    @csrf
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" @checked($billEnabled)
                               class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-slate-700">{{ __('app.admin.products.queue.billing_enabled_label') }}</span>
                    </label>

                    {{-- Receiving method --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.billing_method') }}</label>
                        <select name="method" x-model="method"
                                class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-72">
                            <option value="promptpay_phone">{{ __('app.admin.products.queue.billing_method_phone') }}</option>
                            <option value="promptpay_id">{{ __('app.admin.products.queue.billing_method_id') }}</option>
                            <option value="bank_account">{{ __('app.admin.products.queue.billing_method_bank') }}</option>
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- PromptPay value (phone / national ID) --}}
                        <div x-show="method !== 'bank_account'">
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.billing_promptpay') }}</label>
                            <input type="text" name="promptpay" value="{{ $promptpay }}"
                                   :placeholder="method === 'promptpay_id' ? '1-2345-67890-12-3' : '0812345678'"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.products.queue.billing_promptpay_hint') }}</p>
                        </div>

                        {{-- Bank account fields --}}
                        <div x-show="method === 'bank_account'" x-cloak>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.billing_bank_name') }}</label>
                            <input type="text" name="bank_name" value="{{ $bankName }}" placeholder="กสิกรไทย"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div x-show="method === 'bank_account'" x-cloak>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.billing_account_no') }}</label>
                            <input type="text" name="account_no" value="{{ $accountNo }}" placeholder="1328846019"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.billing_account_name') }}</label>
                            <input type="text" name="account_name" value="{{ $accountName }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('app.admin.products.queue.billing_slipok_branch') }}</label>
                            <input type="text" name="slipok_branch" value="{{ $slipBranch }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                {{ __('app.admin.products.queue.billing_slipok_key') }}
                                @if ($slipKeySet)
                                    <span class="ml-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.products.queue.tts_key_set') }}</span>
                                @endif
                            </label>
                            <input type="password" name="slipok_key" autocomplete="off"
                                   placeholder="{{ $slipKeySet ? '••••••••  ('.__('app.admin.products.queue.tts_key_keep').')' : 'SLIPOK…' }}"
                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">{{ __('app.admin.products.queue.billing_match_hint') }}</p>
                    <p class="text-xs text-slate-500">{{ __('app.admin.products.queue.billing_slipok_hint') }}</p>

                    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('app.admin.products.queue.billing_save') }}</button>
                </form>

                {{-- Test transfer: upload a real test slip to confirm the SlipOK
                     wiring without consuming the slip or activating anything. --}}
                <form method="POST" action="{{ route('admin.queue.slip-test') }}" enctype="multipart/form-data"
                      class="space-y-3 border-t border-slate-100 px-6 py-5">
                    @csrf
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.queue.slip_test_heading') }}</h4>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.products.queue.slip_test_hint') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" name="slip" accept="image/*" required
                               class="block w-full max-w-xs rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            {{ __('app.admin.products.queue.slip_test_btn') }}
                        </button>
                    </div>
                    @error('slip') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
