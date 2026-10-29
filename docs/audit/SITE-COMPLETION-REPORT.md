# Site completion report (2026-10-29)

**Scope:** StudyMedicineUKNigeria.com. **Branch:** `claude/new-session-p6gdm6`. **Production:** release `2026-10-28T11-12-26` (commit `5d76858`), unchanged.

Work on the plan in `docs/audit/LAUNCH-READINESS-AUDIT-2026-10-29.md`:
- **What was done:** repository fixes with tests, read-only checks of production, and official-page reads from GitHub runners.
- **What was not done:** no deploy, no change to the server, environment, DNS, Stripe, database records, legal declarations or email; no message to a lead or a university.

## Verdict: NOT READY (to take payments)

**Payments are open** in production with a live Stripe key. Required consumer-law information is still missing:
- the trader's legal name and geographic address;
- the model cancellation form;
- a complaints procedure.

**The ICO fee and the legal review** are not done either.

**Only the owner can supply these.** Until then, no student should be approved for paid services.

**As an information and lead-capture site it works.** That covers:
- 36 indexed pages, 83/83 Google-readiness checks passing, Lighthouse 96–99 and accessibility 100;
- every journey passing locally;
- security headers and access control in place.

**It becomes CONDITIONALLY READY** once owner actions B2, B4, B5 and B6 of `SITE-REMEDIATION-PLAN.md` are done.

## Unresolved critical and high blockers

| # | Severity | Blocker | Who | Where |
|---|---|---|---|---|
| 1 | Critical | Trading identity not published: no legal name and no geographic address. CCR 2013 Sch. 2 (b)–(e) and E-Commerce Regs reg. 6. Payments are live | Owner | `OWNER-LEGAL-CHECKLIST.md` §1 |
| 2 | Critical | No model cancellation form (CCR 2013 reg. 13(1)(b)). The statutory text exists only as an image on legislation.gov.uk and was not reconstructed | Owner or adviser | checklist §2 |
| 3 | High | No complaints procedure (Sch. 2 (o)). Draft ready for approval | Owner | checklist §3 |
| 4 | High | ICO data protection fee not paid and no reference published. The site holds student identity documents | Owner | checklist §1 |
| 5 | High | Legal pages still "Version 0.9.2 … under legal review" | Owner's adviser | checklist §6 |
| 6 | High | Stripe support email not set; £125 live rehearsal not run; test-mode journey blocked (no `STRIPE_TEST_SECRET`) | Owner | checklist §4 |
| 7 | High | Three eligibility leads unanswered for 9 days, against a promise of 2 working days | Owner | Admin → Leads |
| 8 | High | Search Console feed not connected, so there is no demand evidence for indexing decisions | Owner | `docs/ops/SEARCH-CONSOLE-AND-GA4.md` "Data feed" |

**Fixed in the branch, live after deploy:**
- the blank legal name;
- CI's failing dependency audit;
- the daily false failure of the sitemap probe;
- the watcher flags on Aberdeen, Buckingham and UCAS.

## Fixes (commits on `claude/new-session-p6gdm6`)

| Commit | Fix | Files |
|---|---|---|
| b92d1e3 | Empty identity settings count as unset; no blank legal name anywhere; admin checklist and Live Verification report identity presence | `config/site.php`; `resources/views/layouts/public.blade.php`; `resources/views/content/org/{contact,legal-terms,about,legal-privacy,status}.blade.php`; `resources/views/vendor/mail/html/message.blade.php`; `app/Http/Controllers/Admin/DashboardController.php`; `.github/workflows/live-verify.yml`; `tests/Feature/LegalIdentityTest.php` |
| d663616 | Overdue-lead indicator (2 working days) on the dashboard and in Admin → Leads | `app/Models/Lead.php`; `app/Http/Controllers/Admin/MiscAdminController.php`; `resources/views/admin/{leads,dashboard}.blade.php`; `tests/Feature/LeadFollowUpTest.php` |
| 7a1372c | The source watcher treats bot-verification pages as unreachable | `app/Console/Commands/CheckSources.php`; `tests/Feature/SourceWatcherTest.php` |
| 2fc065e, f61987e, 48a2f9a, 1d3e72a | 30 facts re-verified from official pages; Imperial's route corrected | `ops/verification/reviews/2026-10-29-*.json`; `data/verification/decisions-2026-10-29-*.csv` |
| 05eb2cd | Sitemap probe: CDN 403 to a fake Googlebot is a warning; a real outage fails; self-test in CI | `ops/sitemap-probe.sh`; `ops/qa/sitemap-probe-selftest.sh`; `ops/qa/fixtures/fake-cdn.py`; `.github/workflows/{ci,sitemap-probe}.yml` |
| d29286b | SEO page audit regenerated (36 URLs, 0 flagged) | `docs/seo/PAGE-AUDIT.md` |
| 41e2a80 | A vacuous test now checks real course records | `tests/Feature/HealthcareTaxonomyTest.php` |
| efa5fbd | `symfony/mime` 7.4.22 (CVE-2026-45070) | `composer.lock` |
| ab0894f | A school is indexed only with a live decision-register row as well as verified facts and publication; Admin → Universities shows the reason a page is noindex | `app/Support/DecisionRegister.php`; `app/Models/University.php`; `app/Http/Controllers/Admin/{ReferenceAdminController,DashboardController}.php`; `resources/views/admin/reference/universities.blade.php`; `tests/Feature/SchoolRegisterGateTest.php`; `tests/Feature/PublicSeoTest.php` |
| c5105a9, f12fac8, 22e71d9 and later | Owner legal checklist, university report, remediation plan, this report, audit addendum, pre-deployment checklist | `docs/audit/*` |

## Tests and results (29 October)

| Command | Result |
|---|---|
| `vendor/bin/pint --test` | passed |
| `php -d memory_limit=1G vendor/bin/phpunit` | **278 passed**, 19,374 assertions, 0 risky (after the register gate) |
| `php -d memory_limit=1G vendor/bin/phpunit --filter 'Stripe\|Payment\|Pricing\|Checkout\|Webhook'` | 48 passed: webhook signature, idempotency, livemode, amount and currency match, duplicates, price privacy |
| `ops/qa/sitemap-probe-selftest.sh` | 3/3 (open, fake-Googlebot block, outage) |
| `ops/production-rehearsal.sh 8090` (production mode, throwaway database) | all production checks passed (44 ok) |
| `node ops/qa/journey.cjs` | eligibility → register → verify → application → autosave → passport upload → documents, payments, messages, submissions → data export: 0 problems |
| `node ops/qa/staff-journey.cjs` | admin two-step sign-in → document review → service approval (T1 £125, T2 £695, T3 £1,295 shown only now) → proposal → student approval → submitted with reference: 0 problems |
| `node ops/qa/payment-journey.cjs` (phone and desktop, Stripe stand-in) | no public fee leak; cancel at checkout → retry → pay → signed webhook → paid; the page amount equals the charged amount (£695): 0 problems |
| `node ops/qa/stripe-e2e.cjs` (stand-in, T1–T3) | T1 declined card shown, then paid £125; T2 £695; T3 £1,295; each read back: `livemode false`, `paid`. One earlier attempt timed out at the admin step (likely the reused two-step code from the previous run); the rerun passed |
| `composer audit`, `npm audit --omit=dev` | no advisories |
| `python3 ops/verification/check-quotes.py` (four review files) | 0 problems |
| CI | green on every push from `c5105a9` to `22e71d9`; see `PRE-DEPLOYMENT-CHECKLIST.md` for the final head |

**Stripe.** Sandbox testing against real Stripe is BLOCKED: there is no `STRIPE_TEST_SECRET`. Everything that can be tested without it passes:
- the mocked unit tests;
- the stand-in journeys.

The server verifies the webhook signature and never trusts the return URL.

## Owner information needed

- **Legal name** of the trading entity. If incorporated: company number, part of the UK and registered office. Otherwise a geographic business address.
- **ICO registration reference**, or the exemption reason.
- **VAT number**, only if registered.
- **Legal review date.**
- **The Schedule 3 Part B form text**, as on legislation.gov.uk, and the two time limits for the complaints procedure.
- **Stripe support email and statement descriptor.**
- **A `sk_test_` key** for `STRIPE_TEST_SECRET`.
- **Analytics choice:** `first_party` or `ga4`.
- **Decisions on the 12 schools that have no Nigerian demand evidence:**
  - Cambridge, Queen's Belfast and King's College London: indexed now, and leave the sitemap on deploy unless the owner records a publication directive in their register rows;
  - Aston, Brunel, Chester, Imperial, Keele, Kent and Medway, Lancaster, Surrey and Swansea: facts verified, but they stay noindex until the register records demand evidence or an owner directive.

## Production changes awaiting approval

1. **Deploy this branch** with *Deploy to Hostinger*, target production. It runs the production rehearsal, takes a backup, needs approval, and rolls back on a failed smoke test. Effect:
   - legal-name fallback;
   - overdue-lead indicator;
   - watcher fix;
   - security update;
   - 30 fact decisions imported;
   - the sitemap stays at 36 URLs, with different members:
     - out: Cambridge, King's College London and Queen's Belfast (no live register row);
     - in: Aberdeen, Buckingham and the cost page (see `PRE-DEPLOYMENT-CHECKLIST.md`).
2. **Set the identity variables** on the production environment, then run *Update server settings*. Do this only after the owner supplies the values.
3. **Admin actions (owner):** publish or unpublish decisions for the schools listed above, and reply to the three leads.

## Manual steps (owner)

These are in `OWNER-LEGAL-CHECKLIST.md` and `SITE-REMEDIATION-PLAN.md` §B:
- Search Console data feed (about 15 minutes);
- URL Inspection live test of `/sitemap.xml`;
- Stripe Dashboard settings;
- the £125 live rehearsal with refund;
- a second staff account.

**The sitemap probe.** The Hostinger CDN refuses requests that claim to be Googlebot from GitHub's addresses. It serves browsers normally. So the probe now judges content as a browser sees it and only warns about the fake-Googlebot 403. Only Search Console can show what real Googlebot receives.

## The single highest-value next action

**The owner supplies the trading identity:** legal name and geographic address, plus the ICO registration.

It unblocks:
- the legal pages, the Stripe account details and the cancellation form's "To" line;
- the complaints procedure;
- the legal review.

Together these turn the verdict to CONDITIONALLY READY. On the same day, answer the three waiting leads: they are the only conversions the site has produced.
