@extends('layouts.app')

@section('title', ($module['label'] ?? 'Lookups') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Lookups')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="lookups">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Lookups' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">
            {{ $module['summary'] ?? 'Manage system lookup tables used by dropdowns across the HRIS.' }}
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach (($module['req_ids'] ?? ['ADM-006']) as $req)
                <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle border border-border text-[11px] font-semibold text-heading">{{ $req }}</span>
            @endforeach
            <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-primary-soft text-primary text-[11px] font-semibold">Phase P3</span>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[16rem_minmax(0,1fr)] gap-4 md:gap-5">
        <aside class="bg-surface border border-border rounded-2xl p-3 md:p-4 h-fit xl:sticky xl:top-20" data-lookup-types aria-label="Lookup types">
            <p class="px-2 text-[11px] font-bold uppercase tracking-wide text-faint mb-2">Tables</p>
            <nav class="flex xl:flex-col gap-1 overflow-x-auto xl:overflow-visible -mx-1 px-1 pb-1 xl:pb-0" data-type-list>
                <p class="text-sm text-muted px-2 py-3">Loading…</p>
            </nav>
        </aside>

        <x-ui.data-panel id="lookups-table" title="Lookup values" create-label="Add value">
            <x-slot:subtitle>System values can be renamed or deactivated but not deleted. Custom values can be removed.</x-slot:subtitle>
            <x-slot:filters>
                <div class="relative w-full sm:col-span-2 lg:w-56 lg:flex-none">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input
                        type="search"
                        data-filter="search"
                        placeholder="Search code or label…"
                        class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                    >
                </div>
                <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                    <option value="">All status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <input type="hidden" data-filter="type" value="">
            </x-slot:filters>

            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-semibold">Code</th>
                    <th class="px-4 py-3 font-semibold">Label</th>
                    <th class="px-4 py-3 font-semibold">Sort</th>
                    <th class="px-4 py-3 font-semibold">Flags</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </x-slot:head>

            <x-slot:modals>
                <x-ui.modal id="lookup-modal" title="Add lookup value" subtitle="Shared dropdown option" max-width="max-w-lg">
                    <form id="lookup-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                            <input type="hidden" name="id" value="">

                            <div>
                                <label class="ui-label ui-label-required" for="lookup-type">Lookup table</label>
                                <select id="lookup-type" name="type" required class="ui-select" data-lookup-type-select></select>
                                <p class="hidden text-xs text-danger mt-1" data-error="type"></p>
                            </div>

                            <div>
                                <label class="ui-label ui-label-required" for="lookup-code">Code</label>
                                <input id="lookup-code" name="code" required maxlength="60" class="ui-input font-mono text-sm" placeholder="e.g. contractual" pattern="[a-z0-9_]+" autocomplete="off">
                                <p class="text-xs text-muted mt-1">Lowercase letters, numbers, underscores. Used in APIs and validation.</p>
                                <p class="hidden text-xs text-danger mt-1" data-error="code"></p>
                            </div>

                            <div>
                                <label class="ui-label ui-label-required" for="lookup-label">Label</label>
                                <input id="lookup-label" name="label" required maxlength="120" class="ui-input" placeholder="e.g. Contractual">
                                <p class="hidden text-xs text-danger mt-1" data-error="label"></p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="ui-label" for="lookup-sort">Sort order</label>
                                    <input id="lookup-sort" name="sort_order" type="number" min="0" max="9999" value="0" class="ui-input">
                                    <p class="hidden text-xs text-danger mt-1" data-error="sort_order"></p>
                                </div>
                                <div class="flex items-end pb-1">
                                    <label class="inline-flex items-center gap-2 h-10 min-h-[44px] text-sm text-heading">
                                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-border">
                                        Active (shown in dropdowns)
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="ui-label" for="lookup-description">Description</label>
                                <textarea id="lookup-description" name="description" rows="2" maxlength="500" class="ui-input" placeholder="Optional admin note"></textarea>
                                <p class="hidden text-xs text-danger mt-1" data-error="description"></p>
                            </div>
                        </div>
                        <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                            <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                            <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[9rem]">Save value</button>
                        </div>
                    </form>
                </x-ui.modal>
            </x-slot:modals>
        </x-ui.data-panel>
    </div>
</section>
@endsection
