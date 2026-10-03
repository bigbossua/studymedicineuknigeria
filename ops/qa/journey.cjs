// Fresh-account student journey: register → verify → start → personal step autosave → upload passport → documents → payments → messages → export.
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const { execSync } = require('child_process'); const fs = require('fs');
(async () => {
  const base = 'http://127.0.0.1:8000'; const out = process.argv[2]; const log = {}; const problems = [];
  const email = 'journey-' + Date.now() + '@example.test';
  const b = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  const page = await (await b.newContext({ viewport: { width: 390, height: 844 } })).newPage(); // mobile first
  page.on('console', m => { if (m.type() === 'error' || /Content Security Policy|Refused/.test(m.text())) problems.push(page.url() + ' :: ' + m.text().slice(0, 160)); });
  page.on('pageerror', e => problems.push(page.url() + ' :: ' + e.message.slice(0, 160)));
  page.on('response', r => { if (r.status() >= 500) problems.push('HTTP ' + r.status() + ' ' + r.url()); });
  // eligibility → register prefill
  await page.goto(base + '/apply-online/eligibility', { waitUntil: 'networkidle' });
  for (const [n, v] of [['qualification', 'alevels_ib'], ['sciences', 'yes'], ['english', 'none'], ['ucat', 'planned']]) await page.check(`input[name=${n}][value=${v}]`).catch(() => page.selectOption(`select[name=${n}]`, v));
  await page.selectOption('select[name=intake_year]', '2028').catch(() => page.fill('input[name=intake_year]', '2028'));
  await page.fill('input[name=name]', 'Journey Tester'); await page.fill('input[name=email]', email); await page.check('input[name=consent]');
  await page.click('form[action$="/eligibility"] button[type=submit]'); await page.waitForLoadState('networkidle');
  log.eligibilitySummary = (await page.textContent('.eyebrow:has-text("route map")').catch(() => 'no result eyebrow'));
  log.registerLinkOnResult = await page.locator('a[href$="/register"]').count();
  // register (prefilled from the lead)
  await page.goto(base + '/register', { waitUntil: 'networkidle' });
  log.prefilledEmail = await page.inputValue('#email');
  await page.fill('#name', 'Journey Tester'); await page.fill('#email', email); await page.fill('#password', 'Longpass12345'); await page.fill('#password_confirmation', 'Longpass12345'); await page.check('input[name=terms]');
  await page.click('button[type=submit]'); await page.waitForLoadState('networkidle'); log.afterRegister = page.url();
  // simulate the verification link (local only)
  execSync(`php artisan tinker --execute="App\\\\Models\\\\User::where('email','${email}')->first()->forceFill(['email_verified_at'=>now()])->save(); echo 'verified';"`, { cwd: process.cwd() });
  await page.goto(base + '/portal', { waitUntil: 'networkidle' }); log.portalAfterVerify = page.url();
  await page.screenshot({ path: `${out}/j-start.png`, fullPage: true });
  // start an application (T2)
  const tierOptions = await page.$$eval('input[name=service_tier_id]', els => els.map(e => e.value)).catch(() => []);
  if (tierOptions.length) await page.check(`input[name=service_tier_id][value="${tierOptions[1] || tierOptions[0]}"]`); else await page.selectOption('select[name=service_tier_id]', { index: 1 }).catch(() => {});
  await page.selectOption('select[name=intake_year]', '2028'); await page.click('form[action$="/portal/start"] button[type=submit]'); await page.waitForLoadState('networkidle');
  log.afterStart = page.url(); log.applicationNumber = (await page.textContent('body')).match(/SMUKN-\d{4}-\d{6}/)?.[0] || 'none';
  await page.screenshot({ path: `${out}/j-dashboard.png`, fullPage: true });
  const n = log.applicationNumber;
  // personal step with autosave
  await page.goto(`${base}/portal/${n}/application/personal`, { waitUntil: 'networkidle' });
  await page.fill('#legal_first_names', 'Journey'); await page.fill('#legal_surname', 'Tester'); await page.waitForTimeout(2500);
  log.autosave = (await page.textContent('[data-save-indicator]')).trim();
  // documents: upload a PNG passport
  await page.goto(`${base}/portal/${n}/documents`, { waitUntil: 'networkidle' });
  const passportLink = page.locator('a[href*="/documents/"]', { hasText: /passport/i }).first(); log.passportRow = await passportLink.count();
  if (await passportLink.count()) { await passportLink.click(); await page.waitForLoadState('networkidle'); }
  const png = `${out}/j-passport.png`; execSync(`php -r '$i=imagecreatetruecolor(1200,800); imagefill($i,0,0,imagecolorallocate($i,200,210,220)); imagepng($i,"${png}");'`);
  const input = page.locator('input[type=file]').first(); log.fileInput = await input.count();
  if (await input.count()) { await input.setInputFiles(png); const btn = page.locator('form[enctype] button').first(); await btn.click(); await page.waitForLoadState('networkidle'); }
  log.afterUpload = page.url(); log.uploadStatus = (await page.textContent('.alert-success, .alert-danger').catch(() => 'no alert')).trim().slice(0, 120);
  await page.goto(`${base}/portal/${n}/documents`, { waitUntil: 'networkidle' }); log.docChips = await page.$$eval('.chip', els => els.map(e => e.textContent.trim()).filter(t => /review|required|accepted|received/i.test(t)).slice(0, 6));
  await page.screenshot({ path: `${out}/j-documents.png`, fullPage: true });
  for (const p of ['payments', 'messages', 'submissions']) { const r = await page.goto(`${base}/portal/${n}/${p}`, { waitUntil: 'networkidle' }); log['page_' + p] = r.status(); }
  const exp = await page.request.get(`${base}/portal/profile/export`); log.export = exp.status() + ' ' + (exp.headers()['content-type'] || '') + ' ' + (await exp.text()).length + ' bytes';
  log.problems = problems; console.log(JSON.stringify(log, null, 1)); await b.close();
})().catch(e => { console.error('FAILED', e.message); process.exit(1); });
