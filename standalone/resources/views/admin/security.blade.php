<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.security.heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Stats --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-rose-700">{{ __('app.admin.security.failed_24h') }}</div>
                    <div class="mt-2 text-3xl font-bold text-rose-800">{{ $stats['failed_24h'] }}</div>
                    <div class="mt-0.5 text-xs text-rose-700/80">{{ __('app.admin.security.failed_7d_hint', ['n' => $stats['failed_7d']]) }}</div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-emerald-700">{{ __('app.admin.security.success_24h') }}</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-800">{{ $stats['success_24h'] }}</div>
                    <div class="mt-0.5 text-xs text-emerald-700/80">{{ __('app.admin.security.success_7d_hint', ['n' => $stats['success_7d']]) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('app.admin.security.ratio_label') }}</div>
                    @php
                        $total = max(1, $stats['failed_7d'] + $stats['success_7d']);
                        $failedPct = round($stats['failed_7d'] * 100 / $total);
                    @endphp
                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-emerald-100">
                        <div class="h-full bg-rose-500" style="width: {{ $failedPct }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-slate-500">
                        <span>{{ $failedPct }}% {{ __('app.admin.security.ratio_failed') }}</span>
                        <span>{{ 100 - $failedPct }}% {{ __('app.admin.security.ratio_success') }}</span>
                    </div>
                </div>
            </div>

            {{-- Top failing IPs --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.security.top_ips') }}</h3>
                    <p class="text-xs text-slate-500">{{ __('app.admin.security.top_ips_hint') }}</p>
                </div>
                @if ($topFailingIps->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.security.no_failures') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($topFailingIps as $row)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div>
                                    <code class="text-sm font-medium text-slate-900">{{ $row->ip }}</code>
                                    <div class="text-xs text-slate-500">{{ __('app.admin.security.last_seen') }}: {{ \Carbon\Carbon::parse($row->last_seen)->diffForHumans() }}</div>
                                </div>
                                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                                    {{ $row->attempts }} {{ __('app.admin.security.attempts') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Recent attempts --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.security.recent_attempts') }}</h3>
                    <div class="flex gap-1">
                        <a href="{{ route('admin.security') }}"
                           class="rounded-full px-3 py-1 text-xs font-medium {{ ! request('only') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ __('app.admin.filter_all') }}</a>
                        <a href="{{ route('admin.security', ['only' => 'failed']) }}"
                           class="rounded-full px-3 py-1 text-xs font-medium {{ request('only') === 'failed' ? 'bg-rose-600 text-white' : 'text-rose-600 hover:bg-rose-50' }}">{{ __('app.admin.security.failed_only') }}</a>
                        <a href="{{ route('admin.security', ['only' => 'success']) }}"
                           class="rounded-full px-3 py-1 text-xs font-medium {{ request('only') === 'success' ? 'bg-emerald-600 text-white' : 'text-emerald-600 hover:bg-emerald-50' }}">{{ __('app.admin.security.success_only') }}</a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('app.admin.security.col_when') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.security.col_email') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.security.col_result') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.security.col_ip') }}</th>
                                <th class="px-6 py-3">{{ __('app.admin.security.col_agent') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @forelse ($recentAttempts as $a)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-500" title="{{ $a->created_at }}">{{ $a->created_at->diffForHumans() }}</td>
                                    <td class="px-6 py-3 text-slate-700">
                                        {{ $a->email ?: '—' }}
                                        @if ($a->user?->is_admin)
                                            <span class="ml-1 rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700">admin</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($a->success)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">{{ __('app.admin.security.success') }}</span>
                                        @else
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">{{ __('app.admin.security.failed') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $a->ip ?: '—' }}</td>
                                    <td class="px-6 py-3 text-xs text-slate-500"><div class="max-w-xs truncate" title="{{ $a->user_agent }}">{{ $a->user_agent ?: '—' }}</div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.security.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
