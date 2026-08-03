/**
 * Topbar notifications bell (SPA)
 * Axios: GET /api/v1/notifications/recent, POST .../read, POST .../mark-all-read
 *
 * Menu + backdrop are moved to document.body so position:fixed is viewport-relative
 * (nesting under #topbar backdrop-filter would clip the panel on mobile).
 */
import http from '../../utils/http';
import { toastError, toastSuccess } from '../../utils/toast';
import { escapeHtml } from '../../utils/server-table';

const POLL_MS = 60_000;
const MD_MQ = '(min-width: 768px)';

function formatRelative(value) {
    if (!value) {
        return '';
    }
    try {
        const date = new Date(value);
        const diffSec = Math.round((Date.now() - date.getTime()) / 1000);
        if (diffSec < 60) {
            return 'just now';
        }
        if (diffSec < 3600) {
            return `${Math.floor(diffSec / 60)}m ago`;
        }
        if (diffSec < 86400) {
            return `${Math.floor(diffSec / 3600)}h ago`;
        }
        return date.toLocaleDateString();
    } catch {
        return '';
    }
}

function setBadge(badge, count) {
    if (!badge) {
        return;
    }
    const n = Number(count) || 0;
    if (n <= 0) {
        badge.classList.add('hidden');
        badge.textContent = '0';
        return;
    }
    badge.classList.remove('hidden');
    badge.textContent = n > 99 ? '99+' : String(n);
}

function renderList(listEl, items) {
    if (!listEl) {
        return;
    }
    if (!items.length) {
        listEl.innerHTML = '<p class="px-3 py-6 text-sm text-muted text-center">No notifications yet.</p>';
        return;
    }

    listEl.innerHTML = items.map((item) => {
        const unread = !item.is_read;
        return `
            <button
                type="button"
                role="menuitem"
                data-notif-id="${escapeHtml(item.id)}"
                data-notif-url="${escapeHtml(item.action_url || '')}"
                class="w-full text-left px-3 py-2.5 hover:bg-subtle transition-colors flex gap-2.5 min-h-[44px] ${unread ? 'bg-primary/5' : ''}"
            >
                <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0 ${unread ? 'bg-danger' : 'bg-transparent'}" aria-hidden="true"></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-heading truncate">${escapeHtml(item.title)}</span>
                    ${item.body ? `<span class="block text-xs text-muted mt-0.5 line-clamp-2">${escapeHtml(item.body)}</span>` : ''}
                    <span class="block text-[11px] text-faint mt-1">${escapeHtml(formatRelative(item.created_at))}</span>
                </span>
            </button>
        `;
    }).join('');
}

export function initTopbarNotifications() {
    const root = document.querySelector('[data-topbar-notifications]');
    if (!root) {
        return;
    }

    const btn = root.querySelector('#topbar-notifications-btn');
    const menu = root.querySelector('#topbar-notifications-menu');
    const backdrop = root.querySelector('[data-notif-backdrop]');
    const badge = root.querySelector('[data-notif-badge]');
    const listEl = menu?.querySelector('[data-notif-list]');
    const markAllBtn = menu?.querySelector('[data-notif-mark-all]');

    if (!btn || !menu) {
        return;
    }

    // Escape #topbar backdrop-filter containing block.
    if (backdrop && backdrop.parentElement !== document.body) {
        document.body.appendChild(backdrop);
    }
    if (menu.parentElement !== document.body) {
        document.body.appendChild(menu);
    }

    let open = false;
    let loading = false;

    function positionMenu() {
        const isMd = window.matchMedia(MD_MQ).matches;
        if (!isMd) {
            menu.style.left = '0.75rem';
            menu.style.right = '0.75rem';
            menu.style.top = '4.75rem';
            menu.style.width = 'auto';
            menu.style.maxWidth = 'none';
            return;
        }

        const rect = btn.getBoundingClientRect();
        const width = Math.min(352, window.innerWidth - 24);
        let left = rect.right - width;
        left = Math.max(12, Math.min(left, window.innerWidth - width - 12));
        menu.style.left = `${left}px`;
        menu.style.right = 'auto';
        menu.style.top = `${rect.bottom + 12}px`;
        menu.style.width = `${width}px`;
        menu.style.maxWidth = 'none';
    }

    function setOpen(next) {
        open = next;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            positionMenu();
            menu.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
            backdrop?.classList.remove('hidden');
            refresh();
        } else {
            menu.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
            backdrop?.classList.add('hidden');
        }
    }

    async function refresh() {
        if (loading) {
            return;
        }
        loading = true;
        try {
            const { data } = await http.get('/notifications/recent', { params: { limit: 8 } });
            const payload = data?.data || {};
            setBadge(badge, payload.unread_count);
            renderList(listEl, payload.items || []);
            window.dispatchEvent(new CustomEvent('pcspc:notifications-updated', {
                detail: { unread_count: payload.unread_count || 0 },
            }));
        } catch (error) {
            if (listEl && open) {
                listEl.innerHTML = `<p class="px-3 py-6 text-sm text-muted text-center">${escapeHtml(error.response?.data?.message || 'Unable to load notifications')}</p>`;
            }
        } finally {
            loading = false;
        }
    }

    async function refreshBadgeOnly() {
        try {
            const { data } = await http.get('/notifications/unread-count');
            setBadge(badge, data?.data?.unread_count);
        } catch {
            // Keep last known badge on poll errors.
        }
    }

    btn.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(!open);
    });

    backdrop?.addEventListener('click', () => setOpen(false));

    document.addEventListener('click', (event) => {
        if (!open) {
            return;
        }
        const t = event.target;
        if (root.contains(t) || menu.contains(t) || t === backdrop) {
            return;
        }
        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && open) {
            setOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (open) {
            positionMenu();
        }
    });

    listEl?.addEventListener('click', async (event) => {
        const itemBtn = event.target.closest('[data-notif-id]');
        if (!itemBtn) {
            return;
        }
        const id = itemBtn.getAttribute('data-notif-id');
        const url = itemBtn.getAttribute('data-notif-url') || '';
        try {
            await http.post(`/notifications/${id}/read`);
            await refresh();
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to update notification');
            return;
        }
        setOpen(false);
        if (url) {
            window.location.href = url;
        }
    });

    markAllBtn?.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        try {
            await http.post('/notifications/mark-all-read');
            toastSuccess('All notifications marked as read');
            await refresh();
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to mark all as read');
        }
    });

    window.addEventListener('pcspc:notifications-refresh', () => {
        refresh();
    });

    refreshBadgeOnly();
    window.setInterval(refreshBadgeOnly, POLL_MS);
}
