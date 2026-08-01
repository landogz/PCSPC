@php
    $tabs = [
        ['key' => 'employment', 'label' => 'Employment', 'short' => 'Job', 'icon' => 'ph-briefcase'],
        ['key' => 'personal', 'label' => 'Personal', 'short' => 'Personal', 'icon' => 'ph-user'],
        ['key' => 'contact', 'label' => 'Contact', 'short' => 'Contact', 'icon' => 'ph-envelope-simple'],
        ['key' => 'documents', 'label' => 'Documents', 'short' => 'IDs', 'icon' => 'ph-identification-card'],
        ['key' => 'dependents', 'label' => 'Dependents', 'short' => 'Family', 'icon' => 'ph-users-three'],
        ['key' => 'education', 'label' => 'Education', 'short' => 'School', 'icon' => 'ph-graduation-cap'],
        ['key' => 'history', 'label' => 'History', 'short' => 'History', 'icon' => 'ph-clock-counter-clockwise'],
        ['key' => 'training', 'label' => 'Training', 'short' => 'Train', 'icon' => 'ph-chalkboard-teacher'],
        ['key' => 'medical', 'label' => 'Medical', 'short' => 'Medical', 'icon' => 'ph-heartbeat'],
    ];
@endphp

<div class="employee-form-nav flex-shrink-0 border-b border-border bg-surface" data-employee-form-nav>
    <div class="employee-tabs-wrap relative px-3 pt-3 sm:px-5" data-employee-tabs-wrap>
        <button
            type="button"
            class="employee-tabs-arrow employee-tabs-arrow-prev"
            data-tabs-prev
            aria-label="Scroll tabs left"
            disabled
        >
            <i class="ph ph-caret-left text-lg" aria-hidden="true"></i>
        </button>

        <div class="employee-tabs-fade pointer-events-none absolute inset-y-3 left-10 z-10 w-5 sm:left-12" aria-hidden="true"></div>
        <div class="employee-tabs-fade employee-tabs-fade-end pointer-events-none absolute inset-y-3 right-10 z-10 w-5 sm:right-12" aria-hidden="true"></div>

        <nav
            class="employee-tabs mx-8 sm:mx-9"
            data-employee-tabs
            role="tablist"
            aria-label="Employee form sections"
        >
            @foreach ($tabs as $index => $tab)
                <button
                    type="button"
                    role="tab"
                    data-tab="{{ $tab['key'] }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    class="employee-tab{{ $index === 0 ? ' is-active' : '' }}"
                >
                    <span class="employee-tab-icon" aria-hidden="true">
                        <i class="ph {{ $tab['icon'] }}"></i>
                    </span>
                    <span class="employee-tab-label">
                        <span class="employee-tab-label-full">{{ $tab['label'] }}</span>
                        <span class="employee-tab-label-short">{{ $tab['short'] }}</span>
                    </span>
                    <span class="employee-tab-error hidden" data-tab-error="{{ $tab['key'] }}" aria-label="Has errors"></span>
                </button>
            @endforeach
        </nav>

        <button
            type="button"
            class="employee-tabs-arrow employee-tabs-arrow-next"
            data-tabs-next
            aria-label="Scroll tabs right"
            disabled
        >
            <i class="ph ph-caret-right text-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="flex flex-col gap-2 px-3 pb-3 pt-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5" data-form-progress-wrap>
        <div class="employee-progress-track min-w-0 flex-1" data-progress-segments aria-hidden="true">
            @foreach ($tabs as $index => $tab)
                <span
                    class="employee-progress-seg{{ $index === 0 ? ' is-current' : '' }}"
                    data-progress-seg="{{ $tab['key'] }}"
                ></span>
            @endforeach
        </div>
        <p
            class="shrink-0 text-[11px] font-medium leading-tight text-text-secondary sm:text-right sm:text-xs"
            data-form-progress
        >
            0 of {{ count($tabs) }} sections started
        </p>
    </div>
</div>
