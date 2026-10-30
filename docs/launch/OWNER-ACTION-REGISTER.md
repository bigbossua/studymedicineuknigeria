# Owner action register (2026-10-30)

**Status values:** complete · awaiting owner · awaiting legal review · blocked.
**Blocks:** D = technical deployment; L = commercial launch (taking students' money); — = neither, but recommended.

Settings named `SITE_*` are GitHub → Settings → Environments → **production** → Environment variables. They are public values, not secrets. Apply them with *Update server settings* (target `production`), then check with *Live verification*.

## Legal identity and registrations

| # | Item | Status | Exact action | Who | Evidence to close | Blocks |
|---|---|---|---|---|---|---|
| O1 | Legal name and business structure | awaiting owner | State whether the business is a sole trader, partnership, limited company or LLP. Set `SITE_LEGAL_NAME` (company: registered name; sole trader: own name, optionally "trading as Study Medicine UK Nigeria") and `SITE_ENTITY_TYPE` (`sole_trader` / `partnership` / `limited_company` / `llp`) | Owner | Our status, footer, terms and emails show the name; Admin checklist "Trading identity published" lists no missing name or structure | L |
| O2 | Geographic address | awaiting owner | Set `SITE_ADDRESS`: where the business is established (a company: its registered office). Not a PO box unless the adviser confirms it is enough | Owner | Contact and Our status show it; the checklist no longer lists `SITE_ADDRESS` | L |
| O3 | Company details (only if a company or LLP) | awaiting owner | Set `SITE_COMPANY_NUMBER` and `SITE_REGISTERED_IN` (e.g. "England and Wales") | Owner | Terms of use read "…, company number …, registered in …, registered office: …" | L (if a company) |
| O4 | ICO data protection fee | awaiting owner | Complete the ICO self-assessment (ico.org.uk → Data protection fee → "Do I need to pay a fee?"). The pack §2 explains why an exemption looks unlikely; tier 1 is £52 if turnover ≤ £632,000 or staff ≤ 10. Pay if required, then set `SITE_ICO_NUMBER`. If exempt, keep the ICO's result and tell Claude | Owner | ICO registration reference (or the saved exemption result); Our status shows "Registration Z…" | L |
| O5 | VAT number | awaiting owner | Set `SITE_VAT_NUMBER` only if VAT-registered; otherwise say "not registered" | Owner | Answer recorded | — |
| O6 | Hosting region (privacy notice transfers) | awaiting owner | Read the data-centre location in hPanel; set `SITE_HOSTING_REGION` (e.g. "the United Kingdom") | Owner | Privacy notice states it | L |
| O7 | Nigeria Data Protection Commission position | awaiting legal review | The adviser assesses whether NDPC registration applies (Our status says "status to be published") | Adviser | A written position; Our status updated | L |

## Legal documents

| # | Item | Status | Exact action | Who | Evidence to close | Blocks |
|---|---|---|---|---|---|---|
| O8 | Legal review | awaiting legal review | The adviser reviews the four legal pages and `LEGAL-REVIEW-PACK.md` items L1–L13. Claude applies the approved wording; then set `SITE_LEGAL_REVIEWED` to the review date | Adviser, then owner | The adviser's written approval; the DRAFT banner disappears | L |
| O9 | Cancellation form | awaiting legal review | The adviser copies CCR 2013 Schedule 3 Part B (published as an image on legislation.gov.uk) and confirms the UK wording (the pack §3 gives the Directive text for comparison) | Adviser | Approved text; it appears on the refund policy and in the contract confirmation | L |
| O10 | Complaints procedure | awaiting owner | Decide the complaints address, the stage 1 and stage 2 response targets and the reviewer (`LEGAL-REVIEW-PACK.md` §5); the adviser approves | Owner, adviser | Approved text published, linked from Contact and the terms | L |
| O11 | ADR position | awaiting legal review | The adviser confirms what, if anything, the trader must now say about ADR (the 2015 Regulations were revoked; the DMCCA 2024 ADR chapter has been in force since 6 April 2026) | Adviser | A written position | L |
| O12 | Contract confirmation email (CCR reg. 16) | awaiting legal review | Approve the content list in the pack §6; Claude then builds it | Adviser, then Claude | Email template merged and tested | L |

## Payments and Stripe

| # | Item | Status | Exact action | Who | Evidence to close | Blocks |
|---|---|---|---|---|---|---|
| O13 | Stripe account details | awaiting owner | Stripe Dashboard (live): set the support email, the statement descriptor, and the business name and address (= O1, O2) | Owner | *Live verification* shows `support_email_set: true` | L |
| O14 | Stripe test key | awaiting owner | Add the repository secret `STRIPE_TEST_SECRET` (`sk_test_…`) | Owner | *Stripe test-mode journey* passes T1–T3 | L |
| O15 | £125 live rehearsal | awaiting owner | Approve and perform `STRIPE-TEST-AND-LIVE-REHEARSAL.md` §6 (pay £125 with your own card, then refund) | Owner | Payment and refund ids; Admin shows REFUNDED_FULL | L |
| O16 | Payments open decision | awaiting owner | Payments are open in production with a live key. Confirm that no student is approved for service selection until O1–O15 are done (the recommended option), or ask Claude to propose a closure | Owner | Written decision | L |

## Students and operations

| # | Item | Status | Exact action | Who | Evidence to close | Blocks |
|---|---|---|---|---|---|---|
| O17 | Three overdue enquiries | awaiting owner | Admin → Leads → overdue: reply to each from the business mailbox, then set "contacted" (`STUDENT-JOURNEY-TEST-REPORT.md` §3) | Owner | Three leads marked contacted | — (urgent) |
| O18 | Second admin account | awaiting owner | Create a second admin with two-step verification, so the business does not depend on one person | Owner | Admin → Users shows 2 admins enrolled | L (recommended) |
| O19 | Restore test | awaiting owner | Monthly: restore the latest off-site backup into a throwaway database (`ops/RESTORE.md`); record the date | Owner | Dated note | — |

## Deployment

| # | Item | Status | Exact action | Who | Evidence to close | Blocks |
|---|---|---|---|---|---|---|
| O20 | Deployment approval | awaiting owner | Approve *Deploy to Hostinger* (target production) for the branch head named in `FINAL-LAUNCH-CHECKLIST.md`, after a fresh *Operations summary* | Owner | Deploy run green; *Live verification* afterwards shows the expected sitemap | D |
| O21 | Cardiff fee | awaiting owner | Cardiff's 2026/27 overseas fee page now redirects to the 2027-entry page, where the fee is "awaiting confirmation". Decide: keep the 2026/27 figure as historical, or record "not published" until Cardiff confirms 2027/28. The watcher will probably flag it tonight, after which Cardiff goes noindex automatically (38 URLs) | Owner or reviewer | Decision file imported; Cardiff indexed or not, by the rule | — |

## Search and measurement

| # | Item | Status | Exact action | Who | Evidence to close | Blocks |
|---|---|---|---|---|---|---|
| O22 | Search Console feed | awaiting owner | Add the secret `GSC_SERVICE_ACCOUNT_JSON` and the variable `SITE_GSC_PROPERTY`, then run *Update server settings* (`docs/ops/SEARCH-CONSOLE-AND-GA4.md`) | Owner | Admin → Search shows data; *Search report* runs | — |
| O23 | Analytics decision | awaiting owner | Set `SITE_ANALYTICS_DECISION` to `first_party` or `ga4` (and `SITE_GA4_ID` if GA4) | Owner | Live verification shows the decision | — |
| O24 | Semrush data | blocked | The account has no API units (`no_api_units`, 30 October); buying them is excluded by the owner's rules. Instead export the Nigeria database keyword reports from the Semrush web interface and send the CSV files | Owner | Files imported with `smukn:semrush-import`; register rows updated | — |
| O25 | Noindex schools | awaiting owner | After 8 weeks of Search Console data, decide which of the 8 schools at 5/5 core facts (Aston, Brunel, Chester, Keele, Kent and Medway, Lancaster, Surrey, Swansea) have Nigerian demand evidence (`OPERATIONS-MANUAL.md` §8) | Owner | Register rows updated with the evidence; exceptions stay limited to Cambridge, KCL and QUB | — |

## Completed this programme (no owner action needed)

| Item | Evidence |
|---|---|
| A rollback undoes the release's fact decisions automatically | commit 8f5fcea; `PRE-DEPLOYMENT-CHECKLIST.md` §5 |
| The deploy only reads the live Stripe catalogue | commit 07c1b63; `PricingVisibilityTest` |
| One legal identity configuration; no wording that assumes a company | `App\Support\LegalIdentity`; `LegalIdentityTest` |
| DRAFT — LEGAL REVIEW REQUIRED banner on every unapproved legal page | `x-legal-version`; `LegalIdentityTest` |
| Lead reply drafts in Admin, sending nothing | `App\Support\LeadReplyDraft`; `LeadFollowUpTest` |
| The nine indexed schools' and the cost page's facts re-checked against official pages today | `evidence/REVERIFY-2026-10-30.md`: 52 of 54 still match; 2 changed (Cardiff, O21) |
