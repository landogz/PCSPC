/**
 * Disable a button and swap its label while an async action runs.
 * Always restores the original HTML (including icons) when loading ends —
 * even if the request failed.
 *
 * @param {HTMLButtonElement|HTMLElement|null|undefined} button
 * @param {boolean} loading
 * @param {string} [loadingLabel='Please wait…']
 */
export function setButtonLoading(button, loading, loadingLabel = 'Please wait…') {
    if (!button) {
        return;
    }

    if (loading) {
        if (button.dataset.originalHtml === undefined) {
            button.dataset.originalHtml = button.innerHTML;
        }
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = loadingLabel;
        return;
    }

    button.disabled = false;
    button.removeAttribute('aria-busy');

    if (button.dataset.originalHtml !== undefined) {
        button.innerHTML = button.dataset.originalHtml;
    }
}
