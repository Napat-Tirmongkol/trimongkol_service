<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('app.admin.heading') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Primary totals --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $cards = [
                        ['k' => __('app.admin.stat_users'), 'v' => $stats['users'], 'tone' => 'slate'],
                        ['k' => __('app.admin.stat_admins'), 'v' => $stats['admins'], 'tone' => 'brand'],
                        ['k' => __('app.admin.stat_classrooms'), 'v' => $stats['classrooms'], 'tone' => 'slate'],
                        ['k' => __('app.admin.stat_submissions'), 'v' => $stats['submissions'], 'tone' => 'slate'],
                    ];
                @endphp
                @foreach ($cards as $c)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ $c['k'] }}</div>
                        <div class="mt-2 text-3xl font-bold {{ $c['tone'] === 'brand' ? 'text-brand-700' : 'text-slate-900' }}">{{ $c['v'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Activity tiles --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-emerald-700">{{ __('app.admin.stat_new_week') }}</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-800">+{{ $stats['new_users_week'] }}</div>
                    <div class="mt-0.5 text-xs text-emerald-700/80">{{ __('app.admin.stat_new_month_hint', ['n' => $stats['new_users_month']]) }}</div>
                </div>
                <div class="rounded-xl border border-sky-200 bg-sky-50/50 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-sky-700">{{ __('app.admin.stat_active_30d') }}</div>
                    <div class="mt-2 text-2xl font-bold text-sky-800">{{ $stats['active_30d'] }}</div>
                    <div class="mt-0.5 text-xs text-sky-700/80">{{ __('app.admin.stat_active_hint') }}</div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-amber-700">{{ __('app.admin.stat_submissions_today') }}</div>
                    <div class="mt-2 text-2xl font-bold text-amber-800">{{ $stats['submissions_today'] }}</div>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5">
                    <div class="text-xs font-medium uppercase tracking-wider text-rose-700">{{ __('app.admin.stat_suspended') }}</div>
                    <div class="mt-2 text-2xl font-bold text-rose-800">{{ $stats['suspended'] }}</div>
                    @if ($stats['suspended'] > 0)
                        <a href="{{ route('admin.users', ['status' => 'suspended']) }}" class="mt-1 inline-block text-xs text-rose-700 underline">{{ __('app.common.view') }} →</a>
                    @endif
                </div>
            </div>

            {{-- Leads tile (service business) --}}
            <a href="{{ route('admin.leads.index', ['status' => 'new']) }}"
               class="block rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-violet-100 p-5 hover:shadow-md">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-violet-700">{{ __('app.admin.leads.tile_heading') }}</div>
                        <div class="mt-2 flex items-baseline gap-3">
                            <span class="text-3xl font-bold text-violet-900">{{ $stats['leads_new'] }}</span>
                            <span class="text-sm text-violet-700">{{ __('app.admin.leads.tile_new_subtitle') }}</span>
                        </div>
                        <div class="mt-1 text-xs text-violet-700/80">{{ __('app.admin.leads.tile_week_hint', ['n' => $stats['leads_week']]) }}</div>
                    </div>
                    <svg class="h-10 w-10 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                    </svg>
                </div>
            </a>

            {{-- Signup trend (last 30 days) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.trend_heading') }}</h3>
                    <span class="text-xs text-slate-500">{{ __('app.admin.trend_subtitle') }}</span>
                </div>
                @php
                    $max = max(1, max(array_column($signupTrend, 'count')));
                    $w = 800; $h = 80; $n = count($signupTrend);
                    $step = $n > 1 ? $w / ($n - 1) : $w;
                @endphp
                <svg viewBox="0 0 {{ $w }} {{ $h + 20 }}" class="mt-3 h-24 w-full">
                    <polyline fill="none" stroke="#0ea5e9" stroke-width="2"
                              points="@foreach ($signupTrend as $i => $p){{ round($i * $step, 1) }},{{ round($h - ($p['count'] / $max) * $h, 1) }} @endforeach" />
                    @foreach ($signupTrend as $i => $p)
                        <circle cx="{{ round($i * $step, 1) }}" cy="{{ round($h - ($p['count'] / $max) * $h, 1) }}" r="2" fill="#0ea5e9">
                            <title>{{ $p['date'] }}: {{ $p['count'] }}</title>
                        </circle>
                    @endforeach
                </svg>
                <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                    <span>{{ $signupTrend[0]['date'] ?? '' }}</span>
                    <span>{{ end($signupTrend)['date'] ?? '' }}</span>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Recent users --}}
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-1">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.recentUsers') }}</h3>
                        <a href="{{ route('admin.users') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.viewAll') }} →</a>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($recentUsers as $user)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $user->name }}</a>
                                    <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
                                </div>
                                <div class="shrink-0 text-xs text-slate-500">{{ $user->created_at->diffForHumans() }}</div>
                            </li>
                        @empty
                            <li class="px-6 py-4 text-sm text-slate-500">{{ __('app.admin.noUsers') }}</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Top users by classrooms --}}
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-1">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.top_users') }}</h3>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($topUsers as $user)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">{{ $user->name }}</a>
                                    <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
                                </div>
                                <div class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $user->classrooms_count }} {{ __('app.admin.stat_classrooms') }}</div>
                            </li>
                        @empty
                            <li class="px-6 py-4 text-sm text-slate-500">{{ __('app.admin.noUsers') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white shadow-sm lg:col-span-1">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-brand-200">{{ __('app.admin.quickActions') }}</h3>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('admin.users') }}"
                           class="flex items-center justify-between rounded-lg bg-white/10 px-4 py-3 backdrop-blur transition hover:bg-white/20">
                            <span class="text-sm font-medium">{{ __('app.admin.tab_users') }}</span><span>→</span>
                        </a>
                        <a href="{{ route('admin.site-settings.edit') }}"
                           class="flex items-center justify-between rounded-lg bg-white/10 px-4 py-3 backdrop-blur transition hover:bg-white/20">
                            <span class="text-sm font-medium">{{ __('app.cms.nav') }}</span><span>→</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
