#!/usr/bin/env python3
"""Parse a page-excerpts job log (raw, or the JSON the GitHub API returns) into {url: {final, status, title, sha, excerpts: {term: [text]}, links}}.
Usage: python3 ops/verification/parse-excerpts.py <job-log> <out.json>"""
import json,re,sys
src,out=sys.argv[1],sys.argv[2]
s=open(src).read()
try: s=json.loads(s)['logs_content']
except Exception: pass
s='\n'.join(re.sub(r'^\S+Z ','',l) for l in s.split('\n'))
blocks={}
for b in re.split(r'\n(?==== https?://)',s):
    if not b.startswith('=== '): continue
    lines=b.split('\n'); url=lines[0][4:].strip()
    d={'excerpts':{},'links':[]}
    for l in lines[1:]:
        if l.startswith('final: '): d['final']=l[7:]
        elif l.startswith('status: '):
            m=re.match(r'status: (-?\d+) \| title: (.*?) \| chars: (\d+) \| sha256: (\w+)',l)
            if m: d['status'],d['title'],d['chars'],d['sha']=int(m[1]),m[2],int(m[3]),m[4]
        elif l.startswith('+++ link: '):
            m=re.match(r'\+\+\+ link: (.*) -> (\S+)$',l)
            if m: d['links'].append([m[1],m[2]])
        elif l.startswith('--- ['):
            m=re.match(r'--- \[(.*?)\] (.*)$',l)
            if m: d['excerpts'].setdefault(m[1],[]).append(m[2])
        elif l.startswith('##[') or 'Post job cleanup' in l: break
    blocks[url]=d
json.dump(blocks,open(out,'w'))
print(len(blocks),'pages')
