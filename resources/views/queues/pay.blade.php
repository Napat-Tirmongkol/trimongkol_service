<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('queues.billing') }}" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.queue.billing.pay_heading', ['plan' => $planConfig['name']]) }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-md space-y-6 px-4 sm:px-6">

            {{-- Amount --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <div class="text-xs uppercase tracking-wider text-slate-500">{{ $planConfig['name'] }} · {{ __('app.queue.billing.one_month') }}</div>
                <div class="mt-1 text-4xl font-extrabold text-slate-900">฿{{ number_format($price) }}</div>
            </div>

            @if ($isBank)
                {{-- Bank transfer details (manual) --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                     x-data="{
                        copy(v) {
                            navigator.clipboard?.writeText(v)
                                .then(() => window.flashToast({ message: @js(__('app.queue.billing.copied')), type: 'success' }))
                                .catch(() => {});
                        },
                     }">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        {{ __('app.queue.billing.bank_transfer') }}
                    </div>

                    <dl class="mt-4 space-y-3 text-sm">
                        @if ($bankName)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">{{ __('app.queue.billing.bank_label') }}</dt>
                                <dd class="font-medium text-slate-800">{{ $bankName }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">{{ __('app.queue.billing.account_no_label') }}</dt>
                            <dd class="flex items-center gap-2">
                                <span class="font-mono text-base font-bold text-slate-900">{{ $accountNo }}</span>
                                <button type="button" @click="copy(@js($accountNo))" class="rounded-md border border-slate-200 p-1.5 text-slate-500 transition hover:bg-slate-50">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </button>
                            </dd>
                        </div>
                        @if ($accountName)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">{{ __('app.queue.billing.account_name_label') }}</dt>
                                <dd class="font-medium text-slate-800">{{ $accountName }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <dt class="text-slate-500">{{ __('app.queue.billing.amount_label') }}</dt>
                            <dd class="text-lg font-extrabold text-slate-900">฿{{ number_format($price) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">{{ __('app.queue.billing.bank_hint') }}</p>
                </div>
            @else
                {{-- PromptPay QR --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm"
                     x-data
                     x-init="
                        const render = () => window.QRCode
                            ? window.QRCode.toCanvas($refs.qr, @js($promptpayPayload), { width: 240, margin: 1 }, () => {})
                            : setTimeout(render, 50);
                        render();
                     ">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        PromptPay
                    </div>
                    <canvas x-ref="qr" class="mx-auto mt-4"></canvas>
                    @if ($accountName)
                        <div class="mt-3 text-sm font-medium text-slate-700">{{ $accountName }}</div>
                    @endif
                    <p class="mt-2 text-xs text-slate-500">{{ __('app.queue.billing.scan_hint') }}</p>
                </div>
            @endif

            {{-- Slip upload --}}
            <form method="POST" action="{{ route('queues.billing.submit', $plan) }}" enctype="multipart/form-data"
                  class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <h3 class="text-sm font-semibold text-slate-900">{{ __('app.queue.billing.upload_heading') }}</h3>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $slipAuto ? __('app.queue.billing.upload_auto') : __('app.queue.billing.upload_manual') }}
                </p>

                <input type="file" name="slip" accept="image/*" required
                       class="mt-4 block w-full rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                @error('slip') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                <button type="submit"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    {{ __('app.queue.billing.submit_slip') }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
