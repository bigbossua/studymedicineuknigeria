
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

// Confirmation prompts without inline handlers (CSP): <form data-confirm="Are you sure?">
document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) e.preventDefault();
});

// Consent-gated Google Analytics 4 (public site only). Nothing loads until the visitor accepts;
// the choice is kept in a first-party cookie for a year. See config/site.php ga4_id.
(() => {
    const id = document.querySelector('meta[name="ga4-id"]')?.content;
    if (!id) return;
    const nonce = document.querySelector('meta[name="csp-nonce"]')?.content || '';
    const cookie = () => document.cookie.split('; ').find((c) => c.startsWith('smukn_consent='))?.split('=')[1];
    const remember = (v) => { document.cookie = `smukn_consent=${v}; Max-Age=31536000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`; };
    const load = () => {
        if (window.__smuknGa) return;
        window.__smuknGa = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'granted' });
        window.gtag('js', new Date());
        window.gtag('config', id, { anonymize_ip: true, allow_google_signals: false });
        const s = document.createElement('script');
        s.async = true; s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`; if (nonce) s.setAttribute('nonce', nonce);
        document.head.appendChild(s);
        const pending = document.querySelector('meta[name="funnel-events"]')?.content;
        if (pending) { try { JSON.parse(pending).forEach((e) => window.gtag('event', e.name, e.params || {})); } catch (e) { /* ignore */ } }
    };
    // Public-site interaction events (sent only once gtag is loaded, i.e. after consent)
    const send = (name, params) => { if (window.__smuknGa && typeof window.gtag === 'function') window.gtag('event', name, params || {}); };
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href*="/apply-online"]');
        if (!a || !a.classList.contains('btn')) return;
        const where = a.closest('header') ? 'header' : a.closest('footer') ? 'footer' : a.closest('[data-floating-cta]') ? 'floating' : a.closest('.cta-band') ? 'cta_band' : 'content';
        send('apply_click', { location: where, page: location.pathname });
    });
    // Contact, outbound official sources and directory filtering: event names and coarse labels only, never what was typed
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const href = a.getAttribute('href');
        if (href.startsWith('mailto:')) send('contact_click', { method: 'email', page: location.pathname });
        else if (href.includes('wa.me/')) send('contact_click', { method: 'whatsapp', page: location.pathname });
        else if (a.target === '_blank' && /^https?:/.test(href) && !href.includes(location.host)) {
            try { send('official_source_click', { domain: new URL(href).hostname.replace(/^www\./, ''), page: location.pathname }); } catch (err) { /* ignore */ }
        }
    });
    const dir = document.querySelector('form[aria-label="Filter medical schools"]');
    if (dir) dir.addEventListener('submit', () => {
        const used = ['nation', 'international', 'test', 'waec'].filter((k) => dir.elements[k]?.value);
        send('directory_filter', { filters: used.join(',') || 'none', searched: dir.elements.q?.value ? 'yes' : 'no' });
    });
    const elig = document.querySelector('form[action$="/eligibility"]');
    if (elig) elig.addEventListener('input', () => send('eligibility_started', { page: location.pathname }), { once: true });
    const banner = document.querySelector('[data-consent-banner]');
    banner?.querySelectorAll('[data-consent]').forEach((b) => b.addEventListener('click', () => { remember(b.dataset.consent); banner.remove(); if (b.dataset.consent === 'granted') load(); }));
    if (cookie() === 'granted') load();
})();
