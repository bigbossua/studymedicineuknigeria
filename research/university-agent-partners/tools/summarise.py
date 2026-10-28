#!/usr/bin/env python3
"""Summarise a crawl-excerpts job log: per university, the pages read and the e-mail addresses visible in the excerpts.
Usage: summarise.py <job-log.txt> [slug]"""
import json, re, sys
s = open(sys.argv[1]).read()
try:
    s = json.loads(s)['logs_content']
except Exception:
    pass
s = '\n'.join(re.sub(r'^\S+Z ', '', l) for l in s.split('\n'))
only = sys.argv[2] if len(sys.argv) > 2 else None
for block in re.split(r'\n(?=##### )', s):
    if not block.startswith('##### '):
        continue
    slug = block.split('\n', 1)[0][6:].strip()
    if only and slug != only:
        continue
    print(f'\n######## {slug}')
    for page in re.split(r'\n(?==== https?://)', block)[1:]:
        lines = page.split('\n')
        url = lines[0][4:].strip()
        final = next((l[7:] for l in lines if l.startswith('final: ')), '')
        st = next((l for l in lines if l.startswith('status: ')), '')
        m = re.match(r'status: (\S+) \| title: (.*?) \| chars: (\d+) \| sha256: (\w+) .*depth: (\d)', st)
        terms = sorted({re.match(r'--- \[(.*?)\]', l).group(1) for l in lines if l.startswith('--- [')})
        emails = sorted({e.rstrip('.') for e in re.findall(r'[\w.+-]+@[\w-]+(?:\.[\w-]+)+', page)})
        print(f"- {url}" + (f" -> {final}" if final and final != url else ''))
        if m:
            print(f"  status {m.group(1)} | {m.group(2)[:80]} | chars {m.group(3)} | sha {m.group(4)} | depth {m.group(5)}")
        print(f"  terms: {', '.join(terms)}")
        if emails:
            print(f"  emails: {', '.join(emails)}")
