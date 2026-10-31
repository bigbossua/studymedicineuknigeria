#!/usr/bin/env python3
"""Runner-side watch for official pages that refuse the server's nightly source watcher (HTTP 403 from the hosting
server's address: Cambridge, Cardiff, Queen's Belfast and others, found 2026-10-31). Builds a page-excerpts spec for the
verified facts on those hosts from the committed evidence (the latest review quote of every fact whose final committed
decision is "verified"), then, after the excerpts run, checks that every recorded official quote is still on its page.
Read-only: it changes no fact; a missing quote fails the run so the owner is told and a reviewer re-reads the page.
Usage:
  python3 ops/verification/blocked-watch.py spec  <host,host,...> <spec.txt> <items.json>
  python3 ops/verification/blocked-watch.py check <items.json> <parsed-excerpts.json>"""
import csv, glob, json, re, sys
from urllib.parse import urlparse

def norm(s):
    s = s.replace('’', "'").replace('‘', "'").replace('“', '"').replace('”', '"').replace(' ', ' ')
    return re.sub(r'\s+', ' ', s.replace('⏎', ' ')).strip().lower()

def segments(quote):
    quote = re.sub(r'\[[^\]]*\]', ' ', quote)
    return [norm(s) for s in re.split(r'\.\.\.|…|⏎', quote) if len(norm(s)) >= 6]

def final_decisions():
    """ref -> decision of the last committed decisions file that names it (files apply in name order)."""
    out = {}
    for f in sorted(glob.glob('data/verification/decisions-*.csv')):
        for r in csv.DictReader(open(f, encoding='utf-8')):
            if (r.get('decision') or '').strip():
                out[r['ref']] = r['decision'].strip()
    return out

def latest_quotes():
    """ref -> (url, quote) from the newest review file that records a verified decision with a quote."""
    out = {}
    for f in sorted(glob.glob('ops/verification/reviews/*.json')):
        data = json.load(open(f, encoding='utf-8'))
        for ref, d in (data.get('decisions', data)).items():
            if not isinstance(d, dict) or d.get('decision') != 'verified' or not d.get('quote'):
                continue
            urls = [u.rstrip('/,;') for u in re.findall(r'https?://[^\s,;]+', d.get('page', ''))]
            if urls:
                out[ref] = (urls[0], d['quote'])
    return out

def spec(hosts, spec_path, items_path):
    hosts = [h.strip().lower() for h in hosts.split(',') if h.strip()]
    decisions = final_decisions()
    items, pages = [], {}
    for ref, (url, quote) in sorted(latest_quotes().items()):
        host = (urlparse(url).hostname or '').lower()
        if decisions.get(ref) != 'verified' or not any(host == h or host.endswith('.' + h) for h in hosts):
            continue
        segs = segments(quote)
        if not segs:
            continue
        items.append({'ref': ref, 'url': url, 'segments': segs})
        # search for the start of each segment (distinctive, short): its context then holds the whole segment
        pages.setdefault(url, set()).update(s[:50] for s in segs)
    with open(spec_path, 'w', encoding='utf-8') as f:
        for url, terms in pages.items():
            f.write(f"{url} | {'; '.join(sorted(terms))}\n")
    json.dump(items, open(items_path, 'w', encoding='utf-8'), indent=1)
    print(f'{len(items)} verified fact(s) on {len(pages)} page(s) of {", ".join(hosts)}')

def check(items_path, parsed_path):
    items = json.load(open(items_path, encoding='utf-8'))
    pages = {}
    for url, d in json.load(open(parsed_path, encoding='utf-8')).items():
        text = norm(' '.join(e.strip('…') for ex in d.get('excerpts', {}).values() for e in ex))
        for key in {url, d.get('final') or url}:
            pages[key.rstrip('/')] = (pages.get(key.rstrip('/'), ('', d.get('status')))[0] + ' ' + text, d.get('status'))
    missing = unreachable = 0
    for it in items:
        text, status = pages.get(it['url'].rstrip('/'), ('', None))
        if not text.strip():
            print(f"UNREACHABLE  {it['ref']}: {it['url']} (status {status})"); unreachable += 1; continue
        gone = [s for s in it['segments'] if s not in text]
        if gone:
            print(f"QUOTE GONE   {it['ref']}: {it['url']} — \"{gone[0][:110]}\""); missing += 1
    print(f"{len(items)} checked: {missing} quote(s) gone, {unreachable} page(s) unreachable")
    sys.exit(1 if missing or unreachable else 0)

if __name__ == '__main__':
    mode = sys.argv[1] if len(sys.argv) > 1 else ''
    if mode == 'spec' and len(sys.argv) == 5:
        spec(*sys.argv[2:])
    elif mode == 'check' and len(sys.argv) == 4:
        check(*sys.argv[2:])
    else:
        print(__doc__); sys.exit(2)
