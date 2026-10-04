#!/usr/bin/env python3
"""Google-readiness audit of one site, read-only (docs/seo/GOOGLE-READINESS.md).

Checks robots.txt, the sitemap, every sitemap page (status, one self-referencing absolute canonical, robots meta and
X-Robots-Tag, unique title and description, one H1 and a heading order without skipped levels, JSON-LD that parses
and carries a BreadcrumbList below the home page, image alt text, generic anchor text, content present without
JavaScript), internal reachability from the home page, URL variants (http, www, trailing slash, upper case), the old
site's redirects and retired URLs, private URLs (never in the sitemap, never indexable), and basic page weight and
asset caching.

Usage:
  python3 ops/seo/google-audit.py                                     # the live site
  python3 ops/seo/google-audit.py --connect http://127.0.0.1:8090    # a production-mode server on this machine
                                                                       (ops/production-rehearsal.sh, REHEARSAL_KEEP=1)
  --json out.json writes every finding. Exit 1 when anything FAILs.
"""
import argparse, csv, json, re, sys, urllib.request, urllib.error
from collections import deque
from html.parser import HTMLParser
from urllib.parse import urlsplit, urljoin

PRIVATE = ['/login', '/register', '/portal', '/admin', '/password/forgot', '/email/verify', '/two-factor/challenge',
           '/apply-online/start/T1', '/portal/applications', '/admin/applications']
GENERIC_ANCHORS = {'click here', 'here', 'read more', 'more', 'this page', 'link', 'learn more'}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


OPENER = urllib.request.build_opener(NoRedirect)


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title, self.desc, self.robots, self.canonicals, self.h, self.ld, self.links, self.imgs = '', None, None, [], [], [], [], []
        self._in = None; self._buf = ''; self._a = None; self._script_ld = False; self._skip = 0; self.text = []; self._main = 0

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag in ('script', 'style', 'noscript'):
            self._skip += 1
            if tag == 'script' and a.get('type') == 'application/ld+json':
                self._script_ld = True; self._buf = ''
        if tag == 'main':
            self._main += 1
        if tag == 'title' or re.fullmatch(r'h[1-6]', tag):
            self._in = tag; self._buf = ''
        if tag == 'meta' and a.get('name') == 'description':
            self.desc = a.get('content') or ''
        if tag == 'meta' and a.get('name') == 'robots':
            self.robots = a.get('content') or ''
        if tag == 'link' and a.get('rel') == 'canonical':
            self.canonicals.append(a.get('href') or '')
        if tag == 'a' and a.get('href'):
            self._a = {'href': a['href'], 'text': '', 'label': a.get('aria-label'), 'in_main': self._main > 0}
        if tag == 'img':
            self.imgs.append(a)

    def handle_endtag(self, tag):
        if tag in ('script', 'style', 'noscript'):
            self._skip = max(0, self._skip - 1)
            if tag == 'script' and self._script_ld:
                self.ld.append(self._buf); self._script_ld = False
        if tag == 'main':
            self._main -= 1
        if tag == self._in:
            text = ' '.join(self._buf.split())
            if tag == 'title':
                self.title = text
            else:
                self.h.append((int(tag[1]), text))
            self._in = None
        if tag == 'a' and self._a:
            self._a['text'] = ' '.join(self._a['text'].split()); self.links.append(self._a); self._a = None

    def handle_data(self, data):
        if self._script_ld:
            self._buf += data; return
        if self._skip:
            return
        if self._in:
            self._buf += data
        if self._a is not None:
            self._a['text'] += data
        if self._main:
            self.text.append(data)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('base', nargs='?', default='https://studymedicineuknigeria.com')
    ap.add_argument('--connect', help='send requests to this origin with the base host in the Host header')
    ap.add_argument('--json')
    args = ap.parse_args()
    base = args.base.rstrip('/'); host = urlsplit(base).netloc
    home = lambda u: u.rstrip('/') if u.rstrip('/') == base else u  # the home URL with or without its slash is one URL
    rows = []  # (area, check, status, detail)

    def rec(area, check, ok, detail=''):
        status = ok if isinstance(ok, str) else ('PASS' if ok else 'FAIL')
        rows.append({'area': area, 'check': check, 'status': status, 'detail': detail})

    def get(url, method='GET'):
        """Fetch without following redirects. url is absolute on the audited site (any scheme / host variant)."""
        parts = urlsplit(url)
        if args.connect:
            target = args.connect.rstrip('/') + (parts.path or '/') + (('?' + parts.query) if parts.query else '')
            req = urllib.request.Request(target, method=method, headers={'Host': parts.netloc, 'X-Forwarded-Proto': parts.scheme, 'User-Agent': 'smukn-google-audit'})
        else:
            req = urllib.request.Request(url, method=method, headers={'User-Agent': 'Mozilla/5.0 (compatible; smukn-google-audit)'})
        try:
            r = OPENER.open(req, timeout=30)
            return r.status, {k.lower(): v for k, v in r.headers.items()}, r.read().decode('utf-8', 'replace')
        except urllib.error.HTTPError as e:
            return e.code, {k.lower(): v for k, v in e.headers.items()}, e.read().decode('utf-8', 'replace')
        except Exception as e:  # connection closed (e.g. a CDN refusing a host) counts as status 0
            return 0, {}, str(e)

    # robots.txt
    st, hd, robots = get(base + '/robots.txt')
    rec('robots', 'robots.txt answers 200 as text/plain', st == 200 and 'text/plain' in hd.get('content-type', ''), f'{st} {hd.get("content-type")}')
    disallow = [l.split(':', 1)[1].strip() for l in robots.splitlines() if l.lower().startswith('disallow:')]
    rec('robots', 'robots.txt does not block the whole site', '/' not in disallow, ', '.join(disallow))
    rec('robots', 'robots.txt names the production sitemap', f'Sitemap: {base}/sitemap.xml' in robots)

    def blocked(path):
        for d in disallow:
            if not d:
                continue
            rx = '^' + re.escape(d).replace(r'\*', '.*').replace(r'\$', '$')
            if re.match(rx, path):
                return d
        return None

    # sitemap
    st, hd, sm = get(base + '/sitemap.xml')
    urls = [home(u) for u in re.findall(r'<loc>([^<]+)</loc>', sm)]
    rec('sitemap', 'sitemap.xml answers 200 as XML', st == 200 and 'xml' in hd.get('content-type', ''), f'{st} {hd.get("content-type")}')
    rec('sitemap', 'every sitemap URL is on the production origin', all(u.startswith(base + '/') or u == base for u in urls), f'{len(urls)} URLs')
    rec('sitemap', 'no sitemap URL has a query string, fragment or trailing slash', all('?' not in u and '#' not in u and not u.endswith('/') for u in urls))
    rec('sitemap', 'no duplicate sitemap URLs', len(urls) == len(set(urls)))
    rec('sitemap', 'no sitemap URL is blocked by robots.txt', not [u for u in urls if blocked(urlsplit(u).path)], ', '.join(u for u in urls if blocked(urlsplit(u).path)))
    rec('sitemap', 'no private URL in the sitemap', not [u for u in urls if any(urlsplit(u).path.startswith(p) for p in ['/login', '/register', '/portal', '/admin', '/password', '/email', '/two-factor', '/apply-online/start', '/webhooks'])])
    lastmods = re.findall(r'<lastmod>([^<]+)</lastmod>', sm)
    rec('sitemap', 'every sitemap URL has a W3C lastmod date', len(lastmods) == len(urls) and all(re.fullmatch(r'\d{4}-\d{2}-\d{2}', d) for d in lastmods), f'{len(lastmods)}/{len(urls)}')

    # every sitemap page
    titles, descs, pages = {}, {}, {}
    for u in urls:
        st, hd, body = get(u)
        p = Page(); p.feed(body); pages[u] = p
        path = urlsplit(u).path or '/'
        issues = []
        if st != 200: issues.append(f'status {st}')
        xr = hd.get('x-robots-tag', '')
        if 'noindex' in xr or 'none' in xr: issues.append(f'X-Robots-Tag {xr}')
        if not p.robots or 'noindex' in p.robots or 'nofollow' in p.robots: issues.append(f'robots meta "{p.robots}"')
        if len(p.canonicals) != 1: issues.append(f'{len(p.canonicals)} canonicals')
        elif home(p.canonicals[0]) != u: issues.append(f'canonical {p.canonicals[0]}')
        h1 = [t for lvl, t in p.h if lvl == 1]
        if len(h1) != 1: issues.append(f'{len(h1)} H1')
        last = 1; skips = []
        for lvl, t in p.h:
            if lvl > last + 1: skips.append(f'h{last}→h{lvl} "{t[:40]}"')
            last = lvl
        if skips: issues.append('heading skip ' + '; '.join(skips[:2]))
        if not (20 <= len(p.title) <= 70): issues.append(f'title length {len(p.title)}')
        if not p.desc or not (100 <= len(p.desc) <= 165): issues.append(f'description length {len(p.desc or "")}')
        titles.setdefault(p.title, []).append(path); descs.setdefault(p.desc, []).append(path)
        types = []; visible = ' '.join(' '.join(p.text).split())
        for raw in p.ld:
            try:
                d = json.loads(raw); types.append(d.get('@type'))
            except Exception:
                issues.append('invalid JSON-LD'); continue
            if d.get('@type') == 'FAQPage':  # FAQ markup only for questions the reader can see on the page
                hidden = [q['name'] for q in d.get('mainEntity', []) if ' '.join(q['name'].split()) not in visible]
                if hidden: issues.append(f'FAQ question not visible: {hidden[0][:50]}')
            if d.get('@type') in ('Product', 'Offer', 'AggregateRating', 'Review') or 'aggregateRating' in raw or '"offers"' in raw or '"price"' in raw:
                issues.append(f'unsupported claim in JSON-LD ({d.get("@type")})')
        if path != '/' and 'BreadcrumbList' not in types: issues.append('no BreadcrumbList')
        noalt = [i.get('src') for i in p.imgs if i.get('alt') is None]
        if noalt: issues.append(f'img without alt: {noalt[:2]}')
        generic = [l['text'] for l in p.links if l['text'].lower() in GENERIC_ANCHORS and not l['label']]
        if generic: issues.append(f'generic anchor text: {generic[:3]}')
        words = len(' '.join(p.text).split())
        if words < 150: issues.append(f'only {words} words in <main> without JavaScript')
        rec('page', path, not issues, '; '.join(issues) or f'{words} words; JSON-LD: {", ".join(t for t in types if t)}')
    rec('titles', 'every sitemap page has a unique title', all(len(v) == 1 for v in titles.values()), '; '.join(f'{k} {v}' for k, v in titles.items() if len(v) > 1))
    rec('descriptions', 'every sitemap page has a unique description', all(len(v) == 1 for v in descs.values()), '; '.join(f'{v}' for k, v in descs.items() if len(v) > 1))

    # internal reachability: breadth-first from the home page over links inside <main> and the site chrome
    seen, queue, discovered = {base}, deque([base]), set()
    while queue:
        u = queue.popleft()
        p = pages.get(u)
        if p is None:
            st, hd, body = get(u)
            if st != 200 or 'html' not in hd.get('content-type', ''):
                continue
            p = Page(); p.feed(body); pages[u] = p
        for l in p.links:
            href = home(urljoin(u + '/', l['href']).split('#')[0]) if u == base else home(urljoin(u, l['href']).split('#')[0])
            if not href.startswith(base) or '?' in href or href.startswith(base + '/images') or href in seen:
                continue
            seen.add(href); discovered.add(href)
            if href in urls:
                queue.append(href)
    unreachable = [u for u in urls if u not in seen and u != base]
    rec('links', 'every sitemap page is reachable by links from the home page', not unreachable, ', '.join(unreachable))
    inbound = {u: 0 for u in urls}
    for src, p in pages.items():
        for l in p.links:
            href = home(urljoin(src, l['href']).split('#')[0])
            if href in inbound and href != src and l['in_main']:
                inbound[href] += 1
    weak = [f'{urlsplit(u).path} ({n})' for u, n in inbound.items() if n < 2 and u != base]
    rec('links', 'every sitemap page has at least 2 in-body links from other pages', not weak, ', '.join(weak))

    # URL variants
    sample = [base, base + '/study-medicine-in-the-uk', base + '/medical-schools', base + '/fees']
    for u in sample:
        path = urlsplit(u).path or '/'
        if args.connect:  # the http→https redirect is the host's (.htaccess / CDN), so only the live site can show it
            rec('variants', f'http:// {path} → 301 https', 'NOT YET AVAILABLE', 'checked against the live site only')
        else:
            st, hd, _ = get('http://' + host + path)
            rec('variants', f'http:// {path} → 301 https', st == 301 and hd.get('location', '').startswith(base), f'{st} {hd.get("location")}')
        st, hd, _ = get('https://www.' + host + path)
        rec('variants', f'www {path} → 301 apex', st == 301 and hd.get('location', '').startswith(base), f'{st} {hd.get("location")}')
        if path != '/':
            st, hd, _ = get(u + '/')
            rec('variants', f'{path}/ → 301 without the slash', st == 301 and hd.get('location', '') == u, f'{st} {hd.get("location")}')
            st, hd, _ = get(base + path.upper())
            rec('variants', f'{path.upper()} is not a duplicate (301 or 404)', st in (301, 404), f'{st} {hd.get("location", "")}')
    st, hd, _ = get(base + '/index.php')
    rec('variants', '/index.php is not a duplicate of the home page', st in (301, 404) or (st == 200 and False), f'{st} {hd.get("location", "")}')

    # the old site's URLs
    try:
        bad = []
        with open('data/seo/legacy-redirects.csv') as f:
            for row in csv.DictReader(f):
                st, hd, _ = get(base + row['from_path'])
                if not (st == 301 and home(hd.get('location') or '') == home(base + row['to_path'])):
                    bad.append(f'{row["from_path"]} {st} {hd.get("location")}')
        rec('redirects', 'every old-site URL answers 301 to its page', not bad, '; '.join(bad[:5]))
        gone = [l.strip() for l in open('data/seo/legacy-gone.txt') if l.strip() and not l.startswith('#')]
        badg = [g for g in gone if get(base + g)[0] != 404]
        rec('redirects', 'retired old-site URLs answer 404', not badg, ', '.join(badg))
        # a redirect target must itself be a final, indexable page (no chains)
        targets = {row['to_path'] for row in csv.DictReader(open('data/seo/legacy-redirects.csv'))}
        chains = [t for t in targets if get(base + t)[0] != 200]
        rec('redirects', 'every redirect target answers 200 (no chains)', not chains, ', '.join(chains))
    except FileNotFoundError:
        rec('redirects', 'legacy redirect data present', 'NOT YET AVAILABLE', 'run from the repository root')

    # private URLs
    for path in PRIVATE:
        st, hd, body = get(base + path)
        p = Page(); p.feed(body)
        safe = st in (301, 302, 303, 401, 403, 404, 405) or 'noindex' in hd.get('x-robots-tag', '') or 'noindex' in (p.robots or '')
        rec('private', f'{path} cannot be indexed', safe, f'{st} x-robots-tag="{hd.get("x-robots-tag", "")}" robots="{p.robots}" location={hd.get("location", "")}')

    # weight and caching (home page)
    st, hd, body = get(base + '/')
    rec('performance', 'home HTML under 100 KB', len(body.encode()) < 100_000, f'{len(body.encode()) // 1024} KB')
    assets = re.findall(r'(?:href|src)="(/build/assets/[^"]+)"', body)
    total = 0
    for a in set(assets):
        s2, h2, b2 = get(base + a)
        total += len(b2.encode())
        rec('performance', f'{a.split("/")[-1]} is cached for a year', 'max-age=31536000' in h2.get('cache-control', '') or 'immutable' in h2.get('cache-control', ''), h2.get('cache-control', ''))
    rec('performance', 'CSS + JS under 120 KB uncompressed', total < 120_000, f'{total // 1024} KB')
    third = sorted({urlsplit(x).netloc for x in re.findall(r'(?:src|href)="(https?://[^"]+)"', body) if urlsplit(x).netloc not in (host,)} - {'wa.me'})
    rec('performance', 'no third-party script or stylesheet on the home page', not [t for t in third if t], ', '.join(third))
    rec('security', 'HSTS sent', 'strict-transport-security' in hd, hd.get('strict-transport-security', ''))
    rec('security', 'Content-Security-Policy sent', 'content-security-policy' in hd)

    fails = [r for r in rows if r['status'] == 'FAIL']
    width = max(len(r['check']) for r in rows)
    for r in rows:
        print(f"{r['status']:<5} {r['area']:<12} {r['check']:<{width}}  {r['detail'] if r['status'] != 'PASS' or r['area'] == 'page' else ''}"[:260])
    print(f"\n{len(rows) - len(fails)} passed, {len(fails)} failed ({len(urls)} sitemap URLs, {len(seen)} internal URLs found)")
    if args.json:
        json.dump({'base': base, 'rows': rows, 'sitemap': urls, 'internal': sorted(seen)}, open(args.json, 'w'), indent=1)
    sys.exit(1 if fails else 0)


if __name__ == '__main__':
    main()
