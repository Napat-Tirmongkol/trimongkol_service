<x-accounting-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.periods') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.periods_sub') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Year-end close panel --}}
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="text-sm font-semibold text-amber-900">{{ __('app.accounting.year_end_close') }}</h3>
                <p class="mt-1 text-xs text-amber-700">{{ __('app.accounting.year_end_close_desc') }}</p>
                <form method="POST" action="{{ route('accounting.periods.year-end-close') }}"
                      data-confirm="{{ __('app.accounting.year_end_confirm') }}" data-confirm-danger="1"
                      class="mt-3 flex items-center gap-3">
                    @csrf
                    <select name="buddhist_year" class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:ring-2 focus:ring-amber-400">
                        @for ($y = now()->year + 543; $y >= now()->year + 543 - 5; $y--)
                            <option value="{{ $y }}">พ.ศ. {{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:opacity-60">
                        {{ __('app.accounting.year_end_close_cta') }}
                    </button>
                </form>
            </section>

            {{-- Periods grouped by year --}}
            @forelse ($byYear as $year => $yearPeriods)
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.fiscal_year') }} พ.ศ. {{ $year }}</h3>
                        @php $allLocked = $yearPeriods->every(fn ($p) => $p->status === 'locked'); @endphp
                        @if ($allLocked)
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">{{ __('app.accounting.period_locked') }}</span>
                        @endif
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($yearPeriods as $period)
                            @php
                                $badge = [
                                    'open'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    'closed' => 'bg-slate-100 text-slate-600 ring-slate-200',
                                    'locked' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                ][$period->status];
                            @endphp
                            <li class="flex items-center justify-between gap-3 px-6 py-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-slate-900">{{ $period->name }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ $period->starts_on->format('d/m/Y') }} – {{ $period->ends_on->format('d/m/Y') }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                        {{ __('app.accounting.period_status_'.$period->status) }}
                                    </span>
                                    @if ($period->status === 'open')
                                        <form method="POST" action="{{ route('accounting.periods.close', $period) }}"
                                              data-confirm="{{ __('app.accounting.period_close_confirm') }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                                {{ __('app.accounting.period_close_btn') }}
                                            </button>
                                        </form>
                                    @elseif ($period->status === 'closed')
                                        <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-brand-700 hover:text-brand-900">
                                                {{ __('app.accounting.period_reopen_btn') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white px-6 py-8 text-center text-sm text-slate-500">
                    {{ __('app.accounting.periods_empty') }}
                </div>
            @endforelse

            {{-- Add new period --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.accounting.period_add') }}</h3>
                </div>
                <form method="POST" action="{{ route('accounting.periods.store') }}" class="space-y-4 p-6">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.period_name') }}</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="{{ now()->year + 543 }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.period_from') }}</label>
                            <input type="date" name="starts_on" value="{{ old('starts_on') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700">{{ __('app.accounting.period_to') }}</label>
                            <input type="date" name="ends_on" value="{{ old('ends_on') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>
                    <div>
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                            {{ __('app.accounting.period_add_btn') }}
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </div>
</x-accounting-layout>
