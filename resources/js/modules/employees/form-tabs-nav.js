/**
 * Horizontal scroll controls for employee form section tabs.
 * Folder: resources/js/modules/employees
 */

function canScroll(el) {
    return el.scrollWidth > el.clientWidth + 2;
}

function updateTabScrollButtons(nav, prevBtn, nextBtn, wrap) {
    if (!nav || !prevBtn || !nextBtn) {
        return;
    }

    const overflow = canScroll(nav);
    const maxScroll = Math.max(0, nav.scrollWidth - nav.clientWidth);
    const left = nav.scrollLeft;
    const atStart = left <= 2;
    const atEnd = left >= maxScroll - 2;

    wrap?.classList.toggle('has-tab-overflow', overflow);
    wrap?.classList.toggle('can-scroll-prev', overflow && !atStart);
    wrap?.classList.toggle('can-scroll-next', overflow && !atEnd);

    prevBtn.disabled = !overflow || atStart;
    nextBtn.disabled = !overflow || atEnd;
    prevBtn.setAttribute('aria-disabled', prevBtn.disabled ? 'true' : 'false');
    nextBtn.setAttribute('aria-disabled', nextBtn.disabled ? 'true' : 'false');
}

function scrollTabsBy(nav, direction) {
    const amount = Math.max(140, Math.round(nav.clientWidth * 0.7));
    nav.scrollBy({ left: direction * amount, behavior: 'smooth' });
}

export function initEmployeeFormTabsNav(root) {
    const wrap = root.querySelector('[data-employee-tabs-wrap]');
    const nav = root.querySelector('[data-employee-tabs]');
    const prevBtn = root.querySelector('[data-tabs-prev]');
    const nextBtn = root.querySelector('[data-tabs-next]');

    if (!nav || !prevBtn || !nextBtn) {
        return { refresh: () => {} };
    }

    const refresh = () => updateTabScrollButtons(nav, prevBtn, nextBtn, wrap);

    prevBtn.addEventListener('click', () => scrollTabsBy(nav, -1));
    nextBtn.addEventListener('click', () => scrollTabsBy(nav, 1));
    nav.addEventListener('scroll', refresh, { passive: true });
    window.addEventListener('resize', refresh);

    requestAnimationFrame(refresh);

    return { refresh };
}
