<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.recurring_journals') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.accounting.recurring_journals_sub') }}</p>
            </div>
            <a href="{{ route('accounting.recurring-journals.create') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('app.accounting.recurring_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
            @forelse ($templates as $t)
                @php
                    $freqBadge = [
                        'monthly'   => 'bg-blue-50 text-blue-700 ring-blue-200',
                        'quarterly' => 'bg-purple-50 text-purple-700 ring-purple-200',
                        'weekly'    => 'bg-teal-50 text-teal-700 ring-teal-200',
                    ][$t->frequency] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ $t->name }}</span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $freqBadge }}">
                                    {{ __('app.accounting.freq_'.$t->frequency) }}
                                </span>
                                @if (! $t->is_active)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200">
                                        {{ __('app.accounting.inactive') }}
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ __('app.accounting.next_run') }}: {{ $t->next_run_date->format('d/m/Y') }}
                                @if ($t->last_run_at)
                                    · {{ __('app.accounting.last_run') }}: {{ $t->last_run_at->format('d/m/Y') }}
                                @endif
                            </div>
                            {{-- Preview lines --}}
                            <div class="mt-2 space-y-0.5">
                                @foreach ($t->lines->take(3) as $line)
                                    <div class="text-xs text-slate-600">
                                        {{ $line->account?->code }} {{ $line->account?->name }}
                                        @if ((float) $line->debit > 0)
                                            <span class="text-slate-400">DR ฿{{ number_format((float) $line->debit, 2) }}</span>
                                        @else
                                            <span class="text-slate-400">CR ฿{{ number_format((float) $line->credit, 2) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($t->lines->count() > 3)
                                    <div class="text-xs text-slate-400">+ {{ $t->lines->count() - 3 }} {{ __('app.accounting.more_lines') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            {{-- Run now --}}
                            @if ($t->is_active)
                                <form method="POST" action="{{ route('accounting.recurring-journals.run', $t) }}">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                                        {{ __('app.accounting.run_now') }}
                                    </button>
                                </form>
                            @endif
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('accounting.recurring-journals.toggle', $t) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-slate-500 hover:text-slate-800">
                                        {{ $t->is_active ? __('app.accounting.deactivate') : __('app.accounting.activate') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('accounting.recurring-journals.destroy', $t) }}"
                                      data-confirm="{{ __('app.accounting.recurring_delete_confirm') }}" data-confirm-danger="1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-500 hover:text-rose-700">{{ __('app.accounting.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="text-sm text-slate-500">{{ __('app.accounting.recurring_empty') }}</p>
                    <a href="{{ route('accounting.recurring-journals.create') }}"
                       class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        {{ __('app.accounting.recurring_new') }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
