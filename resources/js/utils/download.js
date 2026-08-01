/**
 * Trigger a browser file download from a Blob/ArrayBuffer response.
 */
export function downloadBlob(data, filename, mimeType = 'application/octet-stream') {
    const blob = data instanceof Blob
        ? data
        : new Blob([data], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.rel = 'noopener';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}

/**
 * Parse Content-Disposition filename, with a safe fallback.
 */
export function filenameFromContentDisposition(header, fallback) {
    if (!header) {
        return fallback;
    }

    const utfMatch = /filename\*\s*=\s*UTF-8''([^;]+)/i.exec(header);
    if (utfMatch?.[1]) {
        try {
            return decodeURIComponent(utfMatch[1].trim().replace(/["']/g, ''));
        } catch {
            // fall through
        }
    }

    const plainMatch = /filename\s*=\s*("?)([^";]+)\1/i.exec(header);
    if (plainMatch?.[2]) {
        return plainMatch[2].trim();
    }

    return fallback;
}
