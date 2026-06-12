<x-portfolio-layout>
    @php
        // Tailwind JIT can only see literal class strings, so keep the kind
        // colour palette in a plain associative map instead of building class
        // names from `$kind` at runtime.
        $kindBadge = [
            'stock'      => 'bg-sky-50 text-sky-700 ring-sky-200',
            'deposit'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'cash'       => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'debt'       => 'bg-rose-50 text-rose-700 ring-rose-200',
            'gold'       => 'bg-amber-50 text-amber-700 ring-amber-200',
            'crypto'     => 'bg-violet-50 text-violet-700 ring-violet-200',
            'fund'       => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'realestate' => 'bg-stone-100 text-stone-700 ring-stone-200',
            'other'      => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];

        // Group order — debts last so the eye lands on assets first.
        $kindOrder = ['stock', 'fund', 'crypto', 'deposit', 'cash', 'gold', 'realestate', 'other', 'debt'];

        $fmtMoney = fn ($n) => number_format((float) $n, 2);
        $fmtQty = fn ($n) => rtrim(rtrim(number_format((float) $n, 8, '.', ','), '0'), '.') ?: '0';
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Header row: title + actions --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('app.portfolio.heading') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ __('app.portfolio.subheading') }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('app.portfolio.last_refresh') }}:
                        @if ($lastRefresh)
                            <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($lastRefresh)->diffForHumans() }}</span>
                        @else
                            <span class="italic">{{ __('app.portfolio.never_refreshed') }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('portfolio.refresh') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('app.portfolio.refresh') }}
                        </button>
                    </form>
                    <a href="{{ route('portfolio.holdings.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('app.portfolio.add') }}
                    </a>
                </div>
            </div>

            {{-- Summary tiles --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-emerald-700">{{ __('app.portfolio.tile_assets') }}</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-800">฿{{ $fmtMoney($totals['assets']) }}</div>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-rose-700">{{ __('app.portfolio.tile_debts') }}</div>
                    <div class="mt-2 text-2xl font-bold text-rose-800">฿{{ $fmtMoney($totals['debts']) }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('app.portfolio.tile_net') }}</div>
                    <div class="mt-2 text-3xl font-bold {{ $totals['net'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">
                        ฿{{ $fmtMoney($totals['net']) }}
                    </div>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-sky-700">{{ __('app.portfolio.tile_gain') }}</div>
                    @php
                        $gainColor = $totals['gain'] >= 0 ? 'text-emerald-700' : 'text-rose-700';
                        $gainSign = $totals['gain'] >= 0 ? '+' : '';
                    @endphp
                    <div class="mt-2 text-2xl font-bold {{ $gainColor }}">
                        {{ $gainSign }}฿{{ $fmtMoney($totals['gain']) }}
                    </div>
                    <div class="mt-0.5 text-xs {{ $gainColor }}/80">
                        {{ $gainSign }}{{ number_format($totals['gain_pct'], 2) }}%
                    </div>
                </div>
            </div>

            {{-- Trend chart (90-day net-worth line) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.portfolio.trend_heading') }}</h3>
                    <span class="text-xs text-slate-500">{{ __('app.portfolio.trend_subtitle') }}</span>
                </div>

                @if (count($trend) < 2)
                    <p class="mt-6 text-center text-sm italic text-slate-500">{{ __('app.portfolio.trend_empty') }}</p>
                @else
                    @php
                        $values = array_column($trend, 'net');
                        $min = min($values);
                        $max = max($values);
                        $span = max(1, $max - $min);
                        $width = 600;
                        $height = 160;
                        $count = count($trend);

                        $points = [];
                        foreach ($trend as $i => $row) {
                            $x = ($i / ($count - 1)) * $width;
                            $y = $height - (($row['net'] - $min) / $span) * ($height - 20) - 10;
                            $points[] = round($x, 2) . ',' . round($y, 2);
                        }
                        $polyline = implode(' ', $points);
                        $area = '0,' . $height . ' ' . $polyline . ' ' . $width . ',' . $height;
                    @endphp
                    <div class="mt-4">
                        <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="h-40 w-full">
                            <defs>
                                <linearGradient id="netGrad" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.25"/>
                                    <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <polygon points="{{ $area }}" fill="url(#netGrad)"/>
                            <polyline points="{{ $polyline }}" fill="none" stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="mt-2 flex justify-between text-xs text-slate-500">
                            <span>{{ $trend[0]['d'] }} · ฿{{ $fmtMoney($trend[0]['net']) }}</span>
                            <span>{{ end($trend)['d'] }} · ฿{{ $fmtMoney(end($trend)['net']) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Holdings, grouped by kind --}}
            @if ($holdings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                    {{ __('app.portfolio.no_holdings') }}
                </div>
            @else
                @foreach ($kindOrder as $kind)
                    @php $group = $byKind->get($kind); @endphp
                    @if (! $group || $group->isEmpty()) @continue @endif

                    @php
                        $groupTotal = $group->sum(fn ($h) => (float) ($h->current_value_thb ?? 0));
                        $isDebtGroup = $kind === \App\Models\Portfolio\Holding::KIND_DEBT;
                    @endphp

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $kindBadge[$kind] }}">
                                    {{ __("app.portfolio.kinds.{$kind}") }}
                                </span>
                                <span class="text-xs text-slate-500">{{ $group->count() }}</span>
                            </div>
                            <div class="text-sm font-semibold {{ $isDebtGroup ? 'text-rose-700' : 'text-slate-900' }}">
                                {{ $isDebtGroup ? '−' : '' }}฿{{ $fmtMoney($groupTotal) }}
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/60 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">{{ __('app.portfolio.col_label') }}</th>
                                        <th class="px-5 py-2 text-left font-medium">{{ __('app.portfolio.col_symbol') }}</th>
                                        <th class="px-5 py-2 text-right font-medium">{{ __('app.portfolio.col_qty') }}</th>
                                        <th class="px-5 py-2 text-right font-medium">{{ __('app.portfolio.col_price') }}</th>
                                        <th class="px-5 py-2 text-right font-medium">{{ __('app.portfolio.col_value') }}</th>
                                        <th class="px-5 py-2 text-right font-medium">{{ __('app.portfolio.col_gain') }}</th>
                                        <th class="px-5 py-2 text-right font-medium">{{ __('app.portfolio.col_updated') }}</th>
                                        <th class="px-5 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($group as $h)
                                        @php
                                            $val = (float) ($h->current_value_thb ?? 0);
                                            $cost = (float) $h->cost_basis;
                                            $gain = $isDebtGroup ? null : ($val - $cost);
                                            $gainPct = ($cost > 0 && $gain !== null) ? ($gain / $cost * 100) : null;
                                        @endphp
                                        <tr class="hover:bg-slate-50/60">
                                            <td class="px-5 py-3">
                                                <div class="font-medium text-slate-900">{{ $h->label }}</div>
                                                @if ($h->notes)
                                                    <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($h->notes, 60) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $h->symbol ?: '—' }}</td>
                                            <td class="px-5 py-3 text-right tabular-nums text-slate-700">{{ $fmtQty($h->quantity) }}</td>
                                            <td class="px-5 py-3 text-right tabular-nums text-slate-700">
                                                {{ $h->current_price !== null ? $fmtMoney($h->current_price) : '—' }}
                                                @if ($h->currency && $h->currency !== 'THB')
                                                    <span class="text-xs text-slate-400">{{ $h->currency }}</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 text-right tabular-nums font-semibold {{ $isDebtGroup ? 'text-rose-700' : 'text-slate-900' }}">
                                                {{ $isDebtGroup ? '−' : '' }}฿{{ $fmtMoney($val) }}
                                            </td>
                                            <td class="px-5 py-3 text-right tabular-nums">
                                                @if ($gain === null)
                                                    <span class="text-slate-400">—</span>
                                                @else
                                                    <div class="font-medium {{ $gain >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                        {{ $gain >= 0 ? '+' : '' }}฿{{ $fmtMoney($gain) }}
                                                    </div>
                                                    @if ($gainPct !== null)
                                                        <div class="text-xs {{ $gain >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                            {{ $gain >= 0 ? '+' : '' }}{{ number_format($gainPct, 2) }}%
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 text-right text-xs text-slate-500">
                                                {{ $h->last_priced_at ? $h->last_priced_at->diffForHumans() : '—' }}
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    @if (in_array($h->kind, [\App\Models\Portfolio\Holding::KIND_CASH, \App\Models\Portfolio\Holding::KIND_DEPOSIT, \App\Models\Portfolio\Holding::KIND_DEBT], true))
                                                        <a href="{{ route('portfolio.holdings.transactions.index', $h) }}"
                                                           class="rounded-md px-2 py-1 text-xs font-medium text-brand-600 transition hover:bg-brand-50 hover:text-brand-700">
                                                            {{ __('app.portfolio.transactions.ledger_link') }}
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('portfolio.holdings.edit', $h) }}"
                                                       class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                        {{ __('app.portfolio.edit') }}
                                                    </a>
                                                    <form method="POST" action="{{ route('portfolio.holdings.destroy', $h) }}"
                                                          data-confirm="{{ __('app.portfolio.form.delete_confirm') }}" data-confirm-danger="1">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                            {{ __('app.portfolio.delete') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif

            <p class="text-center text-xs text-slate-500">
                {{ __('app.portfolio.refreshing_hint') }}
            </p>
        </div>
    </div>

    <script>
        // รีเฟรชราคาอัตโนมัติทุกๆ 5 นาที (300,000 มิลลิวินาที)
        setInterval(() => {
            fetch("{{ route('portfolio.refresh') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                window.location.reload();
            }).catch(err => console.error('Auto-refresh failed:', err));
        }, 300000);
    </script>
</x-portfolio-layout>
