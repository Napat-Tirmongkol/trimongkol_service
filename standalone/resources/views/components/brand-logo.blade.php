@props([
    'size' => 9,          // tailwind h-/w- unit
    'rounded' => 'rounded-xl',
])
@php
    $name = config('brand.name', config('app.name'));
    $logo = config('brand.logo');
    $initial = mb_strtoupper(mb_substr($name, 0, 1)) ?: 'L';
    $box = "h-{$size} w-{$size}";
@endphp
@if ($logo)
    <img src="{{ \Illuminate\Support\Str::startsWith($logo, ['http', '/']) ? $logo : asset($logo) }}"
         alt="{{ $name }}" {{ $attributes->merge(['class' => "$box $rounded object-contain"]) }}>
@else
    <span {{ $attributes->merge(['class' => "grid $box shrink-0 place-items-center $rounded bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white"]) }}>{{ $initial }}</span>
@endif
