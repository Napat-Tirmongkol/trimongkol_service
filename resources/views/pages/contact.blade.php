@extends('layouts.marketing')

@section('title', __('site.contact.heading') . ' — ' . __('site.brand.name'))

@php
    $infoItems = [
        ['label' => __('site.contact.infoEmail'), 'value' => config('site.email'), 'href' => 'mailto:' . config('site.email'), 'svg' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'],
        ['label' => __('site.contact.infoPhone'), 'value' => config('site.phone'), 'href' => null, 'svg' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/>'],
        ['label' => __('site.contact.infoLine'), 'value' => config('site.line'), 'href' => null, 'svg' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>'],
        ['label' => __('site.contact.infoHoursTitle'), 'value' => __('site.contact.infoHours'), 'href' => null, 'svg' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
    ];
@endphp

@section('content')
    @include('partials.hero', [
        'image' => config('site.hero_images.contact'),
        'eyebrow' => __('site.nav.contact'),
        'title' => __('site.contact.heading'),
        'subtitle' => __('site.contact.subheading'),
        'minHeight' => 'min-h-[60vh]',
    ])

    {{-- Floating contact card --}}
    <section class="relative z-20 mx-auto -mt-24 max-w-6xl px-4 pb-20 sm:px-6 md:pb-28">
        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50">
            <div class="grid gap-0 md:grid-cols-5">
                {{-- Info side --}}
                <div class="bg-slate-900 p-8 text-white md:col-span-2 md:p-10">
                    <h2 class="text-xl font-bold md:text-2xl">{{ __('site.contact.infoTitle') }}</h2>
                    <ul class="mt-6 space-y-5">
                        @foreach ($infoItems as $item)
                            <li class="flex gap-4">
                                <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-xl bg-white/10 backdrop-blur ring-1 ring-white/15">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        {!! $item['svg'] !!}
                                    </svg>
                                </span>
                                <div>
                                    <div class="text-xs font-medium uppercase tracking-[0.14em] text-slate-400">{{ $item['label'] }}</div>
                                    @if ($item['href'])
                                        <a href="{{ $item['href'] }}" class="mt-0.5 block text-sm font-medium text-white hover:text-brand-300">{{ $item['value'] }}</a>
                                    @else
                                        <div class="mt-0.5 text-sm font-medium text-white">{{ $item['value'] }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Form side --}}
                <form method="POST" action="{{ route('contact.submit') }}"
                      class="p-8 md:col-span-3 md:p-10">
                    @csrf

                    @if (session('contact_success'))
                        <p class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                            {{ __('site.contact.formSuccess') }}
                        </p>
                    @endif

                    <h3 class="text-lg font-semibold text-slate-900">{{ __('site.contact.formMessage') }}</h3>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-form-field name="name" :label="__('site.contact.formName')" required autocomplete="name"/>
                        <x-form-field name="email" type="email" :label="__('site.contact.formEmail')" required autocomplete="email"/>
                        <x-form-field name="phone" type="tel" :label="__('site.contact.formPhone')" autocomplete="tel"/>
                        <x-form-field name="company" :label="__('site.contact.formCompany')" autocomplete="organization"/>
                    </div>

                    <div class="mt-4">
                        <label for="message" class="block text-sm font-medium text-slate-700">{{ __('site.contact.formMessage') }}</label>
                        <textarea id="message" name="message" rows="4" required
                                  placeholder="{{ __('site.contact.formMessagePlaceholder') }}"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-slate-900 px-6 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-slate-800 sm:w-auto">
                        {{ __('site.contact.formSubmit') }}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection
