@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'href' => null,
    'hrefLabel' => 'Open',
])

<article {{ $attributes->merge(['class' => 'bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col min-h-[280px]']) }}>
    <div class="flex items-start justify-between gap-3 mb-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-semibold text-heading">{{ $title }}</h3>
                @if ($badge)
                    <span class="inline-flex items-center h-6 px-2 rounded-lg bg-subtle border border-border text-[10px] font-semibold uppercase tracking-wide text-muted">
                        {{ $badge }}
                    </span>
                @endif
            </div>
            @if ($subtitle)
                <p class="text-xs text-muted mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($href)
            <a href="{{ $href }}" class="inline-flex items-center gap-1 text-xs font-medium text-muted hover:text-primary transition-colors flex-shrink-0 min-h-[44px] sm:min-h-0">
                {{ $hrefLabel }}
                <i class="ph ph-arrow-right text-sm"></i>
            </a>
        @endif
    </div>

    <div class="relative flex-1 min-h-[180px]">
        {{ $slot }}
    </div>
</article>
