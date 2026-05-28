<x-app-layout>
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

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.site-settings.update') }}" class="space-y-8">
                @csrf
                @method('PATCH')

                @foreach ($schema as $section => $fields)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">{{ $section }}</h3>
                        <div class="mt-5 space-y-5">
                            @foreach ($fields as $key => $cfg)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ $cfg['label'] }}</label>
                                    <p class="text-xs text-slate-400 font-mono">{{ $key }}</p>

                                    @if ($cfg['type'] === 'shared')
                                        @if (! empty($cfg['textarea']))
                                            <textarea name="s[{{ $key }}]" rows="2"
                                                      class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $values[$key] ?? null ?? '' }}</textarea>
                                        @else
                                            <input type="text" name="s[{{ $key }}]"
                                                   value="{{ $values[$key] ?? null ?? '' }}"
                                                   class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
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
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

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
</x-app-layout>
