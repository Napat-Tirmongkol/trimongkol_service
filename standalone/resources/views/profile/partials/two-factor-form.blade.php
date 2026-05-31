@php
    $user = auth()->user();
    $enabled = $user->hasTwoFactorEnabled();
    $pending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;
    $recoveryCodes = $user->two_factor_recovery_codes ?? [];
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('app.two_factor.heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('app.two_factor.description') }}
        </p>
    </header>

    <div class="mt-6 max-w-xl">

        @if ($enabled)
            {{-- Enabled state --}}
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <strong>{{ __('app.two_factor.status_enabled') }}</strong>
                <span class="text-emerald-600/80">— {{ __('app.two_factor.confirmed_at', ['at' => $user->two_factor_confirmed_at->format('d M Y H:i')]) }}</span>
            </div>

            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-medium text-slate-700 hover:text-slate-900">
                    {{ __('app.two_factor.recovery_view_heading') }} ({{ count($recoveryCodes) }})
                </summary>
                <div class="mt-3 rounded-md bg-slate-900 p-4 font-mono text-sm text-emerald-300">
                    @foreach ($recoveryCodes as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-3 space-y-2"
                      data-confirm="{{ __('app.two_factor.recovery_regen_confirm') }}" data-confirm-danger="1">
                    @csrf
                    <input type="password" name="password" required placeholder="{{ __('app.two_factor.current_password') }}"
                           class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('password') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('app.two_factor.regenerate_codes') }}
                    </button>
                </form>
            </details>

            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-6 space-y-2"
                  data-confirm="{{ __('app.two_factor.disable_confirm') }}" data-confirm-danger="1">
                @csrf @method('DELETE')
                <label class="block text-sm font-medium text-slate-700">{{ __('app.two_factor.disable_heading') }}</label>
                <input type="password" name="password" required placeholder="{{ __('app.two_factor.current_password') }}"
                       class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                @error('password') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                    {{ __('app.two_factor.disable_button') }}
                </button>
            </form>

        @elseif ($pending)
            {{-- Enrollment in progress: show QR + confirm form --}}
            @php
                $uri = \App\Services\Totp::provisioningUri(
                    $user->two_factor_secret,
                    $user->email,
                    config('app.name', 'Tirmongkol')
                );
            @endphp

            <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-inset ring-amber-200">
                {{ __('app.two_factor.enrollment_intro') }}
            </div>

            <div class="mt-6 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                <canvas id="two-factor-qr" data-qr="{{ $uri }}" class="h-48 w-48 shrink-0 rounded-md border border-slate-200 bg-white p-2"></canvas>
                <div class="flex-1 space-y-2 text-sm">
                    <p class="text-slate-600">{{ __('app.two_factor.manual_entry') }}</p>
                    <code class="block break-all rounded bg-slate-100 px-3 py-2 font-mono text-xs text-slate-800">{{ $user->two_factor_secret }}</code>
                </div>
            </div>

            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-medium text-slate-700 hover:text-slate-900">
                    {{ __('app.two_factor.recovery_show_heading') }}
                </summary>
                <p class="mt-2 text-xs text-slate-500">{{ __('app.two_factor.recovery_hint') }}</p>
                <div class="mt-3 rounded-md bg-slate-900 p-4 font-mono text-sm text-emerald-300">
                    @foreach ($recoveryCodes as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            </details>

            <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-6 space-y-2">
                @csrf
                <label class="block text-sm font-medium text-slate-700">{{ __('app.two_factor.confirm_label') }}</label>
                <input type="text" name="code" required inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="10"
                       class="block w-40 rounded-md border-slate-300 text-center font-mono text-lg tracking-widest shadow-sm focus:border-brand-500 focus:ring-brand-500">
                @error('code') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    {{ __('app.two_factor.confirm_button') }}
                </button>
            </form>

        @else
            {{-- Not enrolled --}}
            <p class="text-sm text-slate-600">{{ __('app.two_factor.not_enabled_hint') }}</p>
            <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4">
                @csrf
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    {{ __('app.two_factor.enable_button') }}
                </button>
            </form>
        @endif
    </div>

    @if ($pending)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const draw = () => {
                    if (!window.QRCode) { setTimeout(draw, 100); return; }
                    const c = document.getElementById('two-factor-qr');
                    window.QRCode.toCanvas(c, c.getAttribute('data-qr'), {
                        width: 200,
                        margin: 1,
                        color: { dark: '#0f172a', light: '#ffffff' },
                    }, (err) => { if (err) console.error(err); });
                };
                draw();
            });
        </script>
    @endif
</section>
