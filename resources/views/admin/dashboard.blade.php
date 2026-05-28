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

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @php
                    $cards = [
                        ['k' => __('app.admin.stat_users'), 'v' => $stats['users']],
                        ['k' => __('app.admin.stat_admins'), 'v' => $stats['admins']],
                        ['k' => __('app.admin.stat_classrooms'), 'v' => $stats['classrooms']],
                        ['k' => __('app.admin.stat_assignments'), 'v' => $stats['assignments']],
                        ['k' => __('app.admin.stat_submissions'), 'v' => $stats['submissions']],
                    ];
                @endphp
                @foreach ($cards as $c)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ $c['k'] }}</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900">{{ $c['v'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.recentUsers') }}</h3>
                        <a href="{{ route('admin.users') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.viewAll') }} →</a>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($recentUsers as $user)
                            <li class="flex items-center justify-between px-6 py-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                </div>
                                <div class="text-xs text-slate-500">{{ $user->created_at->diffForHumans() }}</div>
                            </li>
                        @empty
                            <li class="px-6 py-4 text-sm text-slate-500">{{ __('app.admin.noUsers') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-brand-200">{{ __('app.admin.quickActions') }}</h3>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('admin.site-settings.edit') }}"
                           class="flex items-center justify-between rounded-lg bg-white/10 px-4 py-3 backdrop-blur transition hover:bg-white/20">
                            <span class="text-sm font-medium">{{ __('app.cms.nav') }}</span>
                            <span>→</span>
                        </a>
                        <a href="{{ route('admin.users') }}"
                           class="flex items-center justify-between rounded-lg bg-white/10 px-4 py-3 backdrop-blur transition hover:bg-white/20">
                            <span class="text-sm font-medium">{{ __('app.admin.tab_users') }}</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
