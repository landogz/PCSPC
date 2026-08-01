@props([
    'name' => 'employee_id',
    'id' => null,
    'label' => 'Employee',
    'required' => true,
    'hint' => 'Search by name, email, or employee number.',
    'placeholder' => 'Search by name or employee #…',
    'errorKey' => 'employee_id',
    'value' => '',
    'displayValue' => '',
])

@php
    $inputId = $id ?? $name;
    $searchId = $inputId.'-search';
@endphp

<div {{ $attributes->class(['space-y-2']) }} data-employee-search-root>
    <label class="{{ $required ? 'ui-label ui-label-required' : 'ui-label' }}" for="{{ $searchId }}">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-employee-id>
    <div class="relative">
        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none" aria-hidden="true"></i>
        <input
            type="search"
            id="{{ $searchId }}"
            data-employee-search
            autocomplete="off"
            placeholder="{{ $placeholder }}"
            value="{{ $displayValue }}"
            class="w-full h-11 min-h-[44px] pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
            @if ($required) aria-required="true" @endif
        >
        <div
            data-employee-results
            class="hidden absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-border bg-surface shadow-lg"
            role="listbox"
        ></div>
    </div>
    @if ($hint !== '')
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif
    <p class="hidden text-xs text-danger" data-error="{{ $errorKey }}"></p>
    {{ $slot }}
</div>
