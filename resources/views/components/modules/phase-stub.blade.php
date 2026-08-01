@props([
    'reqLabel' => '',
    'upcoming' => [],
    'employeesHref' => null,
])

<div class="space-y-4">
    @if ($reqLabel !== '')
        <p class="text-sm text-text-secondary">
            Employee 201 stub for <span class="font-semibold text-heading">{{ $reqLabel }}</span>.
            Module listing and nested CRUD ship in Phase 6.
        </p>
    @endif

    @if (count($upcoming) > 0)
        <div>
            <h4 class="text-sm font-semibold text-heading mb-2">Planned for Phase 6</h4>
            <ul class="space-y-1.5 text-sm text-text-secondary">
                @foreach ($upcoming as $item)
                    <li class="flex items-start gap-2">
                        <i class="ph ph-check-circle text-primary mt-0.5 flex-shrink-0"></i>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        @if ($employeesHref)
            <a
                href="{{ $employeesHref }}"
                class="inline-flex items-center gap-1.5 h-10 min-h-[44px] px-4 rounded-xl border border-border bg-surface text-sm font-medium text-heading hover:bg-subtle transition-colors"
            >
                <i class="ph ph-users text-base"></i>
                Employees 201
            </a>
        @endif
        {{ $slot }}
    </div>
</div>
