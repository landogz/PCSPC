<?php

/**
 * Shared SPA table shell with filters + context menu host.
 */
?>
@props([
    'id' => 'spa-table',
    'title' => 'Records',
    'createLabel' => null,
])

<section
    class="bg-surface border border-border rounded-2xl overflow-hidden"
    data-spa-module="{{ $id }}"
>
    <div class="p-4 md:p-5 border-b border-border flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <h3 class="text-base font-semibold text-heading">{{ $title }}</h3>
            @if (isset($subtitle))
                <p class="text-sm text-muted mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:items-center flex-wrap">
            {{ $filters ?? '' }}
            @if ($createLabel)
                <button
                    type="button"
                    data-action="create"
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors min-h-[44px] sm:min-h-0"
                >
                    <i class="ph ph-plus text-base"></i>
                    {{ $createLabel }}
                </button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
        <table id="{{ $id }}-table" class="w-full text-sm text-left min-w-[640px]">
            <thead class="bg-subtle text-xs uppercase tracking-wide text-muted">
                {{ $head }}
            </thead>
            <tbody class="divide-y divide-border" data-table-body>
                <tr>
                    <td colspan="99" class="px-4 py-10 text-center text-muted">Loading…</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" data-table-meta>
        <p class="text-xs text-muted" data-meta-label>Showing 0 records</p>
        <div class="flex items-center gap-2">
            <button type="button" data-page="prev" class="h-9 px-3 rounded-lg border border-border text-sm disabled:opacity-40" disabled>Prev</button>
            <span class="text-xs text-muted" data-page-label>Page 1</span>
            <button type="button" data-page="next" class="h-9 px-3 rounded-lg border border-border text-sm disabled:opacity-40" disabled>Next</button>
        </div>
    </div>

    {{ $modals ?? '' }}
</section>

<div
    id="{{ $id }}-context-menu"
    class="hidden fixed z-[80] min-w-[180px] rounded-xl border border-border bg-surface shadow-lg py-1"
    role="menu"
></div>
