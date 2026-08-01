/**
 * Administration module (SPA) — system parameters (ADM-010).
 */
import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { confirmAction, toastError, toastSuccess } from '../../utils/toast';

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

function fillSelect(select, values, current) {
    if (!select || !Array.isArray(values)) {
        return;
    }
    select.innerHTML = values.map((value) => (
        `<option value="${String(value).replaceAll('"', '&quot;')}">${String(value)}</option>`
    )).join('');
    if (current && values.includes(current)) {
        select.value = current;
    }
}

function cacheBust(url) {
    if (!url) {
        return url;
    }
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}v=${Date.now()}`;
}

function applyBrandLogos(url) {
    if (!url) {
        return;
    }
    const busted = cacheBust(url);
    document.querySelectorAll('[data-brand-logo]').forEach((img) => {
        img.src = busted;
    });
}

function fillLogoUi(panel, parameters = {}) {
    const preview = panel.querySelector('[data-logo-preview]');
    const removeBtn = panel.querySelector('[data-logo-remove]');
    const url = parameters.logo_url || '';

    if (preview && url) {
        preview.src = cacheBust(url);
    }

    if (removeBtn) {
        const showReset = Boolean(parameters.has_custom_logo);
        removeBtn.classList.toggle('hidden', !showReset);
        removeBtn.classList.toggle('inline-flex', showReset);
    }
}

function fillParametersForm(form, parameters, meta = {}, panel = null) {
    form.company_name.value = parameters.company_name || '';
    form.company_short_name.value = parameters.company_short_name || '';
    form.currency_code.value = parameters.currency_code || 'PHP';
    form.support_email.value = parameters.support_email || '';
    form.leave_year_start_month.value = String(parameters.leave_year_start_month ?? 1);
    form.rest_day_holiday_paid_hours.value = parameters.rest_day_holiday_paid_hours ?? 8;
    form.default_grace_minutes.value = parameters.default_grace_minutes ?? 0;
    fillSelect(form.timezone, meta.timezones || [], parameters.timezone);
    fillSelect(form.date_format, meta.date_formats || [], parameters.date_format);
    if (form.week_start && parameters.week_start) {
        form.week_start.value = parameters.week_start;
    }
    if (panel) {
        fillLogoUi(panel, parameters);
    }
}

function readParametersForm(form) {
    return {
        company_name: form.company_name.value.trim(),
        company_short_name: form.company_short_name.value.trim(),
        timezone: form.timezone.value,
        date_format: form.date_format.value,
        currency_code: form.currency_code.value.trim().toUpperCase(),
        support_email: form.support_email.value.trim(),
        leave_year_start_month: Number(form.leave_year_start_month.value),
        rest_day_holiday_paid_hours: Number(form.rest_day_holiday_paid_hours.value),
        default_grace_minutes: Number(form.default_grace_minutes.value),
        week_start: form.week_start.value,
    };
}

function initSystemParameters(root) {
    const panel = root.querySelector('[data-spa-module="system-parameters"]');
    const form = document.getElementById('system-parameters-form');
    const saveBtn = panel?.querySelector('[data-save-parameters]');
    const logoInput = panel?.querySelector('[data-logo-input]');
    const uploadBtn = panel?.querySelector('[data-logo-upload]');
    const removeBtn = panel?.querySelector('[data-logo-remove]');
    if (!panel || !form || !saveBtn) {
        return;
    }

    (async () => {
        try {
            const { data } = await http.get('/administration/system-parameters');
            const parameters = data?.data?.parameters || {};
            fillParametersForm(form, parameters, data?.data?.meta || {}, panel);
            if (parameters.logo_url) {
                applyBrandLogos(parameters.logo_url);
            }
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load system parameters');
        }
    })();

    saveBtn.addEventListener('click', async () => {
        clearErrors(form);
        setButtonLoading(saveBtn, true, 'Saving…');

        try {
            const { data } = await http.put('/administration/system-parameters', readParametersForm(form));
            fillParametersForm(form, data?.data?.parameters || {}, data?.data?.meta || {}, panel);
            toastSuccess(data.message || 'System parameters updated');
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save system parameters');
        } finally {
            setButtonLoading(saveBtn, false);
        }
    });

    uploadBtn?.addEventListener('click', () => logoInput?.click());

    logoInput?.addEventListener('change', async () => {
        const file = logoInput.files?.[0];
        if (!file) {
            return;
        }

        clearErrors(form);
        setButtonLoading(uploadBtn, true, 'Uploading…');

        const body = new FormData();
        body.append('logo', file);

        try {
            const { data } = await http.post('/administration/system-parameters/logo', body);
            const parameters = data?.data?.parameters || {};
            fillParametersForm(form, parameters, data?.data?.meta || {}, panel);
            applyBrandLogos(parameters.logo_url);
            toastSuccess(data.message || 'Company logo updated');
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to upload logo');
        } finally {
            logoInput.value = '';
            setButtonLoading(uploadBtn, false);
        }
    });

    removeBtn?.addEventListener('click', async () => {
        const confirmed = await confirmAction({
            title: 'Reset company logo?',
            text: 'This restores the default PCSPC brand logo.',
            confirmButtonText: 'Reset logo',
        });
        if (!confirmed.isConfirmed) {
            return;
        }

        clearErrors(form);
        setButtonLoading(removeBtn, true, 'Resetting…');

        try {
            const { data } = await http.delete('/administration/system-parameters/logo');
            const parameters = data?.data?.parameters || {};
            fillParametersForm(form, parameters, data?.data?.meta || {}, panel);
            applyBrandLogos(parameters.logo_url);
            toastSuccess(data.message || 'Company logo reset to default');
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to reset logo');
        } finally {
            setButtonLoading(removeBtn, false);
        }
    });
}

export function initAdministrationModule() {
    const root = document.querySelector('[data-module="administration"]');
    if (!root) {
        return;
    }

    initSystemParameters(root);
}
