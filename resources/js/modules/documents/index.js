import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, rowActionsCell, cardActions } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';
import { initEmployeeSearch } from '../../utils/employee-search';
import {
    accessBadge,
    CATEGORY_LABELS,
    expiryBadge,
    fileTypeIcon,
    thumbnailMarkup,
} from './helpers';
import { createDocumentPreview } from './preview';

function clearErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function showErrors(form, errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error="${field}"]`);
        if (!el) {
            return;
        }
        el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        el.classList.remove('hidden');
    });
}

function downloadDocument(row) {
    const link = document.createElement('a');
    link.href = row.download_url || `/api/v1/documents/${encodeURIComponent(row.id)}/download`;
    link.setAttribute('download', row.original_name || 'document');
    document.body.appendChild(link);
    link.click();
    link.remove();
}

export function initDocumentsModule() {
    const root = document.querySelector('[data-module="documents"]');
    if (!root) {
        return;
    }

    const canManage = root.dataset.canManage === '1';
    const panel = root.querySelector('[data-spa-module="documents-table"]');
    const modal = document.getElementById('document-modal');
    const form = document.getElementById('document-form');
    const title = modal?.querySelector('[data-modal-title]');
    const fileLabel = form?.querySelector('[data-file-label]');
    const currentFile = form?.querySelector('[data-current-file]');
    const searchInput = panel?.querySelector('[data-filter="search"]');
    const categoryInput = root.querySelector('[data-filter="category"]');
    const expiryInput = root.querySelector('[data-filter="expiry"]');
    const bulkBar = root.querySelector('[data-bulk-bar]');
    const bulkLabel = root.querySelector('[data-bulk-label]');
    const selectAll = panel?.querySelector('[data-select-all]');
    const dropRoot = root.querySelector('[data-drop-root]');
    const dropOverlay = root.querySelector('[data-drop-overlay]');
    let editingId = null;
    const selected = new Set();

    if (!panel || !modal || !form) {
        return;
    }

    const employeeSearch = initEmployeeSearch(form.querySelector('[data-employee-search-root]'));
    const preview = createDocumentPreview({
        root,
        canManage,
        onEdit: (row) => openEdit(row),
    });

    function rowActionsFor() {
        const actions = [
            { key: 'preview', label: 'Quick view' },
            { key: 'download', label: 'Download' },
        ];
        if (canManage) {
            actions.push({ key: 'edit', label: 'Edit' });
            actions.push({ key: 'delete', label: 'Delete', danger: true });
        }
        return actions;
    }

    function syncBulkBar() {
        if (!bulkBar) {
            return;
        }
        const count = selected.size;
        bulkBar.classList.toggle('hidden', count === 0);
        bulkBar.classList.toggle('flex', count > 0);
        if (bulkLabel) {
            bulkLabel.textContent = `${count} selected`;
        }
        if (selectAll) {
            const pageIds = [...panel.querySelectorAll('[data-row-select]')].map((el) => el.value);
            selectAll.checked = pageIds.length > 0 && pageIds.every((id) => selected.has(id));
            selectAll.indeterminate = pageIds.some((id) => selected.has(id)) && !selectAll.checked;
        }
    }

    function updateStats(stats = {}) {
        root.querySelectorAll('[data-count]').forEach((el) => {
            const key = el.dataset.count;
            el.textContent = String(stats[key] ?? 0);
        });

        const byCategory = stats.by_category || {};
        root.querySelectorAll('[data-folder-count]').forEach((el) => {
            const key = el.dataset.folderCount;
            el.textContent = String(key === 'all' ? (stats.total ?? 0) : (byCategory[key] ?? 0));
        });

        const storage = stats.storage || {};
        const percent = storage.percent ?? 0;
        const bar = root.querySelector('[data-storage-bar]');
        const percentEl = root.querySelector('[data-storage-percent]');
        const labelEl = root.querySelector('[data-storage-label]');
        if (bar) {
            bar.style.width = `${Math.min(100, percent)}%`;
        }
        if (percentEl) {
            percentEl.textContent = `${percent}%`;
        }
        if (labelEl) {
            labelEl.textContent = `${storage.used_label || '0 B'} of ${storage.limit_label || '5 GB'} used`;
        }
    }

    function setExpiryTab(value) {
        if (expiryInput) {
            expiryInput.value = value || '';
        }
        root.querySelectorAll('[data-expiry-tab]').forEach((btn) => {
            const active = (btn.dataset.expiryTab || '') === (value || '');
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            btn.classList.toggle('bg-primary-soft', active);
            btn.classList.toggle('text-primary', active);
            btn.classList.toggle('border-primary/30', active);
            btn.classList.toggle('bg-surface', !active);
            btn.classList.toggle('text-heading', !active);
        });
    }

    function setCategoryFolder(value) {
        if (categoryInput) {
            categoryInput.value = value || '';
        }
        root.querySelectorAll('[data-category-folder]').forEach((btn) => {
            const active = (btn.dataset.categoryFolder || '') === (value || '');
            btn.setAttribute('aria-current', active ? 'true' : 'false');
            btn.classList.toggle('bg-primary-soft', active);
            btn.classList.toggle('text-primary', active);
            btn.classList.toggle('text-heading', !active);
            btn.classList.toggle('hover:bg-subtle', !active);
        });
        if (form.category && value) {
            form.category.value = value;
        }
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('expiry')) {
        setExpiryTab(params.get('expiry'));
    } else {
        setExpiryTab('');
    }
    if (params.get('category')) {
        setCategoryFolder(params.get('category'));
    } else {
        setCategoryFolder('');
    }

    const table = createServerTable({
        root: panel,
        endpoint: '/documents',
        columns: 6,
        perPage: 12,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            category: categoryInput?.value || '',
            expiry: expiryInput?.value || '',
        }),
        onLoaded: (payload) => {
            updateStats(payload.stats || {});
            // Restore checkbox state after re-render
            panel.querySelectorAll('[data-row-select]').forEach((checkbox) => {
                checkbox.checked = selected.has(checkbox.value);
            });
            syncBulkBar();
        },
        mapRow: (row) => {
            const actions = rowActionsFor(row);
            const employee = row.employee
                ? `<div class="min-w-0">
                        <p class="font-medium text-heading truncate">${escapeHtml(row.employee.name || '—')}</p>
                        <p class="text-xs text-muted">${escapeHtml(row.employee.employee_number || '')}</p>
                   </div>`
                : '<span class="text-muted">—</span>';

            const checkbox = canManage
                ? `<input type="checkbox" class="rounded border-border" data-row-select value="${escapeHtml(row.id)}" aria-label="Select ${escapeHtml(row.title)}">`
                : '';

            return `
                <tr class="hover:bg-subtle/60 cursor-pointer" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}' data-open-preview>
                    <td class="px-3 py-3" data-stop-preview>${checkbox}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3 min-w-0">
                            ${thumbnailMarkup(row)}
                            <div class="min-w-0">
                                <p class="font-semibold text-heading truncate">${escapeHtml(row.title)}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-muted">${escapeHtml(CATEGORY_LABELS[row.category] || row.category)}</span>
                                    ${accessBadge(row)}
                                    ${row.version_count > 0 ? `<span class="text-[10px] font-semibold text-muted">v${row.version_count + 1}</span>` : ''}
                                </div>
                                ${row.notes ? `<p class="text-xs text-muted mt-0.5 line-clamp-1">${escapeHtml(row.notes)}</p>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">${employee}</td>
                    <td class="px-4 py-3">${expiryBadge(row)}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 min-w-0">
                            ${fileTypeIcon(row, 'h-8 w-8')}
                            <div class="min-w-0">
                                <p class="text-sm text-heading truncate max-w-[10rem]" title="${escapeHtml(row.original_name || '')}">${escapeHtml(row.original_name || '—')}</p>
                                <p class="text-xs text-muted">${escapeHtml(row.file_size_label || '')}</p>
                            </div>
                        </div>
                    </td>
                    ${rowActionsCell(actions).replace(
                        'class="px-4 py-3 text-right whitespace-nowrap"',
                        'class="px-4 py-3 text-right whitespace-nowrap" data-stop-preview',
                    )}
                </tr>
            `;
        },
        mapCard: (row) => {
            const actions = rowActionsFor(row);
            return `
                <article
                    class="rounded-2xl border border-border bg-surface overflow-hidden flex flex-col hover:border-primary/30 transition-colors cursor-pointer"
                    data-row-id="${escapeHtml(row.id)}"
                    data-actions='${JSON.stringify(actions)}'
                    data-open-preview
                >
                    <div class="relative p-3 pb-0" data-stop-preview>
                        ${canManage ? `<label class="absolute top-4 left-4 z-10 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-surface/90 border border-border shadow-sm"><input type="checkbox" class="rounded border-border" data-row-select value="${escapeHtml(row.id)}" aria-label="Select ${escapeHtml(row.title)}"></label>` : ''}
                        ${thumbnailMarkup(row, { tall: true })}
                    </div>
                    <div class="p-4 flex flex-col gap-3 flex-1">
                        <div class="min-w-0">
                            <p class="font-semibold text-heading truncate">${escapeHtml(row.title)}</p>
                            <p class="text-xs text-muted mt-0.5">${escapeHtml(CATEGORY_LABELS[row.category] || row.category)}</p>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            <p class="text-text-secondary truncate">
                                ${escapeHtml(row.employee?.name || '—')}
                                ${row.employee?.employee_number ? `<span class="text-muted">· ${escapeHtml(row.employee.employee_number)}</span>` : ''}
                            </p>
                            <div class="flex flex-wrap gap-1.5">${expiryBadge(row)} ${accessBadge(row)}</div>
                            <p class="text-xs text-muted truncate">${escapeHtml(row.original_name || '—')} · ${escapeHtml(row.file_size_label || '')}</p>
                        </div>
                        <div data-stop-preview>${cardActions(actions)}</div>
                    </div>
                </article>
            `;
        },
        onRowAction: async (action, row) => {
            if (action === 'preview') {
                await preview.open(row);
                return;
            }
            if (action === 'download') {
                downloadDocument(row);
                return;
            }
            if (action === 'edit') {
                openEdit(row);
                return;
            }
            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete document?',
                    text: `${row.title} will be permanently removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/documents/${row.id}`);
                    selected.delete(row.id);
                    toastSuccess(data.message || 'Document deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete document');
                }
            }
        },
    });

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.category.value = categoryInput?.value || 'contract';
        employeeSearch?.clear();
        if (fileLabel) {
            fileLabel.classList.add('ui-label-required');
        }
        form.file.required = true;
        if (currentFile) {
            currentFile.textContent = '';
            currentFile.classList.add('hidden');
        }
    }

    function openCreate(prefillFile = null) {
        resetForm();
        if (title) {
            title.textContent = 'Upload document';
        }
        if (prefillFile) {
            try {
                const transfer = new DataTransfer();
                transfer.items.add(prefillFile);
                form.file.files = transfer.files;
            } catch {
                // Some browsers block programmatic file assignment
            }
        }
        openModal(modal);
        employeeSearch?.focus();
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        if (title) {
            title.textContent = 'Edit document';
        }
        form.id.value = row.id;
        if (row.employee?.id) {
            employeeSearch?.setSelection({
                id: row.employee.id,
                employee_number: row.employee.employee_number,
                full_name: row.employee.name,
                email: row.employee.email,
                label: `${row.employee.employee_number || ''} — ${row.employee.name || ''}`.trim(),
            });
        }
        form.title.value = row.title || '';
        form.category.value = row.category || 'other';
        form.issued_at.value = row.issued_at || '';
        form.expires_at.value = row.expires_at || '';
        form.notes.value = row.notes || '';
        form.file.required = false;
        if (fileLabel) {
            fileLabel.classList.remove('ui-label-required');
        }
        if (currentFile) {
            currentFile.textContent = `Current file: ${row.original_name || 'attached'} (${row.file_size_label || ''}). Re-upload keeps version history.`;
            currentFile.classList.remove('hidden');
        }
        openModal(modal);
    }

    panel.querySelector('[data-action="create"]')?.addEventListener('click', () => openCreate());
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    root.querySelectorAll('[data-expiry-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setExpiryTab(btn.dataset.expiryTab || '');
            selected.clear();
            syncBulkBar();
            table.reload(true);
        });
    });

    root.querySelectorAll('[data-category-folder]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setCategoryFolder(btn.dataset.categoryFolder || '');
            selected.clear();
            syncBulkBar();
            table.reload(true);
        });
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });

    // Selection
    panel.addEventListener('change', (event) => {
        const checkbox = event.target.closest('[data-row-select]');
        if (checkbox) {
            if (checkbox.checked) {
                selected.add(checkbox.value);
            } else {
                selected.delete(checkbox.value);
            }
            syncBulkBar();
            return;
        }
        if (event.target === selectAll) {
            panel.querySelectorAll('[data-row-select]').forEach((el) => {
                el.checked = selectAll.checked;
                if (selectAll.checked) {
                    selected.add(el.value);
                } else {
                    selected.delete(el.value);
                }
            });
            syncBulkBar();
        }
    });

    // Click row / card to quick view (unless interactive child)
    panel.addEventListener('click', (event) => {
        if (event.target.closest('[data-stop-preview], [data-row-action], [data-row-select], a, button, label, input')) {
            return;
        }
        const host = event.target.closest('[data-open-preview][data-row-id]');
        if (!host) {
            return;
        }
        const row = table.getRows().find((item) => item.id === host.dataset.rowId);
        if (row) {
            preview.open(row);
        }
    });

    // Bulk actions
    root.querySelector('[data-bulk-action="clear"]')?.addEventListener('click', () => {
        selected.clear();
        panel.querySelectorAll('[data-row-select]').forEach((el) => {
            el.checked = false;
        });
        syncBulkBar();
    });

    root.querySelector('[data-bulk-action="download"]')?.addEventListener('click', () => {
        const rows = table.getRows().filter((row) => selected.has(row.id));
        if (!rows.length) {
            toastError('Select at least one document.');
            return;
        }
        rows.forEach((row, index) => {
            setTimeout(() => downloadDocument(row), index * 200);
        });
        toastSuccess(`Downloading ${rows.length} file(s)…`);
    });

    root.querySelector('[data-bulk-action="delete"]')?.addEventListener('click', async () => {
        const ids = [...selected];
        if (!ids.length) {
            return;
        }
        const confirmed = await confirmAction({
            title: `Delete ${ids.length} document(s)?`,
            text: 'Selected files and their version history will be permanently removed.',
            confirmButtonText: 'Delete all',
        });
        if (!confirmed.isConfirmed) {
            return;
        }
        try {
            const { data } = await http.post('/documents/bulk-delete', { ids });
            selected.clear();
            toastSuccess(data.message || 'Documents deleted');
            table.reload(true);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to delete documents');
        }
    });

    root.querySelector('[data-bulk-category]')?.addEventListener('change', async (event) => {
        const category = event.target.value;
        const ids = [...selected];
        if (!category || !ids.length) {
            return;
        }
        try {
            const { data } = await http.post('/documents/bulk-category', { ids, category });
            toastSuccess(data.message || 'Category updated');
            event.target.value = '';
            table.reload(true);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to update category');
            event.target.value = '';
        }
    });

    // Drag and drop upload
    if (canManage && dropRoot && dropOverlay) {
        let dragDepth = 0;
        const showOverlay = () => {
            dropOverlay.classList.remove('hidden');
            dropOverlay.classList.add('flex');
        };
        const hideOverlay = () => {
            dropOverlay.classList.add('hidden');
            dropOverlay.classList.remove('flex');
            dragDepth = 0;
        };

        dropRoot.addEventListener('dragenter', (event) => {
            event.preventDefault();
            dragDepth += 1;
            showOverlay();
        });
        dropRoot.addEventListener('dragover', (event) => {
            event.preventDefault();
        });
        dropRoot.addEventListener('dragleave', (event) => {
            event.preventDefault();
            dragDepth = Math.max(0, dragDepth - 1);
            if (dragDepth === 0) {
                hideOverlay();
            }
        });
        dropRoot.addEventListener('drop', (event) => {
            event.preventDefault();
            hideOverlay();
            const file = event.dataTransfer?.files?.[0];
            if (!file) {
                return;
            }
            openCreate(file);
            toastSuccess(`Ready to upload “${file.name}” — complete the form to save.`);
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!canManage) {
            return;
        }
        clearErrors(form);

        const employeeId = employeeSearch?.getValue() || form.querySelector('[data-employee-id]')?.value || '';
        if (!employeeId) {
            showErrors(form, { employee_id: ['Please select an employee.'] });
            toastError('Please select an employee.');
            return;
        }

        const submit = form.querySelector('[type="submit"]');
        const payload = new FormData();
        payload.append('title', form.title.value.trim());
        payload.append('category', form.category.value);
        payload.append('employee_id', employeeId);
        if (form.issued_at.value) {
            payload.append('issued_at', form.issued_at.value);
        }
        if (form.expires_at.value) {
            payload.append('expires_at', form.expires_at.value);
        }
        payload.append('notes', form.notes.value.trim());
        if (form.file.files?.[0]) {
            payload.append('file', form.file.files[0]);
        }

        setButtonLoading(submit, true, 'Saving…');
        try {
            const { data } = editingId
                ? await http.post(`/documents/${editingId}`, payload)
                : await http.post('/documents', payload);

            toastSuccess(data.message || 'Document saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save document');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    table.reload(true);
}
