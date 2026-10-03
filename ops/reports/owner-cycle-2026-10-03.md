# Autonomous owner cycle — 2026-10-03

Format per the owner directive: inspected / found / changed / why / tested / passed / failed / remains / next.

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

- **Inspected**: Actions settings (run 8, 17:49 UTC: all MISSING in repository scope and both environments); Semrush MCP (still `no_api_units`); every sitemap URL for canonical, robots, schema, headings, images and in-body internal links. **Found**: the core landing page had a single in-body inbound link; the fee guide and FAQ lacked heading structure; the directory had no list schema; PHP exposed `X-Powered-By`; the graduate-entry and NECO pages were the two thinnest intent pages. **Changed**: `data/seo/decision-register.csv` + `docs/seo/DECISION-ENGINE.md` (65 query families, statuses with reasons, link plan) with an enforcing test; nine contextual links to the core landing page; headings, ItemList, header removal; graduate-entry, NECO and how-to-apply pages rewritten from the research evidence. **Verified (P0)**: throttles on all public write routes, 10-character letters-and-numbers password rule, secure session cookies in the server bootstrap, `npm audit` clean, fresh-account journey and 28-page crawl clean. **Tested / passed**: 73 tests; CI green on every push; pages rendered locally with no errors.
- **Owner (category B)**: unchanged below. For the SEO engine specifically: Semrush exports (database `ng`, then `uk`) for the 27 themes in `data/semrush/lookup-sheet.csv` and the question filter for the 18 seeds in research 02 §E, saved as `data/semrush/lookups-2026-10-03.csv`; the importer fills research 02 and the register's volume/KD cells.

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

Then run **Inspect Hostinger** (Actions → Run workflow) and the live server report follows from its artifact. Nothing is deployed until that report is reviewed.
