# Owner action register (updated 2026-10-31)

**Status values:** complete · awaiting owner · awaiting legal review · blocked.
**Blocks:**
- **D:** technical deployment.
- **L:** commercial launch (accepting student payments).
- **—:** neither, but recommended.

**Who:**
- **Owner:** only the owner can do it.
- **Adviser:** a qualified legal adviser.
- **Claude:** Claude can complete it once the input exists.

**Settings.** `SITE_*` values go in GitHub → Settings → Environments → **production** → Environment variables. They are public values, not secrets. Apply them with *Update server settings* (target `production`), then check with *Live verification*.

## Legal identity and registrations

| # | What is missing | Why it matters | Evidence that closes it | Blocks | Who | Status |
|---|---|---|---|---|---|---|
| O1 | Legal name and business structure (`SITE_LEGAL_NAME`, `SITE_ENTITY_TYPE`) | The law requires the trader's name before a distance contract (CCR 2013 Sch. 2; E-Commerce Regs reg. 6) | Footer, terms and Our status show the name and structure; the Admin checklist "Trading identity published" no longer lists them | L | Owner (supply); Claude (nothing to build) | awaiting owner |
| O2 | Geographic business address (`SITE_ADDRESS`; a company: registered office) | The same disclosure rules; complaints and cancellations need an address | Contact and Our status show it | L | Owner | awaiting owner |
| O3 | Company number and part of the UK (`SITE_COMPANY_NUMBER`, `SITE_REGISTERED_IN`), only if a company or LLP | Company disclosure rules (registered name, number, registered office on the website) | Terms read "…, company number …, registered in …" | L (if a company) | Owner | awaiting owner |
| O4 | ICO data protection fee decision (`SITE_ICO_NUMBER`) | The site holds identity documents; the ICO expects a fee unless exempt (tier 1 £52) | The ICO registration reference, or the saved self-assessment result showing an exemption | L | Owner | awaiting owner |
| O5 | VAT status (`SITE_VAT_NUMBER`) | The VAT number must be shown if registered | The number, or "not registered" | — | Owner | awaiting owner |
| O6 | Hosting region (`SITE_HOSTING_REGION`) | The privacy notice must state where data is held, for international transfers | The privacy notice states it | L | Owner (read it in hPanel) | awaiting owner |
| O7 | Nigeria Data Protection Commission position | Our status says "status to be published" | The adviser's written position; Our status updated | L | Adviser | awaiting legal review |

## Legal documents

| # | What is missing | Why it matters | Evidence that closes it | Blocks | Who | Status |
|---|---|---|---|---|---|---|
| O8 | Legal review of the four pages and the pack's clauses L1–L13 | Every legal page is marked DRAFT — LEGAL REVIEW REQUIRED; the refund timing conflicts with the 14-day rule (pack C4) | The adviser's written approval. Claude applies the wording; the owner sets `SITE_LEGAL_REVIEWED` and the banner disappears | L | Adviser; then Claude and Owner | awaiting legal review |
| O9 | UK model cancellation form (CCR Sch. 3 Part B) | Required before the contract (reg. 13(1)(b)); legislation.gov.uk publishes it only as an image | The approved text, shown on the refund policy and in the contract confirmation | L | Adviser; then Claude | awaiting legal review |
| O10 | Complaints procedure: address, stage targets, reviewer | CCR Sch. 2 (k); also the practical route for unhappy students | Approved text published and linked from Contact | L | Owner (decide); Adviser (approve); Claude (publish) | awaiting owner |
| O11 | ADR position | The 2015 ADR Regulations were revoked; the DMCCA 2024 chapter has been in force since 6 April 2026 | The adviser's written position | L | Adviser | awaiting legal review |
| O12 | Contract-confirmation email (CCR reg. 16) | The trader must confirm the contract, with the Sch. 2 information, on a durable medium before performance begins | Template built from the approved wording and tested | L | Adviser (wording); Claude (build) | awaiting legal review |

## Payments and Stripe

| # | What is missing | Why it matters | Evidence that closes it | Blocks | Who | Status |
|---|---|---|---|---|---|---|
| O13 | Stripe Dashboard details: support email, statement descriptor, business name and address | Shown on receipts and Checkout; must match O1 and O2 | *Live verification* reports `support_email_set: true` | L | Owner | awaiting owner |
| O14 | Repository secret `STRIPE_TEST_SECRET` | The only way to prove T1–T3 against real Stripe, in test mode | *Stripe test-mode journey* passes | L | Owner (key); Claude (run) | blocked on owner |
| O15 | £125 live rehearsal and refund | Proves the live charge, webhook, receipt and refund once | The payment and refund ids; Admin shows REFUNDED_FULL | L | Owner (approve and pay); Claude (prepare and verify) | awaiting owner |
| O16 | **Commercial-launch approval** (`SITE_COMMERCIAL_LAUNCH_APPROVED`, the approval date) | After this deploy, production starts no new payment until it is set; existing payments and refunds are unaffected | Set only after B1–B17 of the launch checklist; then *Live verification* shows `commercial_launch_approved: true` and payments open | L (this **is** the launch switch) | Owner | awaiting owner (deliberately last) |

## Students and operations

| # | What is missing | Why it matters | Evidence that closes it | Blocks | Who | Status |
|---|---|---|---|---|---|---|
| O17 | **Replies to the three overdue enquiries** | They are the site's only conversions; the 2-working-day promise has been missed | Download the artifact `lead-drafts-encrypted-41168273841` (run 41168273841, expires 7 Nov) and decrypt it with the backup passphrase, as in `ops/RESTORE.md`. Review and edit each draft, send it from the business mailbox, then set each lead to "contacted" | — (urgent) | Owner (send); Claude (drafts done) | awaiting owner |
| O18 | Second admin with two-step verification | Avoids a single point of failure | Admin → Users shows 2 admins enrolled | L (recommended) | Owner | awaiting owner |
| O19 | Monthly restore test | Proves backups restore | A dated note. The daily backup also restore-tests automatically; attempt 2 of run 41030369264 passed | — | Owner | ongoing |
| O20 | Pin the server's SSH host key (`HOSTINGER_SSH_KNOWN_HOSTS`) | Every server workflow currently trusts the key on first use; the 30 October failure also showed `ssh-keyscan` timing out | The variable is set, and the workflows log `SSH_STRICT=yes` | — | Owner (read the key in hPanel, or let Claude print it from a run) | awaiting owner |

## Deployment

| # | What is missing | Why it matters | Evidence that closes it | Blocks | Who | Status |
|---|---|---|---|---|---|---|
| O21 | Deployment approval for the branch head in the final report | Production still runs `5d76858` | The deploy run is green; *Live verification* afterwards shows **38** sitemap URLs (Cardiff now noindex) and payments closed by the gate | D | Owner | awaiting owner |

## Facts and SEO

| # | What is missing | Why it matters | Evidence that closes it | Blocks | Who | Status |
|---|---|---|---|---|---|---|
| O22 | Cardiff 2027/28 overseas fee | Cardiff's page is noindex until the fee and international acceptance are re-verified | Cardiff publishes the fee; Claude reads it and records the fact; register D08 returns to PUBLISHED | — | Claude (when Cardiff publishes) | waiting on Cardiff |
| O23 | Review of the changed quotes at Hertfordshire (3) and City St George's (2), and of Cardiff's IB wording | The runner watch shows the recorded wording is no longer on those pages; none of these schools is indexed | A decision file per school: verified with the new wording, or source changed | — | Claude | open (next cycle) |
| O24 | Leicester pages unreadable from GitHub runners ("Just a moment…") as well as from the server | Leicester facts cannot be watched automatically at all | A manual read, or removal of Leicester facts from public display (the page is not indexed) | — | Owner or Claude | open |
| O25 | Search Console feed (repository secret `GSC_SERVICE_ACCOUNT_JSON`; steps in `OPERATIONS-MANUAL.md` §7) | The only first-party demand evidence; needed before any further school is indexed | Admin → Search shows data; *Search report* runs | — | Owner | awaiting owner |
| O26 | Analytics decision (`SITE_ANALYTICS_DECISION`) | Records the owner's choice between first-party analytics and GA4 | *Live verification* shows it | — | Owner | awaiting owner |
| O27 | Semrush exports (no API units; none will be bought) | Semrush is the SEO research input; without exports there is no keyword evidence | `semrush-ng-YYYY-MM-DD.csv` (main 50 themes) and `semrush-ng-schools-YYYY-MM-DD.csv` (36 school themes) exported from the Semrush web interface and imported as `data/semrush/README.md` describes | — | Owner (export); Claude (import, then register decisions) | awaiting owner |
| O28 | Decisions on further school pages | Eight schools have 5/5 core facts but no demand evidence | Evidence from O25 or O27 recorded in each register row, then the owner's approval | — | Owner (approve); Claude (record) | awaiting evidence |

## Completed in this cycle (no owner action)

| Item | Evidence |
|---|---|
| New payments gated behind the commercial-launch approval | `CommercialLaunchGateTest`; commit `f52be93` |
| Watcher follows redirects; watcher health reported | `SourceWatcherTest`; *Operations summary* 41168271747 |
| Runner-side watch for refused hosts | *Blocked sources watch* 41168890758 |
| Cardiff's withdrawn fee hidden; register, publication list and page audit consistent | commits `5ce3b62`, `8abff80`; mirror shows 38 URLs |
| Three lead drafts generated and encrypted, nothing sent | run 41168273841 (`drafts=3`) |
| Failed 30 October backup re-run, with restore test | run 41030369264, attempt 2: success |
| School-level Semrush workflow prepared | `data/semrush/school-lookup-sheet.csv`; `SemrushImportTest` |
