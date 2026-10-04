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

## Stage 19: portal payment, document and export coverage (P3)

- `PortalPaymentsAndDocumentsTest`: card checkout refused while Stripe is unconfigured; bank transfer creates a MANUAL_REVIEW payment, alerts admins and mirrors `payment_started`; a price from another tier is refused; admin confirmation succeeds the payment, notifies the student, mirrors `payment_completed` and is audited; students cannot confirm their own transfer. Passport image upload: re-encoded and scaled to ≤ 3000 px, encrypted at rest (`.png.enc`, no PNG signature on disk), streamed back decrypted with `no-store`, access logged; other students 403, staff allowed. GDPR export streams JSON with the account and application record.
- Skeleton `ExampleTest` removed. 51 tests, 700+ assertions pass.

## Stage 20: view-level funnel events and the photograph pipeline (P7/P6)

- First-party funnel now starts at discovery: `course_viewed` (medical school page, slug only) and `apply_viewed` (Apply Online landing) are recorded server-side and forwarded to GA4 after consent; `apply_click` (with placement: header, hero, floating, footer, CTA band) and `eligibility_started` fire client-side only when gtag is loaded. Admin → Funnel shows the two new top-of-funnel steps. Stale `Disallow: /medical-schools/compare/` removed from robots (no such route).
- Photograph pipeline for the owner's Unsplash workflow: `brand/photos/manifest.json` + originals → `php artisan smukn:images` → WebP/JPEG at 480/960/1440 px, blurred inline placeholder, public manifest; `<x-photo>` renders a `<picture>` with srcset, dimensions, lazy loading, alt and credit, and renders nothing while a slot is empty. Brief and shot list: `docs/design/IMAGERY-BRIEF.md`. Originals are git-ignored.
- Tests: 53 pass.

## Stage 21: owner-browser research loop (P8)

- Semrush remains without API units, so lookups are made in the owner's Semrush tab and recorded in `data/semrush/lookup-sheet.csv` (27 query themes from research 02). `php artisan smukn:semrush-import <csv>` copies only recorded figures into the research table, dated, and lists themes whose demand now supports a register row. Blank cells stay "DATA UNAVAILABLE". Test covers the import and the no-invention rule.
- Photo slots wired on the home hero, the Nigeria guide, the directory and Apply Online; each renders nothing until its photograph is built, so the pages are unchanged today.
- Settings diagnosis re-run at 15:03 UTC: still nothing present in any scope. 54 tests pass.

## Stage 22: per-page Open Graph cards (P5 — WhatsApp and social sharing)

- Links shared on WhatsApp, X and LinkedIn now carry a branded 1200×630 card with the page title: `App\Support\OgImage` renders with GD and the self-hosted fonts (converted to TTF in `brand/fonts/ttf`), `php artisan smukn:og` builds one card per sitemap page (29 committed under `public/images/og`, keyed by route name), `Seo::resolvedImage()` picks the card for the current route and falls back to the brand default. University pages get an on-demand card at `/images/og/schools/{slug}.png`, cached as a file and refreshed when the record changes. `og:image:alt` added. Build requests carry `X-SMUKN-Build` so they never count as visitors.
- Also this cycle: intake and sitting year bounds derive from the current year; the eligibility check's 2027 dates come from verified facts or stay generic. 57 tests pass.

## Stage 23: trust page, server rules and FAQ growth (P4/P6/P8)

- `/how-we-verify`: the editorial and verification policy as a public page (source hierarchy, what every label means, review cadence, what we never do, how to report an error, live counts from the reference database). Every verification chip across the site now links to it; footer link added; register row 23 updated with a build-status section for all rows.
- `public/.htaccess`: canonical https and non-www 301s (health endpoint exempt), dotfile and source-map denial, one-year immutable caching for fingerprinted CSS/JS/fonts, 30-day images, Brotli/gzip for text. The production smoke test checks both redirects.
- FAQ hub: seven more answers from the observed-question list in research 02 §C, each sourced from our own verified pages or official bodies (foundation year vs A-levels, "best" schools, Arts to Medicine, UCAT slot scarcity, naira conversion, acceptance rates, out-of-scope PLAB question); the working-after-graduation answer now links to the Working in the UK page. 25 published answers, all in the FAQPage JSON-LD.
- Re-crawl: 28 indexable pages, all in the sitemap, no broken links, no snippet findings. 57 tests pass. Settings diagnosis at 15:14 UTC: still nothing present.

## Stage 24: launch checklist and journey regression (P2/P5)

- Admin dashboard now carries a live **launch readiness** checklist (facts verified, university pages published, prices, Stripe, mail, staff two-step enrolment, legal review flag `SITE_LEGAL_REVIEWED`, analytics decision) with links to the screen that resolves each item.
- Fresh-account student journey on a 390 px viewport, from the eligibility check through registration, verification, application start, autosave, passport upload (received, under review), payments, messages, submissions and the JSON export: every step passes with zero console or CSP problems. The QA scripts (crawl, axe, CSP sweep, journey) now live in `ops/qa/` with a README so later sessions and the owner can re-run them.
- WhatsApp links appear in the footer and on Apply Online only when `SITE_WHATSAPP` is set. 59 tests pass. Settings diagnosis at 17:12 UTC: still nothing present; one more self check-in armed for 18:15 UTC.

## Stage 25: security audit and fixes (P0)

A read-only code audit (delegated, then every finding re-verified in the code) found ten issues; all are fixed with regression tests:

| Severity | Finding | Fix |
|---|---|---|
| High | A student's two-step verification could be switched off with the password alone, before passing the challenge | `disable` now requires the passed-challenge session marker (403 otherwise) |
| High | Email-verification links were a fatal error: `EmailVerificationRequest` used in `routes/web.php` without its import, so no new account could ever reach the portal | import added; signed-link test |
| Medium | The student data export serialised whole models, leaking staff notes, assignment, stage override, storage paths, key ids and approval snapshots | explicit allow-list per relation |
| Medium | Macro-enabled Word files (`.docm` renamed) passed the MIME filter and were served under the uploader's filename | zip inspection refuses `vbaProject`/`macroEnabled`; downloads use `code-vN.ext` built from the record; admin disposition via `HeaderUtils` |
| Low | Autosave stored arbitrary request JSON | only the step's declared fields are kept |
| Low | Deactivated prices were still purchasable | active-only lookup |
| Low | Any staff member could redirect any path, including sign-in, to an external URL | admin only, relative targets, private and auth paths refused |
| Low | A late `payment_intent.payment_failed` could downgrade a settled payment | ignored unless the payment is still INITIATED |
| Info | JSON-LD without `JSON_HEX_TAG` | added |
| Low | The plain-text PDF active-content check could not see compressed object streams or `#xx` name escapes | the check now inflates every stream it can and decodes name escapes before scanning (tests with a Flate-hidden `/JavaScript` and `/J#61vaScript`); ClamAV on the server and the sandboxed preview remain second-line controls |

Controls the audit confirmed sound are listed in `ops/reports/security-audit-2026-10-03.md`. 65 tests pass.

## Stage 26: SEO decision engine, technical audit and intent upgrades (P4)

Owner directive of 2026-10-03 ("Google organic growth + SEO + long-tail + internal linking + imagery").

- **Decision engine**: `data/seo/decision-register.csv` (65 query families in clusters A–T) with intent, Nigerian relevance, Semrush database, observed SERP, competing URLs, current and recommended page, supporting pages, relevance score, sources, status and reason; `docs/seo/DECISION-ENGINE.md` defines the statuses (RESEARCH → VALIDATED → BUILD → DRAFT → REVIEW → PUBLISHED → INDEXING → MEASURING → UPDATE, REJECTED with reason) and the rules (one page per intent, upgrade before multiplying, evidence hierarchy, no fabricated metrics, no thin university pages, search-to-action chain). Semrush cells read DATA UNAVAILABLE until the owner's export is imported. `SeoDecisionRegisterTest` enforces vocabulary, reasons, sitemap coverage, live-row indexability, draft noindex and FAQ anchors.
- **Technical audit (local build, 28 sitemap URLs; full 22-point table in `ops/reports/technical-seo-audit-2026-10-03.md`)**: canonicals, trailing-slash and case redirects, query-string canonical, 404, robots, sitemap, OG/Twitter, JSON-LD, image alt and dimensions, snippet lengths all clean. Findings fixed: the core landing page had one in-body inbound link (now nine: home, every hub, FAQ, GEM and NECO pages, with a test); `/fees` had one H2 for 1,573 words (table and range card now headed); the FAQ had no headings (each question is an H2 inside its summary); the directory now emits `ItemList` JSON-LD when unfiltered; `X-Powered-By` is removed by the middleware and by `.htaccess`.
- **Upgrade before multiplying**: graduate-entry page from 317 to about 1,200 words (two routes for a graduate, the one programme that publishes international eligibility, six GEM courses in the directory with published fees and the university's international policy, eleven programmes located whose international eligibility is not established, degree comparison, GAMSAT/UCAT windows, cost comparison, three FAQs with schema); NECO page from 379 to about 950 words (statements naming NECO, NECO English acceptance, WASSCE-only count, side-by-side table, route, three FAQs with schema). Nothing marked UNKNOWN was turned into a yes. The how-to-apply page (the action page of cluster I) went from 479 to about 1,350 words: route and shortlist first, UCAS as an independent applicant including both reference routes, the Nigerian document list, direct-application schools, missed-deadline options, the steps after submission, three FAQs with schema. The UCAT page (cluster H, the strongest single gap) went from 620 to about 1,500 words: the booking and access-arrangement deadlines joined the topic facts (VERIFY-ON-PAGE from the Consortium's dates page), subtest timings and results-to-candidates were recorded as NOT_FOUND so they stay hidden until verified, plus booking from Nigeria step by step, how schools use the score without invented cut-offs, preparation guidance that recommends no paid course, and three FAQs with schema. English requirements (492 to about 1,250 words: the two assessment layers, course-level bands, Nigeria-page bands, WAEC/NECO English acceptance with differing grades, which test and when, the visa layer marked as our understanding until GOV.UK is verified) and the UCAS timeline (526 to about 1,050 words: what each deadline means, the document timeline, the CAS-to-visa risk window) followed. A-levels (643 to about 1,400 words: reading an offer, Cambridge International sittings and predicted grades, IB equivalence, the two A-level years and the UCAT in order) and foundation routes (632 to about 1,050 words: the three things called foundation, published statements separated from the schools with nothing published, calendar, cost and visa consequences, provider questions) completed the pass over every requirements and admissions page; the Medicine pillar (598 to about 1,250 words) then gained the degree structure, live international-places counts, GMC facts and an honest decision section. Our status (306 to about 850 words) now defines the sector vocabulary from research 11 (agent, representative, referral partner, sub-agent, counsellor, UCAS registered centre, British Council training) with our position on each and a four-step check a student can run on any service. Every upgraded page carries two or three visible FAQs with FAQPage schema and links to the core landing page. axe re-run after the template changes: zero violations.
- **Semrush loop closed**: the lookup sheet now names the register rows each theme feeds (`register_ids`), and `smukn:semrush-import` copies recorded volume and difficulty into those rows with the database, date and keyword in the sources cell, so one owner export updates research 02, the asset-register prompts and the decision engine together (tested). The imagery brief records the per-image fields the owner asked for (source, URL, credit, page and slot, filenames, dimensions, alt, optimisation) and `smukn:images` carries the new `page` field through. Apply Online gained a "what you need before you create an account" block and two FAQs with schema (327 to about 575 words).
- **Admin → SEO**: the decision register is readable in the admin with a status filter and counts, so the owner can see every decision and reason without opening the CSV.
- **P0 verification continued**: rate limits confirmed on every public write route (eligibility, register, login, password reset, verification resend, two-step setup and challenge, uploads, messages); password policy minimum 10 characters with letters and numbers on registration, reset and change; secure, HttpOnly, SameSite=Lax session cookies set by the server bootstrap; `npm audit` reports zero vulnerabilities (production and development); the fresh-account mobile journey and the 28-page crawl re-run clean after this stage.
- 73 tests pass. Settings diagnosis at 17:49 UTC: still nothing present in any scope; the 18:15 UTC self check-in will re-run it.

## Stage 27: knowledge-graph link pass and second security sweep (P0/P3)

- **Internal links as a knowledge graph**: measured in-body inbound links for every sitemap page from `<main>` only. Pages below five inbound links received contextual, descriptive links where a reader would want them, not a related-articles block: home now introduces About, Our status, How we verify and the Medicine pillar; About links to How we verify, Contact and the Nigerian guide; How we verify's error-reporting paragraph links to Contact; the application terms link the website terms and privacy notice; the refund policy explains how to ask (Contact, portal messages) and what the pro-rata basis is (service terms); the eligibility consent already linked the privacy notice; how-to-apply and Our status link the services page; the pillar links the admissions hub. Every sitemap page now has at least one in-body inbound link besides the footer, and all hubs have four or more.
- **Second security sweep**: raw Blade output is limited to trusted templates (mail layout, auth frame, our own FAQ HTML); every model with `$guarded = []` is written only through validated arrays (no `request->all()` anywhere); no `env()` calls outside config; portal write routes that create records or are irreversible gained rate limits (start application 10/10 min, deletion request 3/10 min, checkout and manual-transfer 10/10 min, password change 5/min); public write routes were already limited. 74 tests pass.

## Stage 28: university page depth, four more FAQs, expired-form recovery (P2/P4/P8)

- **University pages** (the per-school intent, register row D02) now add two computed sections to every record: "What this means if you are applying from Nigeria" (international policy and places, WAEC/NECO statement, English, foundation route, graduate entry, each sentence switching on whether a published fact exists and never implying one) and "How to apply to this school" (route, test, the UCAS deadline and UCAT window as facts where relevant). `Course` JSON-LD is emitted per record, with an `Offer` only when the fee fact is VERIFIED. Pages went from roughly 290–440 words to 550–700 before any staff verification; they stay `noindex` until published.
- **FAQ hub**: 29 of the 40 observed questions now have sourced answers (added: NECO acceptance and NECO English, A-level school choice without recommendations, the step-by-step process, "is Medicine right for me"), each pointing at the page that holds the detail.
- **Expired-form page (419)** offers a real "go back to the form" link to the previous internal page instead of only home and login.

## Stage 29: hub depth and an eligibility headline fix (P2/P4/P8)

- **Requirements hub** gained a "which route is yours" table (what you hold → published route → what to check first → page) and four FAQs; **Admissions hub** gained a "where are you today" table (position in the cycle → what it means → the one next step) and three FAQs. Both hubs now answer their head intent on the page rather than only routing.
- **Eligibility check (friction found by running it as different applicants)**: the headline above the route map was a generic sentence chosen from the route statuses, so a Nigerian graduate was told "foundation or A-level/IB routes appear possible". The headline is now written per qualification (WAEC/NECO, A-levels/IB, foundation, degree), with a regression test. `#direct` anchor added to the how-to-apply page for the direct-application schools.

## Stage 30: fact verification from any browser (P5)

The network policy blocks every official domain from this environment, and the admin verification queue needs a deployed site. Neither should hold up the 636 pending facts, so verification now runs as a worksheet:

- `php artisan smukn:facts-export` writes `data/verification/worksheet-YYYY-MM-DD.csv`: every VERIFY-ON-PAGE, NOT_FOUND and REVIEW_DUE fact with its exact wording, source URL and a stable reference (`subject:slug:key:year`, never a database id), ordered by priority (1 cycle dates, fees and visa figures; 2 Nigerian-applicant statements; 3 the rest). The 2026-10-03 worksheet is committed.
- The reviewer opens each source in their own browser and fills `decision` (verified / not_published / source_changed / archive), `verified_value` only when the page wording differs, `new_source_url`, `reviewer_note` and `verified_on`.
- `php artisan smukn:facts-import decisions.csv [--dry-run]` applies the decisions: `verified` is refused without a source URL and a date; values are replaced only from `verified_value`; review-due dates follow the admin rules (six months for fees and deadlines, twelve otherwise); notes record the review; an admin-audit row is written; re-running is a no-op. `ops/deploy.sh` replays every committed `decisions-*.csv` after migrations so staging and production carry the same decisions. Round-trip test added.

## Stage 31: document checklist wording (P8)

- Reviewing the fresh-account journey screenshots showed the passport card explaining itself with the personal-statement reason, because one checklist rule required both documents and carried a single reason. The rule is now two rules with their own reasons (passport: the name-matching rule across documents, UCAS and the visa; statement: reviewed against the three UCAS questions); the seeder removes the old combined rule on existing databases.
- The FAQ hub is grouped into six themed sections (qualifications, tests and timing, costs, choosing schools, after graduation, deciding and about us) with a jump list; questions are H3s under group H2s. A new test enforces the search-to-action chain: every live informational page in the decision register must link in-body to the eligibility check or Apply Online.
- **Production consistency**: the computed guidance on university pages now treats a statement as published only when it is also publishable in the current environment, so production never says "the university addresses it (statement above)" about a fact the reader cannot see. A production-mode test pins the rule for university pages and the fee guide (unverified wording and figures absent, placeholders present).

## Stage 32: production sweep for unverified wording (P0/P5)

- A new test renders every sitemap page and a university record in production mode and asserts that no unverified fact's wording (sentences of 40+ characters; UCAS codes and source URLs excepted) appears anywhere. It found one leak: the imported UCAS-code field sometimes carries research notes ("A100 (also A110 Medicine with Foundation Year, 6 years, North West England residents)") and was printed verbatim on the directory, fee guide, graduate-entry page, university pages, the approval screen and in Course schema. Every public rendering now uses the bare code (`Course::shortUcasCode()`); the notes remain in the facts where they belong. Extending the sweep to the gated pages and a home-only record found a second leak: the GMC-status column (research wording such as "Listed as awarding body; … separately under GMC review") rendered as a chip and a warning box on directory cards and university pages; it now shows only while the matching `gmc_status` fact is publishable (`University::publicGmcStatus()`). 83 tests pass with the sweep covering 32 pages. Two course titles and one school name in the dataset also carried research notes ("…no A100 found", "…transfer detail NOT confirmed in this session", "…GMC lists '…'"); the names are now names and the notes moved to the records' notes arrays, with a test that structural fields never carry such phrases. 84 tests.

## Stage 33: preview without deployment, directory signal (P1/P2)

- **Preview**: a static snapshot of every public page (38 pages, six sample university records, real stylesheet and fonts) is published as a private artifact for the owner's visual review, and a `.devcontainer` lets the branch run as the live application in GitHub Codespaces from a browser (installs, migrates, seeds demo data, builds, forwards port 8000). Neither replaces staging; both exist because no Actions settings are present and nothing has ever been deployed.
- **Directory cards** now say whether the university publishes a WAEC/NECO statement ("Published by the university" / "No Nigeria-specific statement located"), computed from the fact's publishability so production never implies a hidden statement; tested in production mode, and a directory filter (`?waec=published`, noindex like every filter) narrows the list to those schools; the WAEC and NECO pages link to it. A priority-1-only worksheet (110 rows) was added for a first verification sitting. 85 tests.

## Stage 34: healthcare course universe as a master taxonomy (Objective 1)

The owner's directive is to cover the whole medical, healthcare and allied-health course universe without creating thin pages, so the first deliverable is a data model, not pages.

- **Taxonomy**: `data/healthcare/subjects.json` (30 subjects, version 2026-10-03d) records for each subject the official terminology and alternative terms, regulator and registration route, professional body, undergraduate entry and length, international availability, Nigerian relevance, WAEC/NECO relevance, A-level and English requirements, foundation and graduate routes, admissions test, application route, fees, deadlines, career pathway, Nigerian and long-tail demand, commercial intent, competition, sources, decision-register ids, status with reason and the research date. Values carry research labels (FACT = seen on an official page, LEAD = aggregator, NOT FOUND, NOT RESEARCHED, VERIFY-ON-PAGE) and are never rendered publicly; a subject's facts reach a page only through `reference_facts`.
- **Evidence**: three WebSearch sweeps (regulators and terminology; Nigerian demand across 39 SERPs; UK availability, entry, English and fees at sample universities, 60 searches) are written up in `docs/research/12-healthcare-course-universe.md` §A–§C. Nothing is inferred across professions or universities: §B shows availability is a per-university fact (Birmingham Midwifery, Cardiff Radiotherapy, Cardiff Dental Therapy and Hygiene and Cardiff Met Healthcare Science do not admit international students; Northumbria Midwifery and Plymouth Dietetics do; Manchester BDS publishes about 15 international places), and English requirements range from IELTS 6.5 (MMU nursing) to 8.0 (Manchester speech and language therapy).
- **Classification**: Medicine PUBLISHED (flagship); Dentistry, Nursing and Biomedical Science VALIDATED; 20 subjects RESEARCH; Public Health, Clinical Psychology, Arts therapies, Clinical Dental Technology, Orthodontic Therapy and Pharmacy Technician REJECTED (postgraduate or non-degree routes outside the mission). No subject other than Medicine may have a public page (`Profession::mayHavePublicPage()`); a test pins this until a subject reaches PUBLISHED on its own evidence.
- **Database**: `professions` table seeded from the JSON (`ProfessionSeeder`, idempotent), `courses.profession` (default `medicine`) so university course records can attach to any subject; Admin → Subjects (noindex, staff only) shows every field and the status counts.
- **Register**: `ops/seo/build-register.py` now generates cluster V from the taxonomy (one row per non-medicine subject plus V01, the single allied-health overview hub, VALIDATED with no URL), so taxonomy and register statuses cannot disagree; `docs/seo/DECISION-ENGINE.md` §7 records the subject layer and its transitions. 111 register rows.
- **Tests**: `HealthcareTaxonomyTest` (vocabulary and evidence, Medicine the only live subject, every course attached to a subject, register mirrors taxonomy, research labels absent from public pages and the admin route redirecting guests, staff screen renders). 91 tests.

## Stage 35: subject long-tail families and the knowledge graph (Objective 2)

- **Register columns**: `subject`, `indexation_decision`, `internal_link_role` and `conversion_role` are derived by the builder from each row's status and URL (decision engine §1), shown in Admin → SEO.
- **Knowledge graph**: `docs/seo/KNOWLEDGE-GRAPH.md` is generated from the taxonomy and the register: Medicine has eight of ten chain nodes built (Registration and Career are DRAFT behind the fact gate); every other subject shows its status and what unlocks BUILD.
- **Nursing and Dentistry long-tail** (research 12 §E, 36 SERP observations): sixteen cluster V family rows (V31–V46) with intent, observed SERP, competitors, supporting pages and reasons; three VALIDATED (WAEC/NECO → nursing, WAEC/NECO → BDS, dental UCAT as a section of the live UCAT page), eleven RESEARCH, two REJECTED (migration and multi-country agents). Findings: nursing's study intent is a minority inside a nurse-migration SERP; no page anywhere maps WAEC/NECO to BDS entry; official pages already answer deadlines, interviews, foundation and visa questions for both subjects. Eleven themes added to the Semrush lookup sheet for the owner. No page created; 111 register rows.
- **Security**: composer and npm audits clean; settings diagnosis run 13 (22:33 UTC) still finds every secret and variable missing.

## Stage 36: technical audit, course scoping, fact integrity and the expanded universe (cycle 31)

- **Technical SEO audit** (crawl 28/28 clean, axe zero violations on public, portal and admin pages, CSP sweep clean, fresh-account journey clean): `/index.php` and `/index.php/<path>` served duplicate pages with a 200 and a self-canonical; they now 301 to the clean path, built from scheme and host so the base URL cannot cause a loop (the first fix looped on the live server and a test that sets the script name now reproduces it). robots, HSTS, trailing-slash and case redirects, 404s and filter canonicals were already correct.
- **Knowledge graph**: every Medicine page carries a route map (course → requirements → Nigerian qualifications → UK medical schools → fees → how to apply → eligibility → Apply Online; registration and work join once the working page's facts are verified). In-body links to the Medicine hub rose from 5 to 17 and to how-to-apply from 7 to 16.
- **Multi-course safety**: every public Medicine query reads courses through `Course::medicine()`; without it the first non-medicine course row would have appeared in the directory, university pages, fee guide and admissions lists and could make a school match a Medicine filter (test proves the leak without the scope).
- **Fact integrity**: the topic seeder kept VERIFIED but overwrote the value on reseed; verified facts are now untouched. The prioritisation proposal fact is archived for the Act's definition of a UK medical graduate (VERIFY-ON-PAGE), interpreted on the working page only once publishable; the two Student visa maintenance figures are SOURCE_CHANGED after a GOV.UK snippet showed different amounts, and the worksheet export now includes that status (`worksheet-2026-10-04.csv` supersedes the 2026-10-03 sheet).
- **Portal honesty**: the start page, study, statement and tests steps stated a 2027 deadline in prose, hard-coded 2027–2030 and a 2028 default, and claimed most schools require applicants to be 18 (unsourced). Deadlines and the statement format now come from UCAS facts when publishable, years follow the calendar (`App\Support\Intake`), the age hint makes no claim; gated pages are now held to the title/description limits (the working page's description was 177 characters).
- **Demo accounts**: the documented demo logins (Codespaces, QA scripts) were created by no seeder; `DemoAccountsSeeder` runs only in local and testing and throws elsewhere.
- **Universe and register**: three research sweeps (allied-health long-tail, taxonomy gaps, Medicine long-tail; research 12 §F–§G, research 13). Taxonomy 36 subjects (Pharmacy VALIDATED; cardiac/clinical physiology folded into Healthcare Science; radiotherapy recorded as therapeutic radiography; ophthalmic and hearing aid dispensing RESEARCH; nursing associate, physician associate, clinical scientist and sonography REJECTED). Register 146 rows: sixteen allied-health families, thirteen Medicine families, legacy B04–B06 superseded; new subjects emit after existing families with an id-stability assertion. FAQ 6, 28 and new 41 answer JUPEB/IJMB/OND/HND, "is it free" and course length. Subject image plan in the imagery brief. Semrush lookup sheet 50 themes; Semrush itself still `no_api_units`.
- **Tests**: 101.

## Stage 37: second security audit and the allied-health course facts (cycle 32)

- **Security audit 2026-10-04** (`ops/reports/security-audit-2026-10-04.md`): 16 findings, all fixed with regression tests (`SecurityAuditFixesTest`). The two medium ones: a forged Host header produced a password-reset email with a valid token on an attacker's link (now TrustHosts on the APP_URL host plus every URL forced onto APP_URL), and deploys replayed verification decisions with no ledger, reverting later reviews (now a `fact_imports` ledger by content hash). Also: admin-only prices and redirect deletion, redirect path normalisation, authenticator replacement guard, per-account login limit, session invalidation on password change (`auth.session`), reset-form enumeration, Markdown-escaped staff emails, capped PDF inflation and image size checks, response statuses only after submission, approval refused on closed applications, the admin preview's sandbox CSP kept, https-only imported sources, formula-escaped worksheets, warning-level import log, confined CLI paths, mysqldump password via the environment.
- **Directory safety**: `University::medicalSchools()` (a Medicine course, or no courses yet) now gates every public university listing, count, statement list and school page.
- **Allied-health course facts**: `data/healthcare/courses.json` → `HealthcareCoursesSeeder`: 21 non-Medicine courses, 39 statements seen on official pages, all VERIFY-ON-PAGE, priority 4 in the worksheet (675 facts, regenerated), never on a Medicine page, reviewer verifications kept on reseed. Two non-medical providers added (Northumbria, Manchester Metropolitan) with `international_policy` not_published.
- **Tests**: 121.

## Stage 38: deployment readiness, the verification engine and search architecture (cycle 33)

- **First deploy would have been empty**: `ops/deploy.sh` migrated but never seeded, and the runbook only seeded the medical school dataset, so production would have had no service tiers (no application could start), checklist rules, topic facts or taxonomy. `smukn:reference-sync` now runs after migrations on every deploy.
- **Seeders were unsafe to repeat**: the dataset importer kept VERIFIED but overwrote the value and reset reviewer decisions; the allied seeder reset non-verified decisions. One rule now governs every seeder (`App\Support\FactSeeding`): create missing facts, refresh unreviewed ones, never touch a fact a person decided on (`reviewed_at`, set by the admin queue and the worksheet import). Editing a verified value in the admin queue un-verifies it.
- **Verification engine**: the worksheet follows the owner's priority order (P1 UCAS Medicine deadlines … P10 course availability, P12 other subjects), groups facts by official page with a count of facts each page resolves, and `--sources` writes a one-row-per-page browsing list (202 pages; 46 resolve 413 facts); P0 (71) and P1 (285) sheets. The admin by-source page uses the same order, shows each page's area and status mix, and never bulk-verifies a SOURCE_CHANGED fact. `smukn:sources-check` (nightly on the server) fingerprints the pages behind verified facts: a removed page, or a changed page where the verified wording or figure is gone, turns its facts to SOURCE_CHANGED and emails admins.
- **Deployment safeguards**: production dispatch requires a successful staging deployment of the same commit; no migration without a verified database backup (previously a failed or skipped dump let migrations run); smoke test checks reference data arrived; workflows can pin the server host key (`HOSTINGER_SSH_KNOWN_HOSTS`).
- **Semrush**: the importer reads Semrush's own export and matches it to the lookup sheet by keyword; `paste-list.txt` holds the 50 clean keywords, so one paste-and-export replaces 50 manual lookups.
- **Search architecture**: home and the core guide both led with "Study Medicine in the UK from Nigeria"; home is now brand-first with its own H1 and links to the guide; the snippet test fails whenever two pages lead with the same title phrase. Crawl 28/28 clean.
- **Healthcare**: `docs/research/14-subject-scorecard.md` (generated) shows per subject the evidence the promotion rule needs and what is missing; no subject promoted (Dentistry, Nursing, Pharmacy have good but unverified evidence).
- **Tests**: 132.

## Stage 39: the path to real staging (cycle 34)

- **MySQL rehearsal** (`ops/reports/deployment-rehearsal-2026-10-04.md`): bootstrap, verified backup, deploy, smoke, automatic and manual rollback, off-site backup and restore on MariaDB with hostile secrets. Fixed: reference data overflowing MySQL columns (bare UCAS codes on course rows, unclear years kept verbatim in notes, profession research columns widened, `value_number` cast to float), secrets on the remote command line and unparsable `.env` values (stdin, single-quoted literals, phpdotenv reader `ops/env-shell.php`, `MYSQL_PWD`), `mysqldump` without `--no-tablespaces`, releases carrying the dev SQLite database, `public/hot` and dev packages, rollback to never-live releases (`releases/.history`, `ops/rollback.sh`, rollback workflow), backups held by the production approval rule.
- **Gates**: production refuses without a required reviewer and without a staging success for the commit; staging refuses without `STAGING_URL` and the basic-auth gate; smoke fails on exposed files, debug output, an open staging site and wrong robots rules; strict host keys once pinned; CI runs the suite on MySQL 8 and proves the smoke gate both ways; diagnose reports the reviewer rule. Owner checklist: `ops/STAGING-SETTINGS.md`.
- **Verification engine**: `fact_changes` audit trail written by the model for every channel, shown per fact in the admin queue; staging publishes like production (`ReferenceFact::showsUnverified()`).
- **Semrush**: semicolon CSV, xlsx refusal, spacing-insensitive matching, conflicting duplicates never recorded, duplicate themes reported; the duplicate lookup theme replaced by A01; a test holds the 50-keyword list to distinct keywords mapped to live register rows.
- **Pages**: generated indexable-page audit (`ops/seo/page-audit.py` → `docs/seo/PAGE-AUDIT.md`); five pages gained an in-body route to eligibility and Apply Online; a test holds every non-legal indexable page to it.
- **Journeys**: `ops/qa/staff-journey.cjs` (admin review → proposal → student approval → submitted → tracking). Fixed: one throttle counter shared by every form (per-route keys now), accepting a document with no upload, a replaced proposal closing without an event.
- **Tests**: 148, on SQLite and MariaDB.

## Stage 40: staging blockers that need no credentials (cycle 35)

- **Registration and password reset returned 500** whenever mail could not be sent (any staging site without a mail password). Both emails are now queued subclasses of Laravel's own (`App\Notifications\Auth\*`), retried by the queue; bootstrap writes `MAIL_MAILER=log` without a mail password.
- **No first admin was possible on a new server** (demo accounts are refused outside local; only an admin could grant roles). `smukn:grant-role` and the *Grant account role* workflow promote a registered account; staff must enrol two-step verification; the last admin cannot be demoted.
- **Staging noindex** header on every response, checked by smoke.
- **Post-deploy review from a GitHub runner** (`ops/qa/staging-review.cjs`, *Review staging*, called after each staging deploy): every page on desktop and mobile for status, CSP, axe, H1 and noindex; CI runs it against a local build so it cannot rot.
- **Backup restore test** in every backup run: decrypt on the runner, restore into a throwaway MariaDB, check key tables (rehearsed locally: passes a real backup, fails a wrong passphrase and a truncated dump).
- **Inspection** no longer prints `APP_KEY` lines or shell history.
- **Tests**: 153, on SQLite and MariaDB.

## Stage 41: first-deploy order and PHP selection (cycle 36)

- **Second MariaDB/SSH rehearsal** of bootstrap → first deploy through a linked subdomain docroot → smoke (including the staging noindex header) → forced failure with automatic rollback → manual rollback → first admin over SSH with the *Grant account role* script: all passed.
- **Fixed**: the documented order bootstrapped staging without linking its docroot, so the first deploy's smoke test would have met Hostinger's placeholder page and rolled back; `ops/STAGING-SETTINGS.md` now links it at bootstrap. Server scripts trusted the first `php` found; they now accept only a PHP 8.3+ binary from `php83`, `php8.3`, `/opt/alt/php83/usr/bin/php` (CloudLinux) or `php`, and say so when none exists (rehearsed with an old `php83` on the path).
- **Tests**: 154, on SQLite and MariaDB.

## Stage 42: approved prices and the paid service journey (cycle 36)

- **Prices**: owner-approved fees T1 £75, T2 £395, T3 £795 (GBP) live in `tier_prices` (seeded by `PlatformSeeder::APPROVED_PRICES`, filled only where empty so an admin change is never reverted by a deploy). T3 is one fee paid at the start like T1 and T2; its two never-priced components are retired, and the refund policy and terms say "submission support" (refund by the existing pro-rata rule; none after submission) instead of a separately priced component. T2 carries the "Most popular" badge (`service_tiers.badge`, `tagline`).
- **Pages**: services page rebuilt (fees, badge, inclusions and exclusions, the owner's disclaimer word for word via `<x-fee-disclaimer>`); "Choose this service" remembers the choice through sign-up; the start page preselects it; creating an application leads to a confirmation page (service, fee, included, not included, third-party costs, no guarantee, terms) with "Continue to secure payment", bank transfer and change of service before payment.
- **Stripe**: Checkout session from the price record only (browser-sent amounts ignored, another service's price refused), labelled as our service fee; payment marked paid only by a signed webhook whose amount, currency, session and service all match (otherwise held for staff); replays and repeated refunds change nothing; expiry, refund, dispute, amount mismatch and service change are in the application history; no second checkout once paid; cancel returns with an explanation; return page waits for the webhook; live keys refused and live events ignored outside production. Staff see service, amount, status, dates and the Stripe reference; price changes are audited with before and after.
- **Server**: *Update server settings* (`ops/update-env.sh`) writes Stripe keys and the mail password into `.env` after bootstrap (stdin, literal quoting, staging refuses live keys).
- **QA**: local Stripe stand-in (`ops/qa/fake-stripe.php`, local only) and `ops/qa/payment-journey.cjs`: on phone and desktop the page price equals the amount sent to checkout (39500 GBP), cancel/retry/pay/webhook all behave; staff journey now pays before review. Axe: 0 violations (public, portal, admin); staging review: 64 page views clean; crawl 28/28.
- **Tests**: 168, on SQLite and MariaDB.

## Stage 43: premium fees and the production launch path (cycle 37)

- **Prices (owner decision, 2026-10-04, supersedes stage 42's fees)**: T1 Eligibility & Course Assessment £125, T2 Medical Application Preparation £695 (Most popular), T3 Full Medical Application Support £1,295, GBP, each one fee paid before work begins. `PlatformSeeder::APPROVED_PRICES` holds them; `SUPERSEDED_PRICES` lets a deploy replace exactly the earlier £75/£395/£795 once (logged as `price.owner_approval_applied`), while any other price an admin set is kept. Stage 42 and cycle 36 stay as written: they record what was approved then. Taglines and summaries reposition the tiers (specialist starting point / core service / comprehensive end to end) without urgency, discounts, testimonials, success rates or guarantees; the fee disclaimer is unchanged word for word.
- **Stripe by environment**: production accepts only live keys (`sk_live_`/`rk_live_`) and only live-mode events; every other environment only test keys and test events. Bootstrap and *Update server settings* refuse the wrong mode for the target.
- **Production preflight** (`ops/deploy.sh`, before backup and migrations): `APP_ENV` matches the target, `APP_DEBUG` false, unverified facts hidden; on production also the public `APP_URL`, a real mailer with a password, and live Stripe keys with a webhook secret when keys exist. Production bootstrap requires the mail password and the public URL.
- **First launch over an existing site**: the deploy's `cutover_docroot` archives the folder the domain serves to `~/backups`, moves it aside (never deletes it) and links it to the release; a failed smoke test puts the old site back. *Roll back* gains `restore_previous_site`. *Backup* gains scope `site`: the existing document root plus its WordPress database (credentials parsed from `wp-config.php` as text, never executed or printed), encrypted and restore-tested on the runner.
- **Production checks**: smoke fails on missing HSTS, a noindex header, a robots.txt without the production sitemap, a password prompt, and `/index.php/…` not redirecting; the page review now runs after production deploys too and fails on a noindex sitemap page, a canonical other than its own https URL, or titles/descriptions outside the limits. *Diagnose* reports the production mail password and the Stripe keys' mode (live or not), never values.
- **Rehearsed locally** (fake SSH into a throwaway home, MariaDB, an "old site" folder): failed smoke → old site restored; successful cutover → production smoke passes (53 schools, HSTS, CSP, indexable); redeploy (already linked); code rollback; restore previous site; relaunch; preflight refusing `APP_DEBUG=true`, `MAIL_MAILER=log`, a test Stripe key and published unverified facts with `current` unchanged; `site` backup of a WordPress folder whose database password contains `$`, `"` and `#`, decrypted and checked. Production-mode page review of the local site: 64 page views clean. Payment journey on phone and desktop at the new fees: £695 on the page, 69500 GBP sent to checkout, paid only after the signed webhook.
- **Tests**: 175, on SQLite and MariaDB.

## Stage 44: fees visible only to approved students; Stripe catalogue (cycle 38)

- **Owner decision (2026-10-04)**: the approved fees (T1 £125, T2 £695 Most popular, T3 £1,295) stay in `tier_prices` and in Stripe but are not public. Public pages (services, Apply Online, start page, JSON-LD) describe the services, inclusions and exclusions with "Service options and pricing are provided after your profile has been reviewed." and lead to Apply Online; the services JSON-LD carries no `offers`. Nothing is hidden client-side: public controllers no longer load prices at all.
- **Journey**: Apply Online → account → application created without a service → profile (staff alerted when complete, `profile.ready`) → staff *Approve for service selection* on the admin application page (`services_approved_at/by`, audit row, event, email "choose your service"; withdrawable while nothing is paid) → portal *Choose your service* lists every service with its exact fee, T2 marked Most popular, nothing preselected → confirmation page (name, fee, inclusions, exclusions, third-party costs, disclaimer, payment terms, refunds, terms checkbox) → Stripe → signed webhook. `User::canSeeServicePrices()` (staff, or the owner of an approved application) guards the services, confirmation, checkout, bank-transfer and service-change routes on the server; the stage asks for payment only after approval.
- **Stripe catalogue** (`App\Services\Payments\StripeCatalog`, `php artisan smukn:stripe-sync [--check]`, workflow *Stripe catalogue*): products with fixed ids `smukn_t1..t3`, one active one-time GBP price each found by lookup key `smukn_tN_gbp`; created only when missing, so repeats and fresh databases never duplicate; an admin's new amount creates a new price and deactivates the old one; no Payment Links. Checkout now sends `line_items[].price` chosen by the server from the price record (stored on the payment as `stripe_price_id`); the webhook still checks amount, currency, session and service. Deploy runs the sync when keys exist.
- **QA**: payment journey on phone and desktop (public source has no fee; start page none; services page refused before approval; after approval £125/£695/£1,295, none preselected; T2 → £695 → 69500 GBP via the catalogue price → paid only by the signed webhook; second run reused the same Stripe price); staff journey through the admin approval button; axe 0 violations on the public services, Apply Online, portal start, service choice and confirmation pages (phone and desktop).
- **Tests**: 182 on SQLite and MariaDB, including `PricingVisibilityTest` (every sitemap URL, feeds, redirects and JSON free of any fee for visitors and unapproved students; approval, selection of each service at its exact price; another student never sees fees; staff see fees, price changes audited; catalogue created once and never duplicated; no Payment Links).

## Stage 45: Stripe configuration made one-step (cycle 39)

- **Credentials**: Diagnose (run on 644ef89, 2026-10-04 06:57 UTC) now reports the staging Stripe secrets and their mode too; `STRIPE_KEY`, `STRIPE_SECRET` and `STRIPE_WEBHOOK_SECRET` are missing in both `staging` and `production`, so nothing has been created in the owner's Stripe account yet.
- **One authoritative price**: `smukn:stripe-sync` now switches off any other active price on `smukn_t1..t3` (never deletes), `--check` reports them, and the run fails if an active Payment Link sells one of the services (reported, never changed).
- **Webhook**: `smukn:stripe-webhook [--url] [--create --secret-file]` verifies the endpoint (https only, enabled, all six events) or creates it and writes the signing secret to a private file; the *Stripe catalogue* workflow (`webhook=create`) hands it straight to the server's `.env` over SSH, so it never appears in a log.
- **Real Stripe journey**: *Stripe test-mode journey* runs the app on a GitHub runner with the staging test key, syncs the catalogue, forwards real Stripe-signed events with `stripe listen`, and drives three new students through admin approval → fees → choice → Stripe's hosted Checkout (test cards; T1 first declined) → signed webhook → paid, reading each session back from Stripe (amount, currency, price id, test mode); then the price-leakage tests and a crawl of the running app. Rehearsed here against the stand-in, which now mimics the hosted form, the decline and `stripe listen`: T1/T2/T3 paid at 12500/69500/129500 GBP with the catalogue price ids.
- **Tests**: 184 on SQLite and MariaDB.

## Stage 46: launch without Stripe, payment honestly closed (cycle 40)

- **Owner decision (2026-10-04)**: production launches before Stripe; Stripe configuration is a post-launch step. Nothing in the Stripe integration or the approved-student model changed.
- **Payment closed until it works**: `StripeService::paymentsOpen()` is true only with a usable Stripe key or when the owner sets `SITE_BANK_TRANSFER=true` (new, default false; bootstrap writes it). Until then the confirmation page still shows the service, exact fee, inclusions, exclusions, terms and refunds, but replaces every payment action with "Payment is not open yet" (no card button, no bank-transfer form); the dashboard says so; checkout and bank-transfer requests are refused on the server; the admin approval card and launch checklist say payment is closed. The public services page mentions bank transfer only when it is on.
- **When it opens**: `smukn:payments-open-notify` emails each approved student who chose a service and has not paid, once (`payments.open_notified`); *Update server settings* runs it after writing Stripe keys.
- **Tests**: 186 on SQLite and MariaDB; browser check of the closed confirmation page (no payment action, axe 0 violations).

## Stage 47: production launch blockers removed where no credentials are needed (cycle 41)

- **Access**: Diagnose (08:38 UTC): every repository, staging and production setting is still missing; `production` has no required reviewer. Nothing below needed them.
- **Public launch checks** (`ops/launch-checks.sh`, workflow *Launch checks*, read-only, weekly): run from GitHub at 08:39/08:44 UTC. Email DNS is ready: Hostinger MX, one SPF record (`include:_spf.mail.hostinger.com ~all`), DMARC `p=none`, DKIM keys at `hostingermail-a/-b/-c`. The certificate covers the domain and `www` until 22 Nov 2026; `staging.` does not resolve yet. The current site is a PHP 8.2 application behind Hostinger's CDN; its sitemap lists 76 URLs.
- **Old URLs**: all 76 are answered: 65 permanent redirects to the equivalent page (`data/seo/legacy-redirects.csv`, seeded by reference-sync, never overriding Admin → Redirects; lookups are case-insensitive so `/Study-medicine-in-…` goes in one hop), 4 same paths, 7 with no equivalent (careers, nursing, dentistry, pharmacy and similar) answer 404 instead of a misleading redirect (`data/seo/legacy-gone.txt`). `LegacyRedirectsTest` checks every one.
- **One host**: `www` answered pages itself; it now answers 301 to the APP_URL host. Trusted hosts are anchored patterns for exactly that host and its `www` form: the bare host was read by Symfony as an unanchored regex and admitted `staging.<host>` and `<host>.evil.example` in production (verified in a production-mode server: now 400).
- **Deploy safety**: staging must be `https://`; a production cutover refuses to run without a restore-tested *site* backup of the current site from the last 7 days; the site backup now also dumps an application's database named in a `.env` in or above the served folder (rehearsed with special characters in the password; `vendor/` left out).
- **Email before registration**: production starts with student registration closed (`SITE_REGISTRATION_OPEN=false`, page "Registration opens shortly", POST refused). *Send test email* sends a real message through the server's SMTP (`smukn:mail-test`, refuses log/array mailers, never prints the password) and opens registration only when the message is accepted and the email DNS checks pass.
- **Smoke**: production fails when `www` does not redirect; every target checks two old-site URLs.
- **Tests**: 194 on SQLite and MariaDB.

## Stage 48: production launch without staging (cycle 43)

- **Owner decision 2026-10-04:** no staging site. `ops/production-rehearsal.sh` stands in for the staging review. It serves the commit in production mode on the runner under the real host name, using a throwaway SQLite database, and runs:
  - smoke;
  - trusted hosts;
  - www → apex;
  - every legacy redirect;
  - no debug output;
  - no fee strings on the sitemap, apply and auth pages;
  - registration closed;
  - `paymentsOpen()` false.
- **`launch-production.yml`** has five jobs, run in order:
  1. **rehearse:**
     - typed `LAUNCH` and the settings check, including the required reviewer;
     - tests;
     - rehearsal;
     - web PHP ≥ 8.3 probe;
     - public DNS/HTTPS.
  2. **backup:**
     - read-only inspection;
     - encrypted site + database backup of the served folder (`auto` detection), restore-tested into MariaDB, kept as an artifact for 90 days.
  3. **launch** (`production` environment approval):
     - bootstrap without Stripe;
     - deploy with preflight, cutover and automatic restore;
     - test email, then registration opens.
  4. **review:** production-mode page review.
  5. **checks:** public checks.
- **`deploy-hostinger.yml`:** a production deploy without a staging deploy of the commit now runs the rehearsal before building the release.
- **The domain answers with PHP 8.2.33.** The launch stops before any change while the website's PHP version is below 8.3; checked again right before the cutover.
- **Tests:** 195 on SQLite and MariaDB.

## Stage 49: first production launch attempt and the document-root fix (cycle 43)

- **Launch run 37200863527 (commit 1bc75e1): the steps before the cutover passed.**
  - **Settings:** detected; the production reviewer is set.
  - **Tests:** 195 passed.
  - **Production rehearsal:** passed every check.
  - **Web PHP version:** 8.3.
  - **Backup:** 4,582 files from `~/domains/studymedicineuknigeria.com/public_html` (no database found for the old site). Encrypted, checksum-verified and decrypted on the runner.
- **The owner approved at 12:38 UTC. The deploy then:**
  - prepared the server;
  - migrated the new database and loaded the reference data;
  - switched the release.
- **The cutover failed.** It replaced `public_html` with a link to the release, and the domain answered 403 on every path. The smoke test failed and the old site was restored automatically within two seconds.
- **Cause:** `docroot-probe.yml` showed correct permissions all the way along the path, but Hostinger answers 403 for any symbolic link reached from the document root, whether it points into `~/apps` or into the domain folder.
- **Fix:** the document root stays a real folder.
  - `ops/publish-docroot.sh` copies the live release's `public/` into it and writes an `index.php` that loads the release by its absolute path. It runs after the cutover, after every later deploy and after every code rollback.
  - It only ever writes into an empty folder or one it published before.
  - A failed publish or smoke test moves the new folder aside and moves the old one back. Nothing is deleted.
- **Rehearsed locally** against a sandbox server home: failed launch with restore, successful launch passing the production smoke test, later deploy, code rollback, and restore of the previous site.
- **Tests:** 196 on SQLite and MariaDB.

## Stage 50: production launched (cycle 44)

- **Launch run 37205984027:** commit `0dfdba8`, release `2026-10-04T13-38-31`, approved 13:38 UTC.
  - The cutover published into a real `public_html`.
  - The production smoke test passed all 37 checks.
  - The page review covered 64 views with 0 problems.
  - The info@ test email was accepted and registration opened.
- **Live verification:** `ops/live-verify.sh` and `live-verify.yml` (read-only) check every old-site URL, fee and debug text on every public page, canonicals, JSON-LD, the auth pages, portal and admin protection, foreign hosts and headers, plus the server state over SSH. The run passed.
  - Foreign hosts are refused at Hostinger's CDN (connection closed, curl code 000).
- **Server state:** payment closed, Stripe off, debug off, unverified facts hidden, 0 pending migrations; 55 universities, 77 courses, 701 facts, 65 redirects, 0 users.

## Stage 51: visual and product refinement (cycle 45)

- **Audit:** every public page was reviewed on desktop (1440 px) and mobile (390 px). It was clean but text-heavy and flat: a text-only hero, a 53-card wall in the directory, and no shared thread across the hub pages.
- **Built:**
  - maps from public-domain data (route globe, UK school map, school locator) and a Lucide icon set;
  - new design tokens and section rhythm;
  - sticky header with an independence line, and a dark footer with the journey;
  - warm page-head band with a journey stepper across all guide pages, plus an "At a glance" panel on the Medicine pillar;
  - new homepage composition;
  - directory with map, nation chips, filter panel and whole-card links;
  - school pages with a locator map and a two-column fact grid;
  - route panel with a "Next step" card.
- **Unchanged:** no new pages, no new claims, no prices; canonicals, JSON-LD and the verification system are untouched.
- **Checks:**
  - 196 tests on SQLite and MariaDB;
  - axe found 0 violations on public, portal and admin pages, after fixing footer contrast, link styling and the utility-bar landmark;
  - SEO crawl: 28 indexable pages, 0 broken links;
  - CSP sweep: 0 violations;
  - production rehearsal: all checks passed.

## Stage 52: Google readiness live, WhatsApp contact, searcher's journey (cycle 46)

- **Released** 2026-10-04 15:45 UTC (release `2026-10-04T15-45-58`, commit b8e676a), with the owner's approval on the `production` environment.
- **Shipped:**
  - Google-readiness work from stages 49–51 (crawl rules, structured data, titles, Search Console tag support, GA4 browser and server events after consent);
  - a floating "WhatsApp us" contact above Apply Online (+44 7842 292527, pre-filled message, tooltip, lifted above the consent banner, kept on the apply pages without a second Apply button);
  - `ops/qa/public-journey.cjs`, which clicks visible links from the home page to registration, now part of *Live verification*;
  - the launch checklist extended to 30 areas.
- **Live verification (run 37214472895), all passed:**
  - 65 old URLs 301; no fee on any public page; canonicals, JSON-LD, headers; foreign hosts refused;
  - Google audit 75/75 (28 sitemap URLs, 85 internal URLs);
  - journey on phone and desktop with no problems;
  - server: production, debug off, payment closed, Stripe off, 0 pending migrations, published-folder document root.
- **Photography:** unsplash.com answers 401/307 to GitHub runners; the official API answers and needs a free Access Key (`UNSPLASH_ACCESS_KEY`, owner action). `ops/design/unsplash.py` uses the API when the key exists.

## Stage 53: live payments, Search Console, final launch verification (cycle 47)

- **Stripe live (owner-approved):**
  - catalogue run 37219596869 created live products `smukn_t1`–`t3` with one active GBP price each (T1 £125, T2 £695, T3 £1,295) and webhook endpoint `we_1UMtAEPRsa8uF28s2QufAeVU` (6 events); no Payment Links;
  - *Update server settings* installed the live keys.
- **Fixed on the way:**
  - `ops/update-env.sh` exported unused inputs as empty variables, which Laravel prefers to `.env`; the cache lost the webhook secret. Inputs are now unset before `config:cache` (test in DeploymentSafetyTest).
- **Verification added:**
  - `smukn:stripe-webhook --self-test`: a Stripe-signed harmless event through the public URL must be accepted (200) and a forged one refused (400);
  - live verification "payments open" mode: live key, webhook secret, self-test, live catalogue, charges enabled, Stripe customer-facing name and statement descriptor are the brand (yes/no only: the repository and its logs are public);
  - session cookie Secure and HttpOnly, no public storage link, WhatsApp contact, and no placeholder, development, key or personal-mailbox text on any public page.
- **Content:** reviewer notes on facts no longer reach public pages (GoogleReadinessTest crawls every public page, school pages included).
- **Search Console:** owner set up the property and submitted the sitemap; Google reports Success, 28 discovered pages. `ops/sitemap-probe.sh` re-checks it daily as Googlebot.
- **Live verification 37224627170 (release `2026-10-04T18-24-28`): all passed.**
- **Local QA on the same code:** 206 tests on SQLite and MariaDB; SEO crawl 28/28, 0 broken; axe 0; CSP 0; public journey phone and desktop; GA4 browser events; student, staff and paid journeys (stand-in Stripe: fee £695 = Checkout 695.00 GBP, signed webhook 200, paid).

## Stage 54: scheduler cron, transactional email and first admin (cycle 48)

- **Symptom:** the owner's verification email never arrived.
- **Read-only diagnosis (`ops/mail-diagnose.sh`, *Mail delivery diagnosis* workflow; counts and class names only, addresses masked):**
  - SMTP settings and sign-in to `smtp.hostinger.com:465` were fine;
  - 3 `QueuedVerifyEmail` jobs were waiting with 0 attempts;
  - Hostinger offers no `crontab` over SSH, so the bootstrap's cron line was never installed (its warning went unnoticed) and the queue worker, which runs from `schedule:run`, never ran.
- **Fix (owner, hPanel → Cron Jobs, every minute):**
  - a first `cd … && php artisan schedule:run` entry was never executed;
  - the absolute form `/opt/alt/php83/usr/bin/php ~/apps/smukn-production/current/artisan schedule:run >> ~/apps/smukn-production/shared/cron.log 2>&1` runs every minute (Hostinger wraps it in `flock` and a 1800 s timeout);
  - the queue drained to 0 with no failed jobs; the owner verified the admin email and enrolled two-step verification.
- **Guard:** *Live verification* now fails when a queued job waits more than 10 minutes; the bootstrap prints the exact hPanel cron line.
- **Admin:** `info@studymedicineuknigeria.com` granted `admin` by *Grant account role* (logged as `account.role_granted_by_console`); two-step verification enrolled.
- **Test fixture:** `ops/qa/fixtures/dummy-test-document.pdf` (plain, labelled "DUMMY TEST DOCUMENT", no personal data) for live operational tests; a test proves the document pipeline accepts it.

## Open items carried forward

1. Hostinger access → server report → deployment (docs/architecture/21).
2. Release-1 public pages (register rows 2–23) with verification gating.
3. Eligibility check (lead capture) and services/pricing page once prices are set.
4. ClamAV on VPS; QR rendering on the two-step setup page; first real backup run and restore test once server access exists.
5. Legal pages (privacy, terms, application terms, refund policy) — drafts need owner/legal review before publication.
