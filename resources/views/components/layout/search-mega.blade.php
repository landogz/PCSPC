{{-- Global search mega menu (command palette style) --}}
<div
    id="search-mega"
    class="hidden fixed inset-0 z-[75] overflow-hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="search-mega-title"
    data-search-mega
>
    <div class="modal-backdrop" data-search-mega-dismiss aria-hidden="true"></div>

    <div class="relative z-10 flex h-full min-h-0 items-start justify-center p-3 pt-[8vh] sm:p-4 sm:pt-[10vh]">
        <div class="search-mega-panel flex flex-col rounded-2xl border border-border bg-surface shadow-2xl overflow-hidden">
            <div class="flex-shrink-0 border-b border-border p-3 sm:p-4">
                <div class="flex items-center gap-2.5 h-12 px-3 rounded-xl bg-subtle border border-border focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20">
                    <i class="ph ph-magnifying-glass text-xl text-muted flex-shrink-0" aria-hidden="true"></i>
                    <input
                        id="search-mega-input"
                        type="search"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="Search modules, people, shortcuts…"
                        class="flex-1 min-w-0 h-full bg-transparent text-sm sm:text-base text-heading placeholder:text-faint focus:outline-none"
                        aria-label="Global search"
                        data-search-mega-input
                    >
                    <kbd class="kbd-hint hidden sm:inline-flex">esc</kbd>
                    <button
                        type="button"
                        class="sm:hidden flex h-9 w-9 items-center justify-center rounded-lg hover:bg-surface text-muted"
                        data-search-mega-dismiss
                        aria-label="Close search"
                    >
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>
                <p id="search-mega-title" class="sr-only">Global search</p>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 sm:p-4" data-search-mega-body>
                <p class="text-sm text-muted text-center py-10">Loading…</p>
            </div>

            <div class="flex-shrink-0 border-t border-border px-3 sm:px-4 py-2.5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1"><kbd class="kbd-hint">↑</kbd><kbd class="kbd-hint">↓</kbd> navigate</span>
                    <span class="inline-flex items-center gap-1"><kbd class="kbd-hint">↵</kbd> open</span>
                    <span class="inline-flex items-center gap-1"><kbd class="kbd-hint">esc</kbd> close</span>
                </div>
                <span class="hidden sm:inline">Tip: press <kbd class="kbd-hint">⌘</kbd><kbd class="kbd-hint">K</kbd> anytime</span>
            </div>
        </div>
    </div>
</div>
