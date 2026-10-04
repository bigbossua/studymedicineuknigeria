# Autonomous owner cycle — 2026-10-03

Format per the owner directive: inspected / found / changed / why / tested / passed / failed / remains / next.

## State at 02:30 UTC on 2026-10-04 (read this first)

| Area | State |
|---|---|
| Code | HEAD on `claude/new-session-p6gdm6`, 101 tests green (incl. a production-mode sweep that no unverified wording reaches any public page, which found and fixed three leaks today: UCAS-code notes, GMC-status wording, research notes in two course titles and one school name), CI green on every push; 28 indexable pages crawl clean; axe zero violations; fresh-account journey clean |
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

## Owner actions still required (unchanged, one place)

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

**New (category B, do this one first; it needs only your browser and a spreadsheet):** open `data/verification/worksheet-2026-10-04.csv` (675 facts: Medicine first, then 39 allied-health facts as priority 4, priority 1 rows first: UCAT and UCAS dates, visa figures, fees). For each row open `source_url`, compare `current_value` with the page, and fill `decision`, `verified_value` (only if the page differs), `reviewer_note` and `verified_on` (YYYY-MM-DD). Save as `data/verification/decisions-2026-10-04.csv`, commit it (or send it to this session), and `php artisan smukn:facts-import` applies it everywhere; the deploy script replays it on staging and production. Twenty priority-1 rows (the UCAT and UCAS topics and the visa figures) un-gate the total-cost and working pages. Full instructions: `data/verification/README.md`.

**Also high-value (category B, two minutes):** the cloud environment's network policy blocks every official domain (ucat.ac.uk, ucas.com, gov.uk, gmc-uk.org and the university sites), so the 535 VERIFY-ON-PAGE facts cannot be checked from here. In the session's title bar open the cloud environment menu → Edit → Network access, and either choose a broader access level or Custom with these domains under Allowed domains (keep the default package-manager list): `ucat.ac.uk`, `ucas.com`, `gov.uk`, `gmc-uk.org`, `medschools.ac.uk`, `foundationprogramme.nhs.uk`, `bma.org.uk`, `ukcisa.org.uk`, and `ac.uk`. Steps: https://code.claude.com/docs/en/cloud-environments#network-access. Once allowed, the next session can read each official page and move facts to VERIFIED with the exact wording, which un-gates the total-cost and working pages automatically.

Then run **Inspect Hostinger** (Actions → Run workflow) and the live server report follows from its artifact. Nothing is deployed until that report is reviewed.
