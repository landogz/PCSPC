/**
 * Shared modal open/close helpers for <x-ui.modal>.
 */
export function openModal(modal) {
    if (!modal) {
        return;
    }
    modal.classList.remove('hidden');
    document.documentElement.classList.add('overflow-hidden');
}

export function closeModal(modal) {
    if (!modal) {
        return;
    }
    modal.classList.add('hidden');
    if (!document.querySelector('[role="dialog"]:not(.hidden)')) {
        document.documentElement.classList.remove('overflow-hidden');
    }
}
