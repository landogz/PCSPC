@foreach ($languages as $index => $language)
    @php
        $snippet = $snippets[$language['id']] ?? '';
    @endphp
    <div
        class="relative {{ $index === 0 ? '' : 'hidden' }}"
        data-api-docs-snippet
        data-lang="{{ $language['id'] }}"
    >
        <button
            type="button"
            class="absolute top-2 right-2 z-10 inline-flex items-center gap-1 h-8 min-h-[32px] px-2.5 rounded-lg border border-border bg-surface text-[11px] font-medium text-heading hover:bg-subtle"
            data-api-docs-copy
            aria-label="Copy {{ $language['label'] }} example"
        >
            <i class="ph ph-copy"></i>
            <span data-api-docs-copy-label>Copy</span>
        </button>
        <pre class="overflow-x-auto p-4 pt-12 text-xs sm:text-sm leading-relaxed bg-bg text-heading"><code data-api-docs-code>{{ $snippet }}</code></pre>
    </div>
@endforeach
