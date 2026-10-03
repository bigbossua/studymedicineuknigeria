# Implementation log

Decisions made during the build, in order. Research and decision documents remain the source of truth for *what* and *why*; this log records *how* and any deviation.

## 2026-10-03 — Stage 0: environment facts

- Hostinger SSH (ports 22 and 65002) is unreachable from the build container (network policy), and no credentials have been supplied. The 16-point server report is therefore **not yet possible**. `ops/inspect-hostinger.sh` (read-only) is ready to run the moment access exists; results go to `ops/reports/`.
- GitHub push is refused (403): the Claude GitHub App is not installed on `bigbossua/studymedicineuknigeria`. All work is committed locally on `claude/new-session-p6gdm6`.
- Composer resolved Laravel 13.34 (PHP ^8.3). GitHub's zip host is blocked, so Composer runs with `--prefer-source` here; on the server a normal `composer install` works.

## Stage 1: brand and foundation

- Brand built from `brand/build.py`: Rod of Asclepius on a navy tile over a quiet saltire, one red point; Source Serif 4 + Inter. Favicon, manifest, OG, avatar and email header exported.
- Tailwind v4 tokens in `resources/css/app.css`; fonts self-hosted (OFL).
- SEO: `App\Support\Seo` object per page; JSON-LD Organization + BreadcrumbList (+ WebSite on home, CollegeOrUniversity on school pages); dynamic robots (disallow-all outside production); sitemap lists only routes carrying a `sitemap` default; 404 view; DB redirects + lowercase/no-trailing-slash canonicalisation as **global** middleware. **Deviation from 18.1:** canonical URLs have *no* trailing slash (Laravel convention).
- Unbuilt register pages render a `noindex` placeholder and never enter the sitemap.

## Stage 2: reference data

- One polymorphic `reference_facts` table instead of separate fee/requirement/deadline tables (see 17.6). Importer is idempotent and never promotes to VERIFIED. 53 universities, 56 courses, 617 facts imported; 24 accept international applicants, 1 international-only, 6 home-only, 22 not yet established.
- Directory + university pages render every fact with its verification chip; unverified facts are hidden in production unless `SITE_PUBLISH_UNVERIFIED=true`. University pages are `noindex` until staff publish them.

## Stage 3: application platform

- Auth: Laravel auth with email verification, rate-limited login, no-enumeration password reset. Roles student/staff/admin (2FA fields present; TOTP enforcement for staff is a follow-up).
- Three state machines (application / document / submission) per docs 12, 14, 16. `StageResolver::resolve()` is the single derivation function; staff may only set judgement stages, and the controller refuses review/approval stages while the form, documents or payment are incomplete.
- Checklist rules are data (`checklist_rules`); re-evaluated when relevant steps change.
- Document pipeline: content-type sniffing, size limits, image re-encode (GD), PDF active-content rejection, ClamAV when present (else `scan_status=unavailable`), app-level encryption for passport and financial documents, UUID paths on the private disk, authenticated streamed downloads with access logging, sandboxed inline preview for staff.
- Payments: Stripe Checkout (hosted) with webhook as source of truth and idempotent event storage; bank-transfer fallback with manual confirmation; prices are **null until set in admin** and nothing can be charged while null. Tier 3 is split into preparation + submission components.
- Approval gate: package snapshot hashed; typed name, IP, user agent, declaration version stored; any package change after approval revokes it and returns the submission to PROPOSED; staff cannot mark PACKAGE_READY/SUBMITTED without a live authorisation. Agreement-gated routes (DIRECT_AGENT, UCAS_CENTRE) are refused because no agreement records exist.
- Notifications: one `ApplicationNotification` per real event; staff alerts via `StaffNotification` to admin users. Queue driver database; scheduler runs the worker each minute (shared-hosting friendly). Reminders: 2/7/14/30-day inactivity, 3/10-day document, 2/7-day approval, max one per 48 h.
- Admin: operations dashboard, application workspace (documents, form, submissions, messages, stage control, assignment, payments, timeline), leads, payments, services/prices, verification queue (the only place a fact becomes VERIFIED; fees/deadlines fall due after 6 months, others 12), universities publish toggle, redirects, users/roles, audit log. Every admin write is recorded in `admin_actions`.
- Tests: 19 feature tests covering SEO behaviour, ownership, upload validation, the approval gate and role boundaries.

## Stage 4: release-1 public pages

- `Topic` added as a third fact subject (UCAS 2027, UCAT 2026, Student visa, Graduate visa, GMC registration, other costs); 36 topic facts seeded from research 08–10 with sources, entering as VERIFY-ON-PAGE or NOT_FOUND. Pages render them through `<x-fact-row>` so every date, fee and rule carries its chip and hides in production until verified.
- Built (asset register BUILD NOW): Medicine pillar; core Nigerian landing (with FAQPage JSON-LD); foundation routes; requirements hub, WAEC, NECO, A-levels, Nigerian degree / graduate entry, English; fee guide (data-driven, range shown separately from official fees); total cost (noindex until visa and living-cost inputs are verified); admissions hub, UCAT, UCAS 2027 timeline, how to apply; FAQ hub (18 sourced answers from the 40 observed questions; the rest deferred until an evidence-based answer exists); Apply Online, services, eligibility check (rule-based route map that never says "eligible", creates a lead, prefills registration); About, Our status, Contact; privacy, terms, application terms, refund policy marked version 0.9 under legal review.
- Sitemap now lists 27 published URLs; the only built page kept out is the total-cost page.
- Tests: 21 feature tests pass.

## Stage 5: deployment tooling (server access still pending)

- `.env.production.example` names every secret the server needs; `ops/deploy.sh` implements the release-directory method (local build → rsync → shared `.env`/storage links → DB backup → migrate → cache → symlink switch → smoke test → automatic rollback); `.github/workflows/ci.yml` runs Pint and the test suite. Code formatted with Pint.
- Still blocked: SSH to Hostinger (network policy and no credentials), therefore the 16-point server report and the first deployment. GitHub push is still refused (Claude GitHub App not installed on the repository).

## Stage 6: live-access investigation and deployment agent

- Direct SSH from the Claude cloud environment is impossible by network policy; the Claude GitHub App is not installed on the repository, so no publishing route (git, REST, MCP) works from a Claude session yet. Full record: `ops/reports/access-investigation-2026-10-03.md`.
- Selected architecture: **GitHub Actions as the deployment agent** (runner → SSH → Hostinger) with `inspect-hostinger.yml` (read-only report as artifact) and `deploy-hostinger.yml` (tests, then `ops/deploy.sh staging|production` with backup and rollback). Private key lives only in GitHub Actions Secrets.
- Blocked on one owner action: installing the Claude GitHub App on the repository; then adding the Actions secret and variables.

## Stage 7: staff two-step verification (P0)

- `App\Support\Totp` implements RFC 6238 (SHA-1, 6 digits, 30 s) with no new dependency; `TwoFactorTest` checks it against the RFC test vectors. Secrets and bcrypt-hashed recovery codes are stored with Laravel's encrypted casts (APP_KEY), never in plain text.
- `EnsureTwoFactor` middleware (alias `2fa`) sits on the portal and admin groups: staff/admin accounts without an authenticator are sent to `/two-factor/setup` and cannot reach `/admin` or `/portal` until enrolled; every account with it enabled must pass `/two-factor/challenge` once per session, and a fresh sign-in clears the pass. A code is accepted once only (replay refused inside the ±30 s window); 8 wrong attempts lock the challenge for 15 minutes; both POSTs are route-throttled.
- Enrolment shows a manual setup key plus an `otpauth://` link (no QR library yet; a self-hosted QR renderer is a follow-up), then eight single-use recovery codes shown once.
- Students may enrol from their profile and switch it off with their password; staff cannot switch it off. Admins can reset another account's authenticator from Users (audited as `two_factor.reset`); lock-out recovery for the last admin is `php artisan smukn:two-factor-reset <email>` on the server.
- Tests: 29 pass (8 new). Workflow tests now carry the passed challenge in the session for staff requests.

## Stage 8: Content Security Policy enforced (P0)

- `SecurityHeaders` now sends an enforced `Content-Security-Policy` instead of report-only: `script-src 'self' 'nonce-…'` (Laravel Vite nonce, new per response), `style-src 'self' 'unsafe-inline'` (inline width on the step progress bar only), `font-src 'self'` (self-hosted fonts), `connect-src 'self'`, `frame-ancestors 'none'`, `object-src 'none'`, `form-action 'self' https://checkout.stripe.com`, Stripe hosts in `frame-src` for the hosted Checkout redirect. `js.stripe.com` is not loaded anywhere, so it is not in `script-src`.
- Removed every inline event handler (logout links now submit the hidden form via `form=`; the admin reset confirmation uses `data-confirm` handled in `app.js`). JSON-LD data blocks are unaffected by `script-src`.
- Verified: PublicSeoTest asserts the header, the nonce on the Vite tags and the absence of inline handlers; a Chromium sweep of 9 public, 8 portal and 11 admin pages (mobile nav toggle, autosave fetch, two-step challenge, data-confirm dialog) produced zero CSP violations.

## Stage 9: encrypted off-site backups (P0, runs once server access exists)

- `ops/backup.sh` (server side, fed over SSH) dumps the database with a private defaults file (no password on the command line), adds `shared/.env` and the private document store for the weekly full scope, writes a manifest, encrypts with AES-256-CBC/PBKDF2 from `BACKUP_PASSPHRASE`, checksums, and prunes its own staging copies. Tested locally against a fake target: decrypts with the right passphrase, refuses the wrong one, passphrase never touches disk.
- `.github/workflows/backup-hostinger.yml`: daily db / weekly full schedule plus manual dispatch; same configuration guard as the other Hostinger workflows plus `BACKUP_PASSPHRASE`; fetches, verifies the checksum, confirms the payload is not readable unencrypted, uploads as an artifact with short retention. `ops/RESTORE.md` documents restore and the quarterly restore test.

## Open items carried forward

1. Hostinger access → server report → deployment (docs/architecture/21).
2. Release-1 public pages (register rows 2–23) with verification gating.
3. Eligibility check (lead capture) and services/pricing page once prices are set.
4. ClamAV on VPS; QR rendering on the two-step setup page; first real backup run and restore test once server access exists.
5. Legal pages (privacy, terms, application terms, refund policy) — drafts need owner/legal review before publication.
