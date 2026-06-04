<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }} / {{ __('app.admin.products.social.label') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.social.tab_feeds') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            {{-- Add feed form --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('app.admin.products.social.add_feed') }}</h3>
                <form method="POST" action="{{ route('admin.social.feeds.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-slate-700">{{ __('app.admin.products.social.feed_name') }}</label>
                        <input type="text" name="name" required maxlength="100" placeholder="{{ __('app.admin.products.social.feed_name_placeholder') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                               value="{{ old('name') }}">
                    </div>
                    <div class="flex-[2]">
                        <label class="mb-1 block text-xs font-medium text-slate-700">{{ __('app.admin.products.social.feed_url') }}</label>
                        <input type="url" name="url" required maxlength="500" placeholder="https://example.com/rss.xml"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                               value="{{ old('url') }}">
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60">
                        {{ __('app.admin.products.social.feed_add') }}
                    </button>
                </form>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Feed list --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('app.admin.products.social.feeds_list') }}</h3>
                </div>
                @if ($feeds->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.social.feeds_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($feeds as $feed)
                            <li class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-900">{{ $feed->name }}</span>
                                        @if (! $feed->is_active)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ __('app.admin.products.social.inactive') }}</span>
                                        @endif
                                        @if ($feed->fail_count >= 3)
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-600">{{ __('app.admin.products.social.feed_failing') }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 truncate text-xs text-slate-400">{{ $feed->url }}</div>
                                    @if ($feed->last_fetched_at)
                                        <div class="mt-0.5 text-xs text-slate-400">{{ __('app.admin.products.social.last_fetched') }}: {{ $feed->last_fetched_at->diffForHumans() }}</div>
                                    @endif
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <form method="POST" action="{{ route('admin.social.feeds.toggle', $feed) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $feed->is_active ? 'border-slate-200 text-slate-600 hover:bg-slate-50' : 'border-brand-200 text-brand-700 hover:bg-brand-50' }}">
                                            {{ $feed->is_active ? __('app.admin.products.social.deactivate') : __('app.admin.products.social.activate') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.social.feeds.destroy', $feed) }}"
                                          data-confirm="{{ __('app.admin.products.social.feed_delete_confirm', ['name' => $feed->name]) }}"
                                          data-confirm-danger="1">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                            {{ __('app.common.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
