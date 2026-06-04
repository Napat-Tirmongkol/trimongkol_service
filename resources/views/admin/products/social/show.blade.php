<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.social.posts') }}" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('app.admin.nav_products') }} / {{ __('app.admin.products.social.tab_posts') }}</div>
                <h2 class="mt-0.5 text-xl font-semibold leading-tight text-gray-800">{{ __('app.admin.products.social.post_detail') }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{-- Meta bar --}}
            <div class="flex flex-wrap items-center gap-3">
                @include('admin.products.social._status_badge', ['status' => $post->status])
                <span class="text-sm text-slate-500">{{ $post->feedSource?->name }}</span>
                @if ($post->source_published_at)
                    <span class="text-sm text-slate-400">{{ $post->source_published_at->format('d M Y') }}</span>
                @endif
                @if ($post->published_at)
                    <span class="text-xs text-emerald-600">{{ __('app.admin.products.social.published_at') }} {{ $post->published_at->format('d M Y H:i') }}</span>
                @endif
            </div>

            {{-- Source news card --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.admin.products.social.source_news') }}</div>
                <h3 class="text-base font-semibold text-slate-900">{{ $post->title }}</h3>
                <a href="{{ $post->source_url }}" target="_blank" rel="noopener"
                   class="mt-1 block truncate text-xs text-brand-600 hover:underline">{{ $post->source_url }}</a>
            </div>

            {{-- Facebook preview --}}
            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.admin.products.social.fb_preview') }}</div>
                <div class="overflow-hidden rounded-xl border border-[#dddfe2] bg-white shadow-sm">
                    {{-- Facebook-style header --}}
                    <div class="flex items-center gap-3 border-b border-[#dddfe2] px-4 py-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                            F
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-[#050505]">Your Facebook Page</div>
                            <div class="flex items-center gap-1 text-xs text-[#65676b]">
                                <span>{{ $post->published_at ? $post->published_at->diffForHumans() : __('app.admin.products.social.not_published') }}</span>
                                <span>·</span>
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 100 16A8 8 0 008 0zM4.5 7.5a.5.5 0 000 1h5.793l-2.147 2.146a.5.5 0 00.708.708l3-3a.5.5 0 000-.708l-3-3a.5.5 0 10-.708.708L10.293 7.5H4.5z"/></svg>
                            </div>
                        </div>
                    </div>
                    {{-- Post content --}}
                    <div class="px-4 py-3">
                        @if ($post->ai_content)
                            <p class="whitespace-pre-line text-[15px] leading-[1.6] text-[#050505]">{{ $post->ai_content }}</p>
                        @else
                            <p class="text-sm italic text-slate-400">{{ __('app.admin.products.social.no_content') }}</p>
                        @endif
                    </div>
                    {{-- Reaction bar (decorative) --}}
                    <div class="flex items-center justify-between border-t border-[#dddfe2] px-4 py-2 text-xs text-[#65676b]">
                        <div class="flex items-center gap-1">
                            <span>👍 ❤️</span>
                            <span>0</span>
                        </div>
                        <span>0 {{ __('app.admin.products.social.comments') }}</span>
                    </div>
                </div>
            </div>

            {{-- Agent Activity Log --}}
            @if ($logs->isNotEmpty() || $post->isPending())
                <div>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.admin.products.social.agent_log') }}</span>
                        @if ($post->isPending())
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                                <span class="h-1.5 w-1.5 animate-ping rounded-full bg-amber-500"></span>
                                {{ __('app.admin.products.social.agent_running') }}
                            </span>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-sm">
                        {{-- Terminal header --}}
                        <div class="flex items-center gap-1.5 border-b border-slate-700 px-4 py-2">
                            <span class="h-3 w-3 rounded-full bg-red-500"></span>
                            <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
                            <span class="h-3 w-3 rounded-full bg-green-500"></span>
                            <span class="ml-2 text-xs text-slate-400">AI Agent — Post #{{ $post->id }}</span>
                        </div>

                        {{-- Log entries --}}
                        <div class="space-y-0 px-4 py-3 font-mono text-xs" id="agent-log-container">
                            @forelse ($logs as $log)
                                @php
                                    $color = match ($log->type) {
                                        'success' => 'text-emerald-400',
                                        'warning' => 'text-amber-400',
                                        'error'   => 'text-red-400',
                                        default   => 'text-slate-300',
                                    };
                                    $icon = match ($log->type) {
                                        'success' => '✓',
                                        'warning' => '⚠',
                                        'error'   => '✗',
                                        default   => '·',
                                    };
                                @endphp
                                <div class="flex items-start gap-3 py-0.5 {{ $color }}">
                                    <span class="shrink-0 text-slate-500">{{ $log->created_at->format('H:i:s') }}</span>
                                    <span class="shrink-0">{{ $icon }}</span>
                                    <span class="flex-1 break-all">{{ $log->message }}</span>
                                    @if ($log->duration_ms)
                                        <span class="shrink-0 text-slate-500">{{ $log->duration_ms }}ms</span>
                                    @endif
                                </div>
                            @empty
                                <div class="py-2 text-slate-500">รอ Agent เริ่มทำงาน...</div>
                            @endforelse

                            @if ($post->isPending())
                                <div class="flex items-center gap-3 py-0.5 text-amber-400">
                                    <span class="shrink-0 text-slate-500">{{ now()->format('H:i:s') }}</span>
                                    <span class="shrink-0 animate-pulse">_</span>
                                    <span>กำลังประมวลผล...</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Edit form --}}
            @if (! $post->isPublished())
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.admin.products.social.edit_content') }}</div>
                    <form method="POST" action="{{ route('admin.social.posts.update', $post) }}">
                        @csrf @method('PATCH')
                        <textarea name="ai_content" rows="10"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm leading-relaxed shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">{{ old('ai_content', $post->ai_content) }}</textarea>
                        @error('ai_content')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <div class="mt-3 flex justify-end">
                            <button type="submit"
                                    class="rounded-lg bg-slate-700 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                                {{ __('app.admin.products.social.save_content') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="flex flex-wrap gap-2">
                @if ($post->isDraft())
                    <form method="POST" action="{{ route('admin.social.posts.approve', $post) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60">
                            {{ __('app.admin.products.social.approve') }}
                        </button>
                    </form>
                @endif
                @if ($post->isApproved())
                    <form method="POST" action="{{ route('admin.social.posts.publish', $post) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60">
                            {{ __('app.admin.products.social.publish_now') }}
                        </button>
                    </form>
                @endif
                @if ($post->isDraft() || $post->isApproved())
                    <form method="POST" action="{{ route('admin.social.posts.reject', $post) }}"
                          data-confirm="{{ __('app.admin.products.social.reject_confirm') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            {{ __('app.admin.products.social.reject') }}
                        </button>
                    </form>
                @endif
                @if ($post->isFailed())
                    <form method="POST" action="{{ route('admin.social.posts.retry', $post) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-2.5 text-sm font-medium text-amber-700 hover:bg-amber-100">
                            {{ __('app.admin.products.social.retry') }}
                        </button>
                    </form>
                @endif
                @if ($post->isPublished() && $post->facebook_post_id)
                    <a href="https://www.facebook.com/{{ $post->facebook_post_id }}"
                       target="_blank" rel="noopener"
                       class="rounded-lg border border-[#1877f2] bg-[#1877f2] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#166fe5]">
                        {{ __('app.admin.products.social.view_on_fb') }}
                    </a>
                @endif
            </div>

        </div>
    </div>
@if ($post->isPending())
<script>setTimeout(() => location.reload(), 3000)</script>
@endif
</x-admin-layout>
