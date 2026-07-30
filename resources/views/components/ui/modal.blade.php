@props([
    'id',
    'title' => null,
    'maxWidth' => 'max-w-lg',
])

{{--
  Shared modal shell with required dimmed backdrop.

  Put a <form class="flex min-h-0 flex-1 flex-col"> in the slot with:
  - scrollable body: flex-1 overflow-y-auto
  - sticky actions: flex-shrink-0 border-t
--}}
<div
    id="{{ $id }}"
    {{ $attributes->class('hidden fixed inset-0 z-[70] overflow-hidden') }}
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $id }}-title" @endif
>
    <div class="modal-backdrop" data-modal-dismiss aria-hidden="true"></div>

    <div class="relative z-10 flex h-full min-h-0 items-end justify-center p-3 sm:items-center sm:p-4">
        <div class="modal-panel {{ $maxWidth }}">
            @if ($title || isset($header))
                <div class="flex flex-shrink-0 items-center justify-between gap-3 border-b border-border p-4 sm:p-5">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h3 id="{{ $id }}-title" class="text-lg font-semibold text-heading" data-modal-title>{{ $title }}</h3>
                    @endisset
                    <button type="button" class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg hover:bg-subtle" data-modal-dismiss aria-label="Close">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
