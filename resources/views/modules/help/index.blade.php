@extends('layouts.app')

@section('title', ($module['label'] ?? 'Help') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Help & Docs')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="{{ route('api-docs') }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-code text-lg"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-text group-hover:text-primary">Public API docs</span>
                    <span class="block text-xs text-muted truncate">Live /api/v1 catalog · JSON export</span>
                </span>
            </a>
            <a href="{{ route('docs.show', ['doc' => 'project-plan']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-map-trifold text-lg"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-text group-hover:text-primary">Project Plan</span>
                    <span class="block text-xs text-muted truncate">Phases P0–P10 · module map</span>
                </span>
            </a>
            <a href="{{ route('docs.show', ['doc' => 'modules']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-list-bullets text-lg"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-text group-hover:text-primary">Menu ↔ Module Map</span>
                    <span class="block text-xs text-muted truncate">Sidebar routes · req IDs · phases</span>
                </span>
            </a>
            <a href="{{ route('docs.show', ['doc' => 'flowcharts']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-flow-arrow text-lg"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-text group-hover:text-primary">Flowcharts</span>
                    <span class="block text-xs text-muted truncate">Login · Modules · Leave/OT · Delivery</span>
                </span>
            </a>
            <div class="flex items-center gap-3 p-3 rounded-xl border border-border bg-subtle/50">
                <span class="w-9 h-9 rounded-lg bg-subtle text-muted flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-file-pdf text-lg"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-text">Bidding pack</span>
                    <span class="block text-xs text-muted">docs/hris-bidding (TOR, SOW, Annex A)</span>
                </span>
            </div>
        </div>
    </x-modules.page>
@endsection
