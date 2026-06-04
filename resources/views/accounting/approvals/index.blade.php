<x-accounting-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="min-w-0">
                <a href="{{ route('accounting.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-700">← {{ __('app.accounting.heading') }}</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('app.accounting.approvals_heading') }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">

            @if ($pendingApprovals->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="text-sm text-slate-500">{{ __('app.accounting.approvals_none') }}</p>
                </div>
            @else
                @foreach ($pendingApprovals as $approval)
                    @php
                        $typeLabel = $approval->subject_type === 'Invoice'
                            ? __('app.accounting.invoices')
                            : __('app.accounting.bills');
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">{{ $typeLabel }}</span>
                                    <span class="font-mono text-sm font-semibold text-slate-900">{{ $approval->subject_label }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ __('app.accounting.approval_for') }}:
                                    <span class="font-medium text-slate-700">{{ $approval->requestedBy->name }}</span>
                                    · {{ $approval->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-start gap-3">
                            {{-- Approve --}}
                            <form method="POST"
                                  action="{{ route('accounting.approvals.approve', $approval) }}"
                                  data-confirm="{{ __('app.accounting.approve') }}?">
                                @csrf
                                <button type="submit"
                                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                                    {{ __('app.accounting.approve') }}
                                </button>
                            </form>

                            {{-- Reject with note --}}
                            <form method="POST"
                                  action="{{ route('accounting.approvals.reject', $approval) }}"
                                  class="flex flex-1 flex-wrap items-center gap-2"
                                  x-data="{ open: false }">
                                @csrf
                                <button type="button"
                                        @click="open = !open"
                                        class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                    {{ __('app.accounting.reject') }}
                                </button>
                                <div x-show="open" x-cloak class="flex w-full flex-wrap items-center gap-2">
                                    <input type="text"
                                           name="note"
                                           maxlength="500"
                                           placeholder="{{ __('app.accounting.approval_note') }} ({{ __('app.accounting.optional') }})"
                                           class="flex-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <button type="submit"
                                            class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">
                                        {{ __('app.accounting.reject') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-accounting-layout>
