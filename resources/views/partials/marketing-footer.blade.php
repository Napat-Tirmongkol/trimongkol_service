@php
    $links = [
        ['route' => 'home', 'label' => t('nav.home')],
        ['route' => 'services', 'label' => t('nav.services')],
        ['route' => 'about', 'label' => t('nav.about')],
        ['route' => 'contact', 'label' => t('nav.contact')],
    ];
@endphp
<footer class="bg-slate-950 text-slate-300">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 md:py-20">
        <div class="grid gap-10 md:grid-cols-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-md shadow-brand-500/30">T</span>
                    <span class="text-base font-semibold text-white">{{ t('brand.name') }}</span>
                </div>
                <p class="mt-4 max-w-sm text-sm text-slate-400">{{ t('footer.tagline') }}</p>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/80">{{ t('footer.navHeading') }}</h3>
                <ul class="mt-5 space-y-2.5">
                    @foreach ($links as $link)
                        <li><a href="{{ route($link['route']) }}" class="text-sm text-slate-300 hover:text-white">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-white/80">{{ t('footer.contactHeading') }}</h3>
                <ul class="mt-5 space-y-2.5 text-sm text-slate-300">
                    <li><a href="mailto:{{ setting('brand.email', config('site.email')) }}" class="hover:text-white">{{ setting('brand.email', config('site.email')) }}</a></li>
                    <li>{{ setting('brand.phone', config('site.phone')) }}</li>
                    <li>LINE: {{ setting('brand.line', config('site.line')) }}</li>
                </ul>
            </div>
        </div>

        <div class="mt-12 border-t border-white/10 pt-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} {{ t('brand.name') }}. {{ t('footer.copyright') }}.
        </div>
    </div>
</footer>
