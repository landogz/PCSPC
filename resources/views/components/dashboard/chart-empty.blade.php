@props([
    'title' => 'Chart unavailable',
    'phase' => null,
    'message' => 'This widget will populate when the related module goes live.',
])

<div
    {{ $attributes->merge(['class' => 'absolute inset-0 z-10 flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-subtle/70 px-4 text-center']) }}
    data-chart-empty
>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-soft text-primary mb-2">
        <i class="ph ph-chart-line-up text-xl"></i>
    </span>
    <p class="text-sm font-medium text-heading">{{ $title }}</p>
    <p class="text-xs text-muted mt-1 max-w-xs">{{ $message }}</p>
    @if ($phase)
        <span class="mt-2 inline-flex items-center h-6 px-2 rounded-lg bg-surface border border-border text-[10px] font-semibold uppercase tracking-wide text-muted">
            Phase {{ $phase }}
        </span>
    @endif
</div>
