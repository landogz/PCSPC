@extends('layouts.app')

@section('title', ($module['label'] ?? 'Administration') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Administration')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="administration">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Administration' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <x-ui.data-panel id="admin-departments" title="Departments" create-label="Add department">
        <x-slot:subtitle>Org structure starter master data for employees and approvals.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative min-w-[180px] flex-1 sm:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search departments…"
                    class="w-full sm:w-56 h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="status" class="h-10 px-3 rounded-xl border border-border bg-surface text-sm min-w-[120px]">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">Code</th>
                <th class="px-4 py-3 font-semibold">Name</th>
                <th class="px-4 py-3 font-semibold">Description</th>
                <th class="px-4 py-3 font-semibold">Status</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <div id="admin-department-modal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" data-modal-dismiss></div>
                <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-border bg-surface shadow-xl">
                    <div class="p-5 border-b border-border flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-heading" data-modal-title>Add department</h3>
                        <button type="button" class="h-9 w-9 rounded-lg hover:bg-subtle" data-modal-dismiss aria-label="Close">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                    <form id="admin-department-form" class="p-5 space-y-4" novalidate>
                        <input type="hidden" name="id" value="">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-heading mb-1.5">Code</label>
                                <input name="code" required maxlength="30" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm uppercase">
                                <p class="hidden text-xs text-danger mt-1" data-error="code"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-heading mb-1.5">Name</label>
                                <input name="name" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-heading mb-1.5">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-sm"></textarea>
                            <p class="hidden text-xs text-danger mt-1" data-error="description"></p>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-border" checked>
                            Active
                        </label>
                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                            <button type="button" data-modal-dismiss class="h-11 px-4 rounded-xl border border-border text-sm font-medium">Cancel</button>
                            <button type="submit" class="h-11 px-4 rounded-xl bg-primary text-white text-sm font-medium">Save department</button>
                        </div>
                    </form>
                </div>
            </div>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
