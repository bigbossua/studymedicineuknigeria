const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const { execSync } = require('child_process'); const fs = require('fs');
let axePath; try { axePath = require.resolve('axe-core/axe.min.js'); } catch (e) { axePath = process.env.AXE_PATH; }
if (!axePath) { console.error('axe-core not found: npm i -g axe-core, or set AXE_PATH=/path/to/axe.min.js'); process.exit(2); }
const axeSource = fs.readFileSync(axePath, 'utf8');
const totp = (secret) => execSync(`php artisan tinker --execute="echo App\\\\Support\\\\Totp::code('${secret}');"`, { cwd: process.cwd() }).toString().trim();
(async () => {
  const base = 'http://127.0.0.1:8000'; const secret = process.argv[2]; const results = {};
  const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  const audit = async (page, label) => {
    await page.addScriptTag({ content: axeSource });
    const r = await page.evaluate(async () => { const res = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] } }); return res.violations.map(v => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.slice(0, 4).map(n => n.target.join(' ')) , count: v.nodes.length })); });
    if (r.length) results[label] = r;
  };
  // public pages from sitemap + auth
  let ctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, bypassCSP: true }); let page = await ctx.newPage();
  const sm = await (await ctx.request.get(base + '/sitemap.xml')).text(); const paths = (sm.match(/<loc>([^<]+)<\/loc>/g) || []).map(l => new URL(l.replace(/<\/?loc>/g, '')).pathname);
  paths.push('/login', '/register', '/password/forgot', '/no-such-page', '/medical-schools/university-of-leicester');
  for (const p of paths) { await page.goto(base + p, { waitUntil: 'networkidle' }); await audit(page, p); }
  // mobile home with nav open
  await page.setViewportSize({ width: 390, height: 844 }); await page.goto(base + '/', { waitUntil: 'networkidle' }); await page.click('[data-nav-toggle]'); await audit(page, '/ (mobile nav open)');
  await page.setViewportSize({ width: 1366, height: 900 });
  // student portal
  await page.goto(base + '/login'); await page.fill('#email', 'student@example.test'); await page.fill('#password', 'Testpass12345'); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  for (const p of ['/portal', '/portal/profile', '/portal/SMN-2028-000001/application', '/portal/SMN-2028-000001/application/personal', '/portal/SMN-2028-000001/application/secondary', '/portal/SMN-2028-000001/documents', '/portal/SMN-2028-000001/payments', '/portal/SMN-2028-000001/submissions', '/portal/SMN-2028-000001/messages', '/two-factor/setup']) { await page.goto(base + p, { waitUntil: 'networkidle' }); await audit(page, p); }
  await ctx.close();
  // admin
  ctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, bypassCSP: true }); page = await ctx.newPage();
  await page.goto(base + '/login'); await page.fill('#email', 'admin@example.test'); await page.fill('#password', 'Adminpass12345'); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle'); await audit(page, '/two-factor/challenge');
  await page.fill('#code', totp(secret)); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  for (const p of ['/admin', '/admin/applications', '/admin/applications/SMN-2028-000001', '/admin/leads', '/admin/payments', '/admin/services', '/admin/verification', '/admin/universities', '/admin/redirects', '/admin/users', '/admin/funnel', '/admin/audit']) { await page.goto(base + p, { waitUntil: 'networkidle' }); await audit(page, p); }
  await browser.close();
  const summary = {}; for (const [p, vs] of Object.entries(results)) for (const v of vs) { summary[v.id] = summary[v.id] || { impact: v.impact, help: v.help, pages: 0, nodes: 0, sample: v.nodes[0] }; summary[v.id].pages++; summary[v.id].nodes += v.count; }
  console.log(JSON.stringify({ pagesWithViolations: Object.keys(results).length, summary }, null, 1));
  fs.writeFileSync(__dirname + '/axe-results.json', JSON.stringify(results, null, 1));
})().catch(e => { console.error(e); process.exit(1); });
