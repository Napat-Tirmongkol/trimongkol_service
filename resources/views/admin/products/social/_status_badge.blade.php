@php
    $badge = [
        'pending_ai' => 'bg-slate-100 text-slate-600',
        'draft'      => 'bg-amber-100 text-amber-700',
        'approved'   => 'bg-blue-100 text-blue-700',
        'published'  => 'bg-emerald-100 text-emerald-700',
        'failed'     => 'bg-red-100 text-red-700',
    ];
    $label = [
        'pending_ai' => __('app.admin.products.social.status_pending_ai'),
        'draft'      => __('app.admin.products.social.status_draft'),
        'approved'   => __('app.admin.products.social.status_approved'),
        'published'  => __('app.admin.products.social.status_published'),
        'failed'     => __('app.admin.products.social.status_failed'),
    ];
@endphp
<span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$status] ?? 'bg-slate-100 text-slate-600' }}">
    {{ $label[$status] ?? $status }}
</span>
