@props(['title' => 'Dashboard'])

<header
    id="topbar"
    data-scrolled="false"
    class="fixed top-0 right-0 z-45 h-20 bg-white/70 dark:bg-w1/60 backdrop-blur-xl border-b border-border-subtle left-0 lg:left-64 transition-all duration-300 data-[scrolled=true]:shadow-sm"
>
    <div class="flex items-center justify-between h-full gap-2 px-3 sm:px-4 lg:px-6">
        <div class="flex items-center gap-2 min-w-0">
            <button
                type="button"
                class="sidebar-toggle-btn w-9 h-9 rounded-lg flex lg:hidden! items-center justify-center text-muted transition-colors flex-shrink-0"
                aria-label="Toggle sidebar"
            >
                <i class="ph ph-list text-xl"></i>
            </button>
            <h1 id="page-title" class="hidden sm:block text-lg sm:text-xl font-bold text-heading truncate">
                {{ $title }}
            </h1>
        </div>

        <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
            <div class="topbar-search relative hidden md:flex items-center w-56 xl:w-72 h-10 px-3 rounded-xl bg-subtle border border-border group cursor-pointer hover:border-border-strong transition-colors"
                 data-search-mega-open
                 role="button"
                 tabindex="0"
                 aria-label="Open search"
            >
                <i class="ph ph-magnifying-glass text-faint text-lg group-hover:text-primary transition-colors flex-shrink-0"></i>
                <span class="flex-1 min-w-0 px-2.5 text-sm text-faint truncate">Search modules, people…</span>
                <kbd class="kbd-hint hidden lg:inline-flex gap-0.5">⌘ K</kbd>
            </div>

            <button
                type="button"
                class="topbar-icon-btn flex md:hidden! w-9 h-9 border border-border"
                aria-label="Search"
                data-search-mega-open
            >
                <i class="ph ph-magnifying-glass text-lg"></i>
            </button>

            <button
                type="button"
                class="topbar-icon-btn w-9 h-9 sm:w-10 sm:h-10 border border-border"
                aria-label="Calendar"
            >
                <i class="ph ph-calendar-dots text-lg sm:text-xl"></i>
            </button>

            <div class="relative" data-topbar-notifications>
                <button
                    type="button"
                    id="topbar-notifications-btn"
                    class="topbar-icon-btn relative w-9 h-9 sm:w-10 sm:h-10 border border-border"
                    aria-label="Notifications"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-controls="topbar-notifications-menu"
                >
                    <span
                        data-notif-badge
                        class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-danger text-white text-[10px] font-semibold leading-none flex items-center justify-center ring-2 ring-surface"
                    >0</span>
                    <i class="ph ph-bell text-lg sm:text-xl"></i>
                </button>

                <div
                    data-notif-backdrop
                    class="topbar-notifications-backdrop hidden"
                    data-notif-dismiss
                    aria-hidden="true"
                ></div>

                <div
                    id="topbar-notifications-menu"
                    class="topbar-notifications-menu dropdown-menu opacity-0 pointer-events-none scale-95 origin-top-right"
                    role="menu"
                    aria-label="Notifications"
                >
                    <div class="flex items-center justify-between gap-2 p-3 border-b border-border-subtle">
                        <p class="text-sm font-semibold text-heading truncate">Notifications</p>
                        <button
                            type="button"
                            data-notif-mark-all
                            class="text-xs font-medium text-primary hover:underline min-h-[44px] sm:min-h-0 px-1 flex-shrink-0"
                        >
                            Mark all read
                        </button>
                    </div>
                    <div class="py-1" data-notif-list>
                        <p class="px-3 py-6 text-sm text-muted text-center">Loading…</p>
                    </div>
                    <div class="p-2 border-t border-border-subtle">
                        <a
                            href="{{ route('modules.show', ['module' => 'notifications']) }}"
                            class="flex items-center justify-center gap-1.5 w-full min-h-[44px] px-3 py-2.5 rounded-xl text-sm font-medium text-text hover:bg-subtle transition-colors"
                            role="menuitem"
                        >
                            View all notifications
                            <i class="ph ph-arrow-right text-sm"></i>
                        </a>
                    </div>
                </div>
            </div>

            <button
                type="button"
                id="theme-toggle"
                class="topbar-icon-btn w-9 h-9 sm:w-10 sm:h-10 border border-border"
                aria-label="Toggle theme"
            >
                <i class="ph ph-moon text-lg sm:text-xl"></i>
            </button>

            <div class="relative">
                <button
                    type="button"
                    id="user-menu-btn"
                    class="flex items-center gap-2 h-9 sm:h-10 pl-1 pr-2 sm:pr-3 rounded-xl border border-border bg-subtle hover:border-border-strong transition-colors"
                    aria-haspopup="menu"
                    aria-expanded="false"
                >
                    <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-semibold flex items-center justify-center flex-shrink-0 overflow-hidden" data-user-avatar>
                        <img data-user-avatar-img alt="" class="hidden w-full h-full object-cover">
                        <span id="topbar-user-initial" data-user-avatar-initial>U</span>
                    </span>
                    <span id="topbar-user-name" class="hidden sm:block text-sm font-medium text-heading max-w-[100px] truncate">—</span>
                    <i class="ph ph-caret-down text-muted text-sm hidden sm:block"></i>
                </button>

                <div
                    id="user-menu"
                    class="dropdown-menu absolute right-0 top-full mt-3 w-60 bg-w1 rounded-2xl shadow-xl border border-border opacity-0 pointer-events-none scale-95 origin-top-right z-50"
                    role="menu"
                >
                    <div class="p-3 border-b border-border-subtle">
                        <p id="user-menu-name" class="text-sm font-semibold text-heading truncate">—</p>
                        <p id="user-menu-email" class="text-xs text-muted truncate">—</p>
                    </div>
                    <div class="p-1.5">
                        <button
                            type="button"
                            id="edit-profile-btn"
                            role="menuitem"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-text hover:bg-subtle transition-colors text-left"
                        >
                            <i class="ph ph-user-circle text-lg text-muted"></i>
                            Edit profile
                        </button>
                        <button
                            type="button"
                            id="change-password-btn"
                            role="menuitem"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-text hover:bg-subtle transition-colors text-left"
                        >
                            <i class="ph ph-lock-key text-lg text-muted"></i>
                            Change password
                        </button>
                        <button
                            type="button"
                            id="logout-others-btn"
                            role="menuitem"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-text hover:bg-subtle transition-colors text-left"
                        >
                            <i class="ph ph-devices text-lg text-muted"></i>
                            Logout other devices
                        </button>
                        <button
                            type="button"
                            id="logout-btn"
                            role="menuitem"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-danger hover:bg-danger-soft transition-colors text-left"
                        >
                            <i class="ph ph-sign-out text-lg"></i>
                            Sign out
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
