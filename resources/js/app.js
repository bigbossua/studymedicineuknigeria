
// Mobile navigation toggle (progressive enhancement; nav works without JS via the footer links)
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');
    if (btn && panel) {
        btn.addEventListener('click', () => {
            const open = panel.toggleAttribute('hidden') === false;
            btn.setAttribute('aria-expanded', String(open));
            document.documentElement.classList.toggle('overflow-hidden', open);
            document.querySelector('[data-floating-cta]')?.classList.toggle('hidden', open);
        });
    }

    // Floating CTA hides while a form field is focused or near the footer/CTA band
    const fab = document.querySelector('[data-floating-cta]');
    if (fab) {
        const hide = (v) => fab.classList.toggle('hidden', v);
        document.addEventListener('focusin', (e) => { if (e.target.closest('form')) hide(true); });
        document.addEventListener('focusout', (e) => { if (e.target.closest('form')) hide(false); });
        const sentinels = document.querySelectorAll('footer, .cta-band');
        if ('IntersectionObserver' in window && sentinels.length) {
            let visible = 0;
            const io = new IntersectionObserver((entries) => {
                entries.forEach((en) => { visible += en.isIntersecting ? 1 : -1; });
                visible = Math.max(0, visible);
                hide(visible > 0);
            }, { rootMargin: '200px 0px' });
            sentinels.forEach((s) => io.observe(s));
        }
    }
});
