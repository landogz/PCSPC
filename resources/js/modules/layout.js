import { initPasswordToggles } from '../utils/password-toggle';

const SIDEBAR_STATE_KEY = 'hr-sidebar';
const DESKTOP_BREAKPOINT = 992;

function openMobileSidebar() {
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');
    if (!mobileSidebar || !overlay) {
        return;
    }

    mobileSidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    void overlay.offsetHeight;
    overlay.classList.remove('opacity-0');
    document.body.style.overflow = 'hidden';
}

function closeMobileSidebar() {
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');
    if (!mobileSidebar || !overlay) {
        return;
    }

    mobileSidebar.classList.add('-translate-x-full');
    overlay.classList.add('opacity-0');
    setTimeout(() => overlay.classList.add('hidden'), 300);
    document.body.style.overflow = '';
}

function collapseSidebar() {
    const desktopSidebar = document.getElementById('sidebar');
    const topbar = document.getElementById('topbar');
    const mainContent = document.getElementById('main-content');
    if (!desktopSidebar || !topbar || !mainContent) {
        return;
    }

    document.documentElement.classList.add('sidebar-collapsed');
    desktopSidebar.classList.remove('w-64');
    desktopSidebar.classList.add('w-20');

    if (window.innerWidth >= DESKTOP_BREAKPOINT) {
        topbar.style.left = '5rem';
        mainContent.style.marginLeft = '5rem';
    } else {
        topbar.style.left = '';
        mainContent.style.marginLeft = '';
    }

    desktopSidebar.querySelectorAll('.nav-text').forEach((el) => {
        el.classList.add('opacity-0', 'w-0', 'overflow-hidden');
        el.classList.remove('opacity-100', 'w-auto');
    });
}

function expandSidebar() {
    const desktopSidebar = document.getElementById('sidebar');
    const topbar = document.getElementById('topbar');
    const mainContent = document.getElementById('main-content');
    if (!desktopSidebar || !topbar || !mainContent) {
        return;
    }

    document.documentElement.classList.remove('sidebar-collapsed');
    desktopSidebar.classList.remove('w-20');
    desktopSidebar.classList.add('w-64');

    if (window.innerWidth >= DESKTOP_BREAKPOINT) {
        topbar.style.left = '16rem';
        mainContent.style.marginLeft = '16rem';
    } else {
        topbar.style.left = '';
        mainContent.style.marginLeft = '';
    }

    desktopSidebar.querySelectorAll('.nav-text').forEach((el) => {
        el.classList.remove('opacity-0', 'w-0', 'overflow-hidden');
        el.classList.add('opacity-100', 'w-auto');
    });
}

function initSidebar() {
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');
    const sidebarToggles = document.querySelectorAll('.sidebar-toggle-btn');
    const desktopSidebar = document.getElementById('sidebar');
    const topbar = document.getElementById('topbar');
    const mainContent = document.getElementById('main-content');

    if (mobileSidebar && overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
        document.querySelectorAll('[data-mobile-sidebar-close]').forEach((btn) => {
            btn.addEventListener('click', closeMobileSidebar);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !mobileSidebar.classList.contains('-translate-x-full')) {
                closeMobileSidebar();
            }
        });

        mobileSidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => closeMobileSidebar());
        });

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (window.innerWidth >= DESKTOP_BREAKPOINT) {
                    closeMobileSidebar();
                } else if (topbar && mainContent) {
                    topbar.style.left = '';
                    mainContent.style.marginLeft = '';
                }
            }, 150);
        });
    }

    if (sidebarToggles.length && desktopSidebar && topbar && mainContent) {
        const savedState = localStorage.getItem(SIDEBAR_STATE_KEY);
        if (savedState === 'expanded') {
            expandSidebar();
        } else {
            collapseSidebar();
        }

        const onToggle = () => {
            if (window.innerWidth < DESKTOP_BREAKPOINT) {
                openMobileSidebar();
                return;
            }

            const isCollapsed = desktopSidebar.classList.contains('w-20');
            if (isCollapsed) {
                expandSidebar();
                localStorage.setItem(SIDEBAR_STATE_KEY, 'expanded');
            } else {
                collapseSidebar();
                localStorage.setItem(SIDEBAR_STATE_KEY, 'collapsed');
            }
        };

        sidebarToggles.forEach((btn) => btn.addEventListener('click', onToggle));
    }
}

function initUserMenu() {
    const btn = document.getElementById('user-menu-btn');
    const menu = document.getElementById('user-menu');
    if (!btn || !menu) {
        return;
    }

    const close = () => {
        menu.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        btn.setAttribute('aria-expanded', 'false');
    };

    const open = () => {
        menu.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
        btn.setAttribute('aria-expanded', 'true');
    };

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = btn.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            close();
        } else {
            open();
        }
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            close();
        }
    });
}

function initThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) {
        return;
    }

    const icon = btn.querySelector('i');
    const syncIcon = () => {
        const dark = document.documentElement.classList.contains('dark');
        if (icon) {
            icon.className = dark ? 'ph ph-sun text-lg sm:text-xl' : 'ph ph-moon text-lg sm:text-xl';
        }
    };

    syncIcon();

    btn.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('hr-theme', dark ? 'dark' : 'light');
        syncIcon();
    });
}

function initPasswordToggle() {
    initPasswordToggles(document);
}

export function initLayoutModule() {
    initSidebar();
    initUserMenu();
    initThemeToggle();
    initPasswordToggle();

    window.openMobileSidebar = openMobileSidebar;
    window.closeMobileSidebar = closeMobileSidebar;
}

export function fillSidebarUser(user) {
    if (!user) {
        return;
    }

    const initial = (user.name || user.email || 'U').trim().charAt(0).toUpperCase();
    const firstName = (user.name || '').trim().split(/\s+/)[0] || 'there';

    const map = {
        'sidebar-user-name': user.name,
        'sidebar-user-email': user.email,
        'sidebar-user-initial': initial,
        'sidebar-user-name-mobile': user.name,
        'sidebar-user-email-mobile': user.email,
        'sidebar-user-initial-mobile': initial,
        'topbar-user-name': user.name,
        'topbar-user-initial': initial,
        'user-menu-name': user.name,
        'user-menu-email': user.email,
        'dash-first-name': firstName,
    };

    Object.entries(map).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value || '—';
        }
    });
}
