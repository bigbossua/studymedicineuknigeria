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

## Stage 10: funnel measurement (P7) – first-party events and consent-gated GA4

- `funnel_events` table + `App\Support\Funnel` mirror the reporting events of 12.9 server-side: `lead_created` (eligibility check), `account_created` (registration) and every application event through one hook in `Application::record()` (`application_started`, `step_completed`, `document_uploaded/accepted/rejected`, `payment_started/completed`, `approval_requested`, `student_approved`, `submitted`, `university_response`, `application_withdrawn`). Rows hold a sha256 of the application number, a keyed hash of the session id, tier, intake year, source page and UTM parameters – never names, emails or application numbers. Tracking never throws.
- Admin → Funnel: unique counts per step for 7/30/90/365 days, conversion of each step from the previous one, split by service tier, lead sources by `utm_source`, and the current analytics configuration.
- GA4 is optional and off by default: `SITE_GA4_ID` blank means no third-party request and no banner. When set, public pages show a consent banner (first-party cookie `smukn_consent`, one year); `app.js` loads gtag only after *Accept analytics*, with ad storage denied, IP anonymisation and Google signals off, and forwards the queued public-site events (`lead_created`, `account_created`). The portal and admin never load it and their CSP never includes Google hosts. Privacy notice updated accordingly.
- Tests: 34 pass (4 new). Browser check: banner on first visit, decline sets the cookie and sends nothing, accept loads the tag and pushes the lead event once, the admin funnel renders.

## Stage 11: snippet hygiene and thin hubs (P5/P6)

- A local Chromium crawl of all 27 indexable pages (sitemap + links) found no broken links and full sitemap coverage, but 19 titles over 65 characters (the brand suffix pushed them to 80–130), 17 descriptions over 165 characters and 4 under 70, and two hubs with little visible copy.
- Fixed: `Seo::fullTitle()` keeps the brand suffix only while the whole title fits in 65 characters; every public title shortened to ≤ 60 characters (H1s unchanged, they are set in the views); every description rewritten to 110–165 characters; legal pages given real descriptions; university page titles use `short_name`. `PublicSeoTest` now walks the sitemap and fails on any title > 65, description outside 100–165, or a page without exactly one H1.
- Contact page: what to include, response expectations, what we do not do, how a medical school can request a re-verification. Admissions hub: the process in order (route → test → shortlist → UCAS → interviews → offer/deposit/CAS/visa) and what applicants from Nigeria most often miss, every step linking to its sourced page.
- Tests: 35 pass.

## Stage 12: accessibility audit (P6)

- axe-core 4.13 (WCAG 2.0/2.1/2.2 A+AA and best-practice rules) run in Chromium over 32 public pages (desktop and mobile with the menu open), 10 portal pages and 13 admin pages. Found: low-contrast step numbers and the amber verification chip, the floating CTA outside any landmark, 9 unlabelled selects and 38 unlabelled inputs in admin forms, two scroll regions without keyboard access, a prohibited ARIA attribute in Laravel's default pagination, two empty table headers.
- Fixed all of them: `text-ink-500` step numbers, a darker `warning-700` token for the chip, `<aside aria-label>` for the floating CTA, `aria-label`s on every admin control, `tabindex="0"` plus labels on scroll regions, a site pagination view registered through `Paginator::defaultView`, screen-reader-only header text. Re-run: zero violations on all 55 pages.

## Stage 13: Working in the UK page with a publish gate (P4/P5)

- New `/working-in-the-uk` (asset register row 25) built from research record 10 and the seeded `student-visa`, `graduate-visa` and `gmc-registration` topic facts: Student visa work rules and maintenance, the six steps from final year to Foundation Year 1 (MLA → UKFP application → allocation → GMC provisional registration → Health and Care Worker visa → full registration), the Graduate visa as fallback with the 2027 change, and the prioritisation Bill labelled as proposal. Every figure is a fact row with chip and source; a "where to check for yourself" list points to GOV.UK, GMC, UKFPO, NHS England and the BMA.
- `App\Support\PublishGate` (`topics-verified:slug,…`): a fact-driven page is `noindex` and absent from the sitemap until every non-archived fact of its topics is VERIFIED or NOT_PUBLISHED; NOT_FOUND keeps it out. The route declares the gate in its sitemap default and `SeoController` honours it. The total-cost page now uses the same gate instead of a permanent noindex. The admin verification queue is therefore the switch that publishes both pages.
- Footer "Understand" column and the Medicine pillar's related links point to the page. Test covers rendering, gating and the flip to indexable. 36 tests pass.

## Stage 14: operations readiness (P8)

- Dependabot extended to Composer and npm (packagist's advisory API is unreachable from the development environment, so advisories arrive as Dependabot alerts/PRs instead); `npm audit` reports 0 vulnerabilities.
- `uptime-check.yml`: scheduled smoke tests against `PRODUCTION_URL` every 30 minutes, one incident issue opened/commented while failing and closed automatically on recovery; inert until the variable is set after the first production deployment.
- `docs/ops/EMAIL-DELIVERABILITY.md`: SPF, DKIM, DMARC and mailbox checklist for the Hostinger-hosted `info@` address, with verification commands.
- Not done: Larastan could not be installed (Composer needs GitHub source clones through the proxy and the request is refused); static analysis stays on the backlog for a session with package access.

## Stage 15: staging gate and verify-by-source (P1/P4)

- `StagingGate` middleware: with `APP_ENV=staging` and `STAGING_BASIC_USER`/`STAGING_BASIC_PASSWORD` set, every request except `/up` and `webhooks/*` needs HTTP basic auth (401 + noindex otherwise); inert in production or without credentials. Wired through `ops/server-bootstrap.sh` (staging `.env` only), the bootstrap workflow (variable + secret) and `ops/smoke.sh` (`SMOKE_AUTH=user:pass`).
- Admin → Verification → *Verify by source*: pending facts grouped by official source URL, ordered by how many facts each page unlocks; one form per source with every fact pre-selected and two bulk decisions (verified on this page / not published on this page). Facts without a source URL are never bulk-verified. The single-fact and bulk paths share one transition method; bulk actions are audited with the fact ids. This is the intended route through the 533-fact backlog.
- Tests: 40 pass (4 new).

## Stage 16: deployment lifecycle rehearsal (P1)

- With a local SSH server standing in for Hostinger, the whole chain was executed for real: inspection → bootstrap → deploy → serve → smoke-tested deploy → forced failure with automatic rollback → encrypted backup fetched and decrypted. Record: `ops/reports/deployment-rehearsal-2026-10-03.md`.
- Three script defects fixed (crontab absence, MySQL-only bootstrap, rollback PHP binary). Route/config/view/event caching confirmed to work on this codebase.

## Stage 17: branded transactional email (P6)

- Mail theme published and branded: SMUKN header image (PNG, absolute URL), navy headings and buttons, warm paper background, footer with legal name, contact address and the independence statement. Applies to every notification including Laravel's email verification and password reset.
- `NotificationRenderTest` renders all 14 `ApplicationNotification` branches, the staff alert and the two framework notifications to HTML and checks the application number in the subject, the greeting, the disclaimer, the brand header and a portal link. 42 tests pass.

## Stage 18: payment, scheduler and error-page coverage (P2/P6)

- `StripeWebhookTest` drives the real webhook endpoint with HMAC-signed payloads: unsigned or unconfigured requests refused (400/503); `checkout.session.completed` marks the payment SUCCEEDED once, records the event, notifies the student and mirrors `payment_completed` into the funnel; Stripe retries are acknowledged without re-processing; unpaid completions, expiry, failure, partial and full refunds and unknown payments all behave.
- `ScheduledCommandsTest` covers `smukn:expire-payments`, `smukn:flag-review-due` and the reminder cadence (2-day inactivity reminder sent once, `--dry` sends nothing, withdrawn applications never reminded).
- Branded, DB-free 419/429/500/503 error pages beside the existing 404, each noindex with the contact address; rendering test added. 49 tests pass.

## Open items carried forward

1. Hostinger access → server report → deployment (docs/architecture/21).
2. Release-1 public pages (register rows 2–23) with verification gating.
3. Eligibility check (lead capture) and services/pricing page once prices are set.
4. ClamAV on VPS; QR rendering on the two-step setup page; first real backup run and restore test once server access exists.
5. Legal pages (privacy, terms, application terms, refund policy) — drafts need owner/legal review before publication.
