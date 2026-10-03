"""In-body internal link graph: for every sitemap URL, count links inside <main> (header, footer and the floating CTA excluded)
and tally inbound links per page from the other sitemap pages. Usage: python3 ops/qa/link-graph.py [BASE_URL] [OUT_JSON]
Pages with few inbound links are candidates for contextual links (docs/seo/DECISION-ENGINE.md §6)."""
import re,urllib.request,json,collections,sys
base=sys.argv[1] if len(sys.argv)>1 else "http://127.0.0.1:8000"
outfile=sys.argv[2] if len(sys.argv)>2 else None
urls=[l for l in re.findall(r"<loc>([^<]+)",urllib.request.urlopen(base+"/sitemap.xml").read().decode())]
paths=[u.replace(base,"") or "/" for u in urls]
inb=collections.Counter(); out={}
for p in paths:
    html=urllib.request.urlopen(base+p).read().decode()
    main=html.split('<main',1)[1].split('</main>',1)[0] if '<main' in html else html
    links=set(re.findall(r'href="(?:http://127\.0\.0\.1:8000)?(/[^"#?]*)',main))
    links={l.rstrip("/") or "/" for l in links if not l.startswith(("/images","/fonts","/build"))}
    links.discard(p)
    out[p]=links
    for l in links: inb[l]+=1
print("page | out-links(main) | in-links(main, from sitemap pages)")
for p in paths:
    print(f"{p} | {len(out[p])} | {inb[p]}")
if outfile:
    json.dump({k:sorted(v) for k,v in out.items()},open(outfile,"w"),indent=1)
