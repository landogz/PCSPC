@extends('layouts.app')

@section('title', ($module['label'] ?? 'Departments') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Departments')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="departments">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'People' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Departments' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <x-ui.data-panel id="departments-table" title="Departments" create-label="Add department">
        <x-slot:subtitle>Org structure master data used by employees, approvals, and reporting.</x-slot:subtitle>
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
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="department-modal" title="Add department">
                <form id="department-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
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
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:p-5">
                        <button type="button" data-modal-dismiss class="h-11 min-h-[44px] px-4 rounded-xl border border-border text-sm font-medium">Cancel</button>
                        <button type="submit" class="h-11 min-h-[44px] px-4 rounded-xl bg-primary text-white text-sm font-medium">Save department</button>
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
