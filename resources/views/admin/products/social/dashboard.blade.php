<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.social.label') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.products.social.desc') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{-- Stat cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.social.stat_feeds') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['feeds_active'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.products.social.stat_feeds_total', ['n' => $stats['feeds']]) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.social.stat_draft') }}</div>
                    <div class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['posts_draft'] }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ __('app.admin.products.social.stat_approved', ['n' => $stats['posts_approved']]) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.social.stat_published_today') }}</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['posts_published_today'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.products.social.stat_total') }}</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['posts_total'] }}</div>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.social.fetch-now') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.admin.products.social.fetch_now') }}
                    </button>
                </form>
                <a href="{{ route('admin.social.posts', ['status' => 'draft']) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm hover:bg-amber-100">
                    {{ __('app.admin.products.social.review_drafts') }}
                    @if ($stats['posts_draft'] > 0)
                        <span class="rounded-full bg-amber-200 px-1.5 py-0.5 text-xs">{{ $stats['posts_draft'] }}</span>
                    @endif
                </a>
            </div>

            {{-- Recent posts --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.social.recent_posts') }}</h3>
                    <a href="{{ route('admin.social.posts') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">{{ __('app.admin.viewAll') }} →</a>
                </div>
                @if ($recent->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.social.posts_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recent as $post)
                            <li class="flex items-center justify-between gap-4 px-6 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-slate-900">{{ $post->title }}</div>
                                    <div class="mt-0.5 text-xs text-slate-400">{{ $post->feedSource?->name }} · {{ $post->created_at->diffForHumans() }}</div>
                                </div>
                                @include('admin.products.social._status_badge', ['status' => $post->status])
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
