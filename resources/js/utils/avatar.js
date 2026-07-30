/**
 * Shared avatar markup (photo or letter initial).
 */
export function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

export function avatarInitial(name, email = '') {
    const source = String(name || email || 'U').trim();

    return (source.charAt(0) || 'U').toUpperCase();
}

/**
 * @param {{ url?: string|null, name?: string, email?: string, sizeClass?: string, textClass?: string, imgClass?: string }} options
 */
export function avatarMarkup({
    url = null,
    name = '',
    email = '',
    sizeClass = 'w-9 h-9',
    textClass = 'text-sm',
    imgClass = '',
} = {}) {
    const initial = escapeHtml(avatarInitial(name, email));
    const safeUrl = url ? escapeHtml(url) : '';

    if (safeUrl) {
        return `
            <span class="${sizeClass} rounded-full overflow-hidden bg-subtle border border-border flex-shrink-0 inline-flex">
                <img src="${safeUrl}" alt="" class="w-full h-full object-cover ${imgClass}" loading="lazy" onerror="this.classList.add('hidden');this.nextElementSibling?.classList.remove('hidden');">
                <span class="hidden ${sizeClass} rounded-full bg-primary text-white ${textClass} font-semibold flex items-center justify-center">${initial}</span>
            </span>
        `;
    }

    return `
        <span class="${sizeClass} rounded-full bg-primary text-white ${textClass} font-semibold flex items-center justify-center flex-shrink-0">
            ${initial}
        </span>
    `;
}

/**
 * Fill layout avatar slots (sidebar / topbar). Falls back to initials.
 */
export function applyUserAvatar(user) {
    const url = user?.avatar_url || null;
    const initial = avatarInitial(user?.name, user?.email);

    document.querySelectorAll('[data-user-avatar]').forEach((slot) => {
        const img = slot.querySelector('[data-user-avatar-img]');
        const letter = slot.querySelector('[data-user-avatar-initial]');

        if (url && img) {
            img.onerror = () => {
                img.classList.add('hidden');
                letter?.classList.remove('hidden');
                slot.classList.add('bg-primary', 'text-white');
            };
            img.src = url;
            img.classList.remove('hidden');
            letter?.classList.add('hidden');
            slot.classList.add('overflow-hidden');
            slot.classList.remove('bg-primary', 'text-white');
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
    });
}
