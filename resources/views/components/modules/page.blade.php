@props([
    'module' => [],
    'moduleKey' => '',
])

@php
    $label = $module['label'] ?? 'Module';
    $phase = $module['phase'] ?? '—';
    $reqIds = $module['req_ids'] ?? [];
    $flowchart = $module['flowchart'] ?? '—';
    $summary = $module['summary'] ?? '';
    $section = $module['section'] ?? '';
@endphp

<section class="space-y-4 md:space-y-5" @if ($moduleKey !== '') data-module="{{ $moduleKey }}" @endif>
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6 relative overflow-hidden">
        <div class="pointer-events-none absolute -top-10 -right-10 w-40 h-40 rounded-full bg-primary/10"></div>
        <div class="pointer-events-none absolute bottom-0 right-20 w-24 h-24 rounded-full bg-primary/5"></div>

        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $section }}</p>
                <h2 class="text-2xl font-bold text-heading mt-1">{{ $label }}</h2>
                <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $summary }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                <span class="inline-flex items-center h-8 px-3 rounded-xl bg-primary-soft text-primary text-xs font-semibold">
                    Phase {{ $phase }}
                </span>
                <span class="inline-flex items-center h-8 px-3 rounded-xl bg-subtle border border-border text-xs font-medium text-text-secondary">
                    Scaffold ready
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 md:gap-4">
        <article class="bg-surface border border-border rounded-2xl p-5 space-y-3 lg:col-span-2">
            <h3 class="text-base font-semibold text-heading">Requirement IDs</h3>
            <p class="text-sm text-muted">From Annex A / SOW module map in <code class="text-xs">docs/PROJECT_PLAN.md</code>.</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($reqIds as $req)
                    <span class="inline-flex items-center h-8 px-3 rounded-lg bg-subtle border border-border text-xs font-semibold text-heading">
                        {{ $req }}
                    </span>
                @empty
                    <span class="text-sm text-muted">No requirement IDs mapped yet.</span>
                @endforelse
            </div>
        </article>

        <article class="bg-surface border border-border rounded-2xl p-5 space-y-3">
            <h3 class="text-base font-semibold text-heading">Flowchart link</h3>
            <p class="text-sm text-text-secondary">{{ $flowchart }}</p>
            <p class="text-xs text-muted">
                See interactive flows in
                <span class="font-medium text-heading">hris-flowcharts</span>
                and delivery phases in
                <span class="font-medium text-heading">PROJECT_PLAN.md</span>.
            </p>
        </article>
    </div>

    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-heading">Build status</h3>
                <p class="text-sm text-muted mt-1">
                    Menu and route are connected. Full API feature pack
                    (<code class="text-xs">Service</code> + <code class="text-xs">Repository</code> +
                    <code class="text-xs">/api/v1/</code> + DataTables module JS) lands in phase {{ $phase }}.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl border border-border bg-surface text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors"
                >
                    <i class="ph ph-arrow-left text-base"></i>
                    Dashboard
                </a>
                <span class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium">
                    <i class="ph ph-folder-simple text-base"></i>
                    modules/{{ $moduleKey }}
                </span>
            </div>
        </div>

        @if (trim((string) $slot) !== '')
            <div class="mt-5">
                {{ $slot }}
            </div>
        @endif
    </div>
</section>
