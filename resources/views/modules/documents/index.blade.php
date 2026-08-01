@extends('layouts.app')

@section('title', ($module['label'] ?? 'Documents') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Documents')

@php
    $canManage = auth()->user()?->hasPermission('documents.manage') ?? false;
@endphp

@section('content')
<section class="space-y-4 md:space-y-5" data-module="documents" data-can-manage="{{ $canManage ? '1' : '0' }}">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'HR Records' }}</p>
                <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Documents' }}</h2>
                <p class="text-sm text-text-secondary mt-2 max-w-3xl">
                    {{ $module['summary'] ?? 'Employee-linked document repository with expiry tracking.' }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (($module['req_ids'] ?? ['DOC-001']) as $req)
                        <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle border border-border text-[11px] font-semibold text-heading">{{ $req }}</span>
                    @endforeach
                    <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-primary-soft text-primary text-[11px] font-semibold">Phase P3</span>
                </div>
            </div>
            <div class="w-full lg:w-64 flex-shrink-0 rounded-xl border border-border bg-subtle/60 p-3" data-storage-meter>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-heading">Repository storage</p>
                    <p class="text-[11px] text-muted" data-storage-percent>0%</p>
                </div>
                <div class="mt-2 h-2 rounded-full bg-border overflow-hidden" aria-hidden="true">
                    <div class="h-full rounded-full bg-primary transition-all" style="width:0%" data-storage-bar></div>
                </div>
                <p class="mt-2 text-xs text-muted" data-storage-label>0 B of 5 GB used</p>
            </div>
        </div>
    </div>

    {{-- Expiry traffic-light tabs --}}
    <div class="flex flex-wrap gap-2" data-expiry-tabs role="tablist" aria-label="Expiry filters">
        <button type="button" data-expiry-tab="" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="true">
            All <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-lg bg-subtle text-xs font-semibold" data-count="total">0</span>
        </button>
        <button type="button" data-expiry-tab="expiring" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="false">
            <span class="h-2 w-2 rounded-full bg-warning" aria-hidden="true"></span>
            Expiring soon <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-lg bg-warning-soft text-xs font-semibold text-heading" data-count="expiring">0</span>
        </button>
        <button type="button" data-expiry-tab="expired" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="false">
            <span class="h-2 w-2 rounded-full bg-danger" aria-hidden="true"></span>
            Expired <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-lg bg-danger-soft text-xs font-semibold text-danger" data-count="expired">0</span>
        </button>
        <button type="button" data-expiry-tab="valid" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="false">
            <span class="h-2 w-2 rounded-full bg-success" aria-hidden="true"></span>
            Valid <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-lg bg-success-soft text-xs font-semibold text-success" data-count="valid">0</span>
        </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[15rem_minmax(0,1fr)] gap-4 md:gap-5">
        {{-- Category folders --}}
        <aside class="bg-surface border border-border rounded-2xl p-3 md:p-4 h-fit xl:sticky xl:top-20" data-category-nav aria-label="Document categories">
            <p class="px-2 text-[11px] font-bold uppercase tracking-wide text-faint mb-2">Folders</p>
            <nav class="flex xl:flex-col gap-1 overflow-x-auto xl:overflow-visible -mx-1 px-1 pb-1 xl:pb-0">
                <button type="button" data-category-folder="" class="inline-flex items-center gap-2 whitespace-nowrap xl:w-full text-left h-10 min-h-[44px] px-3 rounded-xl text-sm font-medium transition-colors bg-primary-soft text-primary" aria-current="true">
                    <i class="ph ph-folders text-base" aria-hidden="true"></i>
                    <span class="flex-1">All files</span>
                    <span class="text-xs opacity-80" data-folder-count="all">0</span>
                </button>
                @foreach ([
                    'contract' => ['Contract', 'ph-file-text'],
                    'government_id' => ['Government IDs', 'ph-identification-card'],
                    'certificate' => ['Certificates', 'ph-certificate'],
                    'clearance' => ['Clearances', 'ph-seal-check'],
                    'policy' => ['Policies', 'ph-notebook'],
                    'other' => ['Other', 'ph-files'],
                ] as $key => [$label, $icon])
                    <button type="button" data-category-folder="{{ $key }}" class="inline-flex items-center gap-2 whitespace-nowrap xl:w-full text-left h-10 min-h-[44px] px-3 rounded-xl text-sm font-medium text-heading hover:bg-subtle transition-colors" aria-current="false">
                        <i class="ph {{ $icon }} text-base text-muted" aria-hidden="true"></i>
                        <span class="flex-1">{{ $label }}</span>
                        <span class="text-xs text-muted" data-folder-count="{{ $key }}">0</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 space-y-3">
            {{-- Bulk actions bar --}}
            <div class="hidden items-center flex-wrap gap-2 rounded-2xl border border-border bg-surface p-3" data-bulk-bar>
                <p class="text-sm text-heading font-medium mr-auto" data-bulk-label>0 selected</p>
                <button type="button" data-bulk-action="download" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] px-3 rounded-xl border border-border text-sm font-medium hover:bg-subtle">
                    <i class="ph ph-download-simple"></i> Download
                </button>
                @if ($canManage)
                    <select data-bulk-category class="h-10 min-h-[44px] px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">Move to category…</option>
                        <option value="contract">Contract</option>
                        <option value="government_id">Government ID</option>
                        <option value="certificate">Certificate</option>
                        <option value="clearance">Clearance</option>
                        <option value="policy">Policy</option>
                        <option value="other">Other</option>
                    </select>
                    <button type="button" data-bulk-action="delete" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] px-3 rounded-xl border border-danger/30 text-danger text-sm font-medium hover:bg-danger/10">
                        <i class="ph ph-trash"></i> Delete
                    </button>
                @endif
                <button type="button" data-bulk-action="clear" class="inline-flex items-center h-10 min-h-[44px] px-3 rounded-xl text-sm text-muted hover:bg-subtle">Clear</button>
            </div>

            <div class="relative" data-drop-root>
                @if ($canManage)
                    <div class="pointer-events-none absolute inset-0 z-20 hidden items-center justify-center rounded-2xl border-2 border-dashed border-primary bg-primary/10 backdrop-blur-[1px]" data-drop-overlay>
                        <div class="text-center px-4">
                            <i class="ph ph-upload-simple text-3xl text-primary"></i>
                            <p class="mt-2 text-sm font-semibold text-heading">Drop files to upload</p>
                            <p class="text-xs text-muted mt-1">PDF, JPG, PNG, WebP, DOC, DOCX · max 10 MB each</p>
                        </div>
                    </div>
                @endif

                <x-ui.data-panel id="documents-table" title="Document repository" :create-label="$canManage ? 'Upload document' : null" :view-toggle="true">
                    <x-slot:subtitle>Browse by folder, track expiry, preview without downloading. Files are stored privately.</x-slot:subtitle>
                    <x-slot:filters>
                        <div class="relative w-full sm:col-span-2 lg:w-56 lg:flex-none">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                            <input
                                type="search"
                                data-filter="search"
                                placeholder="Search documents…"
                                class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            >
                        </div>
                        {{-- Hidden selects kept in sync with folder/tab UI for API params --}}
                        <input type="hidden" data-filter="category" value="">
                        <input type="hidden" data-filter="expiry" value="">
                    </x-slot:filters>

                    <x-slot:head>
                        <tr>
                            @if ($canManage)
                                <th class="px-3 py-3 w-10">
                                    <input type="checkbox" data-select-all class="rounded border-border" aria-label="Select all on page">
                                </th>
                            @else
                                <th class="px-3 py-3 w-10"></th>
                            @endif
                            <th class="px-4 py-3 font-semibold">Document</th>
                            <th class="px-4 py-3 font-semibold">Employee</th>
                            <th class="px-4 py-3 font-semibold">Expiry</th>
                            <th class="px-4 py-3 font-semibold">File</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </x-slot:head>

                    <x-slot:modals>
                        <x-ui.modal id="document-modal" title="Upload document" subtitle="Linked to an employee 201 record" max-width="max-w-lg">
                            <form id="document-form" class="flex min-h-0 flex-1 flex-col" enctype="multipart/form-data" novalidate>
                                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                                    <input type="hidden" name="id" value="">

                                    <div>
                                        <x-ui.employee-search
                                            name="employee_id"
                                            id="document-employee"
                                            label="Employee"
                                            :required="true"
                                            hint="Search and select the employee 201 record this file belongs to."
                                        />
                                    </div>

                                    <div>
                                        <label class="ui-label ui-label-required" for="document-title">Title</label>
                                        <input id="document-title" name="title" required maxlength="180" class="ui-input" placeholder="e.g. Employment contract 2026">
                                        <p class="hidden text-xs text-danger mt-1" data-error="title"></p>
                                    </div>

                                    <div>
                                        <label class="ui-label ui-label-required" for="document-category">Category</label>
                                        <select id="document-category" name="category" required class="ui-select">
                                            <option value="contract">Contract</option>
                                            <option value="government_id">Government ID</option>
                                            <option value="certificate">Certificate</option>
                                            <option value="clearance">Clearance</option>
                                            <option value="policy">Policy</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <p class="hidden text-xs text-danger mt-1" data-error="category"></p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="ui-label" for="document-issued-at">Issued date</label>
                                            <input id="document-issued-at" name="issued_at" type="date" class="ui-input">
                                            <p class="hidden text-xs text-danger mt-1" data-error="issued_at"></p>
                                        </div>
                                        <div>
                                            <label class="ui-label" for="document-expires-at">Expiry date</label>
                                            <input id="document-expires-at" name="expires_at" type="date" class="ui-input">
                                            <p class="hidden text-xs text-danger mt-1" data-error="expires_at"></p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="ui-label" for="document-notes">Notes</label>
                                        <textarea id="document-notes" name="notes" rows="2" maxlength="1000" class="ui-input" placeholder="Optional context"></textarea>
                                        <p class="hidden text-xs text-danger mt-1" data-error="notes"></p>
                                    </div>

                                    <div>
                                        <label class="ui-label ui-label-required" for="document-file" data-file-label>File</label>
                                        <input
                                            id="document-file"
                                            name="file"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,application/pdf,image/*"
                                            class="block w-full text-sm text-text-secondary file:mr-3 file:h-10 file:min-h-[44px] file:px-4 file:rounded-xl file:border-0 file:bg-primary file:text-white file:text-sm file:font-medium hover:file:bg-primary-strong"
                                        >
                                        <p class="text-xs text-muted mt-1">PDF, JPG, PNG, WebP, DOC, DOCX · max 10 MB</p>
                                        <p class="hidden text-xs text-text-secondary mt-1" data-current-file></p>
                                        <p class="hidden text-xs text-danger mt-1" data-error="file"></p>
                                    </div>
                                </div>
                                <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                                    <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                                    @if ($canManage)
                                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[9rem]">Save document</button>
                                    @endif
                                </div>
                            </form>
                        </x-ui.modal>

                        {{-- Quick view lightbox --}}
                        <div id="document-preview-modal" class="hidden fixed inset-0 z-[70] overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="document-preview-title">
                            <div class="modal-backdrop" data-preview-dismiss aria-hidden="true"></div>
                            <div class="relative z-10 flex h-full min-h-0 items-end justify-center p-3 sm:items-center sm:p-4">
                                <div class="modal-panel max-w-5xl w-full max-h-[92vh] flex flex-col">
                                    <div class="relative flex flex-shrink-0 items-start justify-between gap-3 border-b border-border p-4 sm:p-5">
                                        <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl bg-primary" aria-hidden="true"></div>
                                        <div class="min-w-0 pr-2 pt-1">
                                            <h3 id="document-preview-title" class="text-lg font-semibold text-heading truncate" data-preview-title>Document</h3>
                                            <p class="text-sm text-muted mt-0.5 truncate" data-preview-subtitle></p>
                                        </div>
                                        <button type="button" data-preview-dismiss class="inline-flex h-10 w-10 min-h-[44px] min-w-[44px] items-center justify-center rounded-xl border border-border hover:bg-subtle" aria-label="Close preview">
                                            <i class="ph ph-x text-lg"></i>
                                        </button>
                                    </div>
                                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_16rem] gap-4">
                                        <div class="rounded-xl border border-border bg-subtle/40 min-h-[240px] lg:min-h-[420px] flex items-center justify-center overflow-hidden" data-preview-stage>
                                            <p class="text-sm text-muted">Loading preview…</p>
                                        </div>
                                        <aside class="space-y-4">
                                            <div class="space-y-2 text-sm" data-preview-meta></div>
                                            <div class="space-y-2" data-preview-versions>
                                                <p class="text-xs font-bold uppercase tracking-wide text-faint">Version history</p>
                                                <div data-preview-version-list class="space-y-2"></div>
                                            </div>
                                            <div class="flex flex-col gap-2 pt-2">
                                                <button type="button" data-preview-download class="ui-btn-primary min-h-[44px] w-full justify-center">
                                                    <i class="ph ph-download-simple"></i> Download
                                                </button>
                                                @if ($canManage)
                                                    <button type="button" data-preview-edit class="ui-btn-secondary min-h-[44px] w-full justify-center">Edit</button>
                                                @endif
                                            </div>
                                        </aside>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-slot:modals>
                </x-ui.data-panel>
            </div>
        </div>
    </div>
</section>
@endsection
