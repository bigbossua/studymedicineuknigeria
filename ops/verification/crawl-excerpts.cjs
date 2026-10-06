// Finds official pages the reviewer needs (Nigeria country page, Medicine fees, English requirements) by following links
// on the university's own site, then prints excerpts in the page-excerpts.cjs format (=== url / final / status / --- [term]).
// Read-only; stays on the starting site's domain; at most MAX pages per line, two link hops.
// Spec, one university per line:   slug | start-url, start-url | term; term || follow: word; word
//   node ops/verification/crawl-excerpts.cjs spec.txt [context-chars] [max-pages]
const fs = require('fs');
const crypto = require('crypto');
const { chromium } = require('playwright');
const base = (u) => { try { return new URL(u).hostname.split('.').slice(-3).join('.'); } catch { return ''; } };
(async () => {
  const spec = fs.readFileSync(process.argv[2], 'utf8').split('\n').map((l) => l.trim()).filter((l) => l && !l.startsWith('#'));
  const ctxChars = parseInt(process.argv[3] || '300', 10);
  const MAX = parseInt(process.argv[4] || '14', 10);
  const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : { channel: process.env.CHROME_CHANNEL || 'chrome' });
  const ctx = await browser.newContext({ locale: 'en-GB', userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36' });
  const checked = new Date().toISOString().slice(0, 10);
  for (const line of spec) {
    const [main, followPart = ''] = line.split('||').map((s) => s.trim());
    const [slug, starts = '', termList = ''] = main.split('|').map((s) => s.trim());
    const terms = termList.split(';').map((t) => t.trim()).filter(Boolean);
    const follow = followPart.replace(/^follow:\s*/i, '').split(';').map((t) => t.trim().toLowerCase()).filter(Boolean);
    const queue = starts.split(',').map((u) => [u.trim(), 0]).filter(([u]) => u);
    const domains = new Set(queue.map(([u]) => base(u)));
    const seen = new Set(); let n = 0;
    console.log(`\n##### ${slug}`);
    while (queue.length && n < MAX) {
      const [url, depth] = queue.shift();
      if (seen.has(url)) continue; seen.add(url); n++;
      const page = await ctx.newPage();
      let status = 0; let text = ''; let finalUrl = url; let title = ''; let links = [];
      try {
        if (/\.pdf($|\?)/i.test(url)) {
          const r = await ctx.request.get(url, { timeout: 45000 }); status = r.status(); title = 'PDF';
          text = (await require('pdf-parse')(await r.body())).text.replace(/[ \t ]+/g, ' ').replace(/\n\s*\n+/g, '\n');
        } else {
          const r = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
          await page.waitForTimeout(2000);
          status = r ? r.status() : 0; finalUrl = page.url(); title = await page.title();
          links = await page.evaluate(() => [...document.querySelectorAll('a[href]')].map((a) => [(a.innerText || a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 90), a.href.split('#')[0]]));
          await page.evaluate(() => {
            document.querySelectorAll('details').forEach((d) => { d.open = true; });
            const root = document.querySelector('main') || document.body;
            root.querySelectorAll('*').forEach((el) => { if (el.textContent.trim() && getComputedStyle(el).display === 'none' && !['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE'].includes(el.tagName)) el.style.setProperty('display', 'block', 'important'); });
          });
          text = await page.evaluate(() => { const root = document.querySelector('main') || document.body; root.querySelectorAll('script,style,noscript,svg').forEach((x) => x.remove()); return (root.innerText || root.textContent || '').replace(/[ \t ]+/g, ' ').replace(/\n\s*\n+/g, '\n'); });
        }
      } catch (e) { status = -1; title = String(e.message).split('\n')[0]; }
      await page.close();
      const flat = text.replace(/\n/g, ' ⏎ ');
      const hits = terms.filter((t) => new RegExp(t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i').test(flat));
      if (depth < 2) {
        for (const [t, h] of links) {
          if (!/^https?:/.test(h) || !domains.has(base(h)) || seen.has(h)) continue;
          const hay = (t + ' ' + h).toLowerCase();
          if (follow.some((w) => hay.includes(w) || hay.includes(w.replace(/ /g, '-')))) queue.push([h, depth + 1]);
        }
      }
      if (!hits.length && depth > 0) continue; // only pages that carry a term are printed (start pages always are)
      const sha = crypto.createHash('sha256').update(text).digest('hex').slice(0, 16);
      console.log(`\n=== ${url}\nfinal: ${finalUrl}\nstatus: ${status} | title: ${title} | chars: ${text.length} | sha256: ${sha} | read: ${checked} | slug: ${slug} | depth: ${depth}`);
      for (const term of hits) {
        const re = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
        let m; let k = 0; const at = [];
        while ((m = re.exec(flat)) && k < 3) { if (at.some((s) => Math.abs(s - m.index) < ctxChars)) continue; at.push(m.index); k++; console.log(`--- [${term}] …${flat.slice(Math.max(0, m.index - ctxChars), m.index + term.length + ctxChars)}…`); }
      }
    }
  }
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
