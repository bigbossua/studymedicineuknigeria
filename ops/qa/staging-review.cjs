// Post-deploy review of a deployed site from a GitHub runner (or locally): every sitemap page plus the funnel and
// auth pages, desktop and mobile. Per page: status, title, description, H1 count, robots; console and CSP errors with
// the site's real CSP enforced; axe-core violations (separate context, CSP bypassed only to inject axe).
// Fails on any 5xx, CSP/console error, missing or duplicate H1, serious/critical axe violation, or (on staging)
// a page without noindex; on production, a sitemap page that is noindex, has a canonical other than its own
// https URL, or a title/description outside the snippet limits. Writes review.json and review.md to the output directory.
// Usage: BASE=https://staging.example BASIC_USER=… BASIC_PASS=… TARGET=staging node ops/qa/staging-review.cjs <out-dir>
const path = require('path'); const fs = require('fs'); const { execSync } = require('child_process');
const globalRoot = execSync('npm root -g').toString().trim();
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || path.join(globalRoot, 'playwright'));
const axeSource = fs.readFileSync(process.env.AXE_PATH || path.join(globalRoot, 'axe-core/axe.min.js'), 'utf8');
const base = (process.env.BASE || 'http://127.0.0.1:8000').replace(/\/$/, ''); const target = process.env.TARGET || 'local';
const out = process.argv[2] || '.'; fs.mkdirSync(out, { recursive: true });
const httpCredentials = process.env.BASIC_USER ? { username: process.env.BASIC_USER, password: process.env.BASIC_PASS || '' } : undefined;
const EXTRA = ['/login', '/register', '/password/forgot', '/apply-online', '/apply-online/eligibility', '/no-such-page'];

(async () => {
  const browser = await chromium.launch({ ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}), ...(process.env.REVIEW_PROXY ? { proxy: { server: process.env.REVIEW_PROXY } } : {}) }); // REVIEW_PROXY: route the real host name to a local rehearsal server
  const ctx = await browser.newContext({ httpCredentials });
  const sm = await (await ctx.request.get(base + '/sitemap.xml')).text();
  const listed = new Set((sm.match(/<loc>([^<]+)<\/loc>/g) || []).map(l => new URL(l.replace(/<\/?loc>/g, '')).pathname));
  const paths = [...new Set([...listed, ...EXTRA])];
  const origin = process.env.CANONICAL_ORIGIN || 'https://studymedicineuknigeria.com'; // production canonicals
  const pages = []; const failures = [];
  if (target === 'production' && listed.size < 10) failures.push(`sitemap lists ${listed.size} pages`);
  for (const [label, viewport] of [['desktop', { width: 1366, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
    const real = await browser.newContext({ httpCredentials, viewport, isMobile: label === 'mobile' });
    const axeCtx = await browser.newContext({ httpCredentials, viewport, bypassCSP: true });
    const page = await real.newPage(); const axePage = await axeCtx.newPage();
    let errors = [];
    page.on('console', m => { if (m.type() === 'error' || /Content Security Policy|Refused/.test(m.text())) errors.push(m.text().slice(0, 200)); });
    page.on('pageerror', e => errors.push('pageerror: ' + e.message.slice(0, 200)));
    for (const p of paths) {
      errors = [];
      const res = await page.goto(base + p, { waitUntil: 'networkidle' }).catch(e => ({ status: () => 0, headers: () => ({}), err: e.message }));
      const status = res.status(); const headers = res.headers();
      const d = status ? await page.evaluate(() => ({ title: document.title, desc: document.querySelector('meta[name=description]')?.content || '', h1: document.querySelectorAll('h1').length, robots: document.querySelector('meta[name=robots]')?.content || '', canonical: document.querySelector('link[rel=canonical]')?.href || '' })) : {};
      await axePage.goto(base + p, { waitUntil: 'networkidle' }).catch(() => {});
      await axePage.addScriptTag({ content: axeSource }).catch(() => {});
      const axe = await axePage.evaluate(async () => window.axe ? (await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] } })).violations.map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.length })) : [{ id: 'axe-not-loaded', impact: 'serious', nodes: 0 }]).catch(() => []);
      const noindex = /noindex/i.test((headers['x-robots-tag'] || '') + ' ' + (d.robots || ''));
      const row = { viewport: label, path: p, status, title: d.title, desc_len: (d.desc || '').length, h1: d.h1, noindex, console: errors.slice(0, 5), axe };
      const expected404 = p === '/no-such-page';
      const problems = [];
      if (status >= 500 || status === 0) problems.push(`status ${status}`);
      if (!expected404 && status !== 200) problems.push(`status ${status}`);
      const consoleErrs = expected404 ? errors.filter(e => !/status of 404/.test(e)) : errors; // the browser logs the expected 404 itself
      if (consoleErrs.length) problems.push(`${consoleErrs.length} console/CSP error(s)`);
      if (status === 200 && d.h1 !== 1) problems.push(`${d.h1} H1`);
      if (axe.some(v => ['serious', 'critical'].includes(v.impact))) problems.push('axe: ' + axe.filter(v => ['serious', 'critical'].includes(v.impact)).map(v => v.id).join(','));
      if (target === 'staging' && status === 200 && !noindex) problems.push('indexable on staging');
      if (target === 'production' && listed.has(p) && status === 200) {
        if (noindex) problems.push('sitemap page is noindex on production');
        if (![origin + (p === '/' ? '' : p), origin + p].includes(d.canonical)) problems.push(`canonical ${d.canonical || 'missing'}`);
        if ((d.title || '').length > 65) problems.push(`title ${d.title.length} chars`);
        if (row.desc_len < 100 || row.desc_len > 165) problems.push(`description ${row.desc_len} chars`);
      }
      row.problems = problems; pages.push(row);
      if (problems.length) failures.push(`${label} ${p}: ${problems.join('; ')}`);
    }
    await real.close(); await axeCtx.close();
  }
  await browser.close();
  fs.writeFileSync(path.join(out, 'review.json'), JSON.stringify({ base, target, at: new Date().toISOString(), pages, failures }, null, 1));
  const md = [`# Review of ${base} (${target}), ${new Date().toISOString()}`, '', `${pages.length} page views (${paths.length} paths × desktop and mobile), ${failures.length} with problems.`, '',
    '| Viewport | Path | Status | H1 | noindex | Console | Axe | Problems |', '|---|---|---|---|---|---|---|---|',
    ...pages.map(r => `| ${r.viewport} | \`${r.path}\` | ${r.status} | ${r.h1 ?? '–'} | ${r.noindex ? 'yes' : 'no'} | ${r.console.length} | ${r.axe.map(v => v.id + '(' + v.impact + ')').join(' ') || '–'} | ${r.problems.join('; ') || 'ok'} |`)];
  fs.writeFileSync(path.join(out, 'review.md'), md.join('\n') + '\n');
  console.log(`${pages.length} page views, ${failures.length} with problems`); failures.forEach(f => console.log('FAIL ' + f));
  process.exit(failures.length ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
