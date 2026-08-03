@extends($useAppLayout ? 'layouts.app' : 'layouts.guest')

@section('title', ($catalog['title'] ?? 'API Reference').' — '.config('app.name'))
@section('page-title', $catalog['title'] ?? 'API Reference')

@php
    $groupIcons = [
        'auth' => 'ph-shield-check',
        'dashboard' => 'ph-gauge',
        'administration' => 'ph-sliders',
        'security' => 'ph-lock-key',
        'audit' => 'ph-clipboard-text',
        'departments' => 'ph-buildings',
        'lookups' => 'ph-list-bullets',
        'holidays' => 'ph-calendar-blank',
        'shifts' => 'ph-clock',
        'schedules' => 'ph-calendar-dots',
        'documents' => 'ph-folder',
        'employees' => 'ph-users',
        'notifications' => 'ph-bell',
        'search' => 'ph-magnifying-glass',
        'health' => 'ph-heartbeat',
        'up' => 'ph-heartbeat',
    ];
@endphp

@section('content')
<div @if (! $useAppLayout) class="min-h-dvh" @endif data-module="api-docs">
    @unless ($useAppLayout)
        <header class="border-b border-border bg-surface">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ url('/') }}" class="flex-shrink-0" aria-label="{{ config('app.name') }} home">
                        <x-brand.logo variant="compact" class="!max-w-[9rem]" />
                    </a>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-faint">Public API</p>
                        <h1 class="text-lg sm:text-xl font-bold text-heading truncate">{{ $catalog['title'] }}</h1>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('api-docs.json') }}" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] px-3.5 rounded-xl border border-border text-sm font-medium hover:bg-subtle">
                        <i class="ph ph-brackets-curly"></i> JSON
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] px-3.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong">
                        Sign in
                    </a>
                </div>
            </div>
        </header>
    @endunless

    <div class="{{ $useAppLayout ? 'space-y-6' : 'mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 space-y-6' }}">
        <section class="rounded-2xl border border-border bg-surface p-5 sm:p-6 space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 max-w-3xl">
                    @if ($useAppLayout)
                        <h1 class="text-lg sm:text-xl font-bold text-heading">{{ $catalog['title'] }}</h1>
                    @endif
                    <p class="text-sm text-text-secondary {{ $useAppLayout ? 'mt-1' : '' }}">{{ $catalog['subtitle'] }}</p>
                    <p class="text-xs text-muted mt-2">{{ $catalog['updated_note'] }}</p>
                    <p class="text-xs text-muted mt-1">
                        {{ $catalog['totals']['endpoints'] }} endpoints · {{ $catalog['totals']['groups'] }} groups ·
                        generated {{ \Illuminate\Support\Carbon::parse($catalog['generated_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto lg:items-center">
                    @if ($useAppLayout)
                        <a href="{{ route('api-docs.json') }}" class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] px-3.5 rounded-xl border border-border text-sm font-medium hover:bg-subtle">
                            <i class="ph ph-brackets-curly"></i> JSON
                        </a>
                    @endif
                    <div class="relative w-full lg:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input
                            type="search"
                            data-api-docs-search
                            placeholder="Filter endpoints…"
                            class="w-full h-10 min-h-[44px] pl-9 pr-3 rounded-xl border border-border bg-bg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            aria-label="Filter API endpoints"
                        >
                    </div>
                </div>
            </div>

            @if (! empty($catalog['conventions']))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-border">
                    @foreach ($catalog['conventions'] as $label => $value)
                        <div class="rounded-xl border border-border bg-subtle/40 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ $label }}</p>
                            <p class="text-sm text-heading mt-1 break-words">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-border bg-surface overflow-hidden sticky top-16 z-20" data-api-docs-lang-bar>
            <div class="px-4 py-3 sm:px-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-heading">Example language</p>
                    <p class="text-xs text-muted">Applies to every endpoint below. Placeholders only — never commit real secrets.</p>
                </div>
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="Example languages" data-api-docs-lang-tabs>
                    @foreach ($exampleLanguages as $index => $language)
                        <button
                            type="button"
                            role="tab"
                            data-api-docs-lang="{{ $language['id'] }}"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            class="inline-flex items-center h-9 min-h-[40px] px-3 rounded-lg border text-xs font-semibold transition-colors {{ $index === 0 ? 'border-primary bg-primary-soft text-primary' : 'border-border bg-surface text-heading hover:bg-subtle' }}"
                        >
                            {{ $language['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-[15rem_minmax(0,1fr)] gap-4 md:gap-5">
            <aside
                class="bg-surface border border-border rounded-2xl p-3 md:p-4 h-fit lg:sticky lg:top-24"
                data-api-docs-group-nav
                aria-label="API modules"
            >
                <p class="px-2 text-[11px] font-bold uppercase tracking-wide text-faint mb-2">Modules</p>
                <nav class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible -mx-1 px-1 pb-1 lg:pb-0" data-api-docs-nav>
                    <a
                        href="#api-docs-examples"
                        data-api-docs-nav-link="examples"
                        class="inline-flex items-center gap-2 whitespace-nowrap lg:w-full text-left h-10 min-h-[44px] px-3 rounded-xl text-sm font-medium transition-colors bg-primary-soft text-primary"
                        aria-current="true"
                    >
                        <i class="ph ph-rocket-launch text-base" aria-hidden="true"></i>
                        <span class="flex-1 truncate">Quick start</span>
                    </a>
                    @foreach ($catalog['groups'] as $group)
                        <a
                            href="#api-group-{{ $group['key'] }}"
                            data-api-docs-nav-link="{{ $group['key'] }}"
                            class="inline-flex items-center gap-2 whitespace-nowrap lg:w-full text-left h-10 min-h-[44px] px-3 rounded-xl text-sm font-medium text-heading hover:bg-subtle transition-colors"
                            aria-current="false"
                        >
                            <i class="ph {{ $groupIcons[$group['key']] ?? 'ph-cube' }} text-base text-muted" aria-hidden="true"></i>
                            <span class="flex-1 truncate">{{ $group['label'] }}</span>
                            <span class="text-xs text-muted tabular-nums" data-api-docs-nav-count>{{ $group['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 space-y-5">
                <section id="api-docs-examples" class="rounded-2xl border border-border bg-surface overflow-hidden scroll-mt-28" data-api-docs-examples>
                    <div class="px-4 py-3 sm:px-5 border-b border-border bg-subtle/40">
                        <h2 class="text-base font-bold text-heading">Quick start</h2>
                        <p class="text-sm text-text-secondary mt-1">
                            Common flows first. Every endpoint in the catalog also has its own copy-ready examples.
                        </p>
                    </div>

                    <div class="p-4 sm:p-5 space-y-5">
                        @foreach ($examples as $example)
                            <article class="rounded-xl border border-border overflow-hidden" data-api-docs-example="{{ $example['id'] }}">
                                <div class="px-4 py-3 border-b border-border bg-subtle/30">
                                    <h3 class="text-sm font-bold text-heading">{{ $example['title'] }}</h3>
                                    <p class="text-xs text-text-secondary mt-1">{{ $example['description'] }}</p>
                                </div>
                                @include('api-docs.partials.snippets', [
                                    'snippets' => $example['snippets'],
                                    'languages' => $exampleLanguages,
                                ])
                            </article>
                        @endforeach
                    </div>
                </section>

                <div class="space-y-5" data-api-docs-groups>
                    @foreach ($catalog['groups'] as $group)
                        <section
                            id="api-group-{{ $group['key'] }}"
                            class="rounded-2xl border border-border bg-surface overflow-hidden scroll-mt-28"
                            data-api-docs-group
                            data-group-key="{{ $group['key'] }}"
                            data-group-label="{{ strtolower($group['label'].' '.$group['key']) }}"
                        >
                            <div class="px-4 py-3 sm:px-5 border-b border-border bg-subtle/40">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <h2 class="text-base font-bold text-heading">{{ $group['label'] }}</h2>
                                    <span class="text-xs text-muted">{{ $group['count'] }} endpoint{{ $group['count'] === 1 ? '' : 's' }}</span>
                                </div>
                                @if ($group['description'] !== '')
                                    <p class="text-sm text-text-secondary mt-1">{{ $group['description'] }}</p>
                                @endif
                            </div>

                            <div class="divide-y divide-border">
                                @foreach ($group['endpoints'] as $endpoint)
                                    @php
                                        $methodClass = match ($endpoint['method']) {
                                            'GET' => 'bg-success-soft text-success',
                                            'POST' => 'bg-primary-soft text-primary',
                                            'PUT', 'PATCH' => 'bg-warning-soft text-heading',
                                            'DELETE' => 'bg-danger-soft text-danger',
                                            default => 'bg-subtle text-muted',
                                        };
                                        $searchBlob = strtolower(implode(' ', [
                                            $endpoint['method'],
                                            $endpoint['path'],
                                            $endpoint['summary'],
                                            implode(' ', $endpoint['permissions'] ?? []),
                                            $group['label'],
                                        ]));
                                    @endphp
                                    <article
                                        class="p-4 sm:p-5 space-y-3"
                                        data-api-docs-endpoint
                                        data-api-docs-example="{{ $endpoint['method'].'-'.$endpoint['path'] }}"
                                        data-search="{{ e($searchBlob) }}"
                                    >
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center h-7 px-2 rounded-md text-[11px] font-bold tracking-wide {{ $methodClass }}">
                                                    {{ $endpoint['method'] }}
                                                </span>
                                                <code class="text-sm font-semibold text-heading break-all">{{ $endpoint['path'] }}</code>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                @if ($endpoint['auth'])
                                                    <span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-[11px] font-medium text-muted">Auth</span>
                                                @else
                                                    <span class="inline-flex items-center h-6 px-2 rounded-md bg-success-soft text-[11px] font-medium text-success">Public</span>
                                                @endif
                                                @foreach ($endpoint['permissions'] as $permission)
                                                    <span class="inline-flex items-center h-6 px-2 rounded-md border border-border text-[11px] font-medium text-heading">{{ $permission }}</span>
                                                @endforeach
                                                @if (! empty($endpoint['throttle']))
                                                    <span class="inline-flex items-center h-6 px-2 rounded-md border border-border text-[11px] font-medium text-muted">throttle:{{ $endpoint['throttle'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="text-sm text-text-secondary">{{ $endpoint['summary'] }}</p>
                                        <p class="text-[11px] text-muted font-mono truncate">{{ $endpoint['action'] }}</p>

                                        @if (! empty($endpoint['examples']))
                                            <details class="rounded-xl border border-border bg-subtle/20 group" data-api-docs-code-panel>
                                                <summary class="cursor-pointer list-none flex items-center justify-between gap-2 px-3 py-2.5 text-xs font-semibold text-heading select-none min-h-[44px]">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <i class="ph ph-code text-base"></i>
                                                        Code examples
                                                    </span>
                                                    <i class="ph ph-caret-down text-muted transition-transform group-open:rotate-180"></i>
                                                </summary>
                                                <div class="border-t border-border">
                                                    @include('api-docs.partials.snippets', [
                                                        'snippets' => $endpoint['examples'],
                                                        'languages' => $exampleLanguages,
                                                    ])
                                                </div>
                                            </details>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <p class="text-center text-xs text-muted pb-6" data-api-docs-empty hidden>
                    No endpoints match your filter.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
