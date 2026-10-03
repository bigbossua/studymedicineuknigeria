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

## Owner actions still required (unchanged, one place)

GitHub → repository → Settings → Secrets and variables → Actions:

| Kind | Name | Value |
|---|---|---|
| Secret | `HOSTINGER_SSH_KEY` | private half of the owner's `smukn_deploy` key (public key "SMUKN-GitHub-Actions" already in hPanel) |
| Variable | `HOSTINGER_SSH_HOST` | server hostname or IP from hPanel → SSH access |
| Variable | `HOSTINGER_SSH_PORT` | `65002` (shared) or `22` (VPS) |
| Variable | `HOSTINGER_SSH_USER` | hPanel SSH username |
| Secret | `BACKUP_PASSPHRASE` | long random passphrase, kept in the owner's password manager |

Then run **Inspect Hostinger** (Actions → Run workflow) and the live server report follows from its artifact. Nothing is deployed until that report is reviewed.
