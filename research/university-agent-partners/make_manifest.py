#!/usr/bin/env python3
"""Builds SENDING-MANIFEST.csv and SENDING-MANIFEST.md from the committed source files, without changing them:
uk-university-agent-contacts.csv, OUTREACH-DRAFTS.md and OUTREACH-LOG.md.

Each draft is checked against the CSV (recipient, subject, source URL); any mismatch stops the build.
Holds the owner added after the research are listed in OWNER_HOLDS and documented in the manifest.

Run: python3 research/university-agent-partners/make_manifest.py
"""
import csv
import os
import re
import subprocess

HERE = os.path.dirname(os.path.abspath(__file__))
rows = list(csv.DictReader(open(os.path.join(HERE, 'uk-university-agent-contacts.csv'))))
drafts_md = open(os.path.join(HERE, 'OUTREACH-DRAFTS.md')).read()
log_md = open(os.path.join(HERE, 'OUTREACH-LOG.md')).read()
commit = subprocess.run(['git', 'log', '-1', '--format=%h', '--', 'uk-university-agent-contacts.csv', 'OUTREACH-DRAFTS.md', 'OUTREACH-LOG.md'],
                        cwd=HERE, capture_output=True, text=True).stdout.strip()

# Owner instruction of 2026-10-28 (after the research): a conversation with the University of Greater Manchester Medical School
# is already in progress, so no first-contact enquiry may go to it.
OWNER_HOLDS = {
    'University of Greater Manchester': 'HOLD: the owner reports an ongoing conversation with the University of Greater Manchester Medical School (instruction of 2026-10-28); do not send a first-contact enquiry',
}

# ---- parse the drafts ----
drafts = {}
for block in re.split(r'\n(?=## \d+\. )', drafts_md)[1:]:
    head = re.match(r'## \d+\. (.*) \(group ([A-C])\)', block)
    to = re.search(r'- \*\*To:\*\* (\S+)', block)
    subject = re.search(r'- \*\*Subject:\*\* (.*)', block)
    why = re.search(r'- \*\*Why this address:\*\* (\S+)', block)
    body = re.search(r'```text\n(.*?)\n```', block, re.S)
    assert head and to and subject and why and body, f'unparsable draft: {block[:80]}'
    drafts[head.group(1)] = {'to': to.group(1), 'subject': subject.group(1).strip(), 'source': why.group(1), 'body': body.group(1)}

# ---- previous contact recorded in the outreach log ----
queue = [l for l in log_md.splitlines() if re.match(r'\| \d+ \|', l)]
sent_lines = [l for l in queue if re.search(r'\| (SENT|SEND FAILED|DUPLICATE[^|]*) \|', l)]
assert len(queue) == len(rows), f'log queue has {len(queue)} rows, CSV {len(rows)}'
assert not sent_lines, f'the log records sends; review before building: {sent_lines}'

out = []
for r in rows:
    u = r['university']
    d = drafts.get(u)
    ready = r['outreach_status'].startswith('READY TO SEND')
    assert ready == bool(d), f'draft/CSV status mismatch for {u}'
    if d:
        assert d['to'] == r['recipient_email'], f'{u}: draft To {d["to"]} != CSV {r["recipient_email"]}'
        assert d['subject'] == r['email_subject'], f'{u}: subject differs'
        assert d['source'] == r['source_url'], f'{u}: source URL differs'
    previous = 'None recorded in OUTREACH-LOG.md (Sent folder not yet checked)'
    if u in OWNER_HOLDS:
        eligible, reason = 'NO — HOLD', OWNER_HOLDS[u]
        previous = 'Owner reports an ongoing conversation with the medical school (not in the log; details not in the repository)'
    elif ready:
        eligible, reason = 'YES (after the Sent/Inbox duplicate check)', ''
    elif r['outreach_status'] == 'NOT ACCEPTING AGENTS':
        eligible, reason = 'NO — EXCLUDED', f'University does not accept new agents: {r["evidence"]}'
    elif r['outreach_status'].startswith('HOLD'):
        eligible, reason = 'NO — HOLD', r['next_action'] + f' ({r["nigeria"]})'
    elif r['outreach_status'].startswith('FORM REQUIRED'):
        eligible, reason = 'NO — FORM (owner only)', 'The university uses an application/expression-of-interest form; it must not be submitted on the owner\'s behalf'
    else:
        eligible, reason = 'NO — NOT VERIFIED', f'No verified official address relevant to agents or recruitment ({r["evidence"]})'
    recipient = (r['contact_name'].split(',')[0] + ' — ' if r['contact_name'] else (r['contact_team'] + ' — ' if d else '')) + (r['recipient_email'] or '')
    out.append({
        'university': u, 'group': r['group'], 'recipient': recipient.strip(' —'), 'recipient_email': r['recipient_email'],
        'contact_team': r['contact_team'], 'source_url': r['source_url'], 'source_evidence': r['evidence'], 'evidence_run': r['evidence_run'],
        'subject': d['subject'] if d else '', 'body': d['body'] if d else '',
        'outreach_status': 'HOLD — OWNER (ongoing conversation)' if u in OWNER_HOLDS else r['outreach_status'].replace(' (no mailbox access from this session)', ''),
        'previous_contact': previous, 'eligible_now': eligible, 'exclusion_or_hold_reason': reason, 'agent_application_url': r['agent_application_url'],
    })

order = {'YES': 0, 'NO — HOLD': 1, 'NO — FORM': 2, 'NO — EXCLUDED': 3, 'NO — NOT VERIFIED': 4}
out.sort(key=lambda x: (order[x['eligible_now'].split(' (')[0]], x['group'], x['university']))
cols = list(out[0].keys())
with open(os.path.join(HERE, 'SENDING-MANIFEST.csv'), 'w', newline='') as fh:
    w = csv.DictWriter(fh, fieldnames=cols, lineterminator='\n')
    w.writeheader()
    w.writerows(out)

count = lambda p: sum(1 for x in out if x['eligible_now'].startswith(p))
md = [
    '# Sending manifest: university recruitment-partner enquiries',
    '',
    f'Built by `make_manifest.py` from `uk-university-agent-contacts.csv`, `OUTREACH-DRAFTS.md` and `OUTREACH-LOG.md` at commit {commit}. Those files are not changed.',
    '',
    '**Nothing has been sent.** The outreach log records no SENT, SEND FAILED or DUPLICATE entry.',
    '',
    '## Totals',
    '',
    '| | Count |',
    '|---|---|',
    f'| Universities in the manifest | {len(out)} |',
    f'| Eligible to e-mail now (after the Sent/Inbox duplicate check) | {count("YES")} |',
    f'| Held (Manchester: Nigeria held by IDP; Greater Manchester: ongoing conversation) | {count("NO — HOLD")} |',
    f'| Agent application forms (owner only, not to be submitted) | {count("NO — FORM")} |',
    f'| Excluded: do not accept new agents | {count("NO — EXCLUDED")} |',
    f'| Not verified (no official address relevant to agents yet, or site blocked) | {count("NO — NOT VERIFIED")} |',
    '',
    '## Corrections and additions to the source files (documented here, source files unchanged)',
    '',
    '- **University of Greater Manchester: moved from READY TO SEND to HOLD.**',
    '  - The owner reported (instruction of 2026-10-28) an ongoing conversation with its medical school.',
    '  - That conversation is not recorded in `OUTREACH-LOG.md`, and its details are not in the repository.',
    '  - Its draft is kept in `OUTREACH-DRAFTS.md` but must not be sent.',
    '- **No other change.**',
    '  - Every remaining draft matches the CSV: same recipient, subject and official source URL. The build checks this.',
    '',
    '## Before the first send',
    '',
    '1. **Search the mailbox (Sent and Inbox) for each recipient address and university name.** Include the earlier subject "International Student Recruitment Cooperation".',
    '   - Any earlier enquiry: mark the row DUPLICATE / PREVIOUSLY CONTACTED.',
    '   - Any reply: answer the reply instead of sending this first-contact e-mail.',
    '2. **Confirm the sender address.**',
    '   - The signature says `info@studymedicineuknigeria.com`.',
    '   - If the connected mailbox sends from a different address, change the signature line to the real sending address before sending.',
    '3. **Send one e-mail per university:** one recipient, no CC or BCC.',
    '   - Use the subject and body exactly as below; the greeting and opening are already personalised.',
    '   - Do not attach anything.',
    '4. **After each send:**',
    '   - confirm it appears in Sent;',
    '   - record the date and time and any bounce in `OUTREACH-LOG.md`;',
    '   - change the status to SENT only when it appears in Sent.',
    '',
    '## Manifest',
    '',
    '| # | University | Recipient | Official source | Status | Previous contact | Eligible now | Reason for exclusion or hold |',
    '|---|---|---|---|---|---|---|---|',
]
for i, x in enumerate(out, 1):
    md.append(f"| {i} | {x['university']} | {x['recipient'] or '—'} | {x['source_url']} | {x['outreach_status']} | {x['previous_contact']} | {x['eligible_now']} | {x['exclusion_or_hold_reason'] or '—'} |")
md += ['', '## Eligible e-mails: complete drafts', '']
for i, x in enumerate([x for x in out if x['eligible_now'].startswith('YES')], 1):
    md += [f"### {i}. {x['university']}", '', f"- **To:** {x['recipient_email']}", f"- **Recipient:** {x['recipient']}",
           f"- **Subject:** {x['subject']}", f"- **Official source for this address:** {x['source_url']}",
           f"- **What the page says:** {x['source_evidence']}", '', '```text', x['body'], '```', '']
open(os.path.join(HERE, 'SENDING-MANIFEST.md'), 'w').write('\n'.join(md))
print({k: count(k) for k in order})
