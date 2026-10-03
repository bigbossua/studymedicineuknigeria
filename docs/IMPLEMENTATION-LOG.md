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

## Open items carried forward

1. Hostinger access → server report → deployment (docs/architecture/21).
2. Release-1 public pages (register rows 2–23) with verification gating.
3. Eligibility check (lead capture) and services/pricing page once prices are set.
4. ClamAV on VPS; QR rendering on the two-step setup page; first real backup run and restore test once server access exists.
5. Legal pages (privacy, terms, application terms, refund policy) — drafts need owner/legal review before publication.
