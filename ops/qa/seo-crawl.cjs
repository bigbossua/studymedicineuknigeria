// Crawl every internal public URL reachable from the home page and the sitemap; report SEO/QA defects.
const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
(async () => {
  const base = 'http://127.0.0.1:8000'; const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 } }); const page = await ctx.newPage();
  const seen = new Map(); const queue = ['/']; const issues = []; const linkTargets = new Map();
  const sm = await (await ctx.request.get(base + '/sitemap.xml')).text(); (sm.match(/<loc>([^<]+)<\/loc>/g) || []).forEach(l => queue.push(new URL(l.replace(/<\/?loc>/g, '')).pathname));
  while (queue.length && seen.size < 150) {
    const path = queue.shift(); if (seen.has(path)) continue;
    const res = await page.goto(base + path, { waitUntil: 'domcontentloaded' }).catch(() => null); if (!res) { seen.set(path, { status: 'ERR' }); continue; }
    const status = res.status(); const info = { status };
    if (status === 200 && (res.headers()['content-type'] || '').includes('text/html')) {
      const d = await page.evaluate(() => ({
        title: document.title, desc: document.querySelector('meta[name=description]')?.content || '', canonical: document.querySelector('link[rel=canonical]')?.href || '',
        robots: document.querySelector('meta[name=robots]')?.content || '', h1: [...document.querySelectorAll('h1')].map(h => h.textContent.trim()),
        imgsNoAlt: [...document.querySelectorAll('img:not([alt])')].length, links: [...document.querySelectorAll('a[href]')].map(a => a.getAttribute('href')),
        jsonld: [...document.querySelectorAll('script[type="application/ld+json"]')].length, words: document.querySelector('main')?.innerText.split(/\s+/).length || 0,
        internalLinks: [...document.querySelectorAll('main a[href]')].filter(a => a.href.startsWith(location.origin)).length, emptyLinks: [...document.querySelectorAll('a[href="#"], a[href=""]')].length,
        lastVerified: /last verified|verified on|checked on/i.test(document.body.innerText), ogImage: document.querySelector('meta[property="og:image"]')?.content || '',
      }));
      Object.assign(info, d);
      const noindex = /noindex/.test(d.robots);
      if (!noindex) {
        if (d.title.length > 65) issues.push([path, 'title > 65 chars (' + d.title.length + ')', d.title]);
        if (d.title.length < 25) issues.push([path, 'title < 25 chars', d.title]);
        if (d.desc.length > 165) issues.push([path, 'description > 165 chars (' + d.desc.length + ')']);
        if (d.desc.length < 70) issues.push([path, 'description < 70 chars (' + d.desc.length + ')', d.desc]);
        if (d.h1.length !== 1) issues.push([path, 'h1 count ' + d.h1.length, d.h1.join(' | ')]);
        if (d.imgsNoAlt) issues.push([path, 'images without alt: ' + d.imgsNoAlt]);
        if (d.jsonld === 0) issues.push([path, 'no JSON-LD']);
        if (d.words < 250) issues.push([path, 'thin: ' + d.words + ' words in <main>']);
        if (d.internalLinks < 3) issues.push([path, 'few internal links in main: ' + d.internalLinks]);
        if (d.emptyLinks) issues.push([path, 'empty/# links: ' + d.emptyLinks]);
        if (d.canonical !== base + path && d.canonical !== 'https://studymedicineuknigeria.com' + path) issues.push([path, 'canonical mismatch', d.canonical]);
      }
      for (const href of d.links) {
        if (!href || href.startsWith('#') || /^(mailto|tel|https?|otpauth|javascript):/.test(href)) continue;
        const p = href.split('#')[0].split('?')[0]; if (!p) continue;
        linkTargets.set(p, (linkTargets.get(p) || []).concat(path));
        if (!seen.has(p) && !queue.includes(p) && !/^\/(portal|admin|logout|two-factor)/.test(p)) queue.push(p);
      }
    }
    seen.set(path, info);
  }
  // titles duplicated across indexable pages
  const titles = {}; for (const [p, i] of seen) if (i.status === 200 && i.title && !/noindex/.test(i.robots || '')) (titles[i.title] = titles[i.title] || []).push(p);
  for (const [t, ps] of Object.entries(titles)) if (ps.length > 1) issues.push([ps.join(' , '), 'duplicate title', t]);
  const broken = [...seen].filter(([p, i]) => i.status >= 400 || i.status === 'ERR').map(([p, i]) => [p, 'status ' + i.status, 'linked from: ' + [...new Set(linkTargets.get(p) || [])].slice(0, 3).join(', ')]);
  const indexable = [...seen].filter(([p, i]) => i.status === 200 && i.title && !/noindex/.test(i.robots || '')).map(([p]) => p);
  const smPaths = (sm.match(/<loc>([^<]+)<\/loc>/g) || []).map(l => new URL(l.replace(/<\/?loc>/g, '')).pathname);
  const notInSitemap = indexable.filter(p => !smPaths.includes(p)); const inSitemapNoindex = smPaths.filter(p => seen.get(p) && /noindex/.test(seen.get(p).robots || ''));
  console.log(JSON.stringify({ crawled: seen.size, indexable: indexable.length, sitemap: smPaths.length, notInSitemap, inSitemapNoindex, broken, issues }, null, 1));
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
