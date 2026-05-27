@php
    $links = [
        ['route' => 'home', 'label' => __('site.nav.home')],
        ['route' => 'services', 'label' => __('site.nav.services')],
        ['route' => 'about', 'label' => __('site.nav.about')],
        ['route' => 'contact', 'label' => __('site.nav.contact')],
    ];
@endphp
<footer class="border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-12">
        <div class="grid gap-10 md:grid-cols-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white">T</span>
                    <span class="text-lg font-semibold text-slate-900">{{ __('site.brand.name') }}</span>
                </div>
                <p class="mt-3 max-w-sm text-sm text-slate-600">{{ __('site.footer.tagline') }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-900">{{ __('site.footer.navHeading') }}</h3>
                <ul class="mt-4 space-y-2">
                    @foreach ($links as $link)
                        <li><a href="{{ route($link['route']) }}" class="text-sm text-slate-600 hover:text-brand-700">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-900">{{ __('site.footer.contactHeading') }}</h3>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li><a href="mailto:{{ config('site.email') }}" class="hover:text-brand-700">{{ config('site.email') }}</a></li>
                    <li>{{ config('site.phone') }}</li>
                    <li>LINE: {{ config('site.line') }}</li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-200 pt-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} {{ __('site.brand.name') }}. {{ __('site.footer.copyright') }}.
        </div>
    </div>
</footer>
