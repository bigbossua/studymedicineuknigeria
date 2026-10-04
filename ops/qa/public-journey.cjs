// The searcher's path, by clicking only links a visitor can see: a Google landing on the home page → Medicine →
// Requirements → Medical schools → Fees → Eligibility → Apply Online → Registration. Read-only: it stops at the
// registration form, so it is safe against production. Phone viewport by default; `desktop` as the third argument.
//   node ops/qa/public-journey.cjs <output-dir> [base-url] [desktop]
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
(async () => {
  const out = process.argv[2] || '/tmp'; const base = (process.argv[3] || 'http://127.0.0.1:8000').replace(/\/$/, '');
  const desktop = process.argv[4] === 'desktop';
  const b = await chromium.launch({ executablePath: process.env.CHROME_PATH || undefined });
  const page = await (await b.newContext({ viewport: desktop ? { width: 1366, height: 860 } : { width: 390, height: 844 } })).newPage();
  const problems = [];
  page.on('console', (m) => { if (m.type() === 'error') problems.push(page.url() + ' :: ' + m.text().slice(0, 160)); });
  page.on('response', (r) => { if (r.status() >= 400 && r.url().startsWith(base)) problems.push('HTTP ' + r.status() + ' ' + r.url()); });
  await page.route(/googletagmanager|google-analytics/, (r) => r.abort());
  // [label, path the step must reach, link to click from the previous page (searched inside <main> first)]
  const steps = [
    ['Medicine', '/study-medicine-in-the-uk', 'a[href$="/study-medicine-in-the-uk"]'],
    ['Requirements', '/requirements', 'a[href$="/requirements"]'],
    ['Medical schools', '/medical-schools', 'a[href$="/medical-schools"]'],
    ['Fees', '/fees', 'a[href$="/fees"]'],
    ['Eligibility', '/apply-online/eligibility', 'a[href$="/apply-online/eligibility"]'],
    ['Apply Online', '/apply-online', 'a[href$="/apply-online"]'],
    ['Registration', '/register', 'a[href$="/register"]'],
  ];
  const log = [];
  await page.goto(base + '/', { waitUntil: 'networkidle', referer: 'https://www.google.com/' });
  log.push({ step: 'Home (from Google)', url: page.url(), h1: (await page.textContent('h1')).trim() });
  for (const [label, path, sel] of steps) {
    const inMain = page.locator('main ' + sel).filter({ visible: true });
    const anywhere = page.locator(sel).filter({ visible: true });
    const where = (await inMain.count()) ? 'body' : (await anywhere.count()) ? 'navigation' : null;
    if (!where) { problems.push(`${label}: no visible link from ${page.url()}`); log.push({ step: label, reached: false }); break; }
    await (where === 'body' ? inMain : anywhere).first().click();
    await page.waitForLoadState('networkidle');
    const ok = new URL(page.url()).pathname === path;
    if (!ok) problems.push(`${label}: expected ${path}, landed on ${page.url()}`);
    if (/£\s?(125|695|1,?295)\b/.test(await page.textContent('body'))) problems.push(`${label}: a service fee is visible on ${page.url()}`);
    log.push({ step: label, via: where, url: page.url(), h1: ((await page.textContent('h1').catch(() => '')) || '').trim(), floatingCta: await page.locator('[data-floating-cta]').count() });
    await page.screenshot({ path: `${out}/pj-${desktop ? 'd' : 'm'}-${path.replace(/\W+/g, '_') || 'home'}.png` });
  }
  const form = await page.locator('form input#email').count();
  log.push({ registrationForm: form > 0 });
  if (!form) problems.push('Registration: no form on ' + page.url());
  console.log(JSON.stringify({ viewport: desktop ? 'desktop' : 'phone', log, problems }, null, 1));
  await b.close();
  process.exit(problems.length ? 1 : 0);
})();
