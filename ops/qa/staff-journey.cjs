// Staff and approval journey in a browser, continuing the latest application (run journey.cjs first):
// admin two-step sign-in → document review → submission proposal → ready for approval → student approves the exact
// package → admin marks it submitted → student tracks it. Local only (uses tinker for TOTP codes and to accept the
// remaining placeholder documents). Reports console/CSP problems, 5xx responses and every flash message seen.
// Usage: node ops/qa/staff-journey.cjs <output-dir>
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const { execFileSync } = require('child_process');
const base = process.env.APP_BASE || 'http://127.0.0.1:8000'; const out = process.argv[2] || '.';
const tinker = (php) => execFileSync('php', ['artisan', 'tinker', '--execute', php], { cwd: process.cwd() }).toString().trim(); // no shell: PHP $variables stay intact
const log = { flashes: [] }; const problems = []; let current = null;
(async () => {
  const info = JSON.parse(tinker("$a = App\\Models\\Application::latest('id')->first(); echo json_encode(['no' => $a->application_number, 'email' => $a->user->email, 'name' => $a->user->name]);"));
  log.application = info.no;
  const b = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  const watch = (page) => {
    page.on('console', m => { if (!page.url().startsWith('http://127.0.0.1:12111') && (m.type() === 'error' || /Content Security Policy|Refused/.test(m.text()))) problems.push(page.url() + ' :: ' + m.text().slice(0, 160)); }); // the Stripe stand-in's own page is not under test
    page.on('pageerror', e => problems.push(page.url() + ' :: ' + e.message.slice(0, 160)));
    page.on('response', r => { if (r.status() >= 500) problems.push('HTTP ' + r.status() + ' ' + r.url()); });
  };
  const flash = async (page, step) => { const t = await page.locator('[role=status], [role=alert], .alert').allTextContents().catch(() => []); log.flashes.push(step + ': ' + (t.map(s => s.trim().replace(/\s+/g, ' ')).filter(Boolean).join(' | ') || '(none)')); };

  // admin: sign in with two-step verification
  const admin = await (await b.newContext({ viewport: { width: 1280, height: 900 } })).newPage(); watch(admin); current = admin;
  await admin.goto(base + '/login'); await admin.fill('#email', 'admin@example.test'); await admin.fill('#password', 'Adminpass12345');
  await admin.click('button[type=submit]'); await admin.waitForLoadState('networkidle');
  const secret = tinker("echo App\\Models\\User::where('email','admin@example.test')->first()->two_factor_secret;");
  await admin.fill('#code', tinker(`echo App\\Support\\Totp::code('${secret}');`)); await admin.click('button[type=submit]'); await admin.waitForLoadState('networkidle');
  log.adminAfter2fa = admin.url();
  const show = base + '/admin/applications/' + info.no;
  await admin.goto(show, { waitUntil: 'networkidle' }); log.adminShow = admin.url();

  // review the passport the student uploaded in the browser; waive (never accept) what was not uploaded
  const review = admin.locator('form[action*="/documents/"][action$="/review"]').filter({ has: admin.locator('select[name=decision] option[value=accept]') }).first();
  if (await review.count()) { await review.locator('select[name=decision]').selectOption('accept'); await review.locator('button').click(); await admin.waitForLoadState('networkidle'); await flash(admin, 'document review'); } else { log.flashes.push('document review: no reviewable upload (already decided)'); }
  tinker(`$a = App\\Models\\Application::where('application_number','${info.no}')->first(); $id = App\\Models\\User::where('email','admin@example.test')->value('id'); foreach ($a->documents as $d) { if (! $d->currentVersion && $d->status !== App\\Enums\\DocumentStatus::NOT_REQUIRED) { $d->transition(App\\Enums\\DocumentStatus::NOT_REQUIRED, $id, 'QA: not uploaded in this run'); } } echo 'ok';`); // QA shortcut: waive what the student journey did not upload

  // student signs in and pays the service fee: through checkout and a signed webhook when the Stripe stand-in is
  // running (ops/qa/fake-stripe.php), otherwise by a labelled local shortcut
  const student = await (await b.newContext({ viewport: { width: 390, height: 844 } })).newPage(); watch(student);
  await student.goto(base + '/login'); await student.fill('#email', info.email); await student.fill('#password', 'Longpass12345'); await student.click('button[type=submit]'); await student.waitForLoadState('networkidle');
  await student.goto(`${base}/portal/${info.no}/payments`, { waitUntil: 'networkidle' });
  const payButton = student.locator('button:has-text("Continue to secure payment")');
  if (await payButton.count() && await payButton.isEnabled()) {
    await student.check('input[name=accept_terms]'); await payButton.click(); await student.waitForLoadState('networkidle');
    const store = process.env.FAKE_STRIPE_STORE || require('os').tmpdir() + '/fake-stripe-sessions.json';
    const last = Object.values(JSON.parse(require('fs').readFileSync(store, 'utf8'))).pop();
    const payload = JSON.stringify({ id: 'evt_staffqa_' + Date.now(), object: 'event', type: 'checkout.session.completed', api_version: '2024-06-20', created: Math.floor(Date.now() / 1000), livemode: false,
      data: { object: { object: 'checkout.session', id: last.id, payment_status: 'paid', amount_total: last.amount_total, currency: last.currency, payment_intent: 'pi_' + last.id, metadata: last.metadata } } });
    const ts = Math.floor(Date.now() / 1000); const sig = require('crypto').createHmac('sha256', process.env.STRIPE_WEBHOOK_SECRET || 'whsec_local_qa').update(`${ts}.${payload}`).digest('hex');
    log.payment = (await student.request.post(base + '/webhooks/stripe', { data: payload, headers: { 'Stripe-Signature': `t=${ts},v1=${sig}`, 'Content-Type': 'application/json' } })).status() + ' via webhook';
  } else {
    tinker(`$a = App\\Models\\Application::where('application_number','${info.no}')->first(); $a->payments()->create(['tier_price_id' => $a->tier->priceFor('full')->id, 'status' => 'SUCCEEDED', 'amount_minor' => $a->tier->priceFor('full')->amount_minor, 'currency' => 'GBP', 'method' => 'MANUAL_TRANSFER', 'note' => 'QA shortcut', 'succeeded_at' => now()]); echo 'ok';`);
    log.payment = 'QA shortcut (card payments not enabled on this server)';
  }

  // QA shortcut (local only): the student journey fills one step; mark every form section complete so the approval gate can open
  tinker(`$a = App\\Models\\Application::where('application_number','${info.no}')->first(); $a->forceFill(['section_status' => array_fill_keys(array_keys(App\\Services\\Applications\\FormSteps::all()), 'complete')])->save(); echo 'ok';`);

  // propose a submission and send it for the student's approval
  await admin.goto(show, { waitUntil: 'networkidle' });
  await admin.click('summary:has-text("Propose a submission target")');
  const propose = admin.locator('form[action$="/submissions"]');
  const uni = await propose.locator('select[name=university_id] option').nth(1).getAttribute('value');
  await propose.locator('select[name=university_id]').selectOption(uni);
  await propose.locator('[name=intake]').fill('September 2028');
  await propose.locator('select[name=route_code]').selectOption('UCAS_STUDENT');
  await propose.locator('[name=choices]').fill('A100 Medicine');
  await propose.locator('button').click(); await admin.waitForLoadState('networkidle'); await flash(admin, 'propose');
  const stage = admin.locator('form[action$="/stage"]');
  await stage.locator('select[name=stage_override]').selectOption('READY_FOR_STUDENT_APPROVAL'); await stage.locator('button').click(); await admin.waitForLoadState('networkidle'); await flash(admin, 'ready for approval');
  await admin.screenshot({ path: `${out}/s-admin-ready.png`, fullPage: true });

  // student: approve the exact package
  current = student;
  await student.goto(`${base}/portal/${info.no}/approve`, { waitUntil: 'networkidle' }); log.approvePage = student.url();
  await student.screenshot({ path: `${out}/s-approve.png`, fullPage: true });
  await student.fill('[name=typed_name]', info.name); await student.check('[name=confirm]');
  await student.click('button:has-text("Approve and authorise")'); await student.waitForLoadState('networkidle'); await flash(student, 'student approval');

  // admin: package ready, then submitted with a reference
  for (const status of ['PACKAGE_READY', 'SUBMITTED']) {
    await admin.goto(show, { waitUntil: 'networkidle' });
    const upd = admin.locator('form[action*="/submissions/"]').last(); // the newest submission
    await upd.locator('select[name=status]').selectOption(status);
    if (status === 'SUBMITTED') await upd.locator('[name=external_reference]').fill('UCAS-QA-001');
    await upd.locator('button').click(); await admin.waitForLoadState('networkidle'); await flash(admin, 'submission ' + status);
  }

  // student: tracking
  await student.goto(`${base}/portal/${info.no}/submissions`, { waitUntil: 'networkidle' });
  log.studentTracking = (await student.locator('main').innerText()).replace(/\s+/g, ' ').slice(0, 400);
  await student.screenshot({ path: `${out}/s-tracking.png`, fullPage: true });
  log.status = tinker(`$s = App\\Models\\Application::where('application_number','${info.no}')->first()->submissions()->latest('id')->first(); echo $s?->status.' '.$s?->external_reference.' auth:'.($s?->authorisation_id ? 'yes' : 'no');`);
  log.problems = problems;
  console.log(JSON.stringify(log, null, 1));
  await b.close();
})().catch(async (e) => {
  log.failedAt = current ? current.url() : 'before the browser opened'; log.error = String(e.message || e).split('\n')[0]; log.problems = problems;
  if (current) await current.screenshot({ path: `${out}/s-failure.png`, fullPage: true }).catch(() => {});
  console.log(JSON.stringify(log, null, 1)); process.exit(1);
});
