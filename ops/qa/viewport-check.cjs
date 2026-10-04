// Horizontal overflow at four widths (narrow phone 320, phone 390, tablet 768, desktop 1366) on public pages, the
// student portal and the admin, with the elements that stick out. Local demo accounts only; never against production.
//   node ops/qa/viewport-check.cjs <admin-totp-secret> [out-dir-for-screenshots]
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const { execSync } = require('child_process');
const totp = (secret) => execSync(`php artisan tinker --execute="echo App\\\\Support\\\\Totp::code('${secret}');"`).toString().trim();
(async () => {
  const base = 'http://127.0.0.1:8000'; const secret = process.argv[2]; const out = process.argv[3];
  const APP = process.env.APP_NO || 'SMUKN-' + (new Date().getFullYear() + 2) + '-000001';
  const widths = [320, 390, 768, 1366];
  const pub = ['/', '/study-medicine-in-the-uk', '/study-medicine-in-the-uk/from-nigeria', '/medical-schools', '/medical-schools/university-of-leicester', '/requirements', '/requirements/waec', '/fees', '/admissions/ucat', '/faq', '/apply-online', '/apply-online/eligibility', '/apply-online/services', '/contact', '/privacy', '/refund-policy', '/login', '/register', '/password/forgot', '/no-such-page'];
  const portal = ['/portal', '/portal/profile', `/portal/${APP}/application`, `/portal/${APP}/application/personal`, `/portal/${APP}/documents`, `/portal/${APP}/payments`, `/portal/${APP}/submissions`, `/portal/${APP}/messages`];
  const admin = ['/admin', '/admin/applications', `/admin/applications/${APP}`, '/admin/payments', '/admin/verification', '/admin/universities', '/admin/users', '/admin/funnel'];
  const b = await chromium.launch({ executablePath: process.env.CHROME_PATH || undefined });
  const problems = []; let checked = 0;
  const check = async (page, path, w) => {
    const r = await page.evaluate(() => {
      const vw = document.documentElement.clientWidth; const sw = document.documentElement.scrollWidth;
      const culprits = [];
      if (sw > vw + 1) for (const el of document.querySelectorAll('body *')) {
        const rect = el.getBoundingClientRect(); const cs = getComputedStyle(el);
        if (rect.right > vw + 1 && rect.width > 0 && cs.position !== 'fixed' && !el.closest('[class*="overflow-x-auto"], .table-wrap, pre')) culprits.push((el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : '')).slice(0, 90) + ` (${Math.round(rect.right)}px)`);
      }
      return { vw, sw, culprits: culprits.slice(0, 4) };
    });
    checked++;
    if (r.sw > r.vw + 1) problems.push({ path, width: w, scrollWidth: r.sw, culprits: r.culprits });
    if (out && (w === 320 || w === 768)) await page.screenshot({ path: `${out}/vp-${w}-${path.replace(/\W+/g, '_') || 'home'}.png` });
  };
  const run = async (ctx, paths) => { const page = await ctx.newPage(); for (const w of widths) { await page.setViewportSize({ width: w, height: 900 }); for (const p of paths) { await page.goto(base + p, { waitUntil: 'networkidle' }); await check(page, p, w); } } };

  let ctx = await b.newContext(); await run(ctx, pub); await ctx.close();
  ctx = await b.newContext(); let page = await ctx.newPage();
  await page.goto(base + '/login'); await page.fill('#email', 'student@example.test'); await page.fill('#password', 'Testpass12345'); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  await run(ctx, portal); await ctx.close();
  ctx = await b.newContext(); page = await ctx.newPage();
  await page.goto(base + '/login'); await page.fill('#email', 'admin@example.test'); await page.fill('#password', 'Adminpass12345'); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  await page.fill('#code', totp(secret)); await page.click('button[type=submit]'); await page.waitForLoadState('networkidle');
  await run(ctx, admin); await ctx.close();
  await b.close();
  console.log(JSON.stringify({ checked, problems }, null, 1));
  process.exit(problems.length ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
