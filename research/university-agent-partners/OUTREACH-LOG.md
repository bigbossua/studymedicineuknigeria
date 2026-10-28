# Outreach log: university recruitment-partner enquiries

## Status on 2026-10-28: nothing has been sent

**No e-mail was sent from this session.** It runs in a cloud container with no browser or computer-control tool, so it cannot reach the Hostinger Mail tab open on the owner's computer.

For the same reason, these steps were **not done**:
- the sender address was not confirmed;
- the Sent folder was not searched for earlier "International Student Recruitment Cooperation" enquiries;
- no university reply was reviewed.

All three must be done in the mailbox before the first send. A university found there as already contacted moves to DUPLICATE / PREVIOUSLY CONTACTED, and a university that has replied gets a reply-specific next action, not this first-contact e-mail.

**To send:**
1. Open a Claude session that runs on the owner's computer (the Claude Desktop app, or `claude remote-control` in this folder), or send manually.
2. Send the drafts in `OUTREACH-DRAFTS.md` one by one: one recipient each, no CC or BCC.
3. After each send, check the Sent folder, then fill in `sent_at`, `outreach_status` (SENT) and `delivery_status` in the CSV (edit `build_contacts.py`, which regenerates the CSV) and in the table below.

Bounces and non-delivery reports must be recorded as SEND FAILED or under delivery status. A message that appears in Sent has not necessarily been delivered.

## Status definitions

| Status | Meaning |
|---|---|
| SENT | Confirmed in the Sent folder (none yet) |
| SEND FAILED | Attempted but not sent, or bounced (none yet) |
| READY TO SEND — NOT SENT | Verified official address, no published bar on new agents; draft prepared; not sent because this session has no mailbox access |
| FORM REQUIRED — OWNER REVIEW | The university uses an application or expression-of-interest form; not submitted, no declarations made |
| NOT ACCEPTING AGENTS | The university's own page rules out new agents or agents altogether; do not contact |
| HOLD — OWNER REVIEW | Not barred, but the evidence makes a first-contact enquiry inappropriate (Manchester: Nigeria is held exclusively by IDP) |
| NO VERIFIED CONTACT / PENDING VERIFICATION | No official address relevant to agents or recruitment found yet, or the site blocked the reader |
| DUPLICATE / PREVIOUSLY CONTACTED | To be set after the Sent folder check (none known) |

## Queue

| # | University | Group | Recipient | Status | Sent at | Delivery | Next action |
|---|---|---|---|---|---|---|---|
| 1 | Brighton and Sussex Medical School (University of Brighton) | A | InternationalOffice@brighton.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry; ask whether BSMS Medicine is commissionable for Brighton agents (Sussex runs a separate network: agents@sussex.ac.uk) |
| 2 | Lancaster University | A | internationalrecruitment@lancaster.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry; ask whether Nigeria is open to a new partner |
| 3 | University of Nottingham | A | agents@nottingham.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Agent Relations Team; ask about Nigeria and Medicine eligibility |
| 4 | University of Plymouth | A | international@plymouth.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the International Office |
| 5 | Hull York Medical School (University of York) | A | — | FORM REQUIRED — OWNER REVIEW | — | — | Owner to decide whether to complete the York form (needs British Council certification and two referees); the University of Hull agent page returned 404 |
| 6 | University of Wolverhampton | A | — | FORM REQUIRED — OWNER REVIEW | — | — | Owner to review and complete the expression of interest form |
| 7 | Aston University | B | africa@aston.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Africa team address |
| 8 | Cardiff University | B | agents@cardiff.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Agents Team |
| 9 | Keele University | B | international@keele.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the International team |
| 10 | Newcastle University | B | africa.io@ncl.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Africa regional address |
| 11 | Queen Mary University of London | B | africa@qmul.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Africa regional team |
| 12 | Queen's University Belfast | B | africa@qub.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Africa team |
| 13 | St Mary's University, Twickenham | B | international@stmarys.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry; confirm the Medicine programme start and GMC status first |
| 14 | University of Birmingham | B | africa@contacts.bham.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Africa team |
| 15 | University of Bristol | B | international-office@bristol.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the International Office |
| 16 | University of Chester | B | international@chester.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry; graduate-entry Medicine only |
| 17 | University of Edinburgh | B | Representatives@ed.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry, acknowledging that MBChB is excluded from commission; ask about healthcare programmes |
| 18 | University of Exeter | B | intoff@exeter.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the International Office |
| 19 | University of Glasgow | B | mark.stansfield@glasgow.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Recruitment Manager for Africa |
| 20 | University of Greater Manchester | B | afbo@greatermanchester.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry; the branch-office model may exclude agents |
| 21 | University of Hertfordshire | B | Nigeria@herts.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the Nigeria team; ask for the agent-relations contact |
| 22 | University of Kent (Kent and Medway Medical School) | B | agents@kent.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry; ask whether KMMS Medicine falls under Kent agreements |
| 23 | University of Leeds | B | internationaloffice@leeds.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the International Office |
| 24 | University of Sheffield | B | udochika.ezeocha@sheffield.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the regional representative |
| 25 | University of Surrey | B | international@surrey.ac.uk | READY TO SEND — NOT SENT | — | — | Send the enquiry to the International team |
| 26 | University of Manchester | B | — | HOLD — OWNER REVIEW | — | — | Do not send a first-contact enquiry for Nigeria: the territory is held exclusively by IDP |
| 27 | University of Sunderland | C | international.office@sunderland.ac.uk | READY TO SEND — NOT SENT | — | — | Secondary priority (healthcare only); send after the Medicine group |
| 28 | Anglia Ruskin University | C | — | PENDING VERIFICATION | — | — | Secondary priority (healthcare only) |
| 29 | Bangor University | C | — | FORM REQUIRED — OWNER REVIEW | — | — | Secondary priority: Medicine is home-only; healthcare courses only |
| 30 | Brunel University of London | C | — | PENDING VERIFICATION | — | — | Read the Nigeria "Your Country" page for the regional manager |
| 31 | City St George's, University of London | C | — | PENDING VERIFICATION | — | — | Further verification |
| 32 | Edge Hill University | C | — | PENDING VERIFICATION | — | — | Secondary priority (healthcare only) |
| 33 | King's College London | C | — | PENDING VERIFICATION | — | — | Further verification |
| 34 | Manchester Metropolitan University | C | — | PENDING VERIFICATION | — | — | Secondary priority (healthcare only) |
| 35 | Northumbria University | C | — | FORM REQUIRED — OWNER REVIEW | — | — | Secondary priority (healthcare only); owner to review the form |
| 36 | Ulster University | C | — | PENDING VERIFICATION | — | — | Further verification |
| 37 | University College London | C | — | PENDING VERIFICATION | — | — | Owner to open the page in a browser |
| 38 | University of Aberdeen | C | — | PENDING VERIFICATION | — | — | Find the Africa recruitment contact (enquiry form only so far) |
| 39 | University of Buckingham | C | — | PENDING VERIFICATION | — | — | Find the International Officer for Nigeria |
| 40 | University of Cambridge | C | — | PENDING VERIFICATION | — | — | Low priority; do not e-mail general admissions about agency |
| 41 | University of Dundee | C | — | PENDING VERIFICATION | — | — | Owner to open the page in a browser |
| 42 | University of East Anglia | C | — | PENDING VERIFICATION | — | — | Owner to open the page in a browser |
| 43 | University of Lancashire | C | — | PENDING VERIFICATION | — | — | Find the Nigeria/Africa recruitment contact |
| 44 | University of Leicester | C | — | PENDING VERIFICATION | — | — | Owner to open the page in a browser |
| 45 | University of Lincoln | C | — | PENDING VERIFICATION | — | — | Secondary priority (healthcare only) |
| 46 | University of Liverpool | C | — | PENDING VERIFICATION | — | — | Find the agent-relations contact |
| 47 | University of Oxford | C | — | PENDING VERIFICATION | — | — | Owner to open the page in a browser |
| 48 | University of Southampton | C | — | PENDING VERIFICATION | — | — | Further verification |
| 49 | University of St Andrews | C | — | PENDING VERIFICATION | — | — | Further verification |
| 50 | University of Worcester | C | — | PENDING VERIFICATION | — | — | Further verification |
| 51 | Imperial College London (including the Pears Cumbria School of Medicine) | X | — | NOT ACCEPTING AGENTS | — | — | Do not contact |
| 52 | Swansea University | X | — | NOT ACCEPTING AGENTS | — | — | Do not contact; re-check in six months |
| 53 | University of Warwick | X | — | NOT ACCEPTING AGENTS | — | — | Do not contact; re-check the agent page in six months |
