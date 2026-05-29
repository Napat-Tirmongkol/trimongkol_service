@extends('layouts.marketing')

@section('title', t('donate.heading') . ' — ' . t('brand.name'))

@php
    $qr = setting('donate.qr');
    $name = setting('donate.name');
    $account = setting('donate.account');
    $enabled = setting('donate.enabled') === '1';
@endphp

@section('content')
    @include('partials.hero', [
        'image' => setting('hero_image.contact', config('site.hero_images.contact') ?? config('site.hero_images.home')),
        'eyebrow' => t('donate.nav'),
        'title' => t('donate.heading'),
        'subtitle' => t('donate.subtitle'),
        'minHeight' => 'min-h-[50vh]',
    ])

    <section class="relative z-20 mx-auto -mt-24 max-w-xl px-4 pb-20 sm:px-6 md:pb-28">
        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/50">
            @if ($enabled && $qr)
                <div class="bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-5 text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-brand-100">{{ t('donate.scan') }}</p>
                </div>
                <div class="p-8 text-center">
                    <div class="mx-auto inline-block rounded-2xl bg-white p-3 ring-1 ring-slate-200">
                        <img src="{{ $qr }}" alt="QR" class="mx-auto h-64 w-64 object-contain">
                    </div>

                    @if ($name || $account)
                        <dl class="mx-auto mt-6 max-w-xs space-y-2 text-sm">
                            @if ($name)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">{{ t('donate.name_label') }}</dt>
                                    <dd class="font-semibold text-slate-900">{{ $name }}</dd>
                                </div>
                            @endif
                            @if ($account)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">{{ t('donate.account_label') }}</dt>
                                    <dd class="font-mono font-semibold text-slate-900">{{ $account }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif

                    <p class="mx-auto mt-6 max-w-sm text-sm leading-relaxed text-slate-600">{{ t('donate.note') }}</p>
                </div>
            @else
                <div class="p-12 text-center text-sm text-slate-500">{{ t('donate.disabled') }}</div>
            @endif
        </div>
    </section>
@endsection
