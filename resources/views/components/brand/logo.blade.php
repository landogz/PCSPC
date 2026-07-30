@props([
    'variant' => 'full', // full | compact | sidebar
    'class' => '',
])

@php
    $max = match ($variant) {
        'compact' => 'max-w-[10rem] sm:max-w-[11rem]',
        'sidebar' => 'max-w-[9.5rem]',
        default => 'max-w-[16rem] sm:max-w-[18rem]',
    };
@endphp

<img
    src="{{ asset('images/brand/pcspc-logo.png') }}"
    alt="Philippine Coastal Storage &amp; Pipeline Corporation"
    width="650"
    height="200"
    decoding="async"
    {{ $attributes->class([
        'h-auto w-full object-contain object-left',
        $max,
        $class,
    ]) }}
>
