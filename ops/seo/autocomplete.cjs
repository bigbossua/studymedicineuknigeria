// Google autocomplete suggestions as Nigerian users see them (gl=ng, hl=en): evidence that people search for a school or
// topic from Nigeria. Read-only, public endpoint, one request per query with a pause. Output: one JSON line per query
//   {"q": "...", "gl": "ng", "suggestions": [...], "read": "YYYY-MM-DD"}
// Spec: one query per line; a line ending in " *" is also expanded with " a" … " z".
//   node ops/seo/autocomplete.cjs queries.txt
const fs = require('fs');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
(async () => {
  const lines = fs.readFileSync(process.argv[2], 'utf8').split('\n').map((l) => l.trim()).filter((l) => l && !l.startsWith('#'));
  const queries = [];
  for (const l of lines) {
    if (l.endsWith(' *')) { const b = l.slice(0, -2); queries.push(b, ...'abcdefghijklmnopqrstuvwxyz'.split('').map((c) => `${b} ${c}`)); } else queries.push(l);
  }
  const read = new Date().toISOString().slice(0, 10);
  for (const q of queries) {
    let suggestions = []; let status = 0;
    try {
      const r = await fetch(`https://suggestqueries.google.com/complete/search?client=firefox&hl=en&gl=ng&q=${encodeURIComponent(q)}`, { headers: { 'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101 Firefox/130.0', 'Accept-Language': 'en-NG,en;q=0.8' } });
      status = r.status;
      if (r.ok) suggestions = (JSON.parse(Buffer.from(await r.arrayBuffer()).toString('latin1'))[1] || []);
    } catch (e) { status = -1; }
    console.log(JSON.stringify({ q, gl: 'ng', status, suggestions, read }));
    await sleep(350);
  }
})();
