import http from '../utils/http';
import { setButtonLoading } from '../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../utils/toast';
import { fillSidebarUser } from './layout';

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

function redirectAfterAuth(payload = {}) {
    if (payload.password_change_required || payload.user?.password_change_required) {
        window.location.href = '/account/password';
        return;
    }

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
        setButtonLoading(triggerButton, true, 'Signing in…');
        if (triggerButton !== loginSubmit) {
            setButtonLoading(loginSubmit, true, 'Signing in…');
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
            redirectAfterAuth(data.data || {});
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
            setButtonLoading(triggerButton, false);
            if (triggerButton !== loginSubmit) {
                setButtonLoading(loginSubmit, false);
            }
        }
    }

    async function verifyMfaCode(token, otp, triggerButton = mfaSubmit) {
        clearErrors(form);
        setButtonLoading(triggerButton, true, 'Verifying…');

        try {
            await ensureCsrfCookie();

            const { data } = await http.post('/auth/mfa/verify', {
                mfa_token: token,
                otp: String(otp).trim(),
            });

            toastSuccess(data.message || 'Login successful');
            redirectAfterAuth(data.data || {});
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
            setButtonLoading(triggerButton, false);
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
        if (data?.data?.password_change_required || user?.password_change_required) {
            window.location.assign('/account/password');
            return;
        }
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

function initChangePasswordPage() {
    const root = document.querySelector('[data-module="change-password"]');
    const form = document.getElementById('change-password-form');
    if (!root || !form) {
        return;
    }

    const submitBtn = document.getElementById('change-password-submit');
    const hintEl = root.querySelector('[data-password-hint]');
    const reasonEl = root.querySelector('[data-password-reason]');

    (async () => {
        try {
            const [{ data: meData }, { data: policyData }] = await Promise.all([
                http.get('/auth/me'),
                http.get('/auth/password/policy'),
            ]);

            if (!meData?.data?.password_change_required && !meData?.data?.user?.password_change_required) {
                window.location.assign('/dashboard');
                return;
            }

            const reason = meData?.data?.password_change_reason || meData?.data?.user?.password_change_reason;
            if (reasonEl) {
                reasonEl.textContent = reason === 'expired'
                    ? 'Your password has expired. Set a new password to continue.'
                    : 'You must set a new password before continuing.';
            }

            if (hintEl) {
                hintEl.textContent = policyData?.data?.policy?.hint
                    || meData?.data?.password_policy_hint
                    || '';
            }
        } catch {
            toastError('Session expired. Please sign in again.');
            window.location.assign('/login');
        }
    })();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);
        setButtonLoading(submitBtn, true, 'Saving…');

        try {
            await ensureCsrfCookie();
            const { data } = await http.post('/auth/password', {
                current_password: form.current_password.value,
                password: form.password.value,
                password_confirmation: form.password_confirmation.value,
            });

            toastSuccess(data.message || 'Password updated');
            window.location.assign('/dashboard');
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to update password');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });
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

        setButtonLoading(logoutBtn, true, 'Signing out…');

        try {
            await http.post('/auth/logout');
            toastSuccess('Signed out');
            window.location.assign('/login');
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to sign out');
            setButtonLoading(logoutBtn, false);
        }
    });

    const logoutOthersHandler = async (event) => {
        const button = event.currentTarget;
        const result = await confirmAction({
            title: 'Logout other devices?',
            text: 'Active tokens on other devices will be revoked.',
            confirmButtonText: 'Logout others',
        });

        if (!result.isConfirmed) {
            return;
        }

        setButtonLoading(button, true, 'Logging out…');

        try {
            const { data } = await http.post('/auth/logout-others');
            toastSuccess(data.message || 'Other devices logged out');
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to logout other devices');
        } finally {
            setButtonLoading(button, false);
        }
    };

    document.querySelectorAll('#logout-others-btn, #logout-others-btn-main').forEach((btn) => {
        btn.addEventListener('click', logoutOthersHandler);
    });
}

export function initAuthModule() {
    initLoginPage();
    initChangePasswordPage();
    initDashboardPage();
    initLogoutControls();
}
