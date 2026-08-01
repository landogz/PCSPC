@props([
    'title',
    'subtitle' => '',
    'icon' => 'ph-folder',
    'badge' => 'P6 stub',
    'phaseNote' => 'Full records land in Phase 6.',
    'moduleHref' => null,
    'moduleLabel' => null,
])

<section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-border pb-3">
        <div class="flex items-center gap-2 min-w-0">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary flex-shrink-0">
                <i class="ph {{ $icon }} text-base"></i>
            </span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-sm font-semibold tracking-wide text-heading">{{ $title }}</h4>
                    <span class="inline-flex items-center h-6 px-2 rounded-lg bg-subtle border border-border text-[10px] font-semibold uppercase tracking-wide text-muted">
                        {{ $badge }}
                    </span>
                </div>
                @if ($subtitle !== '')
                    <p class="text-xs text-muted mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center space-y-3">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <i class="ph {{ $icon }} text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-heading">{{ $title }} coming soon</p>
            <p class="text-xs text-muted mt-1 max-w-md mx-auto">{{ $phaseNote }}</p>
        </div>
        {{ $slot }}
        @if ($moduleHref && $moduleLabel)
            <a
                href="{{ $moduleHref }}"
                class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] px-4 rounded-xl border border-border bg-surface text-sm font-medium text-heading hover:bg-subtle transition-colors"
            >
                Open {{ $moduleLabel }}
                <i class="ph ph-arrow-right text-base"></i>
            </a>
        @endif
    </div>
</section>
