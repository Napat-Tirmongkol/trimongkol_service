@php
    $companyName = setting('company.name') ?: (setting('brand.name') ?: config('app.name'));
@endphp
<div class="flex items-start justify-between gap-6 border-b-2 border-slate-900 pb-5">
    <div class="flex items-start gap-3">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-bold text-white">T</span>
        <div>
            <div class="text-base font-bold text-slate-900">{{ $companyName }}</div>
            @if (setting('company.address'))
                <div class="mt-0.5 max-w-xs whitespace-pre-line text-xs leading-relaxed text-slate-500">{{ setting('company.address') }}</div>
            @endif
            <div class="mt-0.5 space-x-1 text-xs text-slate-500">
                @if (setting('company.tax_id'))<span>เลขผู้เสียภาษี {{ setting('company.tax_id') }}</span>@endif
                @if (setting('company.branch'))<span>· {{ setting('company.branch') }}</span>@endif
                @if (setting('company.phone'))<span>· โทร {{ setting('company.phone') }}</span>@endif
            </div>
        </div>
    </div>
    <div class="shrink-0 text-right">
        <div class="text-xl font-bold tracking-tight text-slate-900">{{ $docTitle }}</div>
        <div class="text-[11px] uppercase tracking-[0.15em] text-slate-400">{{ $docTitleEn }}</div>
        <div class="mt-2 font-mono text-sm font-semibold text-slate-700">{{ $docNo }}</div>
    </div>
</div>
