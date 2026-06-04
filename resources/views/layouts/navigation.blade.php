@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('') ?: '·';
    $otherLocale = app()->getLocale() === 'th' ? 'en' : 'th';
    $currentWorkspace = \App\Services\CurrentWorkspace::get($user);
    $userWorkspaces = $user ? $user->workspaces()->orderBy('name')->get() : collect();
@endphp
<nav x-data="{
        open: false,
        feedbackOpen: false,
        feedbackCategory: 'bug',
        feedbackAttachment: null,
        feedbackAttachmentName: '',
        feedbackAttachmentUrl: '',
        feedbackAttachmentError: '',
        setFeedbackAttachment(event) {
            const file = event.target.files && event.target.files[0];
            if (this.feedbackAttachmentUrl) URL.revokeObjectURL(this.feedbackAttachmentUrl);
            this.feedbackAttachmentError = '';
            if (!file) {
                this.feedbackAttachment = null;
                this.feedbackAttachmentName = '';
                this.feedbackAttachmentUrl = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.feedbackAttachmentError = 'ไฟล์เกิน 5MB';
                event.target.value = '';
                return;
            }
            this.feedbackAttachment = file;
            this.feedbackAttachmentName = file.name;
            this.feedbackAttachmentUrl = URL.createObjectURL(file);
        },
        clearFeedbackAttachment() {
            if (this.feedbackAttachmentUrl) URL.revokeObjectURL(this.feedbackAttachmentUrl);
            this.feedbackAttachment = null;
            this.feedbackAttachmentName = '';
            this.feedbackAttachmentUrl = '';
            this.feedbackAttachmentError = '';
            if (this.$refs.feedbackFileInput) this.$refs.feedbackFileInput.value = '';
        },
    }" class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto object-contain">
                    <span class="hidden text-sm font-semibold tracking-tight text-slate-900 sm:inline">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('home') }}"
                       class="flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        {{ __('app.nav.website_home') }}
                    </a>
                    @if (setting('donate.enabled') === '1')
                        <a href="{{ route('donate') }}"
                           class="flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" class="shrink-0">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            {{ __('app.nav.support') }}
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}"
                       class="rounded-full px-4 py-1.5 text-sm font-medium transition
                              {{ request()->routeIs('dashboard') || request()->routeIs('classrooms.*')
                                  ? 'bg-slate-900 text-white'
                                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        {{ __('app.classrooms.heading') }}
                    </a>
                    @if (\App\Services\ProductGate::enabled('queue'))
                        <a href="{{ route('queues.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium transition
                                  {{ request()->routeIs('queues.*')
                                      ? 'bg-slate-900 text-white'
                                      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            {{ __('app.queue.nav') }}
                            @unless (\App\Services\ProductGate::isOn('queue'))
                                <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">ปิด</span>
                            @endunless
                        </a>
                    @endif
                    @if ($user?->is_admin)
                        <a href="{{ route('admin.dashboard') }}"
                           class="rounded-full px-4 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            {{ __('app.admin.nav') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="hidden items-center gap-2 md:flex">
                {{-- Workspace switcher: only when user has more than one workspace --}}
                @if ($currentWorkspace && $userWorkspaces->count() > 1)
                    <div x-data="{ ws: false }" @click.outside="ws = false" class="relative">
                        <button @click="ws = !ws" type="button"
                                class="flex max-w-[180px] items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-sm hover:bg-slate-50">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400">
                                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            <span class="truncate font-medium text-slate-700">{{ $currentWorkspace->name }}</span>
                            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="shrink-0 text-slate-400">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="ws" x-cloak x-transition
                             class="absolute right-0 mt-2 w-64 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg ring-1 ring-slate-900/5">
                            @foreach ($userWorkspaces as $w)
                                <form method="POST" action="{{ route('workspaces.switch', $w) }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-100 {{ $currentWorkspace->id === $w->id ? 'bg-slate-100 font-semibold' : '' }}">
                                        <span class="truncate text-slate-800">{{ $w->name }}</span>
                                        @if ($currentWorkspace->id === $w->id)
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-emerald-600"><polyline points="20 6 9 17 4 12"/></svg>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                            <div class="my-1 border-t border-slate-100"></div>
                            <a href="{{ route('workspaces.index') }}" class="block rounded-lg px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">
                                {{ __('app.workspaces.manage') }} →
                            </a>
                        </div>
                    </div>
                @endif

                <a href="{{ route('locale.switch', $otherLocale) }}"
                   class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 hover:bg-slate-100">
                    {{ $otherLocale }}
                </a>

                <div x-data="{ menu: false }" @click.outside="menu = false" class="relative">
                    <button @click="menu = !menu" type="button"
                            class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-3 text-sm hover:bg-slate-50">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">{{ $initials }}</span>
                        <span class="font-medium text-slate-800">{{ $user?->name }}</span>
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" class="text-slate-400">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="menu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-56 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-slate-900/5">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <div class="text-sm font-medium text-slate-900">{{ $user?->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $user?->email }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('Profile') }}
                        </a>
                        <a href="{{ route('workspaces.index') }}"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('app.workspaces.manage') }}
                        </a>
                        <a href="{{ route('plans.index') }}"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('app.plans.nav') }}
                        </a>
                        <button type="button" @click="menu = false; feedbackOpen = true"
                                class="block w-full border-t border-slate-100 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                            {{ __('app.feedback.nav') }}
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full border-t border-slate-100 px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <button type="button" @click="open = !open" aria-label="Menu"
                    class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-slate-700 md:hidden">
                <svg x-show="!open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
                <svg x-show="open" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div x-show="open" x-cloak class="border-t border-slate-200 py-3 md:hidden">
            <div class="flex items-center gap-3 px-1 pb-3">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">{{ $initials }}</span>
                <div>
                    <div class="text-sm font-medium text-slate-900">{{ $user?->name }}</div>
                    <div class="text-xs text-slate-500">{{ $user?->email }}</div>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <a href="{{ route('home') }}"
                   class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-slate-400">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    {{ __('app.nav.website_home') }}
                </a>
                @if (setting('donate.enabled') === '1')
                    <a href="{{ route('donate') }}"
                       class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="shrink-0">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        {{ __('app.nav.support') }}
                    </a>
                @endif
                <a href="{{ route('dashboard') }}"
                   class="rounded-md px-3 py-2 text-sm font-medium
                          {{ request()->routeIs('dashboard') || request()->routeIs('classrooms.*')
                              ? 'bg-slate-900 text-white'
                              : 'text-slate-700 hover:bg-slate-100' }}">
                    {{ __('app.classrooms.heading') }}
                </a>
                @if (\App\Services\ProductGate::enabled('queue'))
                    <a href="{{ route('queues.index') }}"
                       class="flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium
                              {{ request()->routeIs('queues.*')
                                  ? 'bg-slate-900 text-white'
                                  : 'text-slate-700 hover:bg-slate-100' }}">
                        {{ __('app.queue.nav') }}
                        @unless (\App\Services\ProductGate::isOn('queue'))
                            <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">ปิด</span>
                        @endunless
                    </a>
                @endif
                @if ($user?->is_admin)
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        {{ __('app.admin.nav') }}
                    </a>
                @endif
                <a href="{{ route('profile.edit') }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    {{ __('Profile') }}
                </a>
                <a href="{{ route('locale.switch', $otherLocale) }}"
                   class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    {{ __('Language') }} · <span class="font-bold uppercase">{{ $otherLocale }}</span>
                </a>
                <button type="button" @click="open = false; feedbackOpen = true"
                        class="rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100">
                    {{ __('app.feedback.nav') }}
                </button>
                <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-slate-200 pt-2">
                    @csrf
                    <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Feedback modal — opened from the user dropdown or mobile menu.
         Teleported to <body> because the nav has backdrop-blur, which
         per the CSS spec creates a containing block for position:fixed
         descendants and would otherwise trap the overlay inside the
         topbar. --}}
    <template x-teleport="body">
    <div x-show="feedbackOpen" x-cloak
         @keydown.escape.window="feedbackOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm"
         x-transition.opacity>
        <div class="flex min-h-screen items-end justify-center p-0 sm:items-center sm:p-4">
            <div @click.outside="feedbackOpen = false"
                 class="relative w-full overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="translate-y-8 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100">
                <form method="POST" action="{{ route('feedback.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">{{ __('app.feedback.heading') }}</h3>
                            <p class="mt-0.5 text-xs text-slate-500">{{ __('app.feedback.subtitle') }}</p>
                        </div>
                        <button type="button" @click="feedbackOpen = false"
                                class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                aria-label="Close">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-4">
                        @php
                            // Active-state class per category, kept as literal strings so
                            // Tailwind picks them up at build time. Alpine swaps the active
                            // set when feedbackCategory matches — :has(:checked) styling was
                            // unreliable on hosts that hadn't rebuilt the CSS bundle.
                            $categoryActive = [
                                'bug' => 'border-rose-400 bg-rose-50 text-rose-700',
                                'suggestion' => 'border-sky-400 bg-sky-50 text-sky-700',
                                'question' => 'border-amber-400 bg-amber-50 text-amber-700',
                            ];
                        @endphp
                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('app.feedback.field_category') }}</label>
                            <div class="mt-1.5 grid grid-cols-3 gap-2">
                                @foreach ($categoryActive as $cat => $activeClass)
                                    <label class="cursor-pointer rounded-lg border px-3 py-2 text-center text-xs font-medium transition hover:bg-slate-50"
                                           :class="feedbackCategory === '{{ $cat }}' ? '{{ $activeClass }}' : 'border-slate-200 text-slate-700'">
                                        <input type="radio" name="category" value="{{ $cat }}" class="sr-only"
                                               x-model="feedbackCategory">
                                        {{ __("app.feedback.cat_{$cat}") }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="feedback-subject" class="block text-xs font-medium text-slate-600">{{ __('app.feedback.field_subject') }} <span class="text-rose-500">*</span></label>
                            <input id="feedback-subject" name="subject" type="text" required maxlength="150"
                                   placeholder="{{ __('app.feedback.field_subject_placeholder') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label for="feedback-message" class="block text-xs font-medium text-slate-600">{{ __('app.feedback.field_message') }} <span class="text-rose-500">*</span></label>
                            <textarea id="feedback-message" name="message" rows="5" required maxlength="4000"
                                      placeholder="{{ __('app.feedback.field_message_placeholder') }}"
                                      class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600">{{ __('app.feedback.field_attachment') }}</label>
                            <input type="file" name="attachment" accept="image/*" class="hidden"
                                   x-ref="feedbackFileInput"
                                   @change="setFeedbackAttachment($event)">
                            <div class="mt-1.5" x-show="!feedbackAttachment">
                                <button type="button" @click="$refs.feedbackFileInput.click()"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                                    {{ __('app.feedback.attach_image') }}
                                </button>
                                <p class="mt-1 text-[11px] text-slate-400">{{ __('app.feedback.attach_hint') }}</p>
                            </div>
                            <div class="mt-1.5 flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 p-2" x-show="feedbackAttachment" x-cloak>
                                <img :src="feedbackAttachmentUrl" alt="" class="h-16 w-16 shrink-0 rounded object-cover">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-medium text-slate-700" x-text="feedbackAttachmentName"></div>
                                    <button type="button" @click="clearFeedbackAttachment()"
                                            class="mt-1 text-xs font-medium text-rose-600 hover:text-rose-700">
                                        {{ __('app.feedback.remove_image') }}
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-rose-600" x-show="feedbackAttachmentError" x-text="feedbackAttachmentError"></p>
                        </div>

                        <input type="hidden" name="page_url" x-init="$el.value = window.location.href">

                        <p class="text-xs leading-relaxed text-slate-500">{{ __('app.feedback.context_note') }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3">
                        <button type="button" @click="feedbackOpen = false"
                                class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                            {{ __('app.common.cancel') }}
                        </button>
                        <button type="submit"
                                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            {{ __('app.feedback.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</nav>
