// Reads official pages in a real browser and prints, for each requested term, the exact text around it, so a reviewer
// can verify facts from the page's own words (the container that drafts decisions cannot reach these sites). Read-only.
// Spec, one page per line:   https://official.page/path | term one; term two; ...
//   node ops/verification/page-excerpts.cjs spec.txt [context-chars]
const fs = require('fs');
const crypto = require('crypto');
const { chromium } = require('playwright');
(async () => {
  const spec = fs.readFileSync(process.argv[2], 'utf8').split('\n').map((l) => l.trim()).filter((l) => l && !l.startsWith('#'));
  const ctxChars = parseInt(process.argv[3] || '280', 10);
  const browser = await chromium.launch({ channel: process.env.CHROME_CHANNEL || 'chrome' });
  const ctx = await browser.newContext({ locale: 'en-GB', userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36' });
  const checked = new Date().toISOString().slice(0, 10);
  for (const line of spec) {
    const [url, termList = ''] = line.split('|').map((s) => s.trim());
    const terms = termList.split(';').map((t) => t.trim()).filter(Boolean);
    const page = await ctx.newPage();
    let status = 0; let text = ''; let finalUrl = url; let title = '';
    try {
      if (/\.pdf($|\?)/i.test(url)) {
        // PDFs (admissions statements) are read as text, not rendered
        const r = await ctx.request.get(url, { timeout: 45000 });
        status = r.status(); title = 'PDF';
        const pdf = await require('pdf-parse')(await r.body());
        text = pdf.text.replace(/[ \t\u00a0]+/g, ' ').replace(/\n\s*\n+/g, '\n');
        throw { pdfDone: true };
      }
      const r = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(2500);
      // cookie banners and accordions hide text from innerText; open every <details> and read textContent of the main region
      await page.evaluate(() => document.querySelectorAll('details').forEach((d) => { d.open = true; }));
      status = r ? r.status() : 0; finalUrl = page.url(); title = await page.title();
      text = await page.evaluate(() => {
        const root = document.querySelector('main') || document.body;
        root.querySelectorAll('script,style,noscript,svg').forEach((n) => n.remove());
        return (root.innerText || root.textContent || '').replace(/[ \t ]+/g, ' ').replace(/\n\s*\n+/g, '\n');
      });
    } catch (e) { if (!e.pdfDone) { status = -1; text = ''; title = String(e.message).split('\n')[0]; } }
    await page.close();
    const sha = crypto.createHash('sha256').update(text).digest('hex').slice(0, 16);
    console.log(`\n=== ${url}\nfinal: ${finalUrl}\nstatus: ${status} | title: ${title} | chars: ${text.length} | sha256: ${sha} | read: ${checked}`);
    if (!terms.length && text) console.log(text.slice(0, 4000));
    const flat = text.replace(/\n/g, ' ⏎ ');
    for (const term of terms) {
      const re = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
      let m; let n = 0; const seen = [];
      while ((m = re.exec(flat)) && n < 4) {
        if (seen.some((s) => Math.abs(s - m.index) < ctxChars)) continue;
        seen.push(m.index); n++;
        console.log(`--- [${term}] …${flat.slice(Math.max(0, m.index - ctxChars), m.index + term.length + ctxChars)}…`);
      }
      if (!n) console.log(`--- [${term}] NOT ON PAGE`);
    }
  }
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
