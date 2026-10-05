#!/usr/bin/env python3
"""Build page-excerpts specs (URL | terms) from the verification worksheet, one line per official page, with search
terms chosen from the keys of the facts on that page. Usage:
  python3 ops/verification/excerpt-spec.py data/verification/worksheet-2026-10-04.csv 4,5,6 out-prefix [pages-per-file]"""
import csv, sys, collections

TERMS = {
    'international_accepted': ['international', 'overseas'], 'international_places': ['international', 'overseas'],
    'international_places_open': ['international', 'overseas'], 'gem_international': ['graduate'],
    'a_level_requirement': ['A level', 'A-level', 'AAA', 'A*'], 'english_requirement': ['IELTS'],
    'english_language_requirement': ['IELTS'], 'foundation_route': ['foundation'],
    'waec_neco_statement': ['WAEC', 'NECO', 'West African', 'Nigeria'],
    'international_fee_gbp': ['£', 'tuition fee'], 'clinical_years_fee_differs': ['clinical', 'year 3', 'years 3'],
}
src, prios, prefix = sys.argv[1], set(sys.argv[2].split(',')), sys.argv[3]
per = int(sys.argv[4]) if len(sys.argv) > 4 else 22
pages = collections.OrderedDict()
for r in csv.DictReader(open(src)):
    if r['priority'] in prios and r['source_url'].startswith('https://'):
        pages.setdefault(r['source_url'], set()).update(TERMS.get(r['key'], [r['key'].replace('_', ' ')]))
urls = list(pages.items())
for i in range(0, len(urls), per):
    with open(f'{prefix}-{i // per + 1}.txt', 'w') as f:
        for u, terms in urls[i:i + per]:
            f.write(f"{u} | {'; '.join(sorted(terms))}\n")
print(len(urls), 'pages')
