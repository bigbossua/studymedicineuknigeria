const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const { execSync } = require('child_process');
const totp = (secret) => execSync(`php artisan tinker --execute="echo App\\\\Support\\\\Totp::code('${secret}');"`, { cwd: process.cwd() }).toString().trim();
(async () => {
  const base = 'http://127.0.0.1:8000'; const secret = process.argv[2]; const violations = []; const errors = [];
  const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 } }); const page = await ctx.newPage();
  page.on('console', m => { const t = m.text(); if (/Content Security Policy|Refused to/.test(t)) violations.push(page.url() + ' :: ' + t.slice(0, 180)); else if (m.type() === 'error') errors.push(page.url() + ' :: ' + t.slice(0, 160)); });
  page.on('pageerror', e => errors.push(page.url() + ' :: ' + e.message.slice(0, 160)));
  page.on('response', r => { if (r.status() >= 500) errors.push('HTTP ' + r.status() + ' ' + r.url()); });
  const visit = async (u) => { await page.goto(base + u, { waitUntil: 'networkidle' }); };
  for (const u of ['/', '/medical-schools', '/study-medicine-in-the-uk/from-nigeria', '/fees', '/apply-online/eligibility', '/faq', '/login', '/register', '/no-such-page']) await visit(u);
  // mobile nav toggle exercises app.js
  await page.setViewportSize({ width: 390, height: 844 }); await visit('/'); await page.click('[data-nav-toggle]'); const navOpen = await page.getAttribute('[data-nav-toggle]', 'aria-expanded'); await page.setViewportSize({ width: 1366, height: 900 });
  // student portal
  await visit('/login'); await page.fill('#email', 'student@example.test'); await page.fill('#password', 'Testpass12345'); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  for (const u of ['/portal', '/portal/profile', '/portal/SMN-2028-000001/application', '/portal/SMN-2028-000001/application/personal', '/portal/SMN-2028-000001/documents', '/portal/SMN-2028-000001/payments', '/portal/SMN-2028-000001/messages']) await visit(u);
  // autosave exercises portal.js (fetch to self)
  await visit('/portal/SMN-2028-000001/application/personal'); await page.fill('#legal_first_names', 'Adaeze'); await page.waitForTimeout(2500); const saved = await page.textContent('[data-save-indicator]');
  await page.click('form[action$="/logout"] button'); await page.waitForLoadState('networkidle');
  // admin with 2FA challenge
  await visit('/login'); await page.fill('#email', 'admin@example.test'); await page.fill('#password', 'Adminpass12345'); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  await page.fill('#code', totp(secret)); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle'); const adminUrl = page.url();
  for (const u of ['/admin', '/admin/applications', '/admin/applications/SMN-2028-000001', '/admin/leads', '/admin/payments', '/admin/services', '/admin/verification', '/admin/universities', '/admin/redirects', '/admin/users', '/admin/audit']) await visit(u);
  // data-confirm handler: dialog should appear on reset (dismiss it)
  let dialogSeen = false; page.once('dialog', async d => { dialogSeen = true; await d.dismiss(); });
  await visit('/admin/users'); const resetBtn = page.locator('form[data-confirm] button'); if (await resetBtn.count()) { await resetBtn.first().click(); await page.waitForTimeout(500); }
  console.log(JSON.stringify({ navOpen, saved: (saved || '').trim(), adminUrl, dialogSeen, resetForms: await resetBtn.count(), violations, errors }, null, 1));
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
