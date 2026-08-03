import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError } from '../../utils/toast';
import { openModal, closeModal } from '../../utils/modal';
import { applyUserAvatar, avatarInitial } from '../../utils/avatar';
import { fillSidebarUser } from '../layout';
import { initPasswordToggles } from '../../utils/password-toggle';

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

function applyProfilePreview(user) {
    const slot = document.querySelector('[data-profile-avatar]');
    if (!slot) {
        return;
    }

    const img = slot.querySelector('[data-profile-avatar-img]');
    const letter = slot.querySelector('[data-profile-avatar-initial]');
    const removeBtn = document.querySelector('[data-profile-photo-remove]');
    const url = user?.avatar_url || null;
    const initial = avatarInitial(user?.name, user?.email);

    if (url && img) {
        img.onerror = () => {
            img.classList.add('hidden');
            letter?.classList.remove('hidden');
            slot.classList.add('bg-primary', 'text-white');
            removeBtn?.classList.add('hidden');
        };
        img.src = url;
        img.classList.remove('hidden');
        letter?.classList.add('hidden');
        slot.classList.remove('bg-primary', 'text-white');
        removeBtn?.classList.remove('hidden');
        return;
    }

    if (img) {
        img.removeAttribute('src');
        img.classList.add('hidden');
    }
    if (letter) {
        letter.textContent = initial;
        letter.classList.remove('hidden');
    }
    slot.classList.add('bg-primary', 'text-white');
    removeBtn?.classList.add('hidden');
}

function closeUserMenu() {
    const btn = document.getElementById('user-menu-btn');
    const menu = document.getElementById('user-menu');
    if (!btn || !menu) {
        return;
    }
    menu.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
    btn.setAttribute('aria-expanded', 'false');
}

function initEditProfileModal() {
    const modal = document.getElementById('edit-profile-modal');
    const form = document.getElementById('edit-profile-form');
    const openBtn = document.getElementById('edit-profile-btn');
    if (!modal || !form || !openBtn) {
        return;
    }

    const nameInput = document.getElementById('profile-name');
    const emailInput = document.getElementById('profile-email');
    const submitBtn = document.getElementById('edit-profile-submit');
    const photoInput = document.querySelector('[data-profile-photo-input]');
    const removeBtn = document.querySelector('[data-profile-photo-remove]');

    let currentUser = null;

    const loadProfile = async () => {
        const { data } = await http.get('/auth/profile');
        currentUser = data?.data?.user || null;
        if (!currentUser) {
            throw new Error('Profile unavailable');
        }
        if (nameInput) {
            nameInput.value = currentUser.name || '';
        }
        if (emailInput) {
            emailInput.value = currentUser.email || '';
        }
        if (photoInput) {
            photoInput.value = '';
        }
        applyProfilePreview(currentUser);
        clearErrors(form);
    };

    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    openBtn.addEventListener('click', async () => {
        closeUserMenu();
        try {
            await loadProfile();
            openModal(modal);
            nameInput?.focus();
        } catch (error) {
            toastError(error?.response?.data?.message || 'Unable to load profile.');
        }
    });

    photoInput?.addEventListener('change', async () => {
        const file = photoInput.files?.[0];
        if (!file) {
            return;
        }

        clearErrors(form);
        const body = new FormData();
        body.append('photo', file);

        try {
            const { data } = await http.post('/auth/profile/avatar', body, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            currentUser = data?.data?.user || currentUser;
            applyProfilePreview(currentUser);
            fillSidebarUser(currentUser);
            applyUserAvatar(currentUser);
            toastSuccess(data?.message || 'Profile photo updated.');
            photoInput.value = '';
        } catch (error) {
            const response = error?.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to upload photo.');
            photoInput.value = '';
        }
    });

    removeBtn?.addEventListener('click', async () => {
        clearErrors(form);
        setButtonLoading(removeBtn, true, 'Removing…');
        try {
            const { data } = await http.delete('/auth/profile/avatar');
            currentUser = data?.data?.user || currentUser;
            applyProfilePreview(currentUser);
            fillSidebarUser(currentUser);
            applyUserAvatar(currentUser);
            toastSuccess(data?.message || 'Profile photo removed.');
        } catch (error) {
            toastError(error?.response?.data?.message || 'Unable to remove photo.');
        } finally {
            setButtonLoading(removeBtn, false);
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);
        setButtonLoading(submitBtn, true, 'Saving…');

        try {
            const { data } = await http.put('/auth/profile', {
                name: nameInput?.value?.trim() || '',
            });
            currentUser = data?.data?.user || currentUser;
            fillSidebarUser(currentUser);
            applyUserAvatar(currentUser);
            applyProfilePreview(currentUser);
            toastSuccess(data?.message || 'Profile updated successfully.');
            closeModal(modal);
        } catch (error) {
            const response = error?.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to update profile.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });
}

function initChangePasswordModal() {
    const modal = document.getElementById('change-password-modal');
    const form = document.getElementById('change-password-modal-form');
    const openBtn = document.getElementById('change-password-btn');
    if (!modal || !form || !openBtn) {
        return;
    }

    const submitBtn = document.getElementById('change-password-modal-submit');
    const hintEl = modal.querySelector('[data-password-modal-hint]');

    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    openBtn.addEventListener('click', async () => {
        closeUserMenu();
        clearErrors(form);
        form.reset();
        initPasswordToggles(modal);

        try {
            const { data } = await http.get('/auth/password/policy');
            const hint = data?.data?.policy?.hint;
            if (hintEl) {
                hintEl.textContent = hint || 'Use a strong unique password.';
            }
        } catch {
            if (hintEl) {
                hintEl.textContent = 'Use a strong unique password.';
            }
        }

        openModal(modal);
        document.getElementById('modal_current_password')?.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);
        setButtonLoading(submitBtn, true, 'Updating…');

        const payload = {
            current_password: form.current_password?.value || '',
            password: form.password?.value || '',
            password_confirmation: form.password_confirmation?.value || '',
        };

        try {
            const { data } = await http.post('/auth/password', payload);
            toastSuccess(data?.message || 'Password updated successfully.');
            form.reset();
            closeModal(modal);
        } catch (error) {
            const response = error?.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to update password.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });
}

export function initProfileModule() {
    initEditProfileModal();
    initChangePasswordModal();
}
