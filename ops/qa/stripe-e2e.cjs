// Real Stripe test-mode journey (run by .github/workflows/stripe-test-journey.yml on a GitHub runner, which can reach
// Stripe): for each service a new student registers, starts an application, staff approve the profile in the admin,
// the student sees the three fees, chooses the service, confirms, pays on Stripe's own hosted Checkout with a test card,
// and only the signed webhook (delivered by `stripe listen`) marks the payment paid. T1 first tries a declined card.
// Each charge is then read back from Stripe: amount, currency, the catalogue price id and test mode.
// Usage: BASE=http://127.0.0.1:8000 STRIPE_SECRET=sk_test_... node ops/qa/stripe-e2e.cjs <out-dir>
// Local rehearsal against ops/qa/fake-stripe.php (same form fields, signed webhook like `stripe listen`): CHECKOUT_HOST_RE=127\.0\.0\.1:12111
const path = require('path'); const fs = require('fs'); const { execFileSync, execSync } = require('child_process');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || path.join(execSync('npm root -g').toString().trim(), 'playwright'));
const base = (process.env.BASE || 'http://127.0.0.1:8000').replace(/\/$/, ''); const out = process.argv[2] || '.'; fs.mkdirSync(out, { recursive: true });
const tinker = (php) => execFileSync('php', ['artisan', 'tinker', '--execute', php], { env: process.env }).toString().trim();
const FEES = { T1: ['£125', 12500], T2: ['£695', 69500], T3: ['£1,295', 129500] };
const result = { started: new Date().toISOString(), services: {}, problems: [] };
let page; let step = 'start';

async function stripePay(p, card, label) {
  await p.waitForURL(new RegExp(process.env.CHECKOUT_HOST_RE || 'checkout\\.stripe\\.com'), { timeout: 60000 }); await p.waitForLoadState('networkidle');
  const total = await p.locator('[data-testid="product-summary-total-amount"], #ProductSummary-totalAmount, .ProductSummary-totalAmount').first().textContent({ timeout: 15000 }).catch(() => null);
  const accordion = p.locator('[data-testid="card-accordion-item-button"]'); if (await accordion.count()) await accordion.click();
  await p.fill('#cardNumber', card); await p.fill('#cardExpiry', '12 / 34'); await p.fill('#cardCvc', '123');
  if (await p.locator('#billingName').count()) await p.fill('#billingName', 'Stripe Test Student');
  if (await p.locator('#billingCountry').count()) await p.selectOption('#billingCountry', 'GB').catch(() => {});
  if (await p.locator('#billingPostalCode').isVisible().catch(() => false)) await p.fill('#billingPostalCode', 'SW1A 1AA');
  const save = p.locator('#enableStripePass'); if (await save.count() && await save.isChecked().catch(() => false)) await save.uncheck().catch(() => {});
  await p.screenshot({ path: `${out}/${label}-stripe-filled.png`, fullPage: true });
  await p.click('[data-testid="hosted-payment-submit-button"], .SubmitButton');
  return total ? total.trim() : null;
}

(async () => {
  const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
  // staff: the demo admin with two-step verification (local database on the runner)
  const admin = await (await browser.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
  step = 'admin sign-in';
  await admin.goto(base + '/login'); await admin.fill('#email', 'admin@example.test'); await admin.fill('#password', 'Adminpass12345');
  await admin.click('button[type=submit]'); await admin.waitForLoadState('networkidle');
  const secret = tinker("echo App\\Models\\User::where('email','admin@example.test')->first()->two_factor_secret;");
  await admin.fill('#code', tinker(`echo App\\Support\\Totp::code('${secret}');`)); await admin.click('button[type=submit]'); await admin.waitForLoadState('networkidle');

  for (const code of ['T1', 'T2', 'T3']) {
    const r = result.services[code] = {};
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true }); page = await ctx.newPage();
    page.on('console', m => { if (page.url().startsWith(base) && (m.type() === 'error' || /Content Security Policy|Refused/.test(m.text()))) result.problems.push(`${code} ${page.url()} :: ${m.text().slice(0, 160)}`); });
    const email = `stripe-e2e-${code.toLowerCase()}-${Date.now()}@example.test`;
    step = `${code} register`;
    await page.goto(base + '/register'); await page.fill('#name', `Stripe Test ${code}`); await page.fill('#email', email);
    await page.fill('#password', 'Longpass12345'); await page.fill('#password_confirmation', 'Longpass12345'); await page.check('input[name=terms]');
    await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
    tinker(`App\\Models\\User::where('email','${email}')->first()->forceFill(['email_verified_at'=>now()])->save(); echo 'ok';`); // local shortcut for the emailed link
    step = `${code} start`;
    await page.goto(base + '/portal', { waitUntil: 'networkidle' });
    r.startPageShowsFee = /£\d/.test(await page.content());
    await page.click('button:has-text("Create my application")'); await page.waitForLoadState('networkidle');
    const no = page.url().match(/SMUKN-\d{4}-\d+/)[0]; r.application = no;
    await page.goto(`${base}/portal/${no}/services`, { waitUntil: 'networkidle' }); r.servicesBeforeApproval = page.url().replace(base, '');

    step = `${code} staff approval`;
    await admin.goto(`${base}/admin/applications/${no}`, { waitUntil: 'networkidle' });
    await admin.click('button:has-text("Approve for service selection")'); await admin.waitForLoadState('networkidle');

    step = `${code} choose`;
    await page.goto(`${base}/portal/${no}/services`, { waitUntil: 'networkidle' });
    r.feesShown = await page.$$eval('[data-service-price]', els => Object.fromEntries(els.map(e => [e.dataset.servicePrice, e.textContent.trim()])));
    await page.click(`[data-service="${code}"]`); await page.click('button:has-text("Continue with this service")'); await page.waitForLoadState('networkidle');
    r.confirmPrice = (await page.textContent('[data-checkout-price]')).trim();
    await page.screenshot({ path: `${out}/${code}-confirm.png`, fullPage: true });
    await page.check('input[name=accept_terms]'); await page.click('button:has-text("Continue to secure payment")');

    if (code === 'T1') { // a declined card leaves nothing paid; the student can try again on the same page
      step = `${code} declined card`;
      r.stripeTotal = await stripePay(page, '4000 0000 0000 0002', `${code}-declined`);
      await page.waitForSelector('text=/declined/i', { timeout: 30000 }).then(() => { r.declinedShown = true; }).catch(() => { r.declinedShown = false; });
      await page.fill('#cardNumber', '');
    }
    step = `${code} pay`;
    const total = await stripePay(page, '4242 4242 4242 4242', code); r.stripeTotal = r.stripeTotal || total;
    await page.waitForURL(u => u.toString().startsWith(base) && u.toString().includes('/payments/return'), { timeout: 90000 });
    r.returnPage = (await page.locator('h1').first().textContent()).trim();

    step = `${code} webhook`;
    let status = ''; for (let i = 0; i < 45 && status !== 'SUCCEEDED'; i++) { status = tinker(`echo App\\Models\\Application::where('application_number','${no}')->first()->payments()->latest('id')->value('status');`); if (status !== 'SUCCEEDED') await page.waitForTimeout(2000); }
    r.statusAfterWebhook = status;
    await page.reload({ waitUntil: 'networkidle' }); r.returnPageAfterWebhook = (await page.locator('h1').first().textContent()).trim();
    await page.screenshot({ path: `${out}/${code}-paid.png`, fullPage: true });

    step = `${code} read back from Stripe`;
    r.db = JSON.parse(tinker(`$p = App\\Models\\Application::where('application_number','${no}')->first()->payments()->latest('id')->first(); echo json_encode(['status' => $p->status, 'amount_minor' => $p->amount_minor, 'currency' => $p->currency, 'stripe_price_id' => $p->stripe_price_id, 'catalogue_price_id' => $p->tierPrice->stripe_price_id, 'session' => $p->stripe_checkout_session_id]);`));
    r.stripe = JSON.parse(tinker(`$s = app(App\\Services\\Payments\\StripeService::class)->client()->checkout->sessions->retrieve('${r.db.session}', ['expand' => ['line_items']]); echo json_encode(['amount_total' => $s->amount_total, 'currency' => $s->currency, 'payment_status' => $s->payment_status, 'livemode' => $s->livemode, 'price' => $s->line_items->data[0]->price->id ?? null, 'unit_amount' => $s->line_items->data[0]->price->unit_amount ?? null]);`));
    const [fee, minor] = FEES[code];
    r.ok = !r.startPageShowsFee && r.servicesBeforeApproval === '/portal' && r.feesShown.T1 === '£125' && r.feesShown.T2 === '£695' && r.feesShown.T3 === '£1,295'
      && r.confirmPrice === fee && r.statusAfterWebhook === 'SUCCEEDED' && r.db.amount_minor === minor && r.stripe.amount_total === minor && r.stripe.currency === 'gbp'
      && r.stripe.payment_status === 'paid' && r.stripe.livemode === false && r.stripe.price === r.db.stripe_price_id && r.db.stripe_price_id === r.db.catalogue_price_id
      && (code !== 'T1' || r.declinedShown === true);
    await ctx.close();
  }
  await browser.close();
  result.ok = Object.values(result.services).every(s => s.ok) && result.problems.length === 0;
  fs.writeFileSync(`${out}/stripe-e2e.json`, JSON.stringify(result, null, 1)); console.log(JSON.stringify(result, null, 1));
  process.exit(result.ok ? 0 : 1);
})().catch(async (e) => {
  result.error = String(e.message || e).split('\n')[0]; result.failedAt = step; result.url = page?.url();
  if (page) await page.screenshot({ path: `${out}/failure.png`, fullPage: true }).catch(() => {});
  fs.writeFileSync(`${out}/stripe-e2e.json`, JSON.stringify(result, null, 1)); console.log(JSON.stringify(result, null, 1)); process.exit(1);
});
