import http from '../../utils/http';
import { escapeHtml } from '../../utils/server-table';
import { accessBadge, CATEGORY_LABELS, expiryBadge } from './helpers';

export function createDocumentPreview({ root, canManage, onEdit }) {
    const modal = document.getElementById('document-preview-modal');
    if (!modal) {
        return { open: async () => {}, close: () => {} };
    }

    const titleEl = modal.querySelector('[data-preview-title]');
    const subtitleEl = modal.querySelector('[data-preview-subtitle]');
    const stage = modal.querySelector('[data-preview-stage]');
    const metaEl = modal.querySelector('[data-preview-meta]');
    const versionList = modal.querySelector('[data-preview-version-list]');
    const downloadBtn = modal.querySelector('[data-preview-download]');
    const editBtn = modal.querySelector('[data-preview-edit]');
    let current = null;

    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        if (stage) {
            stage.innerHTML = '<p class="text-sm text-muted">Loading preview…</p>';
        }
        current = null;
    }

    function openShell() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    async function open(row) {
        current = row;
        openShell();
        if (titleEl) {
            titleEl.textContent = row.title || 'Document';
        }
        if (subtitleEl) {
            subtitleEl.textContent = [
                CATEGORY_LABELS[row.category] || row.category,
                row.employee?.name,
                row.original_name,
            ].filter(Boolean).join(' · ');
        }

        if (metaEl) {
            metaEl.innerHTML = `
                <div class="rounded-xl border border-border p-3 space-y-2">
                    <div class="flex flex-wrap gap-2">${expiryBadge(row)} ${accessBadge(row)}</div>
                    <p class="text-text-secondary"><span class="text-muted">Employee:</span> ${escapeHtml(row.employee?.name || '—')}</p>
                    <p class="text-text-secondary"><span class="text-muted">Size:</span> ${escapeHtml(row.file_size_label || '—')}</p>
                    <p class="text-text-secondary"><span class="text-muted">Uploaded by:</span> ${escapeHtml(row.uploaded_by?.name || '—')}</p>
                    ${row.version_count > 0 ? `<p class="text-text-secondary"><span class="text-muted">Prior versions:</span> ${row.version_count}</p>` : ''}
                </div>
            `;
        }

        if (stage) {
            if (row.is_previewable && row.preview_url) {
                if (row.file_kind === 'image') {
                    stage.innerHTML = `<img src="${escapeHtml(row.preview_url)}" alt="${escapeHtml(row.title || '')}" class="max-h-[70vh] w-full object-contain" />`;
                } else {
                    stage.innerHTML = `<iframe title="Document preview" src="${escapeHtml(row.preview_url)}" class="h-[70vh] w-full rounded-lg bg-surface border-0"></iframe>`;
                }
            } else {
                stage.innerHTML = `
                    <div class="text-center px-6 py-10">
                        <i class="ph ph-eye-slash text-3xl text-muted"></i>
                        <p class="mt-2 text-sm font-medium text-heading">Preview not available</p>
                        <p class="text-xs text-muted mt-1">Download to open this file type.</p>
                    </div>
                `;
            }
        }

        if (versionList) {
            versionList.innerHTML = '<p class="text-xs text-muted">Loading versions…</p>';
            try {
                const { data } = await http.get(`/documents/${row.id}`);
                const doc = data?.data?.document || {};
                current = { ...row, ...doc };
                const versions = Array.isArray(doc.versions) ? doc.versions : [];
                if (!versions.length) {
                    versionList.innerHTML = '<p class="text-xs text-muted">No previous versions. Re-upload a file to start history.</p>';
                } else {
                    versionList.innerHTML = versions.map((version) => `
                        <div class="rounded-lg border border-border p-2.5">
                            <p class="text-xs font-semibold text-heading">v${version.version_number}</p>
                            <p class="text-[11px] text-muted mt-0.5 truncate">${escapeHtml(version.original_name || '')}</p>
                            <p class="text-[11px] text-muted">${escapeHtml(version.uploaded_by?.name || 'Unknown')} · ${escapeHtml(version.file_size_label || '')}</p>
                            <a href="${escapeHtml(version.download_url)}" class="inline-flex items-center gap-1 mt-2 text-xs font-medium text-primary hover:underline">
                                <i class="ph ph-download-simple"></i> Download previous
                            </a>
                        </div>
                    `).join('');
                }
            } catch {
                versionList.innerHTML = '<p class="text-xs text-danger">Unable to load version history.</p>';
            }
        }
    }

    modal.querySelectorAll('[data-preview-dismiss]').forEach((el) => {
        el.addEventListener('click', close);
    });

    downloadBtn?.addEventListener('click', () => {
        if (!current) {
            return;
        }
        const link = document.createElement('a');
        link.href = current.download_url || `/api/v1/documents/${encodeURIComponent(current.id)}/download`;
        link.setAttribute('download', current.original_name || 'document');
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

    editBtn?.addEventListener('click', () => {
        if (!current || !canManage) {
            return;
        }
        close();
        onEdit?.(current);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });

    return { open, close };
}
