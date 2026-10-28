#!/usr/bin/env python3
"""Writes OUTREACH-DRAFTS.md: one individually addressed enquiry per university whose outreach status is READY TO SEND,
from uk-university-agent-contacts.csv. Nothing is sent by this script.

Run after build_contacts.py: python3 research/university-agent-partners/make_drafts.py
"""
import csv
import os

HERE = os.path.dirname(__file__)
rows = list(csv.DictReader(open(os.path.join(HERE, 'uk-university-agent-contacts.csv'))))

# A personal opening that refers only to what the university's own page says (see the CSV evidence column).
OPENING = {
    'University of Nottingham': 'Your overseas representatives page invites enquiries about working as a University of Nottingham representative to the Agent Relations Team, so I am writing to you directly.',
    'Lancaster University': 'Your "Find an agent" page invites organisations interested in working with Lancaster University to contact the International Student Recruitment team, so I am writing to ask about the process.',
    'Brighton and Sussex Medical School (University of Brighton)': 'Your information for international agents asks new agents looking to partner with the University of Brighton to contact the International Office, so I am writing with some information about us and a few questions.',
    'University of Plymouth': 'Your international agent toolkit includes guidance on working with the University as a new agent and names the International Office as the contact for questions about your agent network.',
    'University of Edinburgh': 'Your Agent Guidance pages list this address as the contact for agents looking to get in touch. We have noted from your Commission and Contracts guidance that the MBChB is not eligible for commission, so our questions also cover your healthcare-related programmes.',
    'Cardiff University': 'Your educational advisors pages explain that Cardiff selects its contracted advisors carefully, and list the Agents Team as the point of contact for advisors.',
    'University of Kent (Kent and Medway Medical School)': 'Your Agent Handbook 2026-27 lists this address for the agents team, and we would like to understand how Kent appoints partners, including whether Kent and Medway Medical School programmes fall within Kent agent agreements.',
    'Aston University': 'Your Nigeria country page names the Regional Manager for Africa and this team address as the contact for students in Nigeria, so I hope you can point us to the right colleague for partnership enquiries.',
    'University of Glasgow': 'Your Key Contacts page lists you as International Recruitment Manager for Africa, so I hope you are the right person to advise on how the University appoints representatives in Nigeria.',
    'Queen Mary University of London': 'Your Africa regional page lists this address for the Queen Mary team for Africa, so I hope you can advise on, or direct us to, the right team for representative enquiries in Nigeria.',
    "Queen's University Belfast": "Your Sub-Saharan Africa page lists this address for the region, and your agents and partner information pages describe how Queen's works with agents.",
    'University of Sheffield': 'Your representatives page introduces you as the University of Sheffield Regional Representative for Sub-Saharan Africa, based in Nigeria, so I hope you can advise on, or point us to the right colleague for, representative enquiries.',
    'University of Leeds': 'Your "Representatives in your country or region" page lists the International Office as the contact, so I am writing to ask how the University appoints representatives.',
    'University of Bristol': 'Your guidance for agents names the International Office as the contact for further information, so I am writing to ask about the process for prospective partners.',
    'University of Exeter': 'Your University Agents page explains that Exeter works with contracted education agents, so I am writing to the International Office to ask about the process for prospective partners.',
    'Keele University': 'Your "Applying with an agent" page and published agent framework describe how Keele appoints and reviews its agents, so I am writing to ask how a prospective partner should begin.',
    'University of Chester': 'Your "Working with Agents" page explains that the University works with carefully selected recruitment agents, so I am writing to the International Office to ask how agents are selected.',
    "St Mary's University, Twickenham": "Your agent directory explains that St Mary's works with agents who meet British Council and BUILA standards, so I am writing to ask about your process for new partners.",
    'University of Surrey': "Your page on the University of Surrey and the Agent Quality Framework names the international team as the contact, so I am writing to ask how Surrey appoints recruitment partners.",
    'University of Greater Manchester': 'Your Africa Branch Office page explains that the office is the main point of contact for African applicants, so I would like to ask whether the University also works with recruitment partners in Nigeria.',
    'Newcastle University': 'Your international contact page lists this address as the regional contact for Africa, and your representatives page explains that the Global Recruitment Team works closely with your agents, so I hope you can direct this enquiry to the right colleague.',
    'University of Hertfordshire': 'Your In Country Representatives page names your team as the contact for students from Nigeria and Ghana, so I hope you can advise on, or direct us to the right colleague for, recruitment-partner enquiries.',
    'University of Birmingham': 'Your Africa scholarship pages list this address for the Africa team and name your agent partners in Nigeria, so I hope you can direct this enquiry to the colleague who manages representative appointments.',
    'University of Sunderland': 'Your agents and partners pages describe your compliance requirements and your overseas office in Nigeria, so I am writing to ask whether you consider new partners for healthcare programmes.',
}

BODY = """I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

{opening}

We are interested in learning whether your university is currently considering new international student recruitment partners or education agents for Nigeria.

Our focus is helping prospective students understand course entry requirements, international application processes, documentation requirements and the steps involved in preparing an application. We want to ensure that any guidance we provide is accurate and aligned with each university's published requirements.

For transparency: we are not currently an appointed agent or representative of any UK university, and we receive no payments from universities. Students who use our application-support service pay us directly.

Could you please advise:

1. Whether your university is currently accepting applications from prospective international recruitment agents or education partners.
2. Whether Nigeria is an available territory for a new representative.
3. Your formal appointment process, eligibility criteria and due-diligence requirements.
4. Whether any required British Council training or other agent certification is applicable.
5. Whether your university offers an agent agreement or commission arrangement, and how the relevant terms can be obtained.
6. Whether there is a dedicated application form or team we should contact to proceed.

We would welcome the opportunity to learn about your requirements and determine whether there may be a suitable basis for cooperation.

Thank you for your time. I look forward to your guidance.

Kind regards,
International Partnerships Team
StudyMedicineUKNigeria.com
Email: info@studymedicineuknigeria.com
Website: https://studymedicineuknigeria.com/"""

out = ['# Outreach drafts: university recruitment-partner enquiries',
       '',
       'One e-mail per university, each to the single official address recorded in `uk-university-agent-contacts.csv`. **None of these has been sent.**',
       'Send each one individually (no CC or BCC) from the mailbox after confirming the sender address, then record the result in `OUTREACH-LOG.md`.',
       '']
n = 0
for r in rows:
    if not r['outreach_status'].startswith('READY TO SEND'):
        continue
    n += 1
    who = r['contact_name'].split(',')[0].strip()
    team = r['contact_team'].split('(')[0].strip()
    greeting = f'Dear {who},' if who else f'Dear {team},' if team else 'Dear International Recruitment Team,'
    opening = OPENING[r['university']]
    out += [f'## {n}. {r["university"]} (group {r["group"]})', '',
            f'- **To:** {r["recipient_email"]}',
            f'- **Subject:** {r["email_subject"]}',
            f'- **Why this address:** {r["source_url"]}', '',
            '```text', greeting, '', BODY.format(opening=opening), '```', '']
open(os.path.join(HERE, 'OUTREACH-DRAFTS.md'), 'w').write('\n'.join(out))
print(n, 'drafts -> OUTREACH-DRAFTS.md')
