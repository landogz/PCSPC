@props([
    'id' => 'spa-table',
    'title' => 'Records',
    'createLabel' => null,
    'viewToggle' => false,
])

<section
    class="bg-surface border border-border rounded-2xl overflow-hidden"
    data-spa-module="{{ $id }}"
    @if ($viewToggle) data-view-toggle-enabled="1" @endif
>
    <div class="border-b border-border p-4 md:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between lg:gap-4">
            <div class="min-w-0 lg:max-w-[15rem] xl:max-w-sm flex-shrink-0 lg:pt-0.5">
                <h3 class="text-base font-semibold text-heading">{{ $title }}</h3>
                @if (isset($subtitle))
                    <p class="text-sm text-muted mt-1 line-clamp-2">{{ $subtitle }}</p>
                @endif
            </div>

            @if (isset($filters) || isset($actions) || $createLabel || $viewToggle)
                <div class="flex min-w-0 w-full flex-col gap-2 lg:w-auto lg:flex-1 lg:flex-row lg:flex-wrap lg:items-center lg:justify-end lg:gap-2">
                    @if (isset($filters))
                        <div
                            class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:w-auto lg:flex-wrap lg:items-center lg:gap-2"
                            data-panel-filters
                        >
                            {{ $filters }}
                        </div>
                    @endif

                    <div
                        class="flex w-full flex-col gap-2 sm:flex-row sm:items-center lg:w-auto lg:flex-shrink-0"
                        data-panel-actions
                    >
                        @if ($viewToggle)
                            <div
                                class="inline-flex w-full sm:w-auto items-center rounded-xl border border-border bg-subtle p-1"
                                data-view-toggle
                                role="group"
                                aria-label="Switch list or grid view"
                            >
                                <button
                                    type="button"
                                    data-view-mode="list"
                                    class="inline-flex flex-1 sm:flex-none items-center justify-center gap-1.5 h-9 min-h-[44px] sm:min-h-9 px-3 rounded-lg text-sm font-medium transition-colors"
                                    aria-pressed="true"
                                    title="List view"
                                >
                                    <i class="ph ph-list text-base" aria-hidden="true"></i>
                                    <span class="sm:hidden xl:inline">List</span>
                                </button>
                                <button
                                    type="button"
                                    data-view-mode="grid"
                                    class="inline-flex flex-1 sm:flex-none items-center justify-center gap-1.5 h-9 min-h-[44px] sm:min-h-9 px-3 rounded-lg text-sm font-medium transition-colors"
                                    aria-pressed="false"
                                    title="Grid view"
                                >
                                    <i class="ph ph-squares-four text-base" aria-hidden="true"></i>
                                    <span class="sm:hidden xl:inline">Grid</span>
                                </button>
                            </div>
                        @endif

                        {{ $actions ?? '' }}

                        @if ($createLabel)
                            <button
                                type="button"
                                data-action="create"
                                class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors whitespace-nowrap"
                            >
                                <i class="ph ph-plus text-base"></i>
                                {{ $createLabel }}
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div data-table-view class="overflow-x-auto -webkit-overflow-scrolling-touch">
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

    <div
        data-grid-view
        class="hidden p-4 md:p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3 md:gap-4"
    ></div>

    <div class="p-4 border-t border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" data-table-meta>
        <p class="text-xs text-muted" data-meta-label>Showing 0 records</p>
        <div class="flex items-center gap-2">
            <button type="button" data-page="prev" class="h-9 min-h-[44px] sm:min-h-9 px-3 rounded-lg border border-border text-sm disabled:opacity-40" disabled>Prev</button>
            <span class="text-xs text-muted" data-page-label>Page 1</span>
            <button type="button" data-page="next" class="h-9 min-h-[44px] sm:min-h-9 px-3 rounded-lg border border-border text-sm disabled:opacity-40" disabled>Next</button>
        </div>
    </div>

    {{ $modals ?? '' }}
</section>

<div
    id="{{ $id }}-context-menu"
    class="hidden fixed z-[80] min-w-[180px] rounded-xl border border-border bg-surface shadow-lg py-1"
    role="menu"
></div>
