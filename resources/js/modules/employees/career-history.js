import { escapeHtml } from '../../utils/server-table';
import { initNestedEmployeeRecords } from './nested-records';

const RATE_LABELS = {
    monthly: 'Monthly',
    daily: 'Daily',
    hourly: 'Hourly',
};

function categoryLabel(value, options = []) {
    const match = options.find((item) => item.code === value);
    if (match?.label) {
        return match.label;
    }
    return String(value || '—').replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function populateSelect(select, options, fallbackCodes = []) {
    if (!select) {
        return;
    }

    const items = Array.isArray(options) && options.length
        ? options.map((item) => ({
            code: item.code || item.value || item,
            label: item.label || categoryLabel(item.code || item.value || item),
        }))
        : (Array.isArray(fallbackCodes) ? fallbackCodes : []).map((code) => ({
            code,
            label: categoryLabel(code),
        }));

    if (!items.length) {
        return;
    }

    const current = select.value;
    select.innerHTML = items.map((item) => (
        `<option value="${escapeHtml(item.code)}">${escapeHtml(item.label)}</option>`
    )).join('');
    if (items.some((item) => item.code === current)) {
        select.value = current;
    }
}

function formatSalary(row) {
    if (row.basic_salary == null || row.basic_salary === '') {
        return 'Salary not set';
    }
    const amount = Number(row.basic_salary);
    const formatted = Number.isFinite(amount)
        ? amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : String(row.basic_salary);
    const rate = RATE_LABELS[row.salary_rate_type] || row.salary_rate_type || 'Monthly';
    const currency = row.currency || 'PHP';
    return `${currency} ${formatted} · ${rate}`;
}

export function initEmployeeCareerHistory(options) {
    let categoryOptions = [];

    const api = initNestedEmployeeRecords({
        ...options,
        listSelector: '[data-career-list]',
        emptySelector: '[data-career-empty]',
        lockedSelector: '[data-career-locked]',
        addSelector: '[data-career-add]',
        modalId: 'career-history-modal',
        formId: 'career-history-form',
        collectionPath: 'career-history',
        itemKey: 'history',
        createTitle: 'Add career history',
        editTitle: 'Edit career history',
        deleteTitle: 'Remove career history?',
        deleteText: (row) => `Remove ${row.position_title} (${categoryLabel(row.employment_category, categoryOptions)})?`,
        afterLoad: (meta, form) => {
            categoryOptions = meta.category_options || [];
            populateSelect(form.elements.namedItem('employment_category'), categoryOptions, meta.categories || []);
            populateSelect(
                form.elements.namedItem('salary_rate_type'),
                (meta.rate_types || []).map((code) => ({ code, label: RATE_LABELS[code] || categoryLabel(code) })),
                meta.rate_types || Object.keys(RATE_LABELS),
            );
        },
        resetExtras: (form) => {
            if (form.is_current) {
                form.is_current.checked = false;
            }
            if (form.currency && !form.currency.value) {
                form.currency.value = 'PHP';
            }
            if (form.salary_rate_type && !form.salary_rate_type.value) {
                form.salary_rate_type.value = 'monthly';
            }
            syncCurrentCareerUi(form);
        },
        buildPayload: (form) => ({
            position_title: form.position_title.value.trim(),
            employment_category: form.employment_category.value,
            basic_salary: form.basic_salary.value.trim() === '' ? null : form.basic_salary.value.trim(),
            salary_rate_type: form.salary_rate_type.value || 'monthly',
            currency: (form.currency.value.trim() || 'PHP').toUpperCase(),
            effective_from: form.effective_from.value || null,
            effective_to: form.is_current.checked ? null : (form.effective_to.value || null),
            is_current: Boolean(form.is_current.checked),
            notes: form.notes.value.trim() || null,
        }),
        fillForm: (form, row) => {
            form.position_title.value = row.position_title || '';
            form.employment_category.value = row.employment_category || '';
            form.basic_salary.value = row.basic_salary ?? '';
            form.salary_rate_type.value = row.salary_rate_type || 'monthly';
            form.currency.value = row.currency || 'PHP';
            form.effective_from.value = row.effective_from || '';
            form.effective_to.value = row.effective_to || '';
            form.notes.value = row.notes || '';
            form.is_current.checked = Boolean(row.is_current);
            syncCurrentCareerUi(form);
        },
        renderItem: (row, canManage) => {
            const range = row.is_current
                ? `${escapeHtml(row.effective_from || '—')} – Present`
                : `${escapeHtml(row.effective_from || '—')} – ${escapeHtml(row.effective_to || '—')}`;
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
                            <p class="text-sm text-text-secondary truncate">${escapeHtml(categoryLabel(row.employment_category, categoryOptions))}</p>
                            <p class="text-xs text-muted">${escapeHtml(formatSalary(row))}</p>
                            <p class="text-xs text-muted">${range}</p>
                            ${badge ? `<div class="pt-1">${badge}</div>` : ''}
                        </div>
                        ${actions}
                    </div>
                </article>
            `;
        },
    });

    const form = document.getElementById('career-history-form');
    form?.elements?.namedItem('is_current')?.addEventListener('change', () => syncCurrentCareerUi(form));

    return {
        ...api,
        setMeta: (next) => {
            api.setMeta({
                categories: next.categories || next.employment_categories || [],
                category_options: next.category_options || next.employment_category_options || [],
                rate_types: next.rate_types || next.salary_rate_types || Object.keys(RATE_LABELS),
            });
        },
    };
}

function syncCurrentCareerUi(form) {
    const effectiveTo = form.elements.namedItem('effective_to');
    const isCurrent = Boolean(form.elements.namedItem('is_current')?.checked);
    if (!effectiveTo) {
        return;
    }
    effectiveTo.disabled = isCurrent;
    if (isCurrent) {
        effectiveTo.value = '';
    }
}
