# Launch-readiness audit: StudyMedicineUKNigeria.com (2026-10-29)

**Scope.** This is a read-only audit of:
- the repository at `78d78c3`;
- production release `2026-10-28T11-12-26`;
- the live site.

Nothing was changed on the site, the server, DNS or the mail settings.

**Evidence, all from 29 October 2026:**

| Run | Workflow | What it covers |
|---|---|---|
| 40939681247 | Live Verification | 83/83 Google-readiness checks, journey on phone and desktop, server state |
| 40939694024 | Production review | smoke test, plus 80 page views on desktop and mobile with axe accessibility and CSP checks |
| 40939689599 | Lighthouse | mobile lab scores |
| 40939685566 | Operations Summary | counts only: leads, funnel, facts, schools |
| 40939840413 | Live legal and trust pages | text as served |
| 40691141004 / 40691174950 | Launch checks / mail diagnosis | 28 October |
| CI 40691773686 | Tests | 260 tests, green |

**Statuses:**
- PASS: works, with evidence.
- FAIL: broken or missing, and needed for launch.
- PARTIAL: works in part, or only in tests.
- NOT IMPLEMENTED: does not exist.
- UNKNOWN: cannot be established from here.

## 1. Launch blockers (fix before calling the site launched)

| # | Task | Status | Evidence and affected file/page | Why |
|---|---|---|---|---|
| 1 | Publish the trading identity: legal entity name, registered or geographic address, and company number if incorporated | FAIL | Live `/our-status`: "Operating entity" is blank and "Registered address: To be published". Live `/contact`: "Registered address to be published on Our status". Live Verification: `support_address_published: false`. | Payments are live (`payments_open: true`, live Stripe key, T1–T3 £125/£695/£1,295). UK consumer law requires a trader to give its identity and geographic address before a distance contract. Students and universities also cannot check who they are dealing with. |
| 2 | Fix the blank legal name everywhere it is printed | FAIL | Production sets `SITE_LEGAL_NAME` to an empty string. The templates use `??`, which falls back only on null, so they print nothing. Affected: `resources/views/content/org/legal-privacy.blade.php:6` (live text: `("we") is the data controller`), `status.blade.php:10`, `about.blade.php:17`, `legal-terms.blade.php:5`, `layouts/public.blade.php:117` (footer), `vendor/mail/html/message.blade.php:24` (email footer). | The privacy notice names no data controller, and every page footer and email footer shows an empty name. Fix it in code (treat an empty value as missing) and set the real name. |
| 3 | Register with the ICO (UK data-protection fee), or record why it is not needed, and publish the reference | FAIL | Live `/our-status`: "UK ICO data-protection fee: To be published once registered". Privacy notice: no ICO reference. | The site processes student personal data and documents. A UK-based controller must normally pay the ICO fee. |
| 4 | Complete the legal review of privacy, terms, application terms and refund policy | FAIL | Live `/refund-policy`: "Version 0.9.2, 4 October 2026 … under legal review". The admin launch checklist item "Legal pages reviewed" depends on `SITE_LEGAL_REVIEWED`. `/terms` and `/application-terms` show no review date. | Students can pay now, under terms that say they are still under review. |
| 5 | Run the £125 live payment rehearsal end to end: pay, webhook, admin confirmation, then refund | PARTIAL | Live Verification shows the Stripe live catalogue correct, the webhook self-test passing (35/35 events) and no Payment Links. `payments_by_status: []`: no real payment has ever been processed. `tests/Feature/ServicePaymentsTest.php` and `StripeWebhookTest.php` pass. Stripe account: `support_email_set: false`. | Card payments are open to the public without one proven real transaction. A Stripe support email is shown on receipts and is required for disputes. |
| 6 | Follow up the three eligibility leads | FAIL | Operations Summary: 3 leads, all at status `new`, the oldest waiting 9 days, all suggested T1, 2027 intake. Admin → Leads. | The only conversions the site has produced have gone unanswered. |
| 7 | Decide whether to keep Cambridge, Queen's Belfast and King's College London indexed | FAIL | All three are in the live sitemap. Their register rows are D14, D34 and D27, all status RESEARCH. DECISION-ENGINE §8.3 requires Nigerian demand evidence before indexing. Set noindex in Admin → Universities, or record the evidence. | They break the site's own publication rule. The owner had asked for King's to be noindexed. |

## 2. Content accuracy and credibility

| # | Task | Status | Evidence and affected file/page | Why |
|---|---|---|---|---|
| 8 | Independence wording: no implied appointment, partnership or guarantee | PASS | Every match in a repository-wide search of public templates is a disclaimer or a definition. Live home page: "Independent, not an agent of any university". `/our-status`: "We hold no agreements with any university". | This is correct while no appointment exists. If a university appoints us after the 28 October outreach, update `/our-status`, `/about` and the "no commission" statements first. |
| 9 | Price visibility: no service fee on public pages | PASS | Live Verification: "no service fee on 36 sitemap pages + apply, services, register, login". Live `/apply-online/services`: no "£". | Matches the owner's decision. |
| 10 | Verified-fact coverage | PARTIAL | Operations Summary: of 851 facts, 295 VERIFIED, 151 SOURCE_CHANGED, 342 VERIFY-ON-PAGE, 47 NOT_FOUND, 15 NOT_PUBLISHED, 1 ARCHIVED. | Unverified values are hidden, so pages stay accurate but thin. More than half of all facts are not usable. |
| 11 | Published school pages without verified core facts | FAIL | 40 schools are published in Admin. Only 7 have 5/5 core facts: Brighton and Sussex, Cambridge, Cardiff, Edinburgh, King's College London, Manchester, Queen's Belfast. Twelve have 0–1/5, for example Anglia Ruskin, Bangor, Dundee, UCL and Kent and Medway. | These pages are online with noindex but are linked from the directory, which lists 53 schools. Visitors land on pages that show mostly "not yet verified". Unpublish the schools without data or demand. |
| 12 | Facts flagged on 29 October by the source watcher | FAIL | Newly flagged: Aberdeen `application_route`, Buckingham `international_accepted` and `international_places_open`, UCAS `extra_opens`. The live sitemap fell to 36 URLs: Aberdeen, Buckingham and the cost page dropped out. Lighthouse shows SEO 69 on `/medical-schools/aberdeen` and the cost page because they are now noindex. | Re-read the four facts on their official pages. Restore only what is still supported. |
| 13 | Content dates | PARTIAL | Live: `/contact`, `/medical-schools` and `/refund-policy` say "Last reviewed: 3 October 2026", and `/our-status` "last updated 3 October 2026". | Acceptable now. The weekly fact cycle should refresh them. |
| 14 | Healthcare courses other than Medicine | NOT IMPLEMENTED (by design) | `data/healthcare/subjects.json` (Admin → Subjects). No public healthcare pages. | A deliberate owner decision. Not a launch requirement. |

## 3. Site functionality

| # | Task | Status | Evidence and affected file/page | Why |
|---|---|---|---|---|
| 15 | All public routes answer, redirects work, no debug output | PASS | Smoke test: all pass, including 65 old-site URLs answering 301, 404 for unknown paths, and `.env`, `.git`, `vendor` and logs not served. | — |
| 16 | Eligibility check and lead capture | PASS | Journey on phone and desktop: no problems. `ContentController@eligibilitySubmit` creates a Lead, records a funnel event and emails the admin. `tests/Feature/EligibilityTest.php`. | No confirmation email goes to the student. That is optional. |
| 17 | Registration, sign-in, email verification, password reset, two-step login for staff | PARTIAL | Live: registration open, sign-in renders, portal and admin require sign-in. `AuthFlowTest`, `TwoFactorTest`. Production has only 1 user (the admin) and 0 students. | Proven in tests and in a live render. No real student has completed it in production. |
| 18 | Student portal: application steps, documents, approval, messages, withdrawal, data export and deletion | PARTIAL | 118 routes. `ApplicationWorkflowTest`, `PortalPaymentsAndDocumentsTest`, `DocumentSecurityTest`, `PrivacyRetentionTest` pass. Production: 0 applications, 0 documents. | Built and tested, never used live. Run one full test application with a staff account before marketing it. |
| 19 | Admin: leads, applications, verification queue, universities, redirects, SEO, search, payments, users | PASS | Routes `admin/*`. The only admin account has two-step verification. Staff accounts: 0. | One person operates everything. Add a second staff account if anyone else will handle students. |
| 20 | Website email: notifications, verification and password reset | PARTIAL | Mail diagnosis: `smtp.hostinger.com:465`, sender `info@`, sign-in OK, 0 failed jobs, no mail errors. DNS: SPF and DKIM key present, DMARC `p=none` with no reports. | Delivery to inboxes is unproven. One verification email checked with *Show original* would settle it. This is separate from the university outreach. |
| 21 | Contact and enquiries | PARTIAL | `/contact`: an email address (reply within 2 working days), WhatsApp, and the portal. There is no contact form (no POST route), which is by design. | Works. The missing address is item 1. |
| 22 | Cookies and consent | PASS | The privacy notice explains strictly necessary cookies plus consent-gated Google Analytics. The consent banner is in `layouts/public.blade.php`. GA4 is not configured (`ga4_id_set: false`). | Compliant as built. |

## 4. Technical quality, SEO and operations

| # | Task | Status | Evidence and affected file/page | Why |
|---|---|---|---|---|
| 23 | Titles, descriptions, canonicals, JSON-LD, robots.txt, sitemap, lastmod, internal links | PASS | Google-readiness: 83 passed, 0 failed, on 36 sitemap URLs. | — |
| 24 | Real Googlebot can fetch the site | UNKNOWN | The sitemap probe has failed daily since 27 October: the Hostinger CDN (`hcdn`) returns 403 to the Googlebot user agent from GitHub's addresses. A normal browser gets 200. | If the CDN also blocks Google's real crawler, nothing will be indexed. Check with Search Console URL Inspection, live test. |
| 25 | Search Console connected | NOT IMPLEMENTED | Live Verification: `gsc_connected: false`, `gsc_last_sync: null`. The weekly search report has nothing to report. | This is the main organic-growth signal (`docs/ops/OPERATING-MODE.md`). Without it, indexing and demand can't be measured. |
| 26 | Analytics and conversion tracking | PARTIAL | A first-party funnel records events. Last 30 days: course viewed 3,474 → apply viewed 352 → lead created 3 → account created 1 → applications 0. GA4 is not configured. The admin checklist's "Analytics decision made" needs `SITE_ANALYTICS_DECISION` or a GA4 ID. | The funnel works. Record the analytics decision. The conversion rate is very low, and the traffic source can't be explained without Search Console. |
| 27 | Accessibility, mobile, Content Security Policy | PASS | Production review: 80 page views (desktop and mobile), 0 problems. Lighthouse accessibility 100 on every page measured. | — |
| 28 | Performance | PASS | Lighthouse mobile: Perf 96–99, LCP 1.8–2.4 s, CLS 0.000. The workflow "failed" only because of the two now-noindex pages (item 12). | — |
| 29 | Security headers and access control | PASS | HSTS, CSP, X-Content-Type-Options, Referrer-Policy, X-Frame-Options and Permissions-Policy are sent. Foreign hosts are refused. Guessed document URLs need sign-in. | — |
| 30 | Backups | PASS | Encrypted off-site backup ran successfully every day, 26–29 October. | A restore test was part of the original launch path. Repeat it after any schema change. |
| 31 | Scheduler, queue, monitoring | PASS | Heartbeat 0 minutes old. Queue empty, 0 failed jobs. Uptime check runs every 30 minutes and passes. Retention last ran 25 October (weekly). | — |
| 32 | Deployment reliability and parity | PASS | Production release `2026-10-28T11-12-26` = commit `5d76858`. Every later commit (manifest, outreach and audit docs) is documentation only. No pending migrations. Deploys require approval, with rollback available. | No unreleased code. |
| 33 | Repository SEO reports up to date | FAIL | `docs/seo/PAGE-AUDIT.md` lists 28 URLs; the live sitemap has 36. `docs/seo/SCHOOL-PUBLICATION-AUDIT-2026-10-26.md` predates 15 further publications. | Regenerate (`python3 ops/seo/page-audit.py`) so decisions use current data. |

## 5. SEO and organic growth

| # | Task | Status | Evidence and affected file/page | Why |
|---|---|---|---|---|
| 34 | Core intent pages | PASS | 36 indexed pages cover the main Nigerian query families, each with a register row: study Medicine in the UK from Nigeria, WAEC, NECO, A-levels, graduate entry, English, fees, UCAT, UCAS deadlines, how to apply, foundation routes and working in the UK. Word counts 623–2,981. Each links to eligibility and Apply. | — |
| 35 | Thin or duplicate content | PASS | No page flagged by the readiness audit. The shortest indexed pages are trust and legal pages (`/terms` 275 words, `/refund-policy` 352). | — |
| 36 | New pages | Do not add yet | Register: 201 rows (51 PUBLISHED, 93 RESEARCH, 14 VALIDATED, 40 REJECTED, 3 UPDATE). Semrush returned `no_api_units` on both checks. | No new demand evidence exists until Search Console is connected (item 25) or Semrush units are available. Upgrade existing pages and restore flagged facts instead. |
| 37 | Cost-of-study page and UCAS page | FAIL | `/fees/cost-of-studying-medicine-in-the-uk` dropped out of the sitemap on 29 October (UCAS `extra_opens` flagged). | A high-value page; re-verify the one fact. |

## 6. Business and conversion readiness

| # | Task | Status | Evidence and affected file/page | Why |
|---|---|---|---|---|
| 38 | Paid services (T1–T3) | PARTIAL | Prices exist only in the Stripe catalogue and the portal, and are shown only to an approved student. Live payments are open. | Items 1–5 must be closed before taking money. |
| 39 | University partnerships, agency or commission | NOT IMPLEMENTED | No agreement exists (`/our-status`). The owner reports 23 enquiries sent on 28 October; the repository log records none (`research/university-agent-partners/OUTREACH-LOG.md`). | Nothing on the site may imply a partnership until an agreement is signed and listed. |
| 40 | Staffing and response | FAIL | 1 admin, 0 staff. The contact page promises a reply within 2 working days, but the leads have waited 9 days. | Operational capacity is what turns traffic into students. |

## Priority order

1. Items 1–4: legal identity, the blank-name defect, ICO, and the legal review.
2. Item 5: the payment rehearsal and the Stripe support email.
3. Item 6: answer the three leads.
4. Items 24–25: confirm Googlebot access and connect Search Console.
5. Items 7, 11, 12 and 37: the indexing decisions, unpublishing schools without data, and re-verifying the four flagged facts.
6. Items 17–18 and 20: one full test student journey in production, including email delivery.
7. Item 33: refresh the SEO reports.

## Addendum: status after the completion work (2026-10-29, evening)

The audit above is kept as it was written. This table records what changed on `claude/new-session-p6gdm6`. Nothing below is live until the owner approves the next deploy. Details: `SITE-COMPLETION-REPORT.md`, `SITE-REMEDIATION-PLAN.md`, `OWNER-LEGAL-CHECKLIST.md` and `UNIVERSITY-VERIFICATION-REPORT-2026-10-29.md`.

| Item | Was | Now (branch) | Remaining |
|---|---|---|---|
| 1 Trading identity | FAIL | FAIL. The admin checklist and Live Verification now report which values are missing | owner supplies the values (checklist §1) |
| 2 Blank legal name | FAIL | FIXED (`config/site.php`, `?:` fallbacks; `LegalIdentityTest`) | deploy |
| 3 ICO | FAIL | FAIL | owner |
| 4 Legal review | FAIL | FAIL. Also found: no model cancellation form (CCR reg. 13(1)(b)) and no complaints procedure (Sch. 2 (o)); a draft procedure is ready | owner or adviser |
| 5 Payment rehearsal | PARTIAL | PARTIAL. 48 payment tests and the T1–T3 stand-in journeys pass; real test mode is blocked (no `STRIPE_TEST_SECRET`) | owner |
| 6 Leads | FAIL | FAIL. An overdue indicator was added (`LeadFollowUpTest`) | owner replies |
| 7 Cambridge, QUB, KCL | FAIL | DECIDED BY THE OWNER (2026-10-30): all three stay indexed as an explicit exception, recorded in D14, D27 and D34. Their status stays RESEARCH and no demand evidence is claimed. Code (ab0894f and the exception update): register approval is required for every other school | revisit with Search Console data |
| 11 Schools without core facts | FAIL | 30 facts re-verified: 17 schools at 5/5 after deploy (7 now; Imperial dropped back to 4/5 when the watcher flagged its English requirement on 30 October) | about 121 flagged facts still to re-read |
| 12 Watcher flags of 29 October | FAIL | FIXED. All four re-verified, and bot-check pages no longer raise flags (`CheckSources::isChallenge`) | deploy |
| 24 Googlebot access | UNKNOWN | Probe no longer fails on the CDN's fake-Googlebot block (self-test in CI); real access still unproven | owner: URL Inspection live test |
| 25 Search Console | NOT IMPLEMENTED | unchanged | owner (data feed steps) |
| 33 SEO reports | FAIL | FIXED (`PAGE-AUDIT.md`: 39 expected URLs, 0 flagged) | — |
| 37 Cost page | FAIL | FIXED (UCAS Extra opens re-verified) | deploy |
| new | — | CI dependency audit failing since the `symfony/mime` advisory of 28 October: FIXED (7.4.22) | deploy |

**Verdict after this work: NOT READY to take payments.** Items 1, 3 and 4 and the cancellation form are owner-only. The information site is technically sound.
