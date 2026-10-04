// What the public site would send to GA4, read from window.dataLayer with Google's script stubbed (nothing leaves the
// machine). Needs a server started with a test ID, e.g. SITE_GA4_ID=G-TEST0000 php artisan serve --port=8001.
// Checks: nothing before consent; page_location keeps only the path and utm_* tags; each interaction event carries
// only its documented parameters; nothing resembling an email address, phone number or application number.
//   node ops/qa/ga4-events.cjs [base-url]
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
(async () => {
  const base = (process.argv[2] || 'http://127.0.0.1:8001').replace(/\/$/, '');
  const b = await chromium.launch({ executablePath: process.env.CHROME_PATH || undefined });
  const ctx = await b.newContext({ viewport: { width: 1366, height: 860 } });
  await ctx.route(/googletagmanager\.com|google-analytics\.com/, (r) => r.fulfill({ status: 200, contentType: 'application/javascript', body: '/* stub */' }));
  await ctx.route(/wa\.me|\.ac\.uk|\.gov\.uk|ucas\.com|ucat\.ac\.uk/, (r) => r.fulfill({ status: 204, body: '' }));
  const page = await ctx.newPage();
  const problems = []; const seen = {};
  const layer = async () => page.evaluate(() => (window.dataLayer || []).map((a) => Array.from(a)));
  const events = async () => (await layer()).filter((a) => a[0] === 'event').map((a) => ({ name: a[1], params: a[2] || {} }));

  await page.goto(base + '/?utm_source=test&email=ada%40example.test&token=abc', { waitUntil: 'networkidle' });
  if ((await layer()).length) problems.push('dataLayer is not empty before consent');
  if (await page.locator('script[src*="googletagmanager"]').count()) problems.push('gtag loaded before consent');
  await page.click('[data-consent="granted"]');
  await page.waitForTimeout(200);
  const config = (await layer()).find((a) => a[0] === 'config');
  if (!config) problems.push('no gtag config after consent');
  else {
    seen.config = config[2];
    if (config[2].page_location !== base + '/?utm_source=test') problems.push('page_location not cleaned: ' + config[2].page_location);
    if (config[2].allow_google_signals !== false) problems.push('Google signals not disabled');
  }

  // keep each page in place so its events can be read (app.js's own listeners run first, then navigation is cancelled)
  const hold = () => page.evaluate(() => { document.addEventListener('click', (e) => { if (e.target.closest('a')) e.preventDefault(); }); document.addEventListener('submit', (e) => e.preventDefault()); });
  const all = [];
  const visit = async (path, act) => { await page.goto(base + path, { waitUntil: 'networkidle' }); await hold(); await act(); await page.waitForTimeout(150); all.push(...(await events())); };
  all.push(...(await events()));
  await visit('/', async () => { await page.locator('[data-whatsapp="floating"]').first().click(); await page.locator('main a.btn[href$="/apply-online"]').first().click(); });
  await visit('/requirements/waec', async () => { await page.locator('main a[target="_blank"][href^="https://"]').first().click(); });
  await visit('/medical-schools', async () => {
    const nation = page.locator('form[aria-label="Filter medical schools"] select[name="nation"]');
    if (await nation.count()) await nation.selectOption({ index: 1 });
    await page.locator('form[aria-label="Filter medical schools"] [type=submit]').first().click();
  });
  await visit('/apply-online/eligibility', async () => { await page.locator('form[action$="/eligibility"] input[type=radio]').first().check(); });
  await visit('/contact', async () => { const m = page.locator('main a[href^="mailto:"]'); if (await m.count()) await m.first().click(); });

  const allowed = {
    whatsapp_click: ['location', 'page'], contact_click: ['method', 'location', 'page'], official_source_click: ['domain', 'page'],
    directory_filter: ['filters', 'searched'], eligibility_started: ['page'], apply_click: ['location', 'page'],
    course_viewed: ['school'], apply_viewed: [], lead_created: ['qualification', 'intake_year', 'tier'], account_created: [],
  };
  for (const e of all) {
    seen[e.name] = Array.isArray(e.params) ? {} : e.params;
    if (!(e.name in allowed)) { problems.push('undocumented event ' + e.name); continue; }
    for (const k of Object.keys(e.params)) if (!allowed[e.name].includes(k)) problems.push(`${e.name} carries ${k}`);
    if (/@|\+?\d[\d\s]{8,}|SMUKN-/i.test(JSON.stringify(e.params))) problems.push(`${e.name} carries something personal: ${JSON.stringify(e.params)}`);
  }
  for (const need of ['whatsapp_click', 'official_source_click', 'directory_filter', 'eligibility_started', 'apply_click', 'contact_click']) if (!(need in seen)) problems.push('not sent: ' + need);
  console.log(JSON.stringify({ seen, problems }, null, 1));
  await b.close();
  process.exit(problems.length ? 1 : 0);
})();
