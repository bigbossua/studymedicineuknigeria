#!/usr/bin/env python3
"""Photo sourcing from Unsplash (Unsplash License: free to use, no permission needed; Unsplash+ images are excluded).
Run by .github/workflows/design-photos.yml on a GitHub runner (this repository's sessions cannot reach Unsplash).

  search "query one|query two"   → brand/photos/candidates/<n>-<query>.jpg contact sheets + candidates.json
  fetch  "slug=photoId,slug=id"   → brand/photos/<slug>.jpg (2400 px originals) + brand/photos/fetched.json (credit, link)
  probe  -                        → which Unsplash hosts answer from the runner
With UNSPLASH_ACCESS_KEY set (repository secret) the official API is used; without it, the public pages.

Nothing is published by this script: a photograph reaches the site only when it is added to brand/photos/manifest.json
with its alt text, credit, source URL and licence, and `php artisan smukn:images` builds its derivatives.
"""
import io, json, os, re, subprocess, sys, urllib.parse, urllib.request
from PIL import Image, ImageDraw

UA = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36', 'Accept': 'text/html,application/json', 'Accept-Language': 'en-GB'}
import html as htmlmod
OUT = 'brand/photos'


def get(url, binary=False, text=False):
    # curl first: unsplash.com answered 401 to Python's own HTTP client from the runner
    args = ['curl', '-sSfL', '--compressed', '--max-time', '40'] + [x for k, v in UA.items() for x in ('-H', f'{k}: {v}')] + [url]
    r = subprocess.run(args, capture_output=True)
    if r.returncode == 0:
        data = r.stdout
    else:
        print(f'curl {url}: exit {r.returncode} {r.stderr.decode()[:200]}', file=sys.stderr)
        with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=40) as resp:
            data = resp.read()
    return data if binary else data.decode('utf-8', 'replace') if text else json.loads(data)


KEY = os.environ.get('UNSPLASH_ACCESS_KEY', '')
API = 'https://api.unsplash.com'


def api(path):
    """Official API (free developer app key). unsplash.com itself answers 401 to GitHub runners."""
    r = subprocess.run(['curl', '-sSf', '--max-time', '40', '-H', f'Authorization: Client-ID {KEY}', '-H', 'Accept-Version: v1', API + path], capture_output=True)
    if r.returncode:
        sys.exit(f'Unsplash API {path.split("?")[0]}: curl exit {r.returncode} {r.stderr.decode()[:200]}')
    return json.loads(r.stdout)


def search_api(q):
    res = api('/search/photos?per_page=20&orientation=landscape&content_filter=high&query=' + urllib.parse.quote(q))['results']
    return [{'id': p['id'], 'slug': p.get('slug') or p['id'], 'base': p['urls']['raw'].split('?')[0], 'alt': p.get('alt_description') or '', 'by': p['user']['name']}
            for p in res if free(p)]


def probe(_):
    """Which Unsplash hosts answer from here (status and the first bytes), to tell blocking from a missing key."""
    for u in ['https://unsplash.com/', 'https://unsplash.com/photos/00heEp9LFP0', API + '/photos/random', 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?w=40']:
        r = subprocess.run(['curl', '-s', '-o', '/tmp/probe', '-w', '%{http_code}', '--max-time', '20', u], capture_output=True, text=True)
        body = open('/tmp/probe', 'rb').read()[:160] if os.path.exists('/tmp/probe') else b''
        print(u, r.stdout, body)


def search_html(q):
    """Free photos from the public search page (the JSON API needs a key): id, slug, alt, CDN base, Unsplash+ excluded."""
    page = get('https://unsplash.com/s/photos/' + urllib.parse.quote(q.replace(' ', '-')) + '?orientation=landscape&license=free', text=True)
    out, seen = [], set()
    for block in page.split('<figure')[1:]:
        link = re.search(r'href="/photos/([a-z0-9-]+)"', block)
        img = re.search(r'src="(https://images\.unsplash\.com/photo-[^"?]+)', block)
        if not link or not img or 'plus.unsplash.com' in block or 'premium_photo' in block:
            continue
        slug = link.group(1); pid = slug.rsplit('-', 1)[-1]
        if pid in seen:
            continue
        seen.add(pid)
        alt = re.search(r'alt="([^"]*)"', block)
        out.append({'id': pid, 'slug': slug, 'base': img.group(1), 'alt': htmlmod.unescape(alt.group(1)) if alt else ''})
    return out


def free(p):
    return not p.get('premium') and not p.get('plus') and 'plus.unsplash.com' not in json.dumps(p.get('urls', {}))


def search(queries):
    os.makedirs(f'{OUT}/candidates', exist_ok=True)
    found = {}
    for qi, q in enumerate(queries):
        if not KEY and os.environ.get('GITHUB_ACTIONS'):
            sys.exit('::error::UNSPLASH_ACCESS_KEY is not set: unsplash.com refuses GitHub runners, so only the API works here')
        photos = (search_api(q) if KEY else search_html(q))[:16]
        thumbs = []
        for p in photos:
            try:
                im = Image.open(io.BytesIO(get(p['base'] + '?w=400&q=70&fm=jpg', True))).convert('RGB'); im.thumbnail((400, 270)); thumbs.append((p, im))
            except Exception as e:
                print('skip', p['id'], e)
        cols, cw, ch = 4, 400, 300
        sheet = Image.new('RGB', (cols * cw, ((len(thumbs) + cols - 1) // cols) * ch), 'white')
        d = ImageDraw.Draw(sheet)
        for i, (p, im) in enumerate(thumbs):
            x, y = (i % cols) * cw, (i // cols) * ch
            sheet.paste(im, (x + (cw - im.width) // 2, y))
            d.rectangle([x, y + 270, x + cw, y + 300], fill='black'); d.text((x + 6, y + 278), f"{i + 1}. {p['id']}", fill='white')
            found[p['id']] = {'query': q, 'n': i + 1, 'alt': p['alt'], 'by': p.get('by', ''), 'link': 'https://unsplash.com/photos/' + p['slug']}
        slug = re.sub(r'[^a-z0-9]+', '-', q.lower()).strip('-')
        sheet.save(f'{OUT}/candidates/{qi + 1:02d}-{slug}.jpg', quality=80)
        print(f'{q}: {len(thumbs)} free photos')
    json.dump(found, open(f'{OUT}/candidates/candidates.json', 'w'), indent=1)


def fetch(picks):
    meta = json.load(open(f'{OUT}/fetched.json')) if os.path.exists(f'{OUT}/fetched.json') else {}
    for pick in picks:
        slug, pid = pick.split('=')
        if KEY:
            p = api(f'/photos/{pid}')
            if not free(p):
                sys.exit(f'{pid} is an Unsplash+ image: not covered by the free licence')
            api(p['links']['download_location'].replace(API, ''))  # API guideline: register the download
            open(f'{OUT}/{slug}.jpg', 'wb').write(get(p['urls']['raw'].split('?')[0] + '?w=2400&q=85&fm=jpg&fit=max', True))
            meta[slug] = {'id': pid, 'credit': f"{p['user']['name']} on Unsplash", 'source_url': p['links']['html'],
                          'licence': 'Unsplash License (https://unsplash.com/license)', 'title': p.get('alt_description') or p.get('description') or ''}
            print('fetched', slug, pid, p['user']['name'])
            continue
        page = get(f'https://unsplash.com/photos/{pid}', text=True)
        if 'plus.unsplash.com' in (re.search(r'property="og:image" content="([^"]+)"', page) or [None, ''])[1] or 'Unsplash+ License' in page:
            sys.exit(f'{pid} is an Unsplash+ image: not covered by the free licence')
        og = re.search(r'property="og:image" content="(https://images\.unsplash\.com/photo-[^"?]+)', page)
        title = re.search(r'property="og:title" content="([^"]+)"', page)
        canonical = re.search(r'rel="canonical" href="([^"]+)"', page)
        by = re.search(r'Photo by ([^|<]+?) on Unsplash', htmlmod.unescape(title.group(1)) if title else '')
        if not og or not by:
            sys.exit(f'{pid}: could not read the image or the photographer from its page')
        open(f'{OUT}/{slug}.jpg', 'wb').write(get(og.group(1) + '?w=2400&q=85&fm=jpg&fit=max', True))
        meta[slug] = {'id': pid, 'credit': f"{by.group(1).strip()} on Unsplash", 'source_url': canonical.group(1) if canonical else f'https://unsplash.com/photos/{pid}',
                      'licence': 'Unsplash License (https://unsplash.com/license)', 'title': htmlmod.unescape(title.group(1))}
        print('fetched', slug, pid, by.group(1))
    json.dump(meta, open(f'{OUT}/fetched.json', 'w'), indent=1)


if __name__ == '__main__':
    mode, arg = sys.argv[1], sys.argv[2]
    {'search': search, 'fetch': fetch, 'probe': probe}[mode]([a.strip() for a in re.split(r'[|,]', arg) if a.strip()])
