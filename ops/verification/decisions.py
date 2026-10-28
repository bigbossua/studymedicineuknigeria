#!/usr/bin/env python3
"""Append reviewed decisions to data/verification/decisions-<date>.csv in the worksheet format.
Decisions live in ops/verification/reviews/<date>-*.json: {ref: {decision, verified_value?, new_source_url?, quote, page}}.
With a second argument (a review name), only reviews/<date>-<name>.json is read and the output is decisions-<date>-<name>.csv,
so a follow-up review does not change a decisions file a deploy has already imported."""
import csv, glob, json, sys, os
date = sys.argv[1]
name = sys.argv[2] if len(sys.argv) > 2 else None
ws = {r['ref']: r for r in csv.DictReader(open('data/verification/worksheet-2026-10-04.csv'))}
header = list(next(iter(ws.values())).keys())
out = f'data/verification/decisions-{date}-{name}.csv' if name else f'data/verification/decisions-{date}.csv'
rows = []
for f in sorted(glob.glob(f'ops/verification/reviews/{date}-{name or "*"}.json')):
    for ref, d in json.load(open(f)).items():
        r = dict(ws[ref]) if ref in ws else {**{h: '' for h in header}, 'ref': ref}
        r['decision'] = d['decision']
        r['verified_value'] = d.get('verified_value', '')
        r['new_source_url'] = d.get('new_source_url', '')
        note = (d['note'] + ' ' if d.get('note') else '') + f"AI-assisted review of the official page ({d.get('page', '')}); quote: \"{d['quote']}\""
        r['reviewer_note'] = note[:900]
        r['verified_on'] = date
        rows.append(r)
with open(out, 'w', newline='') as fh:
    w = csv.DictWriter(fh, fieldnames=header, lineterminator='\n'); w.writeheader(); w.writerows(rows)
print(len(rows), 'decisions ->', out)
