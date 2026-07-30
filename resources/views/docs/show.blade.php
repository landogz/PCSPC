@extends('layouts.app')

@section('title', $title.' — '.config('app.name'))
@section('page-title', $title)

@section('content')
<section class="space-y-4">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
            <div>
                <p class="text-xs font-bold tracking-wide text-faint uppercase">Documentation</p>
                <h2 class="text-xl font-bold text-heading mt-1">{{ $title }}</h2>
                <p class="text-xs text-muted mt-1">docs/{{ $filename }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (($doc ?? '') === 'project-plan')
                    <a
                        href="{{ route('docs.show', ['doc' => 'modules']) }}"
                        class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl border border-border text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors"
                    >
                        <i class="ph ph-list-bullets text-base"></i>
                        Module map
                    </a>
                @endif
                <a
                    href="{{ route('modules.show', ['module' => 'help']) }}"
                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl border border-border text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors"
                >
                    <i class="ph ph-arrow-left text-base"></i>
                    Help & Docs
                </a>
            </div>
        </div>

        <div
            id="docs-content"
            class="docs-prose max-w-none pt-2"
            aria-live="polite"
        >
            <div class="flex items-center gap-2 text-sm text-muted py-8 justify-center">
                <i class="ph ph-circle-notch animate-spin text-lg"></i>
                Rendering documentation…
            </div>
        </div>

        <script type="application/json" id="docs-source">{!! json_encode($content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    </div>
</section>
@endsection
