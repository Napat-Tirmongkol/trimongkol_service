@props(['category' => 'homework'])

@php
    $styles = [
        'homework'  => 'bg-amber-50 text-amber-700 ring-amber-200',
        'classwork' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'quiz'      => 'bg-violet-50 text-violet-700 ring-violet-200',
        'exam'      => 'bg-rose-50 text-rose-700 ring-rose-200',
        'project'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'other'     => 'bg-slate-50 text-slate-700 ring-slate-200',
    ];
    $cls = $styles[$category] ?? $styles['other'];
@endphp

<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $cls }}">
    {{ __("app.assignments.category_{$category}") }}
</span>
