import { escapeHtml } from '../../utils/server-table';
import { initNestedEmployeeRecords } from './nested-records';

const LEVEL_LABELS = {
    elementary: 'Elementary',
    high_school: 'High school',
    vocational: 'Vocational',
    associate: 'Associate',
    bachelor: "Bachelor's",
    master: "Master's",
    doctorate: 'Doctorate',
    other: 'Other',
};

function levelLabel(value) {
    return LEVEL_LABELS[value] || String(value || '—').replaceAll('_', ' ');
}

function populateLevels(form, levels, levelOptions = null) {
    const select = form.elements.namedItem('level');
    if (!select) {
        return;
    }

    const options = Array.isArray(levelOptions) && levelOptions.length
        ? levelOptions.map((item) => ({
            code: item.code || item.value || item,
            label: item.label || levelLabel(item.code || item.value || item),
        }))
        : (Array.isArray(levels) ? levels : []).map((value) => ({
            code: value,
            label: levelLabel(value),
        }));

    if (!options.length) {
        return;
    }

    const current = select.value;
    select.innerHTML = options.map((item) => (
        `<option value="${escapeHtml(item.code)}">${escapeHtml(item.label)}</option>`
    )).join('');
    if (options.some((item) => item.code === current)) {
        select.value = current;
    }
}

export function initEmployeeEducations(options) {
    return initNestedEmployeeRecords({
        ...options,
        listSelector: '[data-educations-list]',
        emptySelector: '[data-educations-empty]',
        lockedSelector: '[data-educations-locked]',
        addSelector: '[data-education-add]',
        modalId: 'education-modal',
        formId: 'education-form',
        collectionPath: 'educations',
        itemKey: 'education',
        createTitle: 'Add education',
        editTitle: 'Edit education',
        deleteTitle: 'Remove education?',
        deleteText: (row) => `Remove ${row.institution} from this employee record?`,
        afterLoad: (meta, form) => populateLevels(form, meta.levels || [], meta.level_options || null),
        resetExtras: (form) => {
            if (form.is_highest) {
                form.is_highest.checked = false;
            }
        },
        buildPayload: (form) => ({
            institution: form.institution.value.trim(),
            level: form.level.value,
            degree_or_course: form.degree_or_course.value.trim() || null,
            year_started: form.year_started.value ? Number(form.year_started.value) : null,
            year_ended: form.year_ended.value ? Number(form.year_ended.value) : null,
            is_highest: Boolean(form.is_highest.checked),
            honors: form.honors.value.trim() || null,
            notes: form.notes.value.trim() || null,
        }),
        fillForm: (form, row) => {
            form.institution.value = row.institution || '';
            form.level.value = row.level || 'bachelor';
            form.degree_or_course.value = row.degree_or_course || '';
            form.year_started.value = row.year_started ?? '';
            form.year_ended.value = row.year_ended ?? '';
            form.honors.value = row.honors || '';
            form.notes.value = row.notes || '';
            form.is_highest.checked = Boolean(row.is_highest);
        },
        renderItem: (row, canManage) => {
            const years = [row.year_started, row.year_ended].filter((v) => v != null).join(' – ') || 'Years not set';
            const badge = row.is_highest
                ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-primary-soft text-primary text-[11px] font-semibold">Highest</span>'
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
                            <p class="font-semibold text-heading truncate">${escapeHtml(row.institution)}</p>
                            <p class="text-sm text-text-secondary">${escapeHtml(levelLabel(row.level))}${row.degree_or_course ? ` · ${escapeHtml(row.degree_or_course)}` : ''}</p>
                            <p class="text-xs text-muted">${escapeHtml(years)}${row.honors ? ` · ${escapeHtml(row.honors)}` : ''}</p>
                            ${badge ? `<div class="pt-1">${badge}</div>` : ''}
                        </div>
                        ${actions}
                    </div>
                </article>
            `;
        },
    });
}
