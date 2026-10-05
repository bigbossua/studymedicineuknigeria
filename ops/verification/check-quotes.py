#!/usr/bin/env python3
"""Guard for review files: every quote must be copied verbatim from the excerpts of the page it cites.
Every URL named in "page" is searched. A quote may join several passages with "..." and may carry [bracketed reviewer notes], which are ignored.
Usage: python3 ops/verification/check-quotes.py <review.json> <parsed-excerpts.json> [more parsed files...]
Exit 1 when any quote segment is not found on its page."""
import json, re, sys

def norm(s):
    s = s.replace('’', "'").replace('‘', "'").replace('“', '"').replace('”', '"').replace(' ', ' ')
    return re.sub(r'\s+', ' ', s.replace('⏎', ' ')).strip().lower()

review = json.load(open(sys.argv[1]))
pages = {}
for f in sys.argv[2:]:
    for url, d in json.load(open(f)).items():
        text = norm(' '.join(e.strip('…') for ex in d.get('excerpts', {}).values() for e in ex))
        for key in {url, d.get('final') or url}:
            pages[key.rstrip('/')] = pages.get(key.rstrip('/'), '') + ' ' + text
fails = 0
for ref, d in (review.get('decisions', review)).items():
    urls = [u.rstrip('/,;') for u in re.findall(r'https?://[^\s,;]+', d.get('page', ''))]
    found = [pages[u] for u in urls if u in pages]
    if not found:
        print(f'NO PAGE  {ref}: {urls[:1]}'); fails += 1; continue
    text = ' '.join(found)
    quote = re.sub(r'\[[^\]]*\]', ' ', d.get('quote', ''))
    for seg in re.split(r'\.\.\.|…', quote):
        seg = norm(seg)
        if len(seg) < 6:
            continue
        if seg not in text:
            print(f'MISSING  {ref}: "{seg[:120]}"'); fails += 1
print(f'{fails} problem(s)')
sys.exit(1 if fails else 0)
