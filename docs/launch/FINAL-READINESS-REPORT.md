# Final readiness report (2026-10-30)

**Scope:** StudyMedicineUKNigeria.com, branch `claude/new-session-p6gdm6`. **Production:** release `2026-10-28T11-12-26` (commit `5d76858`), unchanged.

**Not done** (owner rules): no deploy, no production change, no live Stripe change, no payment or refund, no email to a student or lead.

## 1. Verdicts (two separate decisions)

| Decision | Verdict | Why |
|---|---|---|
| **Technical deployment** of the branch head | **Ready, awaiting the owner's explicit approval** | All tests, the rehearsal, the journeys, the SEO and security checks pass (§3). The sitemap is computed exactly (39 URLs). A rollback undoes the release's fact decisions automatically. The deploy only reads Stripe. No migrations |
| **Commercial launch** (taking students' money) | **NOT READY** | The legal identity, ICO position, legal review, cancellation form, complaints procedure, contract-confirmation email and Stripe settings are unresolved (`OWNER-ACTION-REGISTER.md`, items marked L) |

**One change to watch.** Cardiff's 2026/27 overseas fee source page now redirects to the 2027-entry page, where the fee is "awaiting confirmation" (`evidence/REVERIFY-2026-10-30.md`). The nightly source watcher will probably flag it. Cardiff then turns noindex automatically and the live sitemap would show 38 URLs, not 39, until a reviewer decides (O21). The design intends this; it is not a fault.

## 2. Work completed in this programme

| Workstream | Completed | Files |
|---|---|---|
| 1 Legal identity | **Inspected:** every public page, footer, email template, checkout and legal page. **Found:** four different version labels on the legal pages; wording that assumed incorporation ("To be published on incorporation", "Registered address"); no setting for the business structure. **Built:** one identity configuration (`LegalIdentity`: name, structure, company number, part of the UK, address, VAT, ICO) used by Our status, Contact, terms, privacy, footer, emails and the admin checklist; settings `SITE_ENTITY_TYPE` and `SITE_REGISTERED_IN` plumbed through *Update server settings*; *Live verification* reports the structure. ICO self-assessment written against the official criteria (no assumption) | `app/Support/LegalIdentity.php`, `config/site.php`, views, `ops/update-env.sh`, `update-env-hostinger.yml`, `live-verify.yml`, `LegalIdentityTest` |
| 2 Legal documents | Gap analysis against CCR 2013, the E-Commerce Regulations, the trading disclosure rules, the CRA 2015, the 2018 fee regulations and the DMCCA 2024, with verbatim quotes read today. Draft cancellation information, complaints procedure and contract-confirmation content; 13 clauses listed for the adviser. **Every legal page now says "DRAFT — LEGAL REVIEW REQUIRED"** until `SITE_LEGAL_REVIEWED` is set. No legal wording was otherwise changed | `LEGAL-REVIEW-PACK.md`, `resources/views/components/legal-version.blade.php` |
| 3 Stripe | Inspected tiers, checkout, webhook signature, duplicate events, mode, amount and currency checks, refunds, disputes and access rules; ran T1–T3 against the stand-in; wrote the test-mode workflow and the £125 live rehearsal procedure. The deploy's Stripe step is read-only (commit 07c1b63) | `STRIPE-TEST-AND-LIVE-REHEARSAL.md` |
| 4 Student journeys | Visitor → enquiry → account → application → upload → staff review → approval → payment → submission → export, on synthetic data; privacy and access checks; every transition recorded | `STUDENT-JOURNEY-TEST-REPORT.md` |
| 5 Enquiries | The three overdue leads were identified by count. Their details stay on the server (the repository and its logs are public). **Built:** a reply draft for each lead in Admin → Leads, from its own answers, sending nothing | `app/Support/LeadReplyDraft.php`, `resources/views/admin/leads.blade.php`, `LeadFollowUpTest` |
| 6 SEO | Register and exceptions intact (D14, D27, D34; tests pin them, and no demand is claimed). 39 URLs, robots and canonicals re-checked on the production mirror. The nine indexed schools' and the cost page's 54 facts re-checked against official pages today: 52 still match, 2 changed (Cardiff). Evidence needs listed for the 30 noindex schools. Search Console plan written. **Semrush blocked:** no API units | `OPERATIONS-MANUAL.md` §7–§8, `evidence/REVERIFY-2026-10-30.md` |
| 7 Operations | Scheduled jobs, workflows, backups, restore, watcher, audit logs, email, access, incidents and rollback documented, with each workflow's latest result | `OPERATIONS-MANUAL.md` |
| Earlier today | A rollback undoes fact decisions (8f5fcea); the deploy only checks Stripe (07c1b63) | `docs/audit/PRE-DEPLOYMENT-CHECKLIST.md` |

## 3. Verification results (exact)

| Check | Command or run | Result |
|---|---|---|
| Automated tests | `php -d memory_limit=1G vendor/bin/phpunit` | **288 passed**, 19,555 assertions, 0 failed |
| Formatting | `vendor/bin/pint --test` | passed |
| Dependency audits | `composer audit`; `npm audit --omit=dev` | no advisories; 0 vulnerabilities |
| Script syntax | `bash -n ops/*.sh ops/qa/rollback-proof/*.sh` | ok |
| Sitemap probe self-test | `ops/qa/sitemap-probe-selftest.sh` | 3/3 (open; fake-Googlebot block with warnings; outage fails) |
| Production rehearsal | `.env` moved aside, `ops/production-rehearsal.sh 8090` | "rehearsal: all production checks passed" (44 ok, 0 fail) |
| Public journey | `public-journey.cjs` (phone) | `problems: []` |
| Student journey | `journey.cjs` | `problems: []` (application SMUKN-2028-000010) |
| Staff journey | `staff-journey.cjs` | `problems: []`; SUBMITTED with reference |
| Payment journey | `payment-journey.cjs` phone and desktop | SUCCEEDED 69500 GBP; the page amount equals the charge |
| Payments, all tiers | `stripe-e2e.cjs` against the stand-in | T1 (declined, then paid) 12500, T2 69500, T3 129500: SUCCEEDED, `livemode false`, `ok: true` |
| Payments, real Stripe test mode | *Stripe test-mode journey* | **BLOCKED**: no `STRIPE_TEST_SECRET` (O14) |
| SEO and Google readiness | `google-audit.py --connect` (production mode, post-deploy mirror) | **86 passed, 0 failed**; 39 sitemap URLs, 85 internal URLs |
| Sitemap and indexability | `ops/qa/rollback-proof/serve.sh` on the mirror | 39 URLs, identical to `docs/seo/PAGE-AUDIT.md`. The nine schools are `index, follow, max-image-preview:large` with self canonicals; Aston and Imperial are `noindex, nofollow` |
| Facts on indexed pages | page-excerpts runs 41027257475, 41027754353 | 52 of 54 still match; 2 changed (Cardiff) |
| Security and privacy | security, document, two-step, pricing-leak and access tests in the suite | pass (`STUDENT-JOURNEY-TEST-REPORT.md` §2) |
| Live operations | GitHub Actions history | backups 27–29 Oct success; uptime success (30 Oct 08:32); CI green |
| Known red runs | — | Sitemap probe 28–29 Oct: a false alarm fixed in 05eb2cd, after those runs. Performance check 29 Oct: SEO 69 on Aberdeen and the cost page, which are noindex on live today and become indexable with this deploy. Stripe test-mode journey: no test key |
| CI on the final head | GitHub Actions *CI* | see `FINAL-LAUNCH-CHECKLIST.md` (filled after the push) |

## 4. Failures and limits, stated plainly

- **No real-Stripe test.** Payments were proven against the local stand-in only.
- **Semrush:** `no_api_units`, so no keyword data was obtained, and no demand is claimed anywhere.
- **The model cancellation form** is not reproduced from the UK regulations: legislation.gov.uk publishes it only as an image. The pack gives the Directive's text, which is marked for the adviser.
- **ADR.** The 2015 Regulations are revoked, and the DMCCA 2024 chapter has been in force since 6 April 2026. What a trader must now tell consumers was not found in the text read; it is left to the adviser.
- **The ICO self-assessment** itself must be completed by the owner; its questions were not machine-readable.
- **The three lead replies** are not individually drafted in this repository, for privacy. The drafts are generated in Admin after the deploy.

## 5. Recommendation

1. **Deployment:** approve the deploy of the branch head named in `FINAL-LAUNCH-CHECKLIST.md`, after a fresh *Operations summary*. It is safe on the evidence above and reversible, facts included.
2. **Commercial launch:**
   - do not approve any student for paid services until items O1–O16 are closed;
   - the first step is O1–O4 (identity and ICO), which unlocks the legal review (O8–O12) and the Stripe details (O13).
3. **Today:** reply to the three overdue enquiries (O17). This needs no deploy.
