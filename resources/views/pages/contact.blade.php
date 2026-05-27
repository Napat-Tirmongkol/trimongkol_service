@extends('layouts.marketing')

@section('title', __('site.contact.heading') . ' — ' . __('site.brand.name'))

@section('content')
    <section class="relative overflow-hidden border-b border-slate-200 bg-slate-50">
        <div class="bg-grid absolute inset-0 opacity-30" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-4xl px-6 py-20 text-center md:py-24">
            <h1 class="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">{{ __('site.contact.heading') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">{{ __('site.contact.subheading') }}</p>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto grid max-w-6xl gap-10 px-6 md:grid-cols-5">
            <div class="md:col-span-2">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('site.contact.infoTitle') }}</h2>
                <ul class="mt-6 space-y-5">
                    <li class="flex gap-4">
                        <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('site.contact.infoEmail') }}</div>
                            <a href="mailto:{{ config('site.email') }}" class="mt-0.5 block text-sm font-medium text-slate-900 hover:text-brand-700">{{ config('site.email') }}</a>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('site.contact.infoPhone') }}</div>
                            <div class="mt-0.5 text-sm font-medium text-slate-900">{{ config('site.phone') }}</div>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('site.contact.infoLine') }}</div>
                            <div class="mt-0.5 text-sm font-medium text-slate-900">{{ config('site.line') }}</div>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('site.contact.infoHoursTitle') }}</div>
                            <div class="mt-0.5 text-sm font-medium text-slate-900">{{ __('site.contact.infoHours') }}</div>
                        </div>
                    </li>
                </ul>
            </div>

            <form method="POST" action="{{ route('contact.submit') }}"
                  class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm md:col-span-3">
                @csrf

                @if (session('contact_success'))
                    <p class="mb-5 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ __('site.contact.formSuccess') }}
                    </p>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-field name="name" :label="__('site.contact.formName')" required autocomplete="name"/>
                    <x-form-field name="email" type="email" :label="__('site.contact.formEmail')" required autocomplete="email"/>
                    <x-form-field name="phone" type="tel" :label="__('site.contact.formPhone')" autocomplete="tel"/>
                    <x-form-field name="company" :label="__('site.contact.formCompany')" autocomplete="organization"/>
                </div>

                <div class="mt-5">
                    <label for="message" class="block text-sm font-medium text-slate-700">{{ __('site.contact.formMessage') }}</label>
                    <textarea id="message" name="message" rows="5" required
                              placeholder="{{ __('site.contact.formMessagePlaceholder') }}"
                              class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="mt-6 w-full rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                    {{ __('site.contact.formSubmit') }}
                </button>
            </form>
        </div>
    </section>
@endsection
