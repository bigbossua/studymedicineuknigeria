#!/usr/bin/env python3
"""Photo sourcing from Unsplash (Unsplash License: free to use, no permission needed; Unsplash+ images are excluded).
Run by .github/workflows/design-photos.yml on a GitHub runner (this repository's sessions cannot reach Unsplash).

  search "query one|query two"   → brand/photos/candidates/<n>-<query>.jpg contact sheets + candidates.json
  fetch  "slug=photoId,slug=id"   → brand/photos/<slug>.jpg (2400 px originals) + brand/photos/fetched.json (credit, link)

Nothing is published by this script: a photograph reaches the site only when it is added to brand/photos/manifest.json
with its alt text, credit, source URL and licence, and `php artisan smukn:images` builds its derivatives.
"""
import io, json, os, re, sys, urllib.parse, urllib.request
from PIL import Image, ImageDraw

UA = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36', 'Accept': 'application/json'}
OUT = 'brand/photos'


def get(url, binary=False):
    with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=40) as r:
        data = r.read()
    return data if binary else json.loads(data)


def free(p):
    return not p.get('premium') and not p.get('plus') and 'plus.unsplash.com' not in json.dumps(p.get('urls', {}))


def search(queries):
    os.makedirs(f'{OUT}/candidates', exist_ok=True)
    found = {}
    for qi, q in enumerate(queries):
        res = get('https://unsplash.com/napi/search/photos?' + urllib.parse.urlencode({'query': q, 'per_page': 30, 'orientation': 'landscape'}))
        photos = [p for p in res.get('results', []) if free(p)][:16]
        thumbs = []
        for p in photos:
            try:
                im = Image.open(io.BytesIO(get(p['urls']['small'], True))).convert('RGB'); im.thumbnail((400, 270)); thumbs.append((p, im))
            except Exception as e:
                print('skip', p['id'], e)
        cols, cw, ch = 4, 400, 300
        sheet = Image.new('RGB', (cols * cw, ((len(thumbs) + cols - 1) // cols) * ch), 'white')
        d = ImageDraw.Draw(sheet)
        for i, (p, im) in enumerate(thumbs):
            x, y = (i % cols) * cw, (i // cols) * ch
            sheet.paste(im, (x + (cw - im.width) // 2, y))
            d.rectangle([x, y + 270, x + cw, y + 300], fill='black'); d.text((x + 6, y + 278), f"{i + 1}. {p['id']}", fill='white')
            found[p['id']] = {'query': q, 'n': i + 1, 'alt': p.get('alt_description'), 'description': p.get('description'), 'by': p['user']['name'],
                              'link': p['links']['html'], 'w': p['width'], 'h': p['height']}
        slug = re.sub(r'[^a-z0-9]+', '-', q.lower()).strip('-')
        sheet.save(f'{OUT}/candidates/{qi + 1:02d}-{slug}.jpg', quality=80)
        print(f'{q}: {len(thumbs)} free photos')
    json.dump(found, open(f'{OUT}/candidates/candidates.json', 'w'), indent=1)


def fetch(picks):
    meta = json.load(open(f'{OUT}/fetched.json')) if os.path.exists(f'{OUT}/fetched.json') else {}
    for pick in picks:
        slug, pid = pick.split('=')
        p = get(f'https://unsplash.com/napi/photos/{pid}')
        if not free(p):
            sys.exit(f'{pid} is an Unsplash+ image: not covered by the free licence')
        raw = p['urls']['raw'] + ('&' if '?' in p['urls']['raw'] else '?') + 'w=2400&q=85&fm=jpg&fit=max'
        open(f'{OUT}/{slug}.jpg', 'wb').write(get(raw, True))
        meta[slug] = {'id': pid, 'credit': f"{p['user']['name']} on Unsplash", 'source_url': p['links']['html'], 'licence': 'Unsplash License (https://unsplash.com/license)',
                      'alt_from_unsplash': p.get('alt_description'), 'description': p.get('description')}
        try:  # Unsplash asks that a download be registered with the photographer's stats
            get(p['links']['download_location'])
        except Exception:
            pass
        print('fetched', slug, pid, p['user']['name'])
    json.dump(meta, open(f'{OUT}/fetched.json', 'w'), indent=1)


if __name__ == '__main__':
    mode, arg = sys.argv[1], sys.argv[2]
    (search if mode == 'search' else fetch)([a.strip() for a in re.split(r'[|,]', arg) if a.strip()])
