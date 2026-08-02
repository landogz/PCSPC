import { escapeHtml } from '../../utils/server-table';

/**
 * Open a landscape printable window for schedule reports.
 *
 * @param {object} report
 */
export function openSchedulePrintWindow(report) {
    const html = buildPrintDocument(report);
    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const win = window.open(url, '_blank');

    if (!win) {
        URL.revokeObjectURL(url);
        throw new Error('Pop-up blocked. Allow pop-ups to print schedules.');
    }

    win.focus();

    const cleanup = () => {
        try {
            URL.revokeObjectURL(url);
        } catch {
            // ignore
        }
    };

    const triggerPrint = () => {
        try {
            win.print();
        } catch {
            // User can print manually from the window.
        }
    };

    // Delay print until the blob document paints; revoke URL after a short hold.
    setTimeout(triggerPrint, 400);
    setTimeout(cleanup, 60_000);
}

function buildPrintDocument(report) {
    const company = report.company || {};
    const filters = report.filters || {};
    const groups = Array.isArray(report.groups) ? report.groups : [];
    const effectiveLabel = ({
        current: 'Current',
        upcoming: 'Upcoming',
        ended: 'Ended',
        all: 'All periods',
    })[filters.effective] || 'Current';

    const scopeLabel = report.scope === 'department' ? 'Per department' : 'Per employee';
    const logo = company.logo_url
        ? `<img src="${escapeHtml(company.logo_url)}" alt="" class="logo">`
        : '';

    const body = groups.length
        ? groups.map((group) => renderGroup(group)).join('')
        : '<p class="empty">No schedules matched this print selection.</p>';

    return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>${escapeHtml(report.title || 'Shift Schedules')} — Print</title>
<style>
  @page { size: A4 landscape; margin: 12mm; }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    color: #111;
    font: 11px/1.35 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #fff;
  }
  .sheet { padding: 0; }
  .toolbar {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-bottom: 12px;
  }
  .toolbar button {
    border: 1px solid #ccc;
    background: #f5f5f5;
    border-radius: 6px;
    padding: 8px 14px;
    font-size: 12px;
    cursor: pointer;
  }
  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 2px solid #111;
    padding-bottom: 10px;
    margin-bottom: 12px;
  }
  .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
  .logo { height: 42px; width: auto; object-fit: contain; }
  .brand h1 { margin: 0; font-size: 18px; line-height: 1.2; }
  .brand p { margin: 2px 0 0; color: #555; font-size: 11px; }
  .meta { text-align: right; white-space: nowrap; color: #333; }
  .meta strong { display: block; font-size: 12px; }
  .group {
    break-inside: avoid;
    page-break-inside: avoid;
    margin-bottom: 14px;
  }
  .group-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: baseline;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-bottom: none;
    padding: 6px 10px;
  }
  .group-head h2 { margin: 0; font-size: 13px; }
  .group-head span { color: #555; font-size: 10px; }
  .section-head {
    margin: 0;
    padding: 5px 10px;
    background: #eef2ff;
    border: 1px solid #d1d5db;
    border-top: none;
    font-size: 11px;
    font-weight: 600;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  th, td {
    border: 1px solid #d1d5db;
    padding: 5px 7px;
    text-align: left;
    vertical-align: top;
    word-wrap: break-word;
  }
  th {
    background: #f9fafb;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .02em;
  }
  td { font-size: 11px; }
  .empty {
    border: 1px dashed #ccc;
    padding: 24px;
    text-align: center;
    color: #666;
  }
  .footnote {
    margin-top: 10px;
    color: #666;
    font-size: 10px;
  }
  @media print {
    .toolbar { display: none !important; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
  <div class="sheet">
    <div class="toolbar">
      <button type="button" onclick="window.print()">Print</button>
      <button type="button" onclick="window.close()">Close</button>
    </div>
    <header class="header">
      <div class="brand">
        ${logo}
        <div>
          <h1>${escapeHtml(company.name || 'PCSPC')}</h1>
          <p>${escapeHtml(report.title || 'Shift Schedules')} · Landscape</p>
        </div>
      </div>
      <div class="meta">
        <strong>${escapeHtml(scopeLabel)}</strong>
        Period: ${escapeHtml(effectiveLabel)}<br>
        Printed: ${escapeHtml(report.generated_on || '')}
      </div>
    </header>
    ${body}
    <p class="footnote">
      Employee-specific assignments override department schedules when both apply.
      Groups: ${escapeHtml(String(report.totals?.groups ?? groups.length))} ·
      Rows: ${escapeHtml(String(report.totals?.rows ?? 0))}
    </p>
  </div>
</body>
</html>`;
}

function renderGroup(group) {
    const sections = Array.isArray(group.sections) ? group.sections : [];
    const sectionHtml = sections.map((section) => `
      <div class="section-head">${escapeHtml(section.heading || '')}${section.subheading ? ` — ${escapeHtml(section.subheading)}` : ''}</div>
      ${renderTable(section.rows || [])}
    `).join('');

    return `
      <section class="group">
        <div class="group-head">
          <h2>${escapeHtml(group.heading || '—')}</h2>
          <span>${escapeHtml(group.subheading || '')}</span>
        </div>
        ${renderTable(group.rows || [])}
        ${sectionHtml}
      </section>
    `;
  }

function renderTable(rows) {
    if (!rows.length) {
        return `<table>
          <thead>${tableHead()}</thead>
          <tbody><tr><td colspan="8" style="text-align:center;color:#666;">No assignments</td></tr></tbody>
        </table>`;
    }

    const body = rows.map((row) => {
        const shift = row.shift || {};
        const effective = `${row.effective_from || '—'}${row.effective_to ? ` → ${row.effective_to}` : ' → open'}`;
        const overnight = shift.crosses_midnight ? ' (overnight)' : '';
        return `<tr>
          <td>${escapeHtml(shift.code || '—')}</td>
          <td>${escapeHtml(shift.name || '—')}</td>
          <td>${escapeHtml(shift.time_in || '—')}–${escapeHtml(shift.time_out || '—')}${escapeHtml(overnight)}</td>
          <td>${escapeHtml(String(shift.break_minutes ?? '—'))}</td>
          <td>${escapeHtml(row.days_label || 'Every day')}</td>
          <td>${escapeHtml(effective)}</td>
          <td>${escapeHtml(row.period || '—')}</td>
          <td>${escapeHtml(row.notes || '—')}</td>
        </tr>`;
    }).join('');

    return `<table>
      <thead>${tableHead()}</thead>
      <tbody>${body}</tbody>
    </table>`;
}

function tableHead() {
    return `<tr>
      <th style="width:8%">Code</th>
      <th style="width:18%">Shift</th>
      <th style="width:14%">Hours</th>
      <th style="width:8%">Break (min)</th>
      <th style="width:12%">Days</th>
      <th style="width:16%">Effective</th>
      <th style="width:8%">Period</th>
      <th style="width:16%">Notes</th>
    </tr>`;
}
