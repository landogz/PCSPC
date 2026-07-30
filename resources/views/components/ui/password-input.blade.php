@props([
    'name',
    'id' => null,
    'autocomplete' => 'current-password',
    'required' => false,
    'placeholder' => null,
    'disabled' => false,
])

@php
    $inputId = $id ?? $name;
@endphp

<div class="relative" data-password-field>
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="password"
        autocomplete="{{ $autocomplete }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->class('w-full h-11 pl-3 pr-10 rounded-xl border border-border bg-subtle text-sm text-text placeholder:text-faint focus:outline-none focus:border-primary transition-colors') }}
    >
    <button
        type="button"
        data-password-toggle
        aria-controls="{{ $inputId }}"
        aria-label="Show password"
        aria-pressed="false"
        class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg flex items-center justify-center text-muted hover:text-heading hover:bg-subtle transition-colors"
    >
        <i class="ph ph-eye text-lg" aria-hidden="true"></i>
    </button>
</div>
