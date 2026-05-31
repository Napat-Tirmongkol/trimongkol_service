<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.queue.label') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.queue.tab_payments') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            {{-- Status filter --}}
            <div class="inline-flex rounded-full bg-slate-100 p-1 text-sm">
                @foreach (['pending', 'verified', 'rejected'] as $s)
                    <a href="{{ route('admin.queue.payments', ['status' => $s]) }}"
                       class="rounded-full px-4 py-1.5 font-semibold transition {{ $status === $s ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        {{ __('app.admin.products.queue.pay_status_'.$s) }}
                    </a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if ($payments->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-slate-500">{{ __('app.admin.products.queue.pay_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($payments as $p)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-900">{{ $p->workspace?->name ?? '—' }}</span>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ config("queue-plans.{$p->plan_key}.name") ?? $p->plan_key }}</span>
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        ฿{{ number_format($p->amount) }} ·
                                        @if ($p->user) {{ $p->user->email }} · @endif
                                        {{ $p->created_at->format('d M Y H:i') }}
                                        @if ($p->trans_ref) · <code class="text-slate-400">{{ \Illuminate\Support\Str::limit($p->trans_ref, 18) }}</code> @endif
                                        @if ($p->note) · <span class="text-amber-600">{{ $p->note }}</span> @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($p->slip_path)
                                        <a href="{{ route('admin.queue.payments.slip', $p) }}" target="_blank" rel="noopener"
                                           class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            {{ __('app.admin.products.queue.pay_view_slip') }}
                                        </a>
                                    @endif

                                    @if ($p->status === 'pending')
                                        <form method="POST" action="{{ route('admin.queue.payments.approve', $p) }}"
                                              data-confirm="{{ __('app.admin.products.queue.pay_approve_confirm') }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">{{ __('app.admin.products.queue.pay_approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.queue.payments.reject', $p) }}"
                                              data-confirm="{{ __('app.admin.products.queue.pay_reject_confirm') }}" data-confirm-danger="1">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">{{ __('app.admin.products.queue.pay_reject') }}</button>
                                        </form>
                                    @elseif ($p->status === 'verified')
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">{{ __('app.admin.products.queue.pay_status_verified') }}</span>
                                    @else
                                        <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700">{{ __('app.admin.products.queue.pay_status_rejected') }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{ $payments->links() }}
        </div>
    </div>
</x-admin-layout>
