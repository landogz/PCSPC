/**
 * Docs module — Markdown + Mermaid rendering for /docs/*
 * Folder: resources/js/modules/docs
 */
import { marked } from 'marked';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function brandPrimary() {
    return getComputedStyle(document.documentElement)
        .getPropertyValue('--color-primary')
        .trim() || '#d31219';
}

function configureMarked() {
    marked.use({
        gfm: true,
        breaks: false,
        renderer: {
            code({ text, lang }) {
                const language = (lang || '').trim().toLowerCase();

                if (language === 'mermaid') {
                    return [
                        '<div class="docs-mermaid my-5 rounded-2xl border border-border bg-subtle/60 p-4 md:p-6 overflow-x-auto">',
                        `<pre class="mermaid">${escapeHtml(text.trim())}</pre>`,
                        '</div>',
                    ].join('');
                }

                return [
                    '<pre class="docs-code my-4 overflow-x-auto rounded-xl border border-border bg-subtle p-4 text-xs sm:text-sm text-text">',
                    `<code>${escapeHtml(text)}</code>`,
                    '</pre>',
                ].join('');
            },
        },
    });
}

async function renderMermaid(root) {
    const nodes = [...root.querySelectorAll('pre.mermaid')];
    if (!nodes.length) {
        return;
    }

    nodes.forEach((node) => {
        node.setAttribute('aria-busy', 'true');
    });

    const { default: mermaid } = await import('mermaid');
    const primary = brandPrimary();
    const isDark = document.documentElement.classList.contains('dark');

    mermaid.initialize({
        startOnLoad: false,
        securityLevel: 'loose',
        theme: isDark ? 'dark' : 'base',
        themeVariables: {
            primaryColor: '#fde8e9',
            primaryTextColor: '#0e1218',
            primaryBorderColor: primary,
            lineColor: isDark ? '#94a3b8' : '#404a60',
            secondaryColor: isDark ? '#1a2433' : '#ffffff',
            tertiaryColor: isDark ? '#0e1521' : '#f7f8fa',
            background: isDark ? '#0e1521' : '#f7f8fa',
            mainBkg: isDark ? '#1a2433' : '#ffffff',
            secondBkg: '#fde8e9',
            nodeBorder: primary,
            clusterBkg: isDark ? '#1a2433' : '#fde8e9',
            titleColor: isDark ? '#f8fafc' : '#0e1218',
            edgeLabelBackground: isDark ? '#1a2433' : '#ffffff',
            fontFamily: 'ui-sans-serif, system-ui, sans-serif',
            fontSize: '17px',
        },
        flowchart: {
            htmlLabels: true,
            curve: 'basis',
            padding: 24,
            nodeSpacing: 48,
            rankSpacing: 56,
            wrappingWidth: 220,
            useMaxWidth: false,
        },
    });

    await mermaid.run({ nodes });

    nodes.forEach((node) => {
        node.removeAttribute('aria-busy');
        const svg = node.querySelector('svg');
        if (!svg) {
            return;
        }

        const viewBox = svg.viewBox?.baseVal;
        const naturalWidth = viewBox?.width || 320;
        const naturalHeight = viewBox?.height || 320;
        const isTall = naturalHeight > naturalWidth * 1.15;
        const containerWidth = node.closest('.docs-mermaid')?.clientWidth || 1100;

        svg.removeAttribute('height');
        svg.style.display = 'block';
        svg.style.height = 'auto';
        svg.style.margin = '0 auto';

        if (isTall) {
            const widthPx = Math.round(Math.min(Math.max(naturalWidth * 1.6, 520), 680));
            svg.setAttribute('width', String(widthPx));
            svg.style.width = `${widthPx}px`;
            svg.style.maxWidth = '100%';
        } else {
            // Fill content width so horizontal/two-row charts read large.
            const widthPx = Math.round(Math.max(containerWidth - 16, Math.min(naturalWidth * 1.05, containerWidth)));
            svg.setAttribute('width', String(widthPx));
            svg.style.width = '100%';
            svg.style.maxWidth = `${Math.max(widthPx, 960)}px`;
        }
    });
}

export function initDocsModule() {
    const mount = document.getElementById('docs-content');
    const source = document.getElementById('docs-source');

    if (!mount || !source) {
        return;
    }

    configureMarked();

    const markdown = JSON.parse(source.textContent || '""');
    const html = marked.parse(markdown || '');

    mount.innerHTML = [
        '<div class="docs-render-pending flex items-center gap-2 text-sm text-muted py-10 justify-center">',
        '<i class="ph ph-circle-notch animate-spin text-lg"></i>',
        'Rendering diagrams…',
        '</div>',
        `<div class="docs-render-body opacity-0">${html}</div>`,
    ].join('');

    const body = mount.querySelector('.docs-render-body');
    const pending = mount.querySelector('.docs-render-pending');

    renderMermaid(body)
        .catch((error) => {
            console.error('Mermaid render failed', error);
            body.querySelectorAll('pre.mermaid').forEach((node) => {
                if (node.getAttribute('data-processed') === 'true') {
                    return;
                }
                node.classList.add('text-danger', 'text-xs', 'whitespace-pre-wrap');
                node.textContent = `Mermaid error: ${error?.message || 'Unable to render diagram'}\n\n${node.textContent}`;
            });
        })
        .finally(() => {
            pending?.remove();
            body?.classList.remove('opacity-0');
        });
}
