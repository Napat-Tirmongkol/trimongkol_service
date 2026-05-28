@props([
    'image',
    'eyebrow' => null,
    'title',
    'titleHighlight' => null,
    'subtitle' => null,
    'minHeight' => 'min-h-[80vh]',
    'primaryAction' => null,
    'secondaryAction' => null,
])

<section class="relative {{ $minHeight }} w-full overflow-hidden">
    <img src="{{ $image }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/70 via-slate-900/40 to-slate-900/70"></div>

    <div class="relative z-10 mx-auto flex {{ $minHeight }} max-w-5xl flex-col items-center justify-center px-6 pt-24 pb-12 text-center">
        @if ($eyebrow)
            <span class="rounded-full border border-white/30 bg-white/10 px-4 py-1 text-xs font-medium uppercase tracking-[0.18em] text-white/90 backdrop-blur">
                {{ $eyebrow }}
            </span>
        @endif

        <h1 class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight text-white drop-shadow-md md:text-6xl lg:text-7xl">
            {{ $title }}
            @if ($titleHighlight)
                <span class="bg-gradient-to-r from-brand-300 via-brand-200 to-white bg-clip-text text-transparent">
                    {{ $titleHighlight }}
                </span>
            @endif
        </h1>

        @if ($subtitle)
            <p class="mt-6 max-w-2xl text-base text-white/85 md:text-lg">{{ $subtitle }}</p>
        @endif

        @if ($primaryAction || $secondaryAction)
            <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row">
                @if ($primaryAction)
                    <a href="{{ $primaryAction['href'] }}"
                       class="group inline-flex items-center gap-2 rounded-full bg-white px-7 py-3.5 text-base font-semibold text-slate-900 shadow-lg shadow-black/20 transition hover:bg-slate-100">
                        {{ $primaryAction['label'] }}
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition group-hover:translate-x-0.5">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                @endif
                @if ($secondaryAction)
                    <a href="{{ $secondaryAction['href'] }}"
                       class="rounded-full border border-white/40 bg-white/10 px-7 py-3.5 text-base font-medium text-white backdrop-blur transition hover:bg-white/20">
                        {{ $secondaryAction['label'] }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
