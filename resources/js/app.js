
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
        // the analytics consent banner sits at the bottom of the viewport: lift the floating buttons above it while it shows
        const banner = document.querySelector('[data-consent-banner]');
        const lift = () => { fab.style.bottom = banner && banner.isConnected ? `${banner.offsetHeight + 16}px` : ''; };
        lift(); window.addEventListener('resize', lift);
        banner?.querySelectorAll('[data-consent]').forEach((b) => b.addEventListener('click', () => setTimeout(lift, 0)));
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
    // What GA4 may learn about a page: its path (tokens masked) and campaign tags only, never any other query string
    const clean = (u) => { try { const x = new URL(u, location.origin); const q = new URLSearchParams(); x.searchParams.forEach((v, k) => { if (/^utm_/.test(k)) q.set(k, v); }); return x.origin + x.pathname.replace(/\/(reset|verify)\/.*$/, '/$1') + (q.toString() ? `?${q}` : ''); } catch (e) { return ''; } };
    const path = () => new URL(clean(location.href) || location.origin).pathname;
    const remember = (v) => { document.cookie = `smukn_consent=${v}; Max-Age=31536000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`; };
    const load = () => {
        if (window.__smuknGa) return;
        window.__smuknGa = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'granted' });
        window.gtag('js', new Date());
        window.gtag('config', id, { anonymize_ip: true, allow_google_signals: false, allow_ad_personalization_signals: false, page_location: clean(location.href), page_referrer: document.referrer ? clean(document.referrer) : '' });
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
        send('apply_click', { location: where, page: path() });
    });
    // Contact, outbound official sources and directory filtering: event names and coarse labels only, never what was typed
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const href = a.getAttribute('href');
        const where = a.dataset.whatsapp || (a.closest('footer') ? 'footer' : a.closest('header, [aria-label="About this service"]') ? 'header' : 'content');
        if (href.startsWith('mailto:')) send('contact_click', { method: 'email', location: where, page: path() });
        else if (href.includes('wa.me/')) send('whatsapp_click', { location: where, page: path() }); // its own event so GA4 can count it as a key event
        else if (a.target === '_blank' && /^https?:/.test(href) && !href.includes(location.host)) {
            try { send('official_source_click', { domain: new URL(href).hostname.replace(/^www\./, ''), page: path() }); } catch (err) { /* ignore */ }
        }
    });
    const dir = document.querySelector('form[aria-label="Filter medical schools"]');
    if (dir) dir.addEventListener('submit', () => {
        const used = ['nation', 'international', 'test', 'waec'].filter((k) => dir.elements[k]?.value);
        send('directory_filter', { filters: used.join(',') || 'none', searched: dir.elements.q?.value ? 'yes' : 'no' });
    });
    const elig = document.querySelector('form[action$="/eligibility"]');
    if (elig) elig.addEventListener('input', () => send('eligibility_started', { page: path() }), { once: true });
    // Declining (or changing your mind) removes the Google Analytics cookies already set, on this host and its parent domain
    const clearGa = () => document.cookie.split('; ').map((c) => c.split('=')[0]).filter((n) => /^_ga/.test(n)).forEach((n) => {
        document.cookie = `${n}=; Max-Age=0; Path=/`;
        document.cookie = `${n}=; Max-Age=0; Path=/; Domain=.${location.hostname.replace(/^www\./, '')}`;
    });
    const banner = document.querySelector('[data-consent-banner]');
    banner?.querySelectorAll('[data-consent]').forEach((b) => b.addEventListener('click', () => { remember(b.dataset.consent); banner.remove(); if (b.dataset.consent === 'granted') load(); else clearGa(); }));
    // Footer "Cookie settings": forget the choice and the GA cookies, then show the banner again
    document.querySelector('[data-consent-reset]')?.addEventListener('click', () => { document.cookie = 'smukn_consent=; Max-Age=0; Path=/'; clearGa(); location.reload(); });
    if (cookie() === 'denied') clearGa();
    if (cookie() === 'granted') load();
})();
