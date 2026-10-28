import json,re,sys
DEC = re.compile(r"new agents?\b|become (an? )?(agent|representative|partner|recruitment|official)|becoming|prospective (agent|partner|representative)|interested in|apply to (become|be )|application form|request for representation|partner with us|"
 r"not (currently )?(accepting|appointing|recruiting|looking|expanding|taking|seeking)|no (new )?agreements|do not|does not|only agency|suspended|ineligible|MBChB|Medicine|"
 r"commission|British Council|contact|enquir|Nigeria|referee|reference", re.I)
EMAIL = re.compile(r'[\w.+-]+@[\w-]+(?:\.[\w-]+)+')
d=json.load(open(sys.argv[1]))
for u,v in d.items():
    if len(sys.argv)>2 and not any(k in u for k in sys.argv[2:]): continue
    print(f"\n=== {u}\n  {v.get('status')} | {v.get('title','')[:80]} | sha {v.get('sha')} | final {v.get('final')}")
    text=' '.join(e.strip('…') for ex in v['excerpts'].values() for e in ex)
    print('  EMAILS:', sorted({e.rstrip('.') for e in EMAIL.findall(text)}))
    seen=set()
    for s in re.split(r'(?<=[.!?])\s+| ⏎ ', text):
        s=s.strip()
        if 30<len(s)<450 and DEC.search(s) and s[:70] not in seen:
            seen.add(s[:70]); print('  >', s)
