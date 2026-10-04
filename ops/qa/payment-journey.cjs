// Paid-service journey in a phone-sized browser, against a local site whose Stripe calls go to ops/qa/fake-stripe.php:
// public services page (no fee in the source) → Apply Online → register → (verify, local shortcut) → start (no fee) →
// profile approved (local shortcut) → service choice with fees → choose T2 → confirmation page →
// cancel at checkout → retry → pay → return page waits (not paid) → signed webhook → paid → staff see it.
// Checks that the amount on the website equals the amount sent to the checkout, and reports console/CSP/5xx problems.
// Run: see ops/qa/README.md (needs the app served with STRIPE_SECRET=sk_test_local, STRIPE_WEBHOOK_SECRET and
// STRIPE_API_BASE pointing at the stand-in). Usage: node ops/qa/payment-journey.cjs <output-dir> [desktop]
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const { execFileSync } = require('child_process'); const crypto = require('crypto'); const fs = require('fs');
const base = process.env.APP_BASE || 'http://127.0.0.1:8000'; const out = process.argv[2] || '.'; const desktop = process.argv[3] === 'desktop';
const store = process.env.FAKE_STRIPE_STORE || require('os').tmpdir() + '/fake-stripe-sessions.json';
const secret = process.env.STRIPE_WEBHOOK_SECRET || 'whsec_local_qa';
const tinker = (php) => execFileSync('php', ['artisan', 'tinker', '--execute', php], { cwd: process.cwd() }).toString().trim();
const log = { flashes: [] }; const problems = []; let page;
(async () => {
  const b = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  page = await (await b.newContext(desktop ? { viewport: { width: 1366, height: 900 } } : { viewport: { width: 390, height: 844 }, isMobile: true })).newPage();
  const ours = () => page.url().startsWith(base); // the stand-in's own page (favicon etc.) is not under test
  page.on('console', m => { if (ours() && (m.type() === 'error' || /Content Security Policy|Refused/.test(m.text()))) problems.push(page.url() + ' :: ' + m.text().slice(0, 160)); });
  page.on('pageerror', e => problems.push(page.url() + ' :: ' + e.message.slice(0, 160)));
  page.on('response', r => { if (r.status() >= 500) problems.push('HTTP ' + r.status() + ' ' + r.url()); });
  const email = 'pay-' + Date.now() + '@example.test';

  // public services page: services and inclusions, no fee anywhere in the page source
  await page.goto(base + '/apply-online/services', { waitUntil: 'networkidle' });
  const publicHtml = await page.content();
  log.publicFeeLeaks = ['£125', '£695', '£1,295', '12500', '69500', '129500', 'priceCurrency'].filter(x => publicHtml.includes(x));
  log.publicNotice = await page.locator('text=Service options and pricing are provided after your profile has been reviewed.').count();
  log.mostPopular = await page.locator('text=Most popular').count();
  log.disclaimer = await page.locator('text=We do not guarantee admission, a visa, a scholarship or an offer from any university.').count();
  await page.screenshot({ path: `${out}/p-services${desktop ? '-desktop' : ''}.png`, fullPage: true });
  await page.click('[data-cta="services-apply"]'); await page.waitForLoadState('networkidle'); log.afterApply = page.url().replace(base, '');
  await page.goto(base + '/register', { waitUntil: 'networkidle' });

  // register, verify (local shortcut), start: no service and no fee yet
  await page.fill('#name', 'Payment Tester'); await page.fill('#email', email); await page.fill('#password', 'Longpass12345'); await page.fill('#password_confirmation', 'Longpass12345'); await page.check('input[name=terms]');
  await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  tinker(`App\\Models\\User::where('email','${email}')->first()->forceFill(['email_verified_at'=>now()])->save(); echo 'ok';`);
  await page.goto(base + '/portal', { waitUntil: 'networkidle' });
  const startHtml = await page.content(); log.startPageFeeLeaks = ['£125', '£695', '£1,295'].filter(x => startHtml.includes(x));
  await page.click('button:has-text("Create my application")'); await page.waitForLoadState('networkidle');
  const appNo = page.url().match(/SMUKN-\d{4}-\d+/)[0]; log.application = appNo;
  await page.goto(`${base}/portal/${appNo}/services`, { waitUntil: 'networkidle' }); log.servicesBeforeApproval = page.url().replace(base, '');

  // our team reviews the profile (QA shortcut for the admin's "Approve for service selection")
  tinker(`App\\Models\\Application::where('application_number','${appNo}')->first()->forceFill(['services_approved_at'=>now()])->save(); echo 'ok';`);
  await page.goto(`${base}/portal/${appNo}/services`, { waitUntil: 'networkidle' });
  log.prices = await page.$$eval('[data-service-price]', els => Object.fromEntries(els.map(e => [e.dataset.servicePrice, e.textContent.trim()])));
  log.preselected = await page.$$eval('input[name=service_tier_id]:checked', els => els.length);
  await page.screenshot({ path: `${out}/p-choose${desktop ? '-desktop' : ''}.png`, fullPage: true });
  await page.click('[data-service="T2"]'); await page.click('button:has-text("Continue with this service")'); await page.waitForLoadState('networkidle');
  log.afterChoose = page.url().replace(base, '');
  log.confirmPrice = (await page.textContent('[data-checkout-price]')).trim();
  await page.screenshot({ path: `${out}/p-confirm${desktop ? '-desktop' : ''}.png`, fullPage: true });

  // first attempt: cancel at checkout
  await page.check('input[name=accept_terms]'); await page.click('button:has-text("Continue to secure payment")'); await page.waitForLoadState('networkidle');
  log.checkoutAmount = (await page.textContent('[data-amount]')).trim(); log.checkoutName = (await page.textContent('[data-name]')).trim();
  await page.click('[data-cancel]'); await page.waitForLoadState('networkidle');
  log.afterCancel = (await page.locator('text=Payment cancelled').count()) ? 'cancel message shown' : 'NO cancel message';

  // second attempt: pay; returning alone must not mark it paid
  await page.check('input[name=accept_terms]'); await page.click('button:has-text("Continue to secure payment")'); await page.waitForLoadState('networkidle');
  await page.click('[data-pay]'); await page.waitForLoadState('domcontentloaded');
  log.returnBeforeWebhook = (await page.locator('h1').first().textContent()).trim();
  const sessions = JSON.parse(fs.readFileSync(store, 'utf8')); const last = Object.values(sessions).pop();
  log.sentToCheckout = { amount_minor: last.amount_total, currency: last.currency, stripe_price: last.price };

  // Stripe confirms: a signed checkout.session.completed with what was charged
  const payload = JSON.stringify({ id: 'evt_qa_' + Date.now(), object: 'event', type: 'checkout.session.completed', api_version: '2024-06-20', created: Math.floor(Date.now() / 1000), livemode: false,
    data: { object: { object: 'checkout.session', id: last.id, payment_status: 'paid', amount_total: last.amount_total, currency: last.currency, payment_intent: 'pi_qa_' + last.id, metadata: last.metadata } } });
  const ts = Math.floor(Date.now() / 1000); const sig = crypto.createHmac('sha256', secret).update(`${ts}.${payload}`).digest('hex');
  const hook = await page.request.post(base + '/webhooks/stripe', { data: payload, headers: { 'Stripe-Signature': `t=${ts},v1=${sig}`, 'Content-Type': 'application/json' } });
  log.webhook = hook.status() + ' ' + (await hook.text());
  await page.reload({ waitUntil: 'networkidle' });
  log.returnAfterWebhook = (await page.locator('h1').first().textContent()).trim();
  await page.screenshot({ path: `${out}/p-paid${desktop ? '-desktop' : ''}.png`, fullPage: true });
  await page.goto(`${base}/portal/${appNo}/payments`, { waitUntil: 'networkidle' });
  log.paymentsPage = (await page.locator('h1').first().textContent()).trim();
  log.db = tinker(`$p = App\\Models\\Application::where('application_number','${appNo}')->first()->payments()->latest('id')->first(); echo $p->status.' '.$p->amount_minor.' '.$p->currency.' '.$p->tierPrice->tier->code.' '.$p->stripe_price_id;`);
  log.pageEqualsCharge = log.confirmPrice === '£' + (last.amount_total / 100).toLocaleString('en-GB') && last.currency === 'gbp';
  log.problems = problems;
  console.log(JSON.stringify(log, null, 1));
  await b.close();
})().catch(async (e) => { log.error = String(e.message || e).split('\n')[0]; log.failedAt = page?.url(); log.problems = problems; if (page) await page.screenshot({ path: `${out}/p-failure.png`, fullPage: true }).catch(() => {}); console.log(JSON.stringify(log, null, 1)); process.exit(1); });
