#!/usr/bin/env python3
"""Per university: agent-relevant pages read, decisive sentences and agent-related e-mail addresses in a crawl log.
Usage: decisive.py <job-log.txt> [slug ...]"""
import json, re, sys
s = open(sys.argv[1]).read()
try:
    s = json.loads(s)['logs_content']
except Exception:
    pass
s = '\n'.join(re.sub(r'^\S+Z ', '', l) for l in s.split('\n'))
only = set(sys.argv[2:])
REL = re.compile(r'agent|representative|recruitment.partner|advis|counsell|nigeria|partner.zone|work.with.us|africa', re.I)
DEC = re.compile(r"new agents?\b|become (an? )?(agent|representative|partner|recruitment)|becoming (an? )?(agent|representative|partner)|prospective (agent|partner|representative)|"
                 r"interested in (becoming|working|partnering|representing)|apply to (become|be )|application to become|request for representation|"
                 r"not (currently )?(accepting|appointing|recruiting|looking|expanding|taking on|seeking)|no (new )?agreements|do not (work|have agreements|use|pay|endorse)|"
                 r"does not (work|use|pay)|not (work|working) with (agents|any agent)|suspended|"
                 r"commission|National Code|British Council|Agent Quality Framework|Nigeria", re.I)
EMAIL = re.compile(r'[\w.+-]+@[\w-]+(?:\.[\w-]+)+')
AGENTISH = re.compile(r'agent|represent|international|intl|intoff|recruit|partner|africa|global|overseas|nigeria|ukteam|admission', re.I)
for block in re.split(r'\n(?=##### )', s):
    if not block.startswith('##### '):
        continue
    slug = block.split('\n', 1)[0][6:].strip()
    if only and slug not in only:
        continue
    print(f'\n################ {slug}')
    sentences, emails, pages, shas = [], {}, [], set()
    for page in re.split(r'\n(?==== https?://)', block)[1:]:
        lines = page.split('\n')
        url = lines[0][4:].strip()
        head = next((l for l in lines if l.startswith('status: ')), '')
        m = re.match(r'status: (\S+) \| title: (.*?) \| chars: (\d+) \| sha256: (\w+)', head)
        if not m:
            continue
        st, title, sha = m.group(1), m.group(2), m.group(4)
        if sha in shas:
            continue
        shas.add(sha)
        rel = bool(REL.search(url + ' ' + title))
        pages.append(f'{st} {url} | {title[:70]}' + ('' if rel else ' (not agent-related)'))
        if not rel:
            continue
        text = ' '.join(re.sub(r'^--- \[.*?\] ', '', l).strip('…') for l in lines if l.startswith('--- ['))
        for e in EMAIL.findall(text):
            e = e.rstrip('.')
            if AGENTISH.search(e) or AGENTISH.search(title):
                emails.setdefault(e, url)
        for sent in re.split(r'(?<=[.!?])\s+| ⏎ ', text):
            sent = sent.strip()
            if 25 < len(sent) < 400 and DEC.search(sent) and not re.match(r'^[A-Z][a-z]+( [A-Z][a-z]+)* ⏎', sent):
                key = sent.lower()[:90]
                if key not in [x[0] for x in sentences]:
                    sentences.append((key, sent, url))
    for p in pages:
        print('  page', p)
    for e, u in emails.items():
        print(f'  EMAIL {e}  <- {u}')
    for _, sent, u in sentences[:40]:
        print(f'  > {sent}   [{u.split("//")[1][:70]}]')
