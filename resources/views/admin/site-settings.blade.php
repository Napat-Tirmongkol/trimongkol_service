@php
    // Searchable string per section = section title + every field label + key,
    // so the live filter matches either a human label or the dotted setting key.
    $sectionSearch = [];
    foreach ($schema as $section => $fields) {
        $parts = [$section];
        foreach ($fields as $k => $cfg) {
            $parts[] = $cfg['label'];
            $parts[] = $k;
        }
        $sectionSearch[$section] = implode(' ', $parts);
    }
@endphp

<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('app.cms.heading') }}
            </h2>
            <a href="{{ route('home') }}" target="_blank"
               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('app.cms.previewSite') }} ↗
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="cmsEditor()">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="lg:flex lg:items-start lg:gap-8">
                {{-- Sidebar: live search + section navigation --}}
                <aside class="mb-6 lg:mb-0 lg:sticky lg:top-6 lg:w-56 lg:shrink-0">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <label class="relative block">
                            <span class="sr-only">{{ __('app.cms.searchPlaceholder') }}</span>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
                            </svg>
                            <input type="search" x-model="q" autocomplete="off"
                                   placeholder="{{ __('app.cms.searchPlaceholder') }}"
                                   class="w-full rounded-lg border-slate-300 py-2 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </label>

                        {{-- Desktop: vertical section list with scrollspy --}}
                        <nav class="mt-4 hidden space-y-0.5 lg:block">
                            <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('app.cms.sections') }}</p>
                            @foreach ($schema as $section => $fields)
                                <a href="#sec-{{ \Illuminate\Support\Str::slug($section) }}"
                                   data-spy-link="sec-{{ \Illuminate\Support\Str::slug($section) }}"
                                   data-section-search="{{ $sectionSearch[$section] }}"
                                   x-show="hit($el.dataset.sectionSearch)"
                                   class="block truncate rounded-md px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                    {{ $section }}
                                </a>
                            @endforeach
                        </nav>

                        {{-- Mobile: horizontal chips --}}
                        <nav class="mt-3 flex gap-2 overflow-x-auto pb-1 lg:hidden">
                            @foreach ($schema as $section => $fields)
                                <a href="#sec-{{ \Illuminate\Support\Str::slug($section) }}"
                                   data-section-search="{{ $sectionSearch[$section] }}"
                                   x-show="hit($el.dataset.sectionSearch)"
                                   class="shrink-0 whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200">
                                    {{ $section }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                {{-- Form --}}
                <form method="POST" action="{{ route('admin.site-settings.update') }}" enctype="multipart/form-data" class="space-y-8 lg:min-w-0 lg:flex-1">
                    @csrf
                    @method('PATCH')

                    @foreach ($schema as $section => $fields)
                        <section id="sec-{{ \Illuminate\Support\Str::slug($section) }}"
                                 data-spy-section
                                 data-section-search="{{ $sectionSearch[$section] }}"
                                 x-show="hit($el.dataset.sectionSearch)"
                                 class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-base font-semibold text-slate-900">{{ $section }}</h3>
                            <div class="mt-5 space-y-5">
                                @foreach ($fields as $key => $cfg)
                                    <div data-field-search="{{ $section }} {{ $cfg['label'] }} {{ $key }}"
                                         x-show="hit($el.dataset.fieldSearch)">
                                        <label class="block text-sm font-medium text-slate-700">{{ $cfg['label'] }}</label>
                                        <p class="text-xs text-slate-400 font-mono">{{ $key }}</p>

                                        @if ($cfg['type'] === 'shared')
                                            @if (! empty($cfg['toggle']))
                                                @php
                                                    $defaultOn = isset($cfg['default_on']) ? $cfg['default_on'] : true;
                                                    $on = $defaultOn 
                                                        ? ($values[$key] ?? '') !== '0' 
                                                        : ($values[$key] ?? '') === '1';
                                                @endphp
                                                <label class="relative mt-2 inline-flex cursor-pointer items-center">
                                                    <input type="hidden" name="s[{{ $key }}]" value="0">
                                                    <input type="checkbox" name="s[{{ $key }}]" value="1" @checked($on) class="peer sr-only">
                                                    <div class="h-6 w-11 rounded-full bg-slate-300 transition-colors peer-checked:bg-brand-600 peer-focus:ring-2 peer-focus:ring-brand-300 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-full"></div>
                                                </label>
                                            @elseif (! empty($cfg['textarea']))
                                                <textarea name="s[{{ $key }}]" rows="2"
                                                          class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $values[$key] ?? null ?? '' }}</textarea>
                                            @else
                                                <input type="text" name="s[{{ $key }}]"
                                                       value="{{ $values[$key] ?? null ?? '' }}"
                                                       class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                            @endif

                                            @if (! empty($cfg['upload']))
                                                @php $cur = ($values[$key] ?? '') ?: config('site.hero_images.' . \Illuminate\Support\Str::after($key, 'hero_image.')); @endphp
                                                <div class="mt-2 flex items-center gap-3">
                                                    @if ($cur)
                                                        <img src="{{ $cur }}" alt="" class="h-14 w-24 shrink-0 rounded-md object-cover ring-1 ring-slate-200">
                                                    @endif
                                                    <div class="flex-1">
                                                        <label class="block text-xs font-medium text-slate-500">{{ __('app.cms.upload_label') }}</label>
                                                        <input type="file" name="upload[{{ str_replace('.', '_', $key) }}]" accept="image/png,image/jpeg,image/webp"
                                                               class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                                                        <p class="mt-1 text-xs text-slate-400">{{ __('app.cms.upload_hint') }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div class="mt-1.5 grid gap-3 sm:grid-cols-2">
                                                @foreach (['th' => '🇹🇭 TH', 'en' => '🇬🇧 EN'] as $locale => $flag)
                                                    <div>
                                                        <span class="text-xs font-medium text-slate-500">{{ $flag }}</span>
                                                        @if (! empty($cfg['textarea']))
                                                            <textarea name="s[{{ $key }}.{{ $locale }}]" rows="2"
                                                                      placeholder="{{ trans('site.' . $key, [], $locale) }}"
                                                                      class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $values["{$key}.{$locale}"] ?? null ?? '' }}</textarea>
                                                        @else
                                                            <input type="text" name="s[{{ $key }}.{{ $locale }}]"
                                                                   value="{{ $values["{$key}.{$locale}"] ?? null ?? '' }}"
                                                                   placeholder="{{ trans('site.' . $key, [], $locale) }}"
                                                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            <p class="mt-1 text-xs text-slate-400">{{ __('app.cms.placeholderHint') }}</p>
                                        @endif

                                        @if (! empty($cfg['hint']))
                                            <p class="mt-2 text-xs text-slate-500">{{ $cfg['hint'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    {{-- Empty state when the live filter matches nothing --}}
                    <div x-show="!hasResults" x-cloak
                         class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                        {{ __('app.cms.noResults') }}
                    </div>

                    <div class="sticky bottom-4 z-10 rounded-xl bg-slate-900 px-6 py-4 shadow-2xl ring-1 ring-slate-700">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-slate-300">{{ __('app.cms.savedHint') }}</p>
                            <button type="submit"
                                    class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-700">
                                {{ __('app.cms.save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cmsEditor', () => ({
                q: '',
                hit(text) {
                    const q = (this.q || '').trim().toLowerCase();
                    return q === '' || (text || '').toLowerCase().includes(q);
                },
                get hasResults() {
                    if ((this.q || '').trim() === '') return true;
                    return Array.from(this.$root.querySelectorAll('[data-spy-section]'))
                        .some((el) => this.hit(el.dataset.sectionSearch));
                },
            }));
        });

        // Scrollspy — highlight the section nav link nearest the top of the viewport.
        (function () {
            function initSpy() {
                const links = Array.from(document.querySelectorAll('[data-spy-link]'));
                const sections = Array.from(document.querySelectorAll('[data-spy-section]'));
                if (!links.length || !sections.length || !('IntersectionObserver' in window)) return;
                const linkById = {};
                links.forEach((l) => { linkById[l.dataset.spyLink] = l; });
                const activeClasses = ['bg-slate-100', 'font-semibold'];
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        const link = linkById[entry.target.id];
                        if (!link) return;
                        if (entry.isIntersecting) {
                            links.forEach((l) => l.classList.remove(...activeClasses));
                            link.classList.add(...activeClasses);
                        }
                    });
                }, { rootMargin: '-10% 0px -75% 0px', threshold: 0 });
                sections.forEach((s) => observer.observe(s));
            }
            if (document.readyState !== 'loading') initSpy();
            else document.addEventListener('DOMContentLoaded', initSpy);
        })();
    </script>
</x-admin-layout>
