/**
 * Show/hide toggles for password fields (eye / eye-slash).
 * Markup: wrap input + button in [data-password-field], button has [data-password-toggle]
 * and aria-controls pointing at the input id.
 */
export function initPasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        if (button.dataset.passwordToggleBound === '1') {
            return;
        }

        button.dataset.passwordToggleBound = '1';

        button.addEventListener('click', () => {
            const targetId = button.getAttribute('aria-controls');
            const input = (targetId && document.getElementById(targetId))
                || button.closest('[data-password-field]')?.querySelector('input[type="password"], input[type="text"]');
            const icon = button.querySelector('i');

            if (!input || !icon) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            icon.className = show ? 'ph ph-eye-slash text-lg' : 'ph ph-eye text-lg';
        });
    });
}
