# Sending manifest: university recruitment-partner enquiries

Built by `make_manifest.py` from `uk-university-agent-contacts.csv`, `OUTREACH-DRAFTS.md` and `OUTREACH-LOG.md` at commit 094cb89. Those files are not changed.

**Nothing has been sent.** The outreach log records no SENT, SEND FAILED or DUPLICATE entry.

## Totals

| | Count |
|---|---|
| Universities in the manifest | 53 |
| Eligible to e-mail now (after the Sent/Inbox duplicate check) | 23 |
| Held (Manchester: Nigeria held by IDP; Greater Manchester: ongoing conversation) | 2 |
| Agent application forms (owner only, not to be submitted) | 4 |
| Excluded: do not accept new agents | 3 |
| Not verified (no official address relevant to agents yet, or site blocked) | 21 |

## Corrections and additions to the source files (documented here, source files unchanged)

- **University of Greater Manchester: moved from READY TO SEND to HOLD.**
  - The owner reported (instruction of 2026-10-28) an ongoing conversation with its medical school.
  - That conversation is not recorded in `OUTREACH-LOG.md`, and its details are not in the repository.
  - Its draft is kept in `OUTREACH-DRAFTS.md` but must not be sent.
- **No other change.**
  - Every remaining draft matches the CSV: same recipient, subject and official source URL. The build checks this.

## Before the first send

1. **Search the mailbox (Sent and Inbox) for each recipient address and university name.** Include the earlier subject "International Student Recruitment Cooperation".
   - Any earlier enquiry: mark the row DUPLICATE / PREVIOUSLY CONTACTED.
   - Any reply: answer the reply instead of sending this first-contact e-mail.
2. **Confirm the sender address.**
   - The signature says `info@studymedicineuknigeria.com`.
   - If the connected mailbox sends from a different address, change the signature line to the real sending address before sending.
3. **Send one e-mail per university:** one recipient, no CC or BCC.
   - Use the subject and body exactly as below; the greeting and opening are already personalised.
   - Do not attach anything.
4. **After each send:**
   - confirm it appears in Sent;
   - record the date and time and any bounce in `OUTREACH-LOG.md`;
   - change the status to SENT only when it appears in Sent.

## Manifest

| # | University | Recipient | Official source | Status | Previous contact | Eligible now | Reason for exclusion or hold |
|---|---|---|---|---|---|---|---|
| 1 | Brighton and Sussex Medical School (University of Brighton) | University of Brighton International Office — InternationalOffice@brighton.ac.uk | https://www.brighton.ac.uk/international/international-agents/index.aspx | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 2 | Lancaster University | International Student Recruitment Office — internationalrecruitment@lancaster.ac.uk | https://www.lancaster.ac.uk/study/international-students/find-an-agent/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 3 | University of Nottingham | Agent Relations Team — agents@nottingham.ac.uk | https://www.nottingham.ac.uk/studywithus/international-applicants/contact-us/representatives/representatives.aspx | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 4 | University of Plymouth | International Office — international@plymouth.ac.uk | https://www.plymouth.ac.uk/international/international-agent-toolkit | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 5 | Aston University | Babajide Ogundeji — africa@aston.ac.uk | https://www.aston.ac.uk/international/aston-in-your-country/africa/nigeria | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 6 | Cardiff University | Agents Team — agents@cardiff.ac.uk | https://www.cardiff.ac.uk/study/international/educational-advisors/for-advisors | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 7 | Keele University | International team — international@keele.ac.uk | https://www.keele.ac.uk/study/internationalstudents/applyingwithanagent/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 8 | Newcastle University | International Office Africa team — africa.io@ncl.ac.uk | https://www.ncl.ac.uk/international/contact/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 9 | Queen Mary University of London | Anita Walton — africa@qmul.ac.uk | https://www.qmul.ac.uk/international-students/regions/africa/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 10 | Queen's University Belfast | Sub-Saharan Africa recruitment team — africa@qub.ac.uk | https://www.qub.ac.uk/Study/international-students/your-country/sub-saharan-africa/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 11 | St Mary's University, Twickenham | International team — international@stmarys.ac.uk | https://www.stmarys.ac.uk/international/agent-directory.aspx | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 12 | University of Birmingham | Africa recruitment team — africa@contacts.bham.ac.uk | https://www.birmingham.ac.uk/study/scholarships-funding/africa-high-fliers-undergraduate-scholarships | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 13 | University of Bristol | International Office — international-office@bristol.ac.uk | https://www.bristol.ac.uk/international/guidance-for-agents/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 14 | University of Chester | International Office — international@chester.ac.uk | https://www.chester.ac.uk/international/working-with-agents/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 15 | University of Edinburgh | Alistair Hudson — Representatives@ed.ac.uk | https://student-recruitment.ed.ac.uk/agent-guidance/contact | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 16 | University of Exeter | International Office — intoff@exeter.ac.uk | https://www.exeter.ac.uk/international-students/university-agents/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 17 | University of Glasgow | Mark Stansfield — mark.stansfield@glasgow.ac.uk | https://www.gla.ac.uk/international/counsellors/keycontactsandrepresentatives/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 18 | University of Hertfordshire | Bolanle Durodola — Nigeria@herts.ac.uk | https://www.herts.ac.uk/international/contact-us/in-country-representatives | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 19 | University of Kent (Kent and Medway Medical School) | University of Kent agents team — agents@kent.ac.uk | https://media.www.kent.ac.uk/se/55341/AgentHandbook2026-27.pdf | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 20 | University of Leeds | International Office — internationaloffice@leeds.ac.uk | https://www.leeds.ac.uk/international-meet-us/doc/representatives-country-region | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 21 | University of Sheffield | Udochika Ezeocha — udochika.ezeocha@sheffield.ac.uk | https://www.sheffield.ac.uk/international/representatives | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 22 | University of Surrey | International team — international@surrey.ac.uk | https://www.surrey.ac.uk/international/university-surrey-and-agent-quality-framework | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 23 | University of Sunderland | International Office — international.office@sunderland.ac.uk | https://www.sunderland.ac.uk/international/agents-and-partners/ | READY TO SEND — NOT SENT | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | YES (after the Sent/Inbox duplicate check) | — |
| 24 | University of Greater Manchester | Africa Branch Office (AFBO) — afbo@greatermanchester.ac.uk | https://greatermanchester.ac.uk/international/afbo | HOLD — OWNER (ongoing conversation) | Owner reports an ongoing conversation with the medical school (not in the log; details not in the repository) | NO — HOLD | HOLD: the owner reports an ongoing conversation with the University of Greater Manchester Medical School (instruction of 2026-10-28); do not send a first-contact enquiry |
| 25 | University of Manchester | — | https://www.manchester.ac.uk/study/international/country-specific-information/nigeria/contacts/ | HOLD — OWNER REVIEW | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — HOLD | Do not send a first-contact enquiry for Nigeria: the territory is held exclusively by IDP (EXCLUSIVE: IDP is the only agency for Nigeria) |
| 26 | Hull York Medical School (University of York) | — | https://www.york.ac.uk/study/international/agents/ | FORM REQUIRED — OWNER REVIEW | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — FORM (owner only) | The university uses an application/expression-of-interest form; it must not be submitted on the owner's behalf |
| 27 | University of Wolverhampton | — | https://www.wlv.ac.uk/international/education-agents-/ | FORM REQUIRED — OWNER REVIEW | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — FORM (owner only) | The university uses an application/expression-of-interest form; it must not be submitted on the owner's behalf |
| 28 | Bangor University | Kerry Jones | https://www.bangor.ac.uk/international/agent-tool-kit | FORM REQUIRED — OWNER REVIEW | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — FORM (owner only) | The university uses an application/expression-of-interest form; it must not be submitted on the owner's behalf |
| 29 | Northumbria University | — | https://www.northumbria.ac.uk/international/agent-information/ | FORM REQUIRED — OWNER REVIEW | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — FORM (owner only) | The university uses an application/expression-of-interest form; it must not be submitted on the owner's behalf |
| 30 | Imperial College London (including the Pears Cumbria School of Medicine) | — | https://www.imperial.ac.uk/study/international-students/agents/ | NOT ACCEPTING AGENTS | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — EXCLUDED | University does not accept new agents: "No agreements: We do not have agreements with any agents or counsellors for the purposes of student recruitment. No commissions: We do not pay commissions for student referrals." |
| 31 | Swansea University | — | https://www.swansea.ac.uk/international-students/our-agents/agent-resources/ | NOT ACCEPTING AGENTS | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — EXCLUDED | University does not accept new agents: "Are you recruiting new agents? We are not currently recruiting new agents." |
| 32 | University of Warwick | — | https://warwick.ac.uk/study/international/agent/ | NOT ACCEPTING AGENTS | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — EXCLUDED | University does not accept new agents: "University of Warwick is not currently looking for any new agents or representation." |
| 33 | Anglia Ruskin University | — | https://www.aru.ac.uk/international | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Agent URLs returned 404; international@aru.ac.uk is a general international address) |
| 34 | Brunel University of London | — | https://www.brunel.ac.uk/international/Applying-to-Brunel-University-of-London-through-an-Agent | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Agents paid commission; recruitment-support@brunel.ac.uk is for complaints about agents, not partnerships; regional managers are on "Your Country" pages) |
| 35 | City St George's, University of London | — | https://www.citystgeorges.ac.uk/prospective-students/international-students | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (No agent page found from the international hub) |
| 36 | Edge Hill University | — | https://www.edgehill.ac.uk/international/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: edgehill.ac.uk answered 403 (browser check)) |
| 37 | King's College London | — | https://www.kcl.ac.uk/study/international/region/africa | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Africa page read; no agent or recruitment e-mail found; agent URLs returned 404) |
| 38 | Manchester Metropolitan University | — | https://www.mmu.ac.uk/international/agents | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: mmu.ac.uk answered 403 (bot challenge)) |
| 39 | Ulster University | — | https://www.ulster.ac.uk/global | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Agent URLs returned 404; study-abroad advisers are not agent contacts) |
| 40 | University College London | — | https://www.ucl.ac.uk/prospective-students/international/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: ucl.ac.uk answered 403 (bot challenge)) |
| 41 | University of Aberdeen | — | https://www.abdn.ac.uk/study/international/working-recruitment-partners/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Works with contracted recruitment partners after "a thorough review before being contracted"; commission-based payment; no agent contact e-mail published (ukteam@abdn.ac.uk is the UK schools team)) |
| 42 | University of Buckingham | — | https://www.buckingham.ac.uk/international/coming-from/nigeria/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Nigeria page lists appointed Nigerian representatives; "suspended the recruitment of students from Nigeria for Postgraduate Research programmes … January 2027" (not Medicine); no agent-appointment contact) |
| 43 | University of Cambridge | — | https://www.undergraduate.study.cam.ac.uk/international-students | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (No agent page (404); only general admissions (admissions@cam.ac.uk) and counsellor events; no evidence that Cambridge appoints agents) |
| 44 | University of Dundee | — | https://www.dundee.ac.uk/countries/nigeria | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: dundee.ac.uk answered 403 (bot challenge)) |
| 45 | University of East Anglia | — | https://www.uea.ac.uk/study/international/agents | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: uea.ac.uk answered 405 (human verification)) |
| 46 | University of Lancashire | — | https://www.lancashire.ac.uk/international-students/agencies | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment ("international agencies and representatives are paid commission"; AQF; no contact e-mail on the page) |
| 47 | University of Leicester | — | https://le.ac.uk/study/international-students/how-to-apply/university-of-leicester-agents-and-representatives | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: le.ac.uk answered 403 (bot challenge) to the official-page reader) |
| 48 | University of Lincoln | — | https://www.lincoln.ac.uk/international/agents/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Agent URL redirected to the home page; no agent page found) |
| 49 | University of Liverpool | — | https://www.liverpool.ac.uk/international/about-agents/choosing-and-managing-agents/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (Approved-agent network incl. Nigeria (Bridge House Counselling, Sable, TAG); commission paid; no contact e-mail on the pages) |
| 50 | University of Oxford | — | https://www.ox.ac.uk/admissions/undergraduate/applying-to-oxford/guide/agents | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (BLOCKED: ox.ac.uk answered 403 (bot challenge)) |
| 51 | University of Southampton | — | https://www.southampton.ac.uk/study/international | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (No agent page found from the international hub) |
| 52 | University of St Andrews | — | https://www.st-andrews.ac.uk/subjects/entry/local-representatives/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment ("We do not work with any agent, counsellor, or sub-agent whose details do not appear on our official pages." No new-agent route or e-mail) |
| 53 | University of Worcester | — | https://www.worcester.ac.uk/international/ | PENDING VERIFICATION | None recorded in OUTREACH-LOG.md (Sent folder not yet checked) | NO — NOT VERIFIED | No verified official address relevant to agents or recruitment (No agent page found) |

## Eligible e-mails: complete drafts

### 1. Brighton and Sussex Medical School (University of Brighton)

- **To:** InternationalOffice@brighton.ac.uk
- **Recipient:** University of Brighton International Office — InternationalOffice@brighton.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.brighton.ac.uk/international/international-agents/index.aspx
- **What the page says:** "New agents – If you're a new agent looking to partner with us, please contact our International Office on InternationalOffice@brighton.ac.uk with more information."

```text
Dear University of Brighton International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your information for international agents asks new agents looking to partner with the University of Brighton to contact the International Office, so I am writing with some information about us and a few questions.

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
Website: https://studymedicineuknigeria.com/
```

### 2. Lancaster University

- **To:** internationalrecruitment@lancaster.ac.uk
- **Recipient:** International Student Recruitment Office — internationalrecruitment@lancaster.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.lancaster.ac.uk/study/international-students/find-an-agent/
- **What the page says:** "If you have any general enquiries or are interested in working with us please contact internationalrecruitment@lancaster.ac.uk"

```text
Dear International Student Recruitment Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your "Find an agent" page invites organisations interested in working with Lancaster University to contact the International Student Recruitment team, so I am writing to ask about the process.

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
Website: https://studymedicineuknigeria.com/
```

### 3. University of Nottingham

- **To:** agents@nottingham.ac.uk
- **Recipient:** Agent Relations Team — agents@nottingham.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.nottingham.ac.uk/studywithus/international-applicants/contact-us/representatives/representatives.aspx
- **What the page says:** "For any enquiries or correspondence about our representatives – including … enquiries about working as a University of Nottingham representative – please contact the Agent Relations Team"

```text
Dear Agent Relations Team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your overseas representatives page invites enquiries about working as a University of Nottingham representative to the Agent Relations Team, so I am writing to you directly.

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
Website: https://studymedicineuknigeria.com/
```

### 4. University of Plymouth

- **To:** international@plymouth.ac.uk
- **Recipient:** International Office — international@plymouth.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.plymouth.ac.uk/international/international-agent-toolkit
- **What the page says:** Agent toolkit has a "Working with us as a new agent" section; the International Office handles queries about the agent network and contracts

```text
Dear International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your international agent toolkit includes guidance on working with the University as a new agent and names the International Office as the contact for questions about your agent network.

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
Website: https://studymedicineuknigeria.com/
```

### 5. Aston University

- **To:** africa@aston.ac.uk
- **Recipient:** Babajide Ogundeji — africa@aston.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.aston.ac.uk/international/aston-in-your-country/africa/nigeria
- **What the page says:** "You are welcome to contact our Regional Manager for Africa, Babajide Ogundeji, by email at b.ogundeji@aston.ac.uk or africa@aston.ac.uk."

```text
Dear Babajide Ogundeji,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Nigeria country page names the Regional Manager for Africa and this team address as the contact for students in Nigeria, so I hope you can point us to the right colleague for partnership enquiries.

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
Website: https://studymedicineuknigeria.com/
```

### 6. Cardiff University

- **To:** agents@cardiff.ac.uk
- **Recipient:** Agents Team — agents@cardiff.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.cardiff.ac.uk/study/international/educational-advisors/for-advisors
- **What the page says:** "If you are unable to access the Agent Portal, contact the Agent Team: Agents Team agents@cardiff.ac.uk"; "We have a selective recruitment process" (for-students page)

```text
Dear Agents Team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your educational advisors pages explain that Cardiff selects its contracted advisors carefully, and list the Agents Team as the point of contact for advisors.

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
Website: https://studymedicineuknigeria.com/
```

### 7. Keele University

- **To:** international@keele.ac.uk
- **Recipient:** International team — international@keele.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.keele.ac.uk/study/internationalstudents/applyingwithanagent/
- **What the page says:** Agent framework 2024: "When looking to appoint new agents, a standardised process … research regarding the agent; initial contact and references; development of a clear business case"

```text
Dear International team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your "Applying with an agent" page and published agent framework describe how Keele appoints and reviews its agents, so I am writing to ask how a prospective partner should begin.

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
Website: https://studymedicineuknigeria.com/
```

### 8. Newcastle University

- **To:** africa.io@ncl.ac.uk
- **Recipient:** International Office Africa team — africa.io@ncl.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.ncl.ac.uk/international/contact/
- **What the page says:** "Regional contacts – Africa – africa.io@ncl.ac.uk"; agents page: "Contact the international recruitment team for further registration guidance"; the Nigeria page names Joan Apeh as a regional officer

```text
Dear International Office Africa team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your international contact page lists this address as the regional contact for Africa, and your representatives page explains that the Global Recruitment Team works closely with your agents, so I hope you can direct this enquiry to the right colleague.

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
Website: https://studymedicineuknigeria.com/
```

### 9. Queen Mary University of London

- **To:** africa@qmul.ac.uk
- **Recipient:** Anita Walton — africa@qmul.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.qmul.ac.uk/international-students/regions/africa/
- **What the page says:** "Your Queen Mary team for Africa: Anita Walton, Regional Manager – Africa, africa@qmul.ac.uk"; agents page: "If you have any questions, please contact globalengagement@qmul.ac.uk"

```text
Dear Anita Walton,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Africa regional page lists this address for the Queen Mary team for Africa, so I hope you can advise on, or direct us to, the right team for representative enquiries in Nigeria.

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
Website: https://studymedicineuknigeria.com/
```

### 10. Queen's University Belfast

- **To:** africa@qub.ac.uk
- **Recipient:** Sub-Saharan Africa recruitment team — africa@qub.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.qub.ac.uk/Study/international-students/your-country/sub-saharan-africa/
- **What the page says:** Sub-Saharan Africa page lists africa@qub.ac.uk; agents page links "Agents by Region" (Nigeria listed); agent@qub.ac.uk is for existing Queen's Portal agent accounts

```text
Dear Sub-Saharan Africa recruitment team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Sub-Saharan Africa page lists this address for the region, and your agents and partner information pages describe how Queen's works with agents.

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
Website: https://studymedicineuknigeria.com/
```

### 11. St Mary's University, Twickenham

- **To:** international@stmarys.ac.uk
- **Recipient:** International team — international@stmarys.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.stmarys.ac.uk/international/agent-directory.aspx
- **What the page says:** "If you have any questions, feel free to contact our international team at international@stmarys.ac.uk."; works with agents meeting British Council and BUILA standards

```text
Dear International team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your agent directory explains that St Mary's works with agents who meet British Council and BUILA standards, so I am writing to ask about your process for new partners.

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
Website: https://studymedicineuknigeria.com/
```

### 12. University of Birmingham

- **To:** africa@contacts.bham.ac.uk
- **Recipient:** Africa recruitment team — africa@contacts.bham.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.birmingham.ac.uk/study/scholarships-funding/africa-high-fliers-undergraduate-scholarships
- **What the page says:** Africa scholarship pages list africa@contacts.bham.ac.uk and name "our agent partners / local representatives in Nigeria"; the representatives page routes enquiries through a feedback form

```text
Dear Africa recruitment team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Africa scholarship pages list this address for the Africa team and name your agent partners in Nigeria, so I hope you can direct this enquiry to the colleague who manages representative appointments.

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
Website: https://studymedicineuknigeria.com/
```

### 13. University of Bristol

- **To:** international-office@bristol.ac.uk
- **Recipient:** International Office — international-office@bristol.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.bristol.ac.uk/international/guidance-for-agents/
- **What the page says:** "If you have any issues with the agent portal or require further information, contact international-office@bristol.ac.uk."

```text
Dear International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your guidance for agents names the International Office as the contact for further information, so I am writing to ask about the process for prospective partners.

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
Website: https://studymedicineuknigeria.com/
```

### 14. University of Chester

- **To:** international@chester.ac.uk
- **Recipient:** International Office — international@chester.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.chester.ac.uk/international/working-with-agents/
- **What the page says:** Working with Agents page lists International@chester.ac.uk for agent verification and complaints

```text
Dear International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your "Working with Agents" page explains that the University works with carefully selected recruitment agents, so I am writing to the International Office to ask how agents are selected.

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
Website: https://studymedicineuknigeria.com/
```

### 15. University of Edinburgh

- **To:** Representatives@ed.ac.uk
- **Recipient:** Alistair Hudson — Representatives@ed.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://student-recruitment.ed.ac.uk/agent-guidance/contact
- **What the page says:** "Contact information for agents looking to get in touch. Alistair Hudson … Email: Representatives@ed.ac.uk"

```text
Dear Alistair Hudson,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Agent Guidance pages list this address as the contact for agents looking to get in touch. We have noted from your Commission and Contracts guidance that the MBChB is not eligible for commission, so our questions also cover your healthcare-related programmes.

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
Website: https://studymedicineuknigeria.com/
```

### 16. University of Exeter

- **To:** intoff@exeter.ac.uk
- **Recipient:** International Office — intoff@exeter.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.exeter.ac.uk/international-students/university-agents/
- **What the page says:** "The University of Exeter has entered into a commercial arrangement with the following education agents"; intoff@exeter.ac.uk receives agent forms. The "Request for Representation Form" is for students changing agent, not an agent application.

```text
Dear International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your University Agents page explains that Exeter works with contracted education agents, so I am writing to the International Office to ask about the process for prospective partners.

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
Website: https://studymedicineuknigeria.com/
```

### 17. University of Glasgow

- **To:** mark.stansfield@glasgow.ac.uk
- **Recipient:** Mark Stansfield — mark.stansfield@glasgow.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.gla.ac.uk/international/counsellors/keycontactsandrepresentatives/
- **What the page says:** "Mark Stansfield, International Recruitment Manager for Africa & Glasgow International College (based in Glasgow) mark.stansfield@glasgow.ac.uk"; also "Chisom Okpala, International Officer for West Africa (based in Lagos, Nigeria)"

```text
Dear Mark Stansfield,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Key Contacts page lists you as International Recruitment Manager for Africa, so I hope you are the right person to advise on how the University appoints representatives in Nigeria.

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
Website: https://studymedicineuknigeria.com/
```

### 18. University of Hertfordshire

- **To:** Nigeria@herts.ac.uk
- **Recipient:** Bolanle Durodola — Nigeria@herts.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.herts.ac.uk/international/contact-us/in-country-representatives
- **What the page says:** "Nigeria and Ghana – Bolanle Durodola – Bolanle and her team support students from: Nigeria and Ghana – Contact the team on Nigeria@herts.ac.uk"

```text
Dear Bolanle Durodola,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your In Country Representatives page names your team as the contact for students from Nigeria and Ghana, so I hope you can advise on, or direct us to the right colleague for, recruitment-partner enquiries.

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
Website: https://studymedicineuknigeria.com/
```

### 19. University of Kent (Kent and Medway Medical School)

- **To:** agents@kent.ac.uk
- **Recipient:** University of Kent agents team — agents@kent.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://media.www.kent.ac.uk/se/55341/AgentHandbook2026-27.pdf
- **What the page says:** Agent Handbook 2026-27 lists agents@kent.ac.uk; "The commission percentage payable is set out in your agreement"; counsellors must complete British Council training

```text
Dear University of Kent agents team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your Agent Handbook 2026-27 lists this address for the agents team, and we would like to understand how Kent appoints partners, including whether Kent and Medway Medical School programmes fall within Kent agent agreements.

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
Website: https://studymedicineuknigeria.com/
```

### 20. University of Leeds

- **To:** internationaloffice@leeds.ac.uk
- **Recipient:** International Office — internationaloffice@leeds.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.leeds.ac.uk/international-meet-us/doc/representatives-country-region
- **What the page says:** Representatives page lists internationaloffice@leeds.ac.uk; "If an agent is not listed on this page, then the University does not maintain a relationship with them"

```text
Dear International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your "Representatives in your country or region" page lists the International Office as the contact, so I am writing to ask how the University appoints representatives.

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
Website: https://studymedicineuknigeria.com/
```

### 21. University of Sheffield

- **To:** udochika.ezeocha@sheffield.ac.uk
- **Recipient:** Udochika Ezeocha — udochika.ezeocha@sheffield.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.sheffield.ac.uk/international/representatives
- **What the page says:** "Our Regional Representative for Sub-Saharan Africa is based in Nigeria and she can offer advice and support on your application to study at Sheffield." (udochika.ezeocha@sheffield.ac.uk listed)

```text
Dear Udochika Ezeocha,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your representatives page introduces you as the University of Sheffield Regional Representative for Sub-Saharan Africa, based in Nigeria, so I hope you can advise on, or point us to the right colleague for, representative enquiries.

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
Website: https://studymedicineuknigeria.com/
```

### 22. University of Surrey

- **To:** international@surrey.ac.uk
- **Recipient:** International team — international@surrey.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.surrey.ac.uk/international/university-surrey-and-agent-quality-framework
- **What the page says:** AQF page lists international@surrey.ac.uk

```text
Dear International team,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your page on the University of Surrey and the Agent Quality Framework names the international team as the contact, so I am writing to ask how Surrey appoints recruitment partners.

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
Website: https://studymedicineuknigeria.com/
```

### 23. University of Sunderland

- **To:** international.office@sunderland.ac.uk
- **Recipient:** International Office — international.office@sunderland.ac.uk
- **Subject:** International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com
- **Official source for this address:** https://www.sunderland.ac.uk/international/agents-and-partners/
- **What the page says:** Agents and partners pages list international.office@sunderland.ac.uk; "We also manage overseas offices in … Nigeria"

```text
Dear International Office,

I am contacting you on behalf of StudyMedicineUKNigeria.com, an independent education-support platform focused on helping prospective Nigerian students explore medical education opportunities in the United Kingdom.

Your agents and partners pages describe your compliance requirements and your overseas office in Nigeria, so I am writing to ask whether you consider new partners for healthcare programmes.

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
Website: https://studymedicineuknigeria.com/
```
