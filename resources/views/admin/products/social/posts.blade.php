<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }} / {{ __('app.admin.products.social.label') }}</div>
            <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.social.tab_posts') }}</h2>
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

            {{-- Status filter tabs --}}
            @php
                $tabs = [
                    ''           => __('app.admin.products.social.filter_all'),
                    'pending_ai' => __('app.admin.products.social.status_pending_ai'),
                    'draft'      => __('app.admin.products.social.status_draft'),
                    'approved'   => __('app.admin.products.social.status_approved'),
                    'published'  => __('app.admin.products.social.status_published'),
                    'failed'     => __('app.admin.products.social.status_failed'),
                ];
            @endphp
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $val => $label)
                    <a href="{{ route('admin.social.posts', $val !== '' ? ['status' => $val] : []) }}"
                       class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $status === $val ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }}
                        @if ($val !== '' && isset($counts[$val]) && $counts[$val] > 0)
                            <span class="ml-1 rounded-full bg-slate-200 px-1.5 text-xs text-slate-700">{{ $counts[$val] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Posts list --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                @if ($posts->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.products.social.posts_empty') }}</div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($posts as $post)
                            <li class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @include('admin.products.social._status_badge', ['status' => $post->status])
                                            <span class="text-xs text-slate-400">{{ $post->feedSource?->name }}</span>
                                            @if ($post->source_published_at)
                                                <span class="text-xs text-slate-400">{{ $post->source_published_at->format('d M Y') }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ route('admin.social.posts.show', $post) }}"
                                           class="block text-sm font-medium text-slate-900 hover:text-brand-700">{{ $post->title }}</a>
                                        <a href="{{ $post->source_url }}" target="_blank" rel="noopener"
                                           class="text-xs text-brand-600 hover:underline">{{ $post->source_url }}</a>

                                        @if ($post->ai_content)
                                            <div class="mt-2 line-clamp-3 rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-700 whitespace-pre-line">{{ $post->ai_content }}</div>
                                        @endif

                                        @if ($post->error_message)
                                            <div class="mt-1 text-xs text-red-500">{{ $post->error_message }}</div>
                                        @endif

                                        @if ($post->published_at)
                                            <div class="text-xs text-slate-400">{{ __('app.admin.products.social.published_at') }}: {{ $post->published_at->format('d M Y H:i') }}</div>
                                        @endif
                                    </div>

                                    {{-- Action buttons --}}
                                    <div class="flex shrink-0 flex-col gap-1.5">
                                        @if ($post->isDraft())
                                            <form method="POST" action="{{ route('admin.social.posts.approve', $post) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                                                    {{ __('app.admin.products.social.approve') }}
                                                </button>
                                            </form>
                                        @endif
                                        @if ($post->isApproved())
                                            <form method="POST" action="{{ route('admin.social.posts.publish', $post) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                                                    {{ __('app.admin.products.social.publish_now') }}
                                                </button>
                                            </form>
                                        @endif
                                        @if ($post->isDraft() || $post->isApproved())
                                            <form method="POST" action="{{ route('admin.social.posts.reject', $post) }}"
                                                  data-confirm="{{ __('app.admin.products.social.reject_confirm') }}">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                                    {{ __('app.admin.products.social.reject') }}
                                                </button>
                                            </form>
                                        @endif
                                        @if ($post->isFailed())
                                            <form method="POST" action="{{ route('admin.social.posts.retry', $post) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">
                                                    {{ __('app.admin.products.social.retry') }}
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.social.posts.destroy', $post) }}"
                                              data-confirm="{{ __('app.admin.products.social.post_delete_confirm') }}"
                                              data-confirm-danger="1">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="w-full rounded-lg border border-red-100 px-3 py-1.5 text-xs font-medium text-red-500 hover:bg-red-50">
                                                {{ __('app.common.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-slate-100 px-6 py-3">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
