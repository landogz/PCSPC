import http from '../utils/http';
import { toastSuccess, toastError, confirmAction } from '../utils/toast';
import { fillSidebarUser } from './layout';

function setLoading(button, loading, label = null) {
    if (!button) {
        return;
    }

    button.disabled = loading;
    if (label) {
        button.dataset.label ??= button.textContent.trim();
        button.textContent = loading ? label : button.dataset.label;
    }
}

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

async function ensureCsrfCookie() {
    await http.get('/sanctum/csrf-cookie', {
        baseURL: '',
    });
}

function redirectToDashboard() {
    window.location.href = '/dashboard';
}

function initLoginPage() {
    const form = document.getElementById('login-form');
    if (!form) {
        return;
    }

    const loginStep = document.getElementById('login-step');
    const mfaStep = document.getElementById('mfa-step');
    const loginSubmit = document.getElementById('login-submit');
    const mfaSubmit = document.getElementById('mfa-submit');
    const mfaBack = document.getElementById('mfa-back');

    let mfaToken = null;

    const showLoginStep = () => {
        mfaToken = null;
        loginStep.classList.remove('hidden');
        mfaStep.classList.add('hidden');
        clearErrors(form);
    };

    const showMfaStep = (token) => {
        mfaToken = token;
        loginStep.classList.add('hidden');
        mfaStep.classList.remove('hidden');
        clearErrors(form);
        document.getElementById('otp')?.focus();
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        await performLogin({
            login: form.login.value.trim(),
            password: form.password.value,
            autoMfa: false,
            triggerButton: loginSubmit,
        });
    });

    async function performLogin({ login, password, autoMfa = false, triggerButton = loginSubmit }) {
        clearErrors(form);
        setLoading(triggerButton, true, 'Signing in…');
        if (triggerButton !== loginSubmit) {
            setLoading(loginSubmit, true, 'Signing in…');
        }

        try {
            await ensureCsrfCookie();

            const { data } = await http.post('/auth/login', { login, password });

            if (data?.data?.mfa_required) {
                showMfaStep(data.data.mfa_token);
                toastSuccess(data.message || 'MFA required');

                if (data.data.debug_otp) {
                    console.info('[PCSPC MFA debug OTP]', data.data.debug_otp);
                    form.otp.value = data.data.debug_otp;

                    if (autoMfa) {
                        await verifyMfaCode(data.data.mfa_token, data.data.debug_otp, mfaSubmit);
                    }
                }

                return;
            }

            toastSuccess(data.message || 'Login successful');
            redirectToDashboard();
        } catch (error) {
            const response = error.response?.data;
            const status = error.response?.status;
            showErrors(form, response?.errors || {});

            if (status === 429) {
                toastError(response?.message || 'Too many attempts. Please wait a minute and try again.');
            } else {
                toastError(response?.message || 'Unable to sign in');
            }
        } finally {
            setLoading(triggerButton, false);
            if (triggerButton !== loginSubmit) {
                setLoading(loginSubmit, false);
            }
        }
    }

    async function verifyMfaCode(token, otp, triggerButton = mfaSubmit) {
        clearErrors(form);
        setLoading(triggerButton, true, 'Verifying…');

        try {
            await ensureCsrfCookie();

            const { data } = await http.post('/auth/mfa/verify', {
                mfa_token: token,
                otp: String(otp).trim(),
            });

            toastSuccess(data.message || 'Login successful');
            redirectToDashboard();
        } catch (error) {
            const response = error.response?.data;
            const status = error.response?.status;
            showErrors(form, response?.errors || {});

            if (status === 429) {
                toastError(response?.message || 'Too many attempts. Please wait a minute and try again.');
            } else {
                toastError(response?.message || 'Invalid authentication code');
            }
        } finally {
            setLoading(triggerButton, false);
        }
    }

    mfaSubmit?.addEventListener('click', async () => {
        await verifyMfaCode(mfaToken, form.otp.value, mfaSubmit);
    });

    mfaBack?.addEventListener('click', showLoginStep);

    document.querySelectorAll('[data-auto-login]').forEach((button) => {
        button.addEventListener('click', async () => {
            const login = button.getAttribute('data-login') || '';
            const password = button.getAttribute('data-password') || '';
            const autoMfa = button.getAttribute('data-auto-mfa') === '1';

            form.login.value = login;
            form.password.value = password;
            showLoginStep();

            await performLogin({
                login,
                password,
                autoMfa,
                triggerButton: button,
            });
        });
    });
}

async function initDashboardPage() {
    const nameEl = document.getElementById('dash-name');
    const needsProfile = Boolean(nameEl || document.getElementById('sidebar') || document.getElementById('topbar-user-name'));

    if (!needsProfile) {
        return;
    }

    try {
        const { data } = await http.get('/auth/me');
        const user = data?.data?.user;
        if (nameEl) {
            nameEl.textContent = user?.name ?? '—';
        }
        const emailEl = document.getElementById('dash-email');
        if (emailEl) {
            emailEl.textContent = user?.email ?? '—';
        }
        const rolesEl = document.getElementById('dash-roles');
        if (rolesEl) {
            rolesEl.textContent = (user?.roles || []).join(', ') || '—';
        }
        fillSidebarUser(user);
    } catch {
        if (nameEl || document.getElementById('sidebar')) {
            toastError('Session expired. Please sign in again.');
            window.location.assign('/login');
        }
    }
}

function initLogoutControls() {
    const logoutBtn = document.getElementById('logout-btn');

    logoutBtn?.addEventListener('click', async () => {
        const result = await confirmAction({
            title: 'Sign out?',
            text: 'You will need to sign in again to continue.',
            confirmButtonText: 'Sign out',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await http.post('/auth/logout');
            toastSuccess('Signed out');
            window.location.assign('/login');
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to sign out');
        }
    });

    const logoutOthersHandler = async () => {
        const result = await confirmAction({
            title: 'Logout other devices?',
            text: 'Active tokens on other devices will be revoked.',
            confirmButtonText: 'Logout others',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            const { data } = await http.post('/auth/logout-others');
            toastSuccess(data.message || 'Other devices logged out');
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to logout other devices');
        }
    };

    document.querySelectorAll('#logout-others-btn, #logout-others-btn-main').forEach((btn) => {
        btn.addEventListener('click', logoutOthersHandler);
    });
}

export function initAuthModule() {
    initLoginPage();
    initDashboardPage();
    initLogoutControls();
}
