import { escapeHtml } from '../../utils/server-table';

export const CATEGORY_LABELS = {
    contract: 'Contract',
    government_id: 'Government ID',
    certificate: 'Certificate',
    clearance: 'Clearance',
    policy: 'Policy',
    other: 'Other',
};

export function fileTypeMeta(row) {
    const kind = row.file_kind || 'file';
    if (kind === 'image') {
        return { icon: 'ph-file-image', tone: 'bg-success-soft text-success', label: 'Image' };
    }
    if (kind === 'pdf') {
        return { icon: 'ph-file-pdf', tone: 'bg-danger-soft text-danger', label: 'PDF' };
    }
    if (kind === 'word') {
        return { icon: 'ph-file-doc', tone: 'bg-primary-soft text-primary', label: 'Word' };
    }
    return { icon: 'ph-file', tone: 'bg-subtle text-muted', label: 'File' };
}

export function fileTypeIcon(row, sizeClass = 'h-10 w-10') {
    const meta = fileTypeMeta(row);
    return `
        <span class="inline-flex ${sizeClass} items-center justify-center rounded-xl ${meta.tone} flex-shrink-0" title="${escapeHtml(meta.label)}">
            <i class="ph ${meta.icon} text-xl" aria-hidden="true"></i>
        </span>
    `;
}

export function expiryBadge(row) {
    const status = row.expiry_status || (row.is_expired ? 'expired' : row.is_expiring_soon ? 'expiring' : row.expires_at ? 'valid' : 'none');

    if (status === 'none' || !row.expires_at) {
        return '<span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg bg-subtle text-muted text-[11px] font-semibold"><span class="h-1.5 w-1.5 rounded-full bg-muted"></span>No expiry</span>';
    }
    if (status === 'expired') {
        return `<span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg bg-danger-soft text-danger text-[11px] font-semibold" title="Expired"><span class="h-1.5 w-1.5 rounded-full bg-danger"></span>Expired · ${escapeHtml(row.expires_at)}</span>`;
    }
    if (status === 'expiring') {
        return `<span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg bg-warning-soft text-heading text-[11px] font-semibold" title="Expiring within 30 days"><span class="h-1.5 w-1.5 rounded-full bg-warning"></span>Soon · ${escapeHtml(row.expires_at)}</span>`;
    }
    return `<span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg bg-success-soft text-success text-[11px] font-semibold" title="Valid"><span class="h-1.5 w-1.5 rounded-full bg-success"></span>Valid · ${escapeHtml(row.expires_at)}</span>`;
}

export function accessBadge(row) {
    const access = row.access || { label: 'HR staff', icon: 'lock' };
    const icon = access.icon === 'shield' ? 'ph-shield-check' : 'ph-lock-key';
    const tone = access.level === 'restricted'
        ? 'bg-warning-soft text-heading border-warning/30'
        : 'bg-subtle text-muted border-border';

    return `
        <span
            class="inline-flex items-center gap-1 h-6 px-2 rounded-md border ${tone} text-[10px] font-semibold"
            title="${escapeHtml(access.label || 'Private')}${access.shared_with_employee ? '' : ' · not shared with employee'}"
        >
            <i class="ph ${icon} text-xs" aria-hidden="true"></i>
            <span class="hidden sm:inline">${escapeHtml(access.label || 'Private')}</span>
        </span>
    `;
}

export function thumbnailMarkup(row, { tall = false } = {}) {
    const height = tall ? 'h-36' : 'h-11 w-11';
    if (row.file_kind === 'image' && row.preview_url) {
        return `
            <span class="relative ${tall ? 'block w-full' : 'inline-flex'} ${height} rounded-xl overflow-hidden border border-border bg-subtle flex-shrink-0">
                <img src="${escapeHtml(row.preview_url)}" alt="" class="h-full w-full object-cover" loading="lazy" />
            </span>
        `;
    }
    if (tall) {
        const meta = fileTypeMeta(row);
        return `
            <div class="flex h-36 w-full items-center justify-center rounded-xl border border-border bg-subtle ${meta.tone}">
                <i class="ph ${meta.icon} text-5xl opacity-90" aria-hidden="true"></i>
            </div>
        `;
    }
    return fileTypeIcon(row);
}
