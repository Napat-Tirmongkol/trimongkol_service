@php
    // Company profile is per-workspace (multi-tenant); fall back to global
    // settings / app name for safety if a doc is rendered without one.
    $ws = $workspace ?? null;
    $companyName = ($ws?->company_name) ?: (setting('company.name') ?: config('app.name'));
    $companyAddress = ($ws?->company_address) ?: setting('company.address');
    $companyTaxId = ($ws?->tax_id) ?: setting('company.tax_id');
    $companyBranch = ($ws?->branch) ?: setting('company.branch');
    $companyPhone = ($ws?->phone) ?: setting('company.phone');
    $companyInitial = mb_strtoupper(mb_substr($companyName, 0, 1));
@endphp
<div class="flex items-start justify-between gap-6 border-b-2 border-slate-900 pb-5">
    <div class="flex items-start gap-3">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-bold text-white">{{ $companyInitial }}</span>
        <div>
            <div class="text-base font-bold text-slate-900">{{ $companyName }}</div>
            @if ($companyAddress)
                <div class="mt-0.5 max-w-xs whitespace-pre-line text-xs leading-relaxed text-slate-500">{{ $companyAddress }}</div>
            @endif
            <div class="mt-0.5 space-x-1 text-xs text-slate-500">
                @if ($companyTaxId)<span>เลขผู้เสียภาษี {{ $companyTaxId }}</span>@endif
                @if ($companyBranch)<span>· {{ $companyBranch }}</span>@endif
                @if ($companyPhone)<span>· โทร {{ $companyPhone }}</span>@endif
            </div>
        </div>
    </div>
    <div class="shrink-0 text-right">
        <div class="text-xl font-bold tracking-tight text-slate-900">{{ $docTitle }}</div>
        <div class="text-[11px] uppercase tracking-[0.15em] text-slate-400">{{ $docTitleEn }}</div>
        <div class="mt-2 font-mono text-sm font-semibold text-slate-700">{{ $docNo }}</div>
    </div>
</div>
