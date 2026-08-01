import { escapeHtml } from '../../utils/server-table';
import { initNestedEmployeeRecords } from './nested-records';

export function initEmployeeEmploymentHistory(options) {
    const api = initNestedEmployeeRecords({
        ...options,
        listSelector: '[data-history-list]',
        emptySelector: '[data-history-empty]',
        lockedSelector: '[data-history-locked]',
        addSelector: '[data-history-add]',
        modalId: 'employment-history-modal',
        formId: 'employment-history-form',
        collectionPath: 'employment-history',
        itemKey: 'history',
        createTitle: 'Add employment history',
        editTitle: 'Edit employment history',
        deleteTitle: 'Remove employment history?',
        deleteText: (row) => `Remove ${row.position_title} at ${row.employer_name}?`,
        resetExtras: (form) => {
            if (form.is_current) {
                form.is_current.checked = false;
            }
            syncCurrentJobUi(form);
        },
        buildPayload: (form) => ({
            employer_name: form.employer_name.value.trim(),
            position_title: form.position_title.value.trim(),
            location: form.location.value.trim() || null,
            date_from: form.date_from.value || null,
            date_to: form.is_current.checked ? null : (form.date_to.value || null),
            is_current: Boolean(form.is_current.checked),
            notes: form.notes.value.trim() || null,
        }),
        fillForm: (form, row) => {
            form.employer_name.value = row.employer_name || '';
            form.position_title.value = row.position_title || '';
            form.location.value = row.location || '';
            form.date_from.value = row.date_from || '';
            form.date_to.value = row.date_to || '';
            form.notes.value = row.notes || '';
            form.is_current.checked = Boolean(row.is_current);
            syncCurrentJobUi(form);
        },
        renderItem: (row, canManage) => {
            const range = row.is_current
                ? `${escapeHtml(row.date_from || '—')} – Present`
                : `${escapeHtml(row.date_from || '—')} – ${escapeHtml(row.date_to || '—')}`;
            const badge = row.is_current
                ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-success-soft text-success text-[11px] font-semibold">Current</span>'
                : '';
            const actions = canManage
                ? `<div class="flex flex-wrap gap-2 justify-end">
                    <button type="button" class="inline-flex items-center gap-1 h-9 px-3 rounded-lg border border-border text-sm font-medium hover:bg-subtle" data-nested-edit="${escapeHtml(row.id)}">
                        <i class="ph ph-pencil-simple"></i> Edit
                    </button>
                    <button type="button" class="inline-flex items-center gap-1 h-9 px-3 rounded-lg border border-danger/30 text-sm font-medium text-danger hover:bg-danger-soft" data-nested-delete="${escapeHtml(row.id)}">
                        <i class="ph ph-trash"></i> Remove
                    </button>
                   </div>`
                : '';

            return `
                <article class="rounded-xl border border-border bg-surface p-3.5 sm:p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 space-y-1">
                            <p class="font-semibold text-heading truncate">${escapeHtml(row.position_title)}</p>
                            <p class="text-sm text-text-secondary truncate">${escapeHtml(row.employer_name)}${row.location ? ` · ${escapeHtml(row.location)}` : ''}</p>
                            <p class="text-xs text-muted">${range}</p>
                            ${badge ? `<div class="pt-1">${badge}</div>` : ''}
                        </div>
                        ${actions}
                    </div>
                </article>
            `;
        },
    });

    const form = document.getElementById('employment-history-form');
    form?.elements?.namedItem('is_current')?.addEventListener('change', () => syncCurrentJobUi(form));

    return api;
}

function syncCurrentJobUi(form) {
    const dateTo = form.elements.namedItem('date_to');
    const isCurrent = Boolean(form.elements.namedItem('is_current')?.checked);
    if (!dateTo) {
        return;
    }
    dateTo.disabled = isCurrent;
    if (isCurrent) {
        dateTo.value = '';
    }
}
