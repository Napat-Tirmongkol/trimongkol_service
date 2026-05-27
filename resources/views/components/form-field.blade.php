@props([
    'name',
    'label',
    'type' => 'text',
    'autocomplete' => null,
])

@php
    $required = $attributes->has('required');
@endphp

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="ml-1 text-rose-500">*</span>
        @endif
    </label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ old($name) }}"
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           {{ $attributes->merge(['class' => 'mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20']) }}>
    @error($name)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
