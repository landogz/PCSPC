@props([
    'variant' => 'full', // full | compact | sidebar
    'class' => '',
])

@php
    $brand = app(\App\Services\Administration\SystemParameterService::class)->current();
    $logoUrl = $brand['logo_url'];
    $alt = $brand['company_name'] !== ''
        ? $brand['company_name']
        : 'Philippine Coastal Storage & Pipeline Corporation';

    $max = match ($variant) {
        'compact' => 'max-w-[10rem] sm:max-w-[11rem]',
        'sidebar' => 'max-w-[9.5rem]',
        default => 'max-w-[16rem] sm:max-w-[18rem]',
    };
@endphp

<img
    data-brand-logo
    src="{{ $logoUrl }}"
    alt="{{ $alt }}"
    width="650"
    height="200"
    decoding="async"
    {{ $attributes->class([
        'h-auto w-full object-contain object-left',
        $max,
        $class,
    ]) }}
>
