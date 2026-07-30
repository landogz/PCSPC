@php
    use App\Support\Navigation;
    $nav = Navigation::sections();
@endphp

{{-- Mobile Sidebar Overlay --}}
<div
    id="mobile-sidebar-overlay"
    class="fixed inset-0 bg-black/50 z-40 hidden opacity-0 transition-opacity duration-300 lg:hidden"
></div>

{{-- Mobile Sidebar --}}
<aside
    id="mobile-sidebar"
    class="fixed top-0 left-0 z-70 h-full w-[280px] shadow-2xl transform -translate-x-full transition-transform duration-300 lg:hidden flex flex-col"
>
    <div class="h-20 flex items-center justify-between px-4 border-b border-border">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center rounded-lg bg-white px-2.5 py-1.5">
            <img
                src="{{ asset('images/brand/pcspc-logo.png') }}"
                alt="{{ config('app.name') }}"
                width="650"
                height="200"
                decoding="async"
                class="pcspc-sidebar-logo"
            >
        </a>
        <button
            type="button"
            aria-label="Close menu"
            class="w-10 h-10 rounded-xl flex items-center justify-center text-muted hover:bg-white/5"
            data-mobile-sidebar-close
        >
            <i class="ph ph-x text-xl"></i>
        </button>
    </div>

    <nav class="flex-1 flex flex-col overflow-y-auto p-4">
        <div class="space-y-4">
            @foreach ($nav as $group)
                <div class="space-y-1">
                    <p class="nav-section">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $item)
                        <a
                            href="{{ Navigation::href($item) }}"
                            class="mobile-nav-item flex items-center gap-3 px-4 py-3 rounded-xl {{ Navigation::isActive($item) ? 'is-active' : '' }}"
                        >
                            <i class="ph {{ $item['icon'] }} text-xl"></i>
                            <span class="font-medium">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </nav>

    <div class="p-4 border-t border-border flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center text-sm font-semibold flex-shrink-0 overflow-hidden" data-user-avatar>
            <img data-user-avatar-img alt="" class="hidden w-full h-full object-cover">
            <span id="sidebar-user-initial-mobile" data-user-avatar-initial>U</span>
        </div>
        <div class="min-w-0 flex-1">
            <p id="sidebar-user-name-mobile" class="text-sm font-semibold text-white truncate">—</p>
            <p id="sidebar-user-email-mobile" class="text-[11px] text-muted truncate">—</p>
        </div>
    </div>
</aside>

{{-- Desktop Sidebar --}}
<aside
    id="sidebar"
    class="fixed top-0 left-0 z-40 h-full w-64 border-r hidden lg:flex flex-col transition-all duration-300"
>
    <div class="h-20 flex items-center justify-between gap-2 px-3 border-b border-border-subtle">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center overflow-hidden rounded-lg bg-white px-2.5 py-1.5">
            <img
                src="{{ asset('images/brand/pcspc-logo.png') }}"
                alt="{{ config('app.name') }}"
                width="650"
                height="200"
                decoding="async"
                class="pcspc-sidebar-logo"
            >
        </a>
        <button
            type="button"
            class="sidebar-toggle-btn w-8 h-8 rounded-lg flex items-center justify-center text-muted hover:text-white hover:bg-white/5 transition-colors flex-shrink-0"
            aria-label="Toggle sidebar"
        >
            <i class="ph ph-sidebar-simple text-lg"></i>
        </button>
    </div>

    <nav class="flex-1 flex flex-col overflow-y-auto py-3 px-3">
        <div class="space-y-4">
            @foreach ($nav as $group)
                <div class="space-y-1">
                    <p class="nav-section nav-text">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $item)
                        <a
                            href="{{ Navigation::href($item) }}"
                            class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ Navigation::isActive($item) ? 'is-active' : '' }}"
                            title="{{ $item['label'] }}"
                        >
                            <i class="ph {{ $item['icon'] }} text-2xl flex-shrink-0"></i>
                            <span class="nav-text font-medium whitespace-nowrap">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </nav>

    <div class="px-3 py-3 border-t border-border-subtle">
        <div class="flex items-center gap-3 rounded-xl p-2 hover:bg-white/5 transition-colors cursor-pointer">
            <div class="relative flex-shrink-0">
                <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center text-sm font-semibold overflow-hidden" data-user-avatar>
                    <img data-user-avatar-img alt="" class="hidden w-full h-full object-cover">
                    <span id="sidebar-user-initial" data-user-avatar-initial>U</span>
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-primary border-2 border-sidebar"></span>
            </div>
            <div class="nav-text min-w-0 flex-1 leading-tight">
                <p id="sidebar-user-name" class="text-sm font-semibold text-white truncate">—</p>
                <p id="sidebar-user-email" class="text-[11px] text-muted truncate">—</p>
            </div>
            <i class="ph ph-dots-three-vertical nav-text text-muted flex-shrink-0"></i>
        </div>
    </div>
</aside>
