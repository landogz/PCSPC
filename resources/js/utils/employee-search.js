/**
 * Reusable employee typeahead (Users & Security pattern).
 *
 * Markup: wrap with [data-employee-search-root] containing
 * [data-employee-id], [data-employee-search], [data-employee-results].
 */
import http from './http';
import { toastError } from './toast';
import { avatarMarkup, escapeHtml } from './avatar';

/**
 * @param {HTMLElement} root
 * @param {{
 *   endpoint?: string,
 *   params?: Record<string, string|number|boolean>,
 *   onSelect?: (employee: object) => void,
 *   onClear?: () => void,
 *   isOptionDisabled?: (employee: object) => boolean,
 *   optionBadge?: (employee: object) => string,
 *   minChars?: number,
 * }} [options]
 */
export function initEmployeeSearch(root, options = {}) {
    if (!root) {
        return null;
    }

    const hidden = root.querySelector('[data-employee-id]');
    const search = root.querySelector('[data-employee-search]');
    const results = root.querySelector('[data-employee-results]');
    const endpoint = options.endpoint || '/employees/search';
    const minChars = options.minChars ?? 0;
    let timer = null;
    let selected = null;

    function clearSelection() {
        selected = null;
        if (hidden) {
            hidden.value = '';
        }
        if (search) {
            search.value = '';
        }
        results?.classList.add('hidden');
        if (results) {
            results.innerHTML = '';
        }
        options.onClear?.();
    }

    function setSelection(employee) {
        selected = employee;
        if (hidden) {
            hidden.value = employee.id || '';
        }
        if (search) {
            search.value = employee.label || `${employee.employee_number || ''} — ${employee.full_name || ''}`.trim();
        }
        results?.classList.add('hidden');
        options.onSelect?.(employee);
    }

    function renderResults(items = []) {
        if (!results) {
            return;
        }

        if (!items.length) {
            results.innerHTML = '<div class="px-3 py-2.5 text-sm text-muted">No employees found.</div>';
            results.classList.remove('hidden');
            return;
        }

        results.innerHTML = items.map((item) => {
            const disabled = Boolean(options.isOptionDisabled?.(item));
            const payload = encodeURIComponent(JSON.stringify(item));
            const badge = options.optionBadge?.(item) || '';

            return `
                <button
                    type="button"
                    data-employee-option
                    data-employee-json="${payload}"
                    class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm hover:bg-subtle min-h-[44px] ${disabled ? 'opacity-60 cursor-not-allowed' : ''}"
                    ${disabled ? 'aria-disabled="true"' : ''}
                >
                    ${avatarMarkup({
                        url: item.photo_url,
                        name: item.full_name,
                        email: item.email,
                        sizeClass: 'w-8 h-8',
                        textClass: 'text-xs',
                    })}
                    <div class="min-w-0 flex-1">
                        <div class="font-medium text-heading truncate">${escapeHtml(item.full_name || '—')}</div>
                        <div class="text-xs text-muted truncate">${escapeHtml(item.employee_number || '—')} · ${escapeHtml(item.email || 'No email')}</div>
                    </div>
                    ${badge}
                </button>
            `;
        }).join('');
        results.classList.remove('hidden');
    }

    async function runSearch(query) {
        if (query.length < minChars) {
            results?.classList.add('hidden');
            return;
        }

        try {
            const { data } = await http.get(endpoint, {
                params: {
                    search: query || '',
                    ...(options.params || {}),
                },
            });
            renderResults(data?.data?.items || []);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to search employees');
        }
    }

    search?.addEventListener('input', () => {
        selected = null;
        if (hidden) {
            hidden.value = '';
        }
        options.onClear?.();
        clearTimeout(timer);
        timer = setTimeout(() => {
            runSearch(search.value.trim());
        }, 250);
    });

    search?.addEventListener('focus', () => {
        runSearch(search.value.trim());
    });

    results?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-employee-option]');
        if (!button || !results.contains(button)) {
            return;
        }

        let employee;
        try {
            employee = JSON.parse(decodeURIComponent(button.getAttribute('data-employee-json') || '{}'));
        } catch {
            return;
        }

        if (options.isOptionDisabled?.(employee)) {
            return;
        }

        setSelection(employee);
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            results?.classList.add('hidden');
        }
    });

    return {
        getSelected: () => selected,
        getValue: () => hidden?.value || '',
        setSelection,
        clear: () => clearSelection(),
        focus: () => search?.focus(),
        runSearch,
    };
}
