# Autonomous owner cycle — 2026-10-03

Format per the owner directive: inspected / found / changed / why / tested / passed / failed / remains / next.

## State at 02:30 UTC on 2026-10-04 (read this first)

| Area | State |
|---|---|
| Code | HEAD on `claude/new-session-p6gdm6`, 132 tests green (two full security audits, all findings fixed) (incl. a production-mode sweep that no unverified wording reaches any public page, which found and fixed three leaks today: UCAS-code notes, GMC-status wording, research notes in two course titles and one school name), CI green on every push; 28 indexable pages crawl clean; axe zero violations; fresh-account journey clean |
| Deployment | Ready (inspect → bootstrap → deploy → smoke → rollback rehearsed) but **blocked**: no Actions secrets or variables exist in any scope (last check 02:23 UTC on 2026-10-04, run 14: all 18 settings missing in repository, staging and production scopes); the deploy workflow has never run |
| Facts | 636 pending (535 VERIFY-ON-PAGE, 101 NOT_FOUND); official domains are blocked from this environment, so verification runs from the owner's browser via `data/verification/worksheet-2026-10-04.csv` (110 priority-1 rows; regenerated 2026-10-04 to add the prioritisation Act and two changed visa figures) |
| SEO | Decision register with 146 query families incl. cluster V (36-subject healthcare taxonomy, `data/healthcare/subjects.json`, Admin → Subjects; only Medicine may have a page) and reasons (`data/seo/decision-register.csv`, Admin → SEO); 22-point technical audit clean; eleven intent pages upgraded; Semrush figures await the owner's export |
| Conversion | Eligibility → account → application → documents → payments → export tested on a phone viewport; two wording defects found and fixed today (route-map headline, passport checklist reason) |
| Owner-only | listed in the last section, in order of value: verification worksheet · Actions settings · network allow-list · Semrush export · Unsplash picks · prices, legal review, email DNS |

## Cycle 1 (baseline)

- **Inspected**: repository state, GitHub Actions runs, SSH reachability from the Claude environment, local test suite.
- **Found**: HEAD synced with origin; 21 tests green; inspection workflow run 37126232582 fails at "Check configuration" because `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER` (variables) and `HOSTINGER_SSH_KEY` (secret) are absent; direct SSH still blocked by network policy; Dependabot PR #1 open.
- **Changed**: `docs/BASELINE-ASSESSMENT.md`; `ops/server-bootstrap.sh` + `bootstrap-hostinger.yml` (idempotent target preparation, guarded); merged Dependabot PR #1 (actions majors) and aligned the new workflow.
- **Tested / passed**: YAML validated, `bash -n`, CI green on every push.
- **Remains**: live server inspection and all deployment steps (blocked on the owner's Actions settings).

## Cycle 2 — P0 staff two-step verification (stage 7)

- **Found**: 2FA columns existed but nothing enforced them; any staff password alone opened the admin area and student documents.
- **Changed**: dependency-free RFC 6238 TOTP, `EnsureTwoFactor` middleware on portal and admin, enrolment/challenge/recovery pages, admin reset (audited), console lock-out recovery, profile entry point for students.
- **Tested / passed**: 29 tests incl. RFC vectors, replay refusal, recovery-code single use; browser flow on desktop and mobile. **Failed then fixed**: a self-recursive test helper (caught by a memory blow-up in the suite).

## Cycle 3 — P0 Content Security Policy enforced (stage 8)

- **Found**: CSP was report-only with `'unsafe-inline'` scripts; four inline handlers in views.
- **Changed**: enforced nonce-based policy, inline handlers removed, `data-confirm` helper.
- **Tested / passed**: 30 tests; Chromium sweep of 28 public/portal/admin pages with zero CSP violations; confirm dialog verified both ways.

## Cycle 4 — P0 encrypted off-site backups (stage 9)

- **Changed**: `ops/backup.sh`, `backup-hostinger.yml` (daily db / weekly full, artifact retention 14/28 days), `ops/RESTORE.md`, architecture 21.5.
- **Tested / passed**: local run against a fake target through the same stdin pipe the workflow uses; decrypts with the right passphrase, refuses the wrong one, passphrase never on disk. **Failed then fixed**: pruning step aborted under `pipefail` when no full copy existed.
- **Remains**: first real run needs `HOSTINGER_*` settings plus a `BACKUP_PASSPHRASE` secret (owner generates it, e.g. `openssl rand -base64 32`, and stores it in a password manager — without it backups cannot be read).

## Cycle 5 — P7 funnel measurement (stage 10)

- **Changed**: `funnel_events` first-party event mirror (no PII), Admin → Funnel, optional consent-gated GA4 (`SITE_GA4_ID`), privacy notice wording.
- **Tested / passed**: 34 tests; browser: banner → decline sends nothing; accept loads the tag once and forwards `lead_created`; portal/admin never include Google hosts.

## Cycle 6 — P5 snippet hygiene and thin hubs (stage 11)

- **Inspected**: Chromium crawl of all public pages (titles, descriptions, H1s, alt text, JSON-LD, links, sitemap coverage).
- **Found**: no broken links; 19 over-long titles, 21 descriptions outside limits, contact and admissions hubs thin.
- **Changed**: conditional brand suffix, every title/description rewritten, legal descriptions, guard test over the sitemap, real content on both hubs.
- **Tested / passed**: 35 tests; re-crawl shows zero title/description findings.

## Cycle 7 — P6 accessibility (stage 12)

- **Inspected**: axe-core over 55 pages (public desktop/mobile, portal, admin). **Found**: 7 rule failures (contrast, landmarks, unlabelled selects/inputs, scroll regions, pagination ARIA, empty headers). **Changed**: all fixed, custom pagination view. **Tested / passed**: re-run zero violations; 35 tests.

## Cycle 8 — P4/P5 Working in the UK page + publish gate (stage 13)

- **Changed**: new page from research 10 with fact rows; `PublishGate` makes fact-driven pages indexable only when their topics are verified in Admin → Verification (also applied to the total-cost page). **Tested / passed**: 36 tests; page renders with the verification notice locally.
- **Remains**: the owner (or staff) verifies the GOV.UK / GMC / UKFPO facts in the verification queue; the page then enters the sitemap automatically.

## Cycle 9 — operations readiness (P8)

- **Found**: `composer audit` cannot reach packagist from this environment; no uptime monitoring; no email authentication guidance. **Changed**: Dependabot now covers Composer and npm (security advisories and grouped minor/patch PRs); `uptime-check.yml` runs the smoke tests every 30 minutes once `PRODUCTION_URL` exists and keeps one incident issue open while failing; `docs/ops/EMAIL-DELIVERABILITY.md` lists the SPF/DKIM/DMARC steps. `npm audit`: 0 vulnerabilities.

## Cycle 10 — staging gate and verification throughput (stage 15)

- **Found**: architecture 21.6 promised HTTP basic auth on staging but nothing implemented it; the verification queue forced one fact at a time against a 533-fact backlog. **Changed**: `StagingGate` + wiring; *Verify by source* bulk view. **Tested / passed**: 40 tests.
- **Owner follow-up**: add repository variable `STAGING_BASIC_USER` and secret `STAGING_BASIC_PASSWORD` before bootstrapping staging (optional but recommended).

## Cycle 11 — deployment rehearsal (stage 16)

- **Tested / passed**: inspect, bootstrap, two deploys, smoke, rollback and backup against a local SSH server (`ops/reports/deployment-rehearsal-2026-10-03.md`). **Failed then fixed**: crontab guard, DB override, rollback PHP binary.

## Cycle 12 — transactional email (stage 17)

- **Found**: notification emails had never been rendered by any test and used the unbranded framework theme. **Changed**: branded theme, rendering test for every type. **Tested / passed**: 42 tests; sample email rendered in Chromium.

## Cycle 13 — payment webhook, scheduler and error pages (stage 18)

- **Found**: the Stripe webhook and the three scheduled commands had no tests; only the 404 page was branded. **Changed**: signed-webhook tests, scheduler tests, 419/429/500/503 pages. **Tested / passed**: 49 tests.

## Cycle 14 — portal payment, document and export coverage (stage 19)

- **Changed**: tests for bank transfer → admin confirmation, encrypted passport round-trip with access log, data export; skeleton test removed. **Tested / passed**: 51 tests.

## Cycle 15 — access verification after "credentials configured"

- **Inspected**: inspection workflow runs #3 and #4, a new "Diagnose Actions settings" workflow (repository scope and every environment), direct SSH and this environment's variables.
- **Found**: the repository has no Actions secrets or variables anywhere; the two environments that exist were created by earlier runs and are empty; direct SSH remains blocked. Full record: `ops/reports/access-check-2026-10-03b.md`.
- **Changed**: diagnosis workflow (presence-only, re-runnable by the owner); inspection accepts host/user/port from variables or secrets.
- **Blocked**: genuine credential availability. No access fabricated, nothing deployed.

## Cycle 16 — owner-assisted work split (stage 20)

- **Category B (owner's browser)**: enter the five Actions settings (exact steps given in chat and in `access-check-2026-10-03b.md`); pick photographs per `docs/design/IMAGERY-BRIEF.md`; SEMrush exports when wanted (`docs/research/02` lists the queries to look up).
- **Category A (done here)**: funnel view events, client interaction events, photograph pipeline and component, imagery brief. A self check-in re-runs the settings diagnosis at 15:45 UTC and continues the deployment cycle automatically if the settings are present.

## Cycles 17–19 — sharing cards, server rules, trust page (stages 22+)

- **Changed**: branded per-page Open Graph cards (30 static + on-demand university cards) for WhatsApp/social sharing; `public/.htaccess` with https and non-www canonical redirects, dotfile denial, asset caching and compression (smoke test checks the redirects on production); `/how-we-verify` editorial and verification policy page with live record counts, linked from every verification chip and the footer; Search Console / GA4 owner checklist; asset register build-status section; dynamic year bounds; fact-driven eligibility dates.
- **Tested / passed**: 57 tests; cards and page rendered in Chromium. Settings diagnosis re-run at 15:03 and 15:14 UTC: still nothing present.

## Cycle 20 — security audit and fixes (stage 25)

- **Inspected**: delegated read-only audit of authorisation, two-step, documents, payments, sessions, leakage and file writes; every finding re-verified in code. **Found**: 10 issues, two high (student two-step bypass with the password alone; email-verification links fatal because of a missing import). **Changed**: all fixed with regression tests; PDF check now inflates streams and decodes name escapes. **Tested / passed**: 66 tests. Record: `ops/reports/security-audit-2026-10-03.md`.

## Cycle 21 — auth flow coverage and handover notes

- **Changed**: end-to-end tests for registration → verification link → portal and the full password-reset loop (the gates every student passes); `CLAUDE.md` with the rules, commands and conventions for future sessions; university pages link to the how-to-apply route. **Tested / passed**: 68 tests, CI green. Settings diagnosis at 17:12 UTC still empty; next self check-in 18:15 UTC.

## Cycle 22 — SEO decision engine and technical audit (stage 26)

- **Inspected**: Actions settings (run 8 at 17:49 UTC and run 9 at 18:19 UTC after the scheduled check-in: all MISSING in repository scope and both environments); Semrush MCP (still `no_api_units`); every sitemap URL for canonical, robots, schema, headings, images and in-body internal links. **Found**: the core landing page had a single in-body inbound link; the fee guide and FAQ lacked heading structure; the directory had no list schema; PHP exposed `X-Powered-By`; the graduate-entry and NECO pages were the two thinnest intent pages. **Changed**: `data/seo/decision-register.csv` + `docs/seo/DECISION-ENGINE.md` (65 query families, statuses with reasons, link plan) with an enforcing test; nine contextual links to the core landing page; headings, ItemList, header removal; graduate-entry, NECO, how-to-apply, UCAT, English-requirements, UCAS-timeline, A-levels and foundation-routes pages rewritten (eight intent pages, 317–643 words each before, 950–1,540 after); Admin → SEO shows the register with a status filter from the research evidence. **Verified (P0)**: throttles on all public write routes, 10-character letters-and-numbers password rule, secure session cookies in the server bootstrap, `npm audit` clean, fresh-account journey and 28-page crawl clean. **Tested / passed**: 74 tests; CI green on every push; pages rendered locally with no errors; axe zero violations; full 22-point technical audit written to `ops/reports/technical-seo-audit-2026-10-03.md` (production-only items listed for after deployment). One further self check-in armed for 19:15 UTC because the owner has written since the last one.
- **Owner (category B)**: unchanged below. For the SEO engine specifically: Semrush exports (database `ng`, then `uk`) for the 27 themes in `data/semrush/lookup-sheet.csv` and the question filter for the 18 seeds in research 02 §E, saved as `data/semrush/lookups-2026-10-03.csv`; the importer fills research 02 and the register's volume/KD cells.

## Cycle 23 — knowledge-graph links and second security sweep (stage 27)

- **Inspected**: in-body inbound links for all 28 sitemap pages; raw output, mass assignment, `env()` use and unthrottled write routes. **Found**: seven pages with fewer than five in-body inbound links (About 1, Terms 0, Contact 2, Privacy 2, Services 3, Pillar 3, Admissions 4); portal record-creating routes without rate limits. **Changed**: contextual links where a reader needs them (no generic related blocks); throttles on application start, deletion request, checkout, manual transfer and password change. **Tested / passed**: 74 tests; crawl 28/28 clean; Actions settings re-check at 18:19 UTC still MISSING; next self check-in 19:16 UTC.

## Cycle 24 — university page depth, FAQ growth, regression tests (stage 28)

- **Changed**: every university record page gained computed Nigerian-applicant guidance and a how-to-apply block built only from published facts, plus Course schema (Offer only for a VERIFIED fee); 29 of the 40 observed questions now have sourced FAQ answers; the expired-form page offers a real way back. **Tested / passed**: 77 tests including a new university-page suite (home-only wording, published-versus-missing statements, Offer gating); CI green; `composer audit` now reachable and reports no advisories; `npm audit` clean. The requirements hub gained a what-you-hold → route → what-to-check table and four FAQs.

## Cycle 25 — hub depth, eligibility fix, fee ordering (stage 29)

- **Inspected**: the eligibility check as a WAEC-only applicant and as a Nigerian graduate (browser), the hubs' depth, the fee guide against the "cheapest" intent. **Found**: the route-map headline was generic and told a graduate that A-level/IB routes appear possible. **Changed**: headline written per qualification with a regression test; requirements hub "which route is yours" table; admissions hub "where are you today" table; fee guide orderable by lowest published fee (canonical unchanged, with a plain note on what cheapest means); `ops/qa/link-graph.py` added for future link audits. **Tested / passed**: 79 tests; crawl 28/28 clean; CI green.

## Cycle 26 — verification from any browser (stage 30)

- **Found**: 636 facts wait on page checks that neither this environment (network policy) nor the undeployed admin queue can perform. **Changed**: worksheet export and decisions import commands with stable references, strict rules (no verification without source and date, values only from the page) and deploy-time replay; first worksheet committed. **Tested / passed**: round-trip test; 80 tests.

## Cycle 27 — portal wording from the journey screenshots (stage 31)

- **Found**: the passport document card carried the personal-statement reason (one rule, one reason, two documents). **Changed**: two rules with their own reasons; old rule removed on reseed. The worksheet has 110 priority-1 rows across 55 official pages (UCAS 12, UCAT 12, visa 7, GMC 5, then the fee rows), so the dates and fees students act on can be verified in one sitting.

## Cycle 28 — viewable build without deployment (stage 33)

- **Owner asked** for a URL to inspect the current build. **Found**: nothing has ever been deployed (zero deploy runs; settings MISSING at 19:17 UTC, run 11). **Changed**: published a private static snapshot of all public pages as an artifact; added a devcontainer so Codespaces runs the live application from a browser with the demo accounts; directory cards gained a WAEC-statement indicator. **Tested / passed**: 85 tests; CI green.

## Cycle 29 — healthcare course universe taxonomy (stage 34)

- **Owner directive**: build the full healthcare course universe as a data model first; no automatic pages. **Found**: the platform modelled Medicine only; no record of which other professions exist, who regulates them, which universities admit international undergraduates or what Nigerians search for. **Changed**: 30-subject taxonomy (`data/healthcare/subjects.json`) from three research sweeps written up in research doc 12 §A–§C, `professions` table and Admin → Subjects, cluster V in the decision register (one row per subject plus one overview hub, generated from the taxonomy), decision-engine §7. Only Medicine may have a public page; Dentistry, Nursing and Biomedical Science are VALIDATED, 20 subjects RESEARCH, six REJECTED. **Tested / passed**: 91 tests (six new), CI pending on push. **Failed**: nothing. **Remains**: every §B value is a search-snippet FACT or LEAD and must be read on the official page before it becomes a `reference_fact` (owner network allow-list or worksheet); Osteopathy and Chiropractic not yet swept; settings re-check at 22:12 UTC (run 12) still MISSING in every scope; no `data/verification/decisions-*.csv` on the branch yet.

## Cycle 30 — subject long-tail families, derived register columns, knowledge graph (stage 35)

- **Found**: the register lacked the owner's subject, indexation, internal-link and conversion columns, and Nursing and Dentistry had no query-family evidence. **Changed**: columns derived by the builder; `docs/seo/KNOWLEDGE-GRAPH.md` generated per subject; 36 SERP observations clustered into sixteen cluster V family rows (three VALIDATED, eleven RESEARCH, two REJECTED) with research doc 12 §E and eleven new Semrush lookup themes. **Tested / passed**: 91 tests; CI green on every push. **Remains**: Semrush `ng` volumes (owner export) decide the nursing route family; every §E official statement is VERIFY-ON-PAGE.

## Cycle 31 — audit, course scoping, fact integrity, expanded universe (stage 36, 2026-10-04)

- **Inspected**: crawl, link graph, axe, CSP, fresh-account journey, robots/sitemap/canonical/redirect behaviour, every public course query, the topic seeder, portal copy; three research sweeps (49 + 45 + 45 searches); Semrush (still `no_api_units`); Unsplash (blocked from this environment).
- **Found and fixed**: `/index.php` duplicate pages; Medicine hub weakly linked; unscoped course queries; verified facts overwritable on reseed; stale prioritisation and maintenance facts; portal deadline, year and age claims; an over-long gated description; demo accounts missing. Each has a regression test.
- **Changed (research)**: 36-subject taxonomy, 146-row register, FAQ answers for three Nigerian question families, subject image plan, worksheet 2026-10-04.
- **Tested / passed**: 101 tests; CI green on every push. **Remains**: settings still missing (last check 22:33 UTC on 2026-10-03, run 13); every new official statement is VERIFY-ON-PAGE.

## Cycle 32 — second security audit, directory safety, allied-health facts (stage 37, 2026-10-04)

- **Inspected**: a full read-only security audit (113 routes, every area of the P0 list); every finding verified in code. **Found**: 16 issues (2 medium, 12 low, 2 info). **Changed**: all fixed with tests; audit record `ops/reports/security-audit-2026-10-04.md`. Allied-health statements from research are now verifiable records (39 facts, worksheet priority 4). **Tested / passed**: 121 tests; fresh-account journey and CSP sweep clean after the session change; CI green. **Remains**: settings still missing (run 14, 02:23 UTC on 2026-10-04).

## Cycle 33 — deployment readiness, verification engine, search architecture (stage 38, 2026-10-04)

- **Found and fixed**: a first deploy would have shipped an empty database (no service tiers); every seeder could overwrite reviewed facts; an edited verified value stayed verified; bulk verify could re-publish an old value on a changed page; production could be dispatched without staging; a failed backup did not stop migrations; home competed with the core guide for the core query. Each has a test.
- **Built**: priority-ordered, page-grouped worksheet with P0/P1 sheets and a sources list; nightly source-change watcher; Semrush native-export import; subject scorecard.
- **Tested / passed**: 132 tests; crawl 28/28; CI green.

## Cycle 34 — clear the path to real staging (stage 39, 2026-10-04)

- **Rehearsed the staging path on MySQL** (MariaDB, local SSH stand-in; `ops/reports/deployment-rehearsal-2026-10-04.md`): bootstrap → verified backup → deploy → smoke → automatic and manual rollback → encrypted off-site backup → restore. It found that the **first real deploy would have failed** (reference data overflowed MySQL column limits that SQLite ignores; the pre-migrate dump lacked the flag shared hosting needs), that bootstrap put database and Stripe secrets on the server's command line and wrote `.env` values a quote or backslash would break, that releases shipped the local development database and dev packages, that rollback could pick a release that never went live, and that scheduled backups would have waited for production approval. All fixed and tested; CI now runs the suite on MySQL 8 and proves the smoke test fails on an empty application.
- **Gates**: production deploys refuse to run without a required reviewer on the `production` environment and without a successful staging deploy of the same commit; staging refuses to deploy without its URL and password gate; smoke fails on exposed `.env`/`composer.json`/logs, debug output, an open staging site or wrong robots rules; pinned host keys switch every workflow to strict checking; new *Roll back Hostinger release* workflow.
- **Verification engine**: every change to a fact (value, year, source, status, verification) is recorded in `fact_changes` with who or what made it (admin, worksheet import, source watcher, reference sync) and the values before and after, shown as a history in the admin queue. Staging now shows only verified facts, like production, so your review shows what would go live.
- **Semrush**: importer accepts "CSV semicolon", refuses `.xlsx`, ignores keyword spacing, never picks between conflicting duplicate rows; the 50-keyword list had one duplicate, replaced by the uncovered apply-online money query (A01).
- **Pages and journeys**: generated audit of every indexable page (`docs/seo/PAGE-AUDIT.md`): five pages never led to the eligibility check in their body (services, our status, about, how we verify, contact); fixed and held by a test. Browser journeys (student and staff, `ops/qa/staff-journey.cjs`) found three defects, all fixed: one shared rate-limit counter across every form (ten eligibility checks from a shared Nigerian mobile IP blocked registration), staff could accept a document nobody uploaded, and a replaced proposal closed without a reason.
- **Tested**: 148 tests on SQLite and on MariaDB; CI green.

## Cycle 35 — staging blockers that need no credentials (stage 40, 2026-10-04)

- **Found and fixed**: sign-up and password reset crashed without mail settings; a new server had no way to create its first admin; staging pages relied on the password alone for noindex; the inspection could print `APP_KEY` lines and shell history.
- **Built for Phase 3**: *Grant account role* (first admin), *Review staging* (every page, desktop and mobile, after each staging deploy, from GitHub's runners since this session cannot reach the site), restore test in every backup run.
- **Tested**: 153 tests on SQLite and MariaDB; browser journeys clean; CI green.
- **Not done, by rule**: no prices, no verification values, no Semrush figures invented; nothing deployed (diagnose run 16: every setting missing).

## Cycle 36 — approved prices and the paid service journey (stage 42, 2026-10-04)

- **Live in the code**: T1 £75, T2 £395 (Most popular), T3 £795; pricing page, choose → sign up → start → confirm → Stripe Checkout → verified payment → paid status for student and staff. The page price and the amount sent to Stripe come from the same record (browser test: 39500 GBP both). Payment is never marked from the return page, only from a signed Stripe event that matches amount, currency, session and service.
- **Policy wording changed (for your legal review)**: T3 is now one fee paid at the start, so the refund policy (v0.9.1) and application terms (v1, 0.9.1 draft) no longer mention a separately priced "submission component": cancelling before approving a submission package is refunded by the existing pro-rata rule, and nothing is refundable for submission support after submission.
- **Not tested here**: Stripe's real hosted checkout page (this environment cannot reach stripe.com); it is the first thing tested on staging with your Stripe test keys.
- **Tested**: 168 tests on SQLite and MariaDB; phone and desktop payment journeys; staff journey with payment; accessibility 0 violations; crawl clean.

### OWNER ACTION REQUIRED (in order; nothing private ever goes into chat)

1. **WHAT:** create the Hostinger pieces and the GitHub settings in `ops/STAGING-SETTINGS.md`: the GitHub Actions public key added in hPanel; a `staging` subdomain with SSL; two MySQL databases (staging, production); repository secrets `HOSTINGER_SSH_KEY`, `BACKUP_PASSPHRASE`, `STAGING_BASIC_PASSWORD`; repository variables `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER`, `STAGING_URL`, `STAGING_BASIC_USER` (and `HOSTINGER_SSH_KNOWN_HOSTS` once the inspection prints the host key); environment secrets `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` in `staging` (and later `production`); **Required reviewers** on the `production` environment. **WHERE:** hPanel → SSH Access, Domains → Subdomains, Databases; GitHub → Settings → Secrets and variables → Actions, and Settings → Environments. **WHY:** this unlocks inspection, backup, staging deployment and review; production deploys refuse to run until the reviewer rule exists. **SAFE TO DO:** values are typed only into hPanel and GitHub; never paste a key or password into chat. **AFTER YOU DO IT:** I run Diagnose (values never printed) → read-only Inspect and report → Bootstrap staging → Deploy staging (backup, migrate, sync, smoke, auto-rollback) → you review the staging site → production only after your approval.
2. **WHAT:** verify the P0 facts. **WHERE:** `data/verification/sources-2026-10-04.csv` and `worksheet-2026-10-04-p0.csv` in your browser and a spreadsheet; save as `data/verification/decisions-YYYY-MM-DD.csv` and commit it (or send the file). **WHY:** UCAS, UCAT and GMC facts are what students act on first; staging and production show nothing unverified. **SAFE TO DO:** public information only. **AFTER YOU DO IT:** I import it (every change lands in the fact history), check each page those facts feed in production mode, and move to P1. Alternative that lets me read official pages myself: the session's cloud environment menu → Edit → Network access → add `ucas.com`, `ucat.ac.uk`, `gov.uk`, `gmc-uk.org`, `medschools.ac.uk`, `ac.uk` under Allowed domains (steps: https://code.claude.com/docs/en/cloud-environments#network-access); every one of them is denied today.
3. **WHAT:** one Semrush export. **WHERE:** Semrush → Keyword Overview → paste `data/semrush/paste-list.txt` → database Nigeria → Export → CSV; save into `data/semrush/`. **WHY:** volume and difficulty decide which validated families earn a page; none are invented. **SAFE TO DO:** no credentials involved. **AFTER YOU DO IT:** I import it, update the register and scorecard, and re-rank the backlog.
4. **WHAT:** Stripe test-mode keys for staging (prices are already set: £75 / £395 / £795). **WHERE:** Stripe Dashboard (Test mode) → Developers → API keys and → Webhooks (endpoint `https://staging.studymedicineuknigeria.com/webhooks/stripe`, events listed in `ops/STAGING-SETTINGS.md`); then GitHub → Settings → Environments → `staging` → secrets `STRIPE_KEY` (pk_test_…), `STRIPE_SECRET` (sk_test_…), `STRIPE_WEBHOOK_SECRET` (whsec_…). **WHY:** card checkout stays switched off until a test key exists; live keys are refused on staging. **AFTER YOU DO IT:** I run *Update server settings* (staging), then pay each service with Stripe's test cards on phone and desktop (success, decline, cancel, expiry, refund from the Stripe dashboard, webhook replay) and report what staff and student see.
5. Photos (optional): `docs/design/IMAGERY-BRIEF.md`.

## Cycle 37 — premium fees and the production launch path (stage 43, 2026-10-04)

**Launch status (the report format you asked for; nothing below is claimed beyond what ran):**

| Item | Status |
|---|---|
| LIVE URL | https://studymedicineuknigeria.com — **not deployed**: still serves whatever it served before; nothing has touched the server |
| COMMIT | `7a1d70d` on `claude/new-session-p6gdm6` (fees, live-only Stripe in production, preflight, safe cutover); CI run 136 |
| DATABASE | production database not created/connected: no `SMUKN_DB_*` secrets in the `production` environment |
| EMAIL | ready in code (queued verification/reset/notifications over Hostinger SMTP); production refuses to bootstrap or deploy without `SMUKN_MAIL_PASSWORD` and a real mailer |
| STRIPE LIVE STATUS | no keys; production now accepts only `sk_live_`/`pk_live_` and live-mode events, staging only test ones |
| T1 / T2 / T3 | £125 / £695 (Most popular) / £1,295 GBP in the service records; the Stripe charge is built from the same record (browser test: page £695 = 69500 GBP sent to checkout); £1,295 shown with its thousands separator and compared correctly |
| WEBHOOK | `https://studymedicineuknigeria.com/webhooks/stripe` ready (signature, amount, currency, session, service, replay checks); endpoint to be created in Stripe live mode by you |
| SECURITY | unchanged guards plus: wrong-mode Stripe keys refused per environment; deploy preflight (debug off, real mail, public URL, unverified facts hidden) before any change |
| SEO | production smoke and page review fail on noindex, wrong canonical, a robots.txt without the sitemap, missing HSTS, a password prompt, `/index.php/…` not redirecting; local production-mode review 64 page views clean |
| ACCESSIBILITY | 0 serious/critical axe violations in the 64 reviewed views |
| STUDENT / STAFF / PAYMENT JOURNEY | passed locally on phone and desktop with the Stripe stand-in; not yet on a server |
| BACKUP | new pre-launch `site` backup of the existing site (files + WordPress database if any), encrypted and restore-tested; rehearsed locally; not yet run on Hostinger |
| ROLLBACK | rehearsed: failed smoke puts the old site back automatically; code rollback; `restore_previous_site` |
| KNOWN ISSUES | every GitHub setting missing (Diagnose run 18, 06:15 UTC: all rows missing in repository, staging and production; `production` has no required reviewer); P0 facts unverified; Semrush export absent; Stripe's real hosted page untested (this environment cannot reach stripe.com) |

**Superseded:** the cycle-36 fees (£75 / £395 / £795) are replaced by your premium fees. Cycle 36 above stays as written because it records what was approved at the time; a deploy replaces exactly those three old amounts once and logs it, and never overrides a price you set in Admin.

### OWNER ACTION REQUIRED (WHAT I MUST DO → WHERE → WHY → WHAT YOU WILL DO IMMEDIATELY AFTER)

1. **Server access and staging** → the table in `ops/STAGING-SETTINGS.md` sections 1–2 (hPanel SSH key, `staging` subdomain with SSL, two new MySQL databases; GitHub repository secrets and variables; staging environment database secrets; **Required reviewers** on `production`) → nothing can reach the server without them, and production deploys refuse to run without a reviewer and a staging pass of the same commit → I run Diagnose, the read-only inspection (what the domain serves today), the pre-launch backup of the existing site with its restore test, then staging deploy and review, and report.
2. **Production secrets** → GitHub → Settings → Environments → `production` → secrets `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` (the new production database), `SMUKN_MAIL_PASSWORD` (mailbox `info@studymedicineuknigeria.com`), and from Stripe in **live mode** `STRIPE_KEY` (pk_live_…), `STRIPE_SECRET` (sk_live_…), `STRIPE_WEBHOOK_SECRET` (whsec_… of the endpoint `https://studymedicineuknigeria.com/webhooks/stripe` with the six events in section 2) → production bootstrap and deploy refuse to run without real email, and card payment stays off without live keys → after your approval of each run: bootstrap production, deploy with the cutover (old site archived, moved aside, restored automatically on any smoke failure), production smoke and page review, then you register and I grant your admin role (two-step sign-in), then a full backup with restore test, then the owner report with every row filled from the runs.
3. **Live payment check (your decision)** → after launch, you pay T1 (£125) with your own card and refund it in the Stripe Dashboard → proves the live path end to end; Stripe keeps its processing fee on a refunded payment, so this costs that fee → I confirm the webhook, the amounts and the refund in the admin and audit log. If you prefer not to, the first student payment is the first live one, and I watch it.
4. **P0 facts and Semrush** → unchanged from cycle 36 items 2 and 3 → unverified facts stay hidden in production → I import them and update the pages and register.

## Cycle 38 — fees shown only to approved students; Stripe catalogue (stage 44, 2026-10-04)

| Item | Status |
|---|---|
| Public website | No fee on any public page, feed or JSON (tested across every sitemap URL); services explained with inclusions and exclusions; "Service options and pricing are provided after your profile has been reviewed."; CTA Apply Online |
| Unapproved student | Sees services, never a fee; price pages refuse on the server |
| Approval | Staff press *Approve for service selection* on the application (audited; student emailed) |
| Approved student | Sees T1 £125, T2 £695 (Most popular), T3 £1,295; chooses any; sees exact fee and all terms before paying |
| Staff | See fees (Admin → Services, application page); only admins change them; changes audited |
| Stripe products found/created | **None yet in your Stripe account**: this environment cannot reach stripe.com and has no access to your browser. Ready to create: `smukn_t1` Eligibility & Course Assessment, `smukn_t2` Medical Application Preparation, `smukn_t3` Full Medical Application Support |
| Stripe prices | One-time GBP 125.00 / 695.00 / 1,295.00 with lookup keys `smukn_t1_gbp`, `smukn_t2_gbp`, `smukn_t3_gbp`; price ids are issued by Stripe on creation and printed by the *Stripe catalogue* run (rehearsed against the local stand-in: created once, found on every repeat) |
| Payment configuration | Checkout charges the catalogue price chosen by the server from the service record; no Payment Links; webhook verifies signature, amount, currency, session, service; live keys only in production, test keys only on staging |
| Tests | 182 green on SQLite and MariaDB; browser journeys (phone and desktop) and accessibility clean |

### OWNER ACTION REQUIRED (WHAT → WHERE → WHY → WHAT YOU WILL DO IMMEDIATELY AFTER)

1. **Stripe test keys and webhook for staging** → Stripe Dashboard (Test mode) → Developers → API keys (`pk_test_…`, `sk_test_…`) and Webhooks → endpoint `https://staging.studymedicineuknigeria.com/webhooks/stripe` with the six events in `ops/STAGING-SETTINGS.md`; then GitHub → Settings → Environments → `staging` → secrets `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` → I cannot open your Stripe tab or reach stripe.com from here, and keys must never pass through chat → I run *Stripe catalogue* (`staging`, `check`, then `create`), report the three product and price ids, and test each service end to end once staging exists.
2. **Live keys and webhook for production** → the same in Stripe **live mode**, endpoint `https://studymedicineuknigeria.com/webhooks/stripe`, secrets in Environments → `production` → live mode needs its own catalogue → after your approval of the run I create the live products and prices with *Stripe catalogue* (`production`) and report the ids. Do not create products, prices or Payment Links by hand: hand-made duplicates would not carry the lookup keys, and a Payment Link would bypass the profile review.
3. Everything in cycle 37 (server access, production secrets, reviewer) still applies.

## Cycle 39 — Stripe configuration (stage 45, 2026-10-04)

| Step | Status |
|---|---|
| Credentials | **Not yet present**: Diagnose (06:57 UTC) shows `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` missing in `staging` and `production` |
| 1–3 Catalogue sync, three products, one active price each | Ready (*Stripe test-mode journey* or *Stripe catalogue*); not run against your account yet |
| 4 No Payment Links | Enforced: never created; a run fails if one sells a service |
| 5 Price ids | Not issued yet (Stripe assigns them on creation; the run prints them) |
| 6 Webhook | Test-mode journey: real Stripe-signed events via `stripe listen`. Staging/production endpoints: verified or created by *Stripe catalogue* once the server exists |
| 7–8 Real Stripe journey | Ready; rehearsed against the stand-in (T1 decline then paid, T1/T2/T3 paid at the exact fee, catalogue price ids) |
| 9 Price leakage | Re-run here: 184 tests green, no fee on any public page |
| Production | Not deployed |

### OWNER ACTION REQUIRED (WHAT → WHERE → WHY → WHAT YOU WILL DO IMMEDIATELY AFTER)

1. **Stripe test secret key** → GitHub → Settings → Environments → `staging` → New secret `STRIPE_SECRET` = the `sk_test_…` key from Stripe (Test mode) → Developers → API keys (also `STRIPE_KEY` = `pk_test_…`) → every Stripe step needs it and it must never pass through chat → I run *Stripe test-mode journey*: it creates the three test-mode products and prices, prints their ids, pays T1/T2/T3 through Stripe's real Checkout with test cards, confirms each through Stripe-signed webhooks, and re-runs the leakage checks; I report the ids and results.
2. **Live keys** → Environments → `production` → `STRIPE_KEY` (`pk_live_…`), `STRIPE_SECRET` (`sk_live_…`) → the live catalogue is separate from test mode → after your approval of the run, *Stripe catalogue* (`production`, `create`) creates the live products and prices and reports the ids. Live endpoint and live payments wait for the production server (not deployed).
3. Webhook signing secrets: none needed from you if the endpoint is created by *Stripe catalogue* (`webhook=create`) once staging exists; it needs the server settings from cycle 37.

## Cycle 40 — launch without Stripe; payment honestly closed (stage 46, 2026-10-04)

- **Done**: production no longer waits for Stripe. Until live keys exist, approved students see their service, exact fee and terms, but no payment action is shown or accepted; they are emailed once when payment opens. Bank transfer stays off unless you switch it on. Stripe products, prices, Payment Links and webhooks were not created. Code `f08d6df`, CI green, 186 tests on SQLite and MariaDB.
- **Checked**: Diagnose run 37188649260 (08:21 UTC): every repository, staging and production setting is missing; `production` has no required reviewer.

### Remaining launch blockers (WHAT → WHERE → WHY → WHAT YOU WILL DO IMMEDIATELY AFTER)

1. **Server access** → hPanel → SSH Access: add the public key `SMUKN-GitHub-Actions`; GitHub → Settings → Secrets and variables → Actions: secrets `HOSTINGER_SSH_KEY`, `BACKUP_PASSPHRASE`; variables `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER` → nothing can inspect, back up or deploy without them → I run Diagnose, the read-only inspection (what the domain serves now) and the encrypted backup of the current site with its restore test, and report.
2. **Staging** (required before production by the repository rules and the deploy workflow) → hPanel: subdomain `staging` with SSL, one MySQL database; GitHub: variables `STAGING_URL`, `STAGING_BASIC_USER`, secret `STAGING_BASIC_PASSWORD`, and in Environments → `staging` the secrets `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` → production deploys refuse to run without a successful staging deploy of the same commit → I bootstrap and deploy staging, run the smoke test and full page review, and you review it behind its password.
3. **Production secrets and approval** → hPanel: a new, empty production MySQL database and the mailbox `info@studymedicineuknigeria.com`; GitHub → Environments → `production`: `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD`, `SMUKN_MAIL_PASSWORD`, and Required reviewers = you → production refuses to bootstrap without real email and to deploy without your approval → after your approval: bootstrap production, deploy with the cutover (old site archived and restored automatically on any failure), production smoke and page review, your admin account, a full restore-tested backup, then the launch report.
4. **Email deliverability** (before the first student registers) → DNS records SPF, DKIM, DMARC per `docs/ops/EMAIL-DELIVERABILITY.md` → verification and reset emails must reach inboxes → I verify them from the live site.

Not blockers: Stripe (post-launch; payment stays closed), P0 facts (unverified facts stay hidden), Semrush export.

## Cycle 41 — production launch preparation (stage 47, 2026-10-04)

**Verified from outside today (no credentials needed):** your email DNS is ready (Hostinger MX, one SPF record, DMARC, DKIM keys published); the HTTPS certificate covers the domain and `www` until 22 Nov 2026; the current site (PHP 8.2, behind Hostinger's CDN) lists 76 URLs and the new site answers every one (65 redirects, 4 same paths, 7 intentional 404s), so indexed pages keep working after the switch.

**Fixed in this cycle:** `www` now redirects to the main domain; only the exact site host is trusted (the old rule also admitted look-alike hosts); production replaces the current site only after a restore-tested backup of it (files plus its database) from the last 7 days; staging must be HTTPS; production starts with student registration closed and opens it only after a real test email from the production mailbox is accepted and the email DNS checks pass. 194 tests on SQLite and MariaDB. Stripe untouched; payment stays closed.

**Not production-ready yet:** nothing has been deployed. Diagnose (08:38 UTC) shows every repository, staging and production setting missing and no required reviewer on `production`.

### OWNER ACTION REQUIRED (WHAT → WHERE → WHY → WHAT YOU WILL DO IMMEDIATELY AFTER)

1. **Server access** → hPanel → Websites → Manage → Advanced → SSH Access: add the public key `SMUKN-GitHub-Actions` (keep the Claude key); GitHub → Settings → Secrets and variables → Actions → secrets `HOSTINGER_SSH_KEY` (the private `smukn_deploy` key) and `BACKUP_PASSPHRASE`, variables `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER` (from the same hPanel page) → every server step uses them → Diagnose, read-only Inspect (which folder serves the site, what application it is, where its database is), then the encrypted, restore-tested backup of the current site.
2. **Staging** → hPanel → Domains → Subdomains: create `staging`, then Security → SSL for `staging.studymedicineuknigeria.com`; Databases: one new MySQL database and user; GitHub → variables `STAGING_URL` = `https://staging.studymedicineuknigeria.com`, `STAGING_BASIC_USER`, secret `STAGING_BASIC_PASSWORD`; Environments → `staging` → secrets `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` → production only accepts a commit that passed staging → bootstrap and deploy staging, smoke test and full page review (SEO, security headers, accessibility, noindex, password), then you review it.
3. **Production** → hPanel → Databases: a new, empty MySQL database and user; Emails: confirm the mailbox `info@studymedicineuknigeria.com` exists and note its password; GitHub → Environments → `production` → secrets `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD`, `SMUKN_MAIL_PASSWORD`, and Required reviewers = you → production refuses to bootstrap without real email and to deploy without your approval → after your approval: bootstrap, deploy with the cutover (old site archived and restored automatically if any check fails), production smoke and page review.
4. **Right after the switch** → hPanel → Websites → your site → CDN → flush the cache; then check the inbox you give me for the test email (not in spam) → no visitor sees a cached old page; registration opens only once mail is proven → *Send test email* with `registration=open`, then you register and I make you admin (two-step sign-in), then a full restore-tested backup and the launch report.

Recommended, not blocking: add `rua=mailto:info@studymedicineuknigeria.com` to the DMARC record (reports of failed mail); the legal pages still say "under legal review" (your decision). Stripe stays a post-launch step.

## Cycle 42 — deployment setup authorised; Hostinger needs your browser (2026-10-04)

- **Blocked at the first step, honestly:** this session runs in a cloud container. It has no tool that can operate your Chrome or hPanel, and Hostinger is not reachable from it. Nothing was changed on Hostinger or GitHub settings; the current site is untouched.
- **Checked:** Diagnose (09:15 UTC): every repository, staging and production setting is still missing; no required reviewer on `production`.
- **Prepared:** `ops/OWNER-CLICKLIST.md`, the shortest safe order of the hPanel and GitHub steps (about 20 minutes, nothing touches the live site).
- **Two ways forward:** (a) you follow the click-list, and every later step runs from here automatically up to the production approval; or (b) you open a Claude session on your own computer (Claude Desktop app, or `claude remote-control` in a terminal) where Claude can use your browser to do the hPanel part with you, then this cloud session continues.

## Owner actions still required (unchanged, one place)

The complete, current list is `ops/STAGING-SETTINGS.md`; the table below is the original minimum.

GitHub → repository → Settings → Secrets and variables → Actions:

| Kind | Name | Value |
|---|---|---|
| Secret | `HOSTINGER_SSH_KEY` | private half of the owner's `smukn_deploy` key (public key "SMUKN-GitHub-Actions" already in hPanel) |
| Variable | `HOSTINGER_SSH_HOST` | server hostname or IP from hPanel → SSH access |
| Variable | `HOSTINGER_SSH_PORT` | `65002` (shared) or `22` (VPS) |
| Variable | `HOSTINGER_SSH_USER` | hPanel SSH username |
| Secret | `BACKUP_PASSPHRASE` | long random passphrase, kept in the owner's password manager |
| Variable + Secret | `STAGING_BASIC_USER`, `STAGING_BASIC_PASSWORD` | credentials for the staging site's basic-auth gate (recommended) |

Also, before the first student registers: the DNS/mailbox steps in `docs/ops/EMAIL-DELIVERABILITY.md` (SPF, DKIM, DMARC), and after the first production deployment the repository variable `PRODUCTION_URL` so `uptime-check.yml` starts monitoring.

**To see the current build now:** the private snapshot of every public page is at https://claude.ai/artifact/55WJpVJ34YrgXpsHSHMhPY (static: links work, forms and the portal do not). To run the real application from a browser with the demo accounts: GitHub → branch `claude/new-session-p6gdm6` → Code → Codespaces → Create codespace (free personal quota; stop it when done). Nothing is deployed: the deploy workflow has never run because the settings below are absent.

**New (category B, do this one first; it needs only your browser and a spreadsheet):** open `data/verification/sources-2026-10-04.csv` and `worksheet-2026-10-04-p0.csv` (71 facts; the full sheet has 675 in the owner's priority order, grouped by official page, priority 1 rows first: UCAT and UCAS dates, visa figures, fees). For each row open `source_url`, compare `current_value` with the page, and fill `decision`, `verified_value` (only if the page differs), `reviewer_note` and `verified_on` (YYYY-MM-DD). Save as `data/verification/decisions-2026-10-04.csv`, commit it (or send it to this session), and `php artisan smukn:facts-import` applies it everywhere; the deploy script replays it on staging and production. Twenty priority-1 rows (the UCAT and UCAS topics and the visa figures) un-gate the total-cost and working pages. Full instructions: `data/verification/README.md`.

**Also high-value (category B, two minutes):** the cloud environment's network policy blocks every official domain (ucat.ac.uk, ucas.com, gov.uk, gmc-uk.org and the university sites), so the 535 VERIFY-ON-PAGE facts cannot be checked from here. In the session's title bar open the cloud environment menu → Edit → Network access, and either choose a broader access level or Custom with these domains under Allowed domains (keep the default package-manager list): `ucat.ac.uk`, `ucas.com`, `gov.uk`, `gmc-uk.org`, `medschools.ac.uk`, `foundationprogramme.nhs.uk`, `bma.org.uk`, `ukcisa.org.uk`, and `ac.uk`. Steps: https://code.claude.com/docs/en/cloud-environments#network-access. Once allowed, the next session can read each official page and move facts to VERIFIED with the exact wording, which un-gates the total-cost and working pages automatically.

Then run **Inspect Hostinger** (Actions → Run workflow) and the live server report follows from its artifact. Nothing is deployed until that report is reviewed.
