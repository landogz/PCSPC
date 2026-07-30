/**
 * Administration module (SPA) — password policy (ADM-005).
 */
import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastError, toastSuccess } from '../../utils/toast';

function clearErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function showErrors(form, errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error="${field}"]`);
        if (!el) {
            return;
        }
        el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        el.classList.remove('hidden');
    });
}

function fillPolicyForm(form, policy) {
    form.min_length.value = policy.min_length ?? 8;
    form.expire_days.value = policy.expire_days ?? 90;
    form.history_count.value = policy.history_count ?? 5;
    form.require_mixed_case.checked = Boolean(policy.require_mixed_case);
    form.require_numbers.checked = Boolean(policy.require_numbers);
    form.require_symbols.checked = Boolean(policy.require_symbols);
    form.uncompromised.checked = Boolean(policy.uncompromised);
    form.force_change_temporary.checked = Boolean(policy.force_change_temporary);

    const hint = form.closest('[data-spa-module="password-policy"]')?.querySelector('[data-policy-hint]');
    if (hint) {
        hint.textContent = policy.hint || '';
    }
}

function readPolicyForm(form) {
    return {
        min_length: Number(form.min_length.value),
        expire_days: Number(form.expire_days.value),
        history_count: Number(form.history_count.value),
        require_mixed_case: form.require_mixed_case.checked,
        require_numbers: form.require_numbers.checked,
        require_symbols: form.require_symbols.checked,
        uncompromised: form.uncompromised.checked,
        force_change_temporary: form.force_change_temporary.checked,
    };
}

export function initAdministrationModule() {
    const root = document.querySelector('[data-module="administration"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="password-policy"]');
    const form = document.getElementById('password-policy-form');
    const saveBtn = panel?.querySelector('[data-save-policy]');
    if (!panel || !form || !saveBtn) {
        return;
    }

    (async () => {
        try {
            const { data } = await http.get('/administration/password-policy');
            fillPolicyForm(form, data?.data?.policy || {});
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load password policy');
        }
    })();

    saveBtn.addEventListener('click', async () => {
        clearErrors(form);
        setButtonLoading(saveBtn, true, 'Saving…');

        try {
            const { data } = await http.put('/administration/password-policy', readPolicyForm(form));
            fillPolicyForm(form, data?.data?.policy || {});
            toastSuccess(data.message || 'Password policy updated');
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save password policy');
        } finally {
            setButtonLoading(saveBtn, false);
        }
    });
}
