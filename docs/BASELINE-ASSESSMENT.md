# Baseline assessment — 2026-10-03

A statement of what exists, what is verified, and what is not, taken before any server change. Companion to `docs/IMPLEMENTATION-LOG.md` (decisions) and `ops/reports/` (access and server findings).

## 1. Repository and delivery

| Area | State | Evidence |
|---|---|---|
| Repository | `bigbossua/studymedicineuknigeria`, public, default branch `claude/new-session-p6gdm6`, 19 commits, no secrets tracked | pre-push scan; GitHub contents checks |
| CI | GitHub Actions: Composer install, Vite build, Pint, PHPUnit on every push; green on all pushes | runs on 6a0b926 → 27b7652 |
| Tests | 21 feature tests / 109 assertions: SEO behaviour, ownership, upload validation, approval gate, role boundaries, eligibility check | `php artisan test` |
| Code style | Pint clean | CI |
| Deployment agent | `inspect-hostinger.yml` (read-only) and `deploy-hostinger.yml` (staging/production) registered; guard verified: with no settings they stop before any SSH | runs 37125079386, 37126232582 |
| Direct SSH from Claude cloud | **Not possible** (network policy) | `ops/reports/access-check-2026-10-03.md` |
| Hostinger server | **Not yet inspected**; nothing known about PHP version, docroot, existing site, database | blocked on Actions settings |

## 2. Application (Laravel 13.34, PHP ^8.3)

| Layer | What exists | Verified how |
|---|---|---|
| Public site | 27 indexable pages (home, Medicine pillar, Nigerian landing, foundation routes, requirements hub + WAEC/NECO/A-levels/GEM/English, fee guide, admissions hub + UCAT/UCAS 2027/how to apply, FAQ, Apply Online, services, eligibility, About, Our status, Contact, 4 legal drafts) plus the medical-school directory and 53 university records; total-cost page noindex until inputs verified | route sweep (all 200), screenshots, tests |
| SEO | per-page canonical/description/robots; Organization, BreadcrumbList, WebSite, CollegeOrUniversity, Service and FAQPage JSON-LD; dynamic robots (disallow-all outside production); sitemap of published routes only; DB redirects + lowercase/no-trailing-slash canonicalisation (global middleware); branded 404 | tests, curl |
| Reference data | universities (53), courses (56), reference_facts (617 + 36 topic facts) each with source URL and verification status; importer idempotent; **0 facts VERIFIED** so far; in production unverified facts are hidden | DB counts |
| Accounts | registration, email verification, rate-limited login, no-enumeration reset; roles student/staff/admin; **TOTP enforced for staff/admin** (enrol before any admin page, challenge per session, single-use codes, recovery codes, admin reset, console lock-out recovery) | tests (TwoFactorTest) |
| Applications | numbered `SMUKN-YYYY-NNNNNN`, 9 autosaving steps, derived stage resolver, next-action engine, data-driven checklist (10 rules), withdraw | tests, browser flow |
| Documents | content sniffing, size limits, image re-encode, PDF active-content rejection, ClamAV when present, encryption for passport/financial, private UUID storage, logged downloads, staff sandboxed preview | tests, browser flow |
| Payments | Stripe Checkout + webhook (idempotent event log), bank-transfer fallback with manual confirmation; **prices null → nothing chargeable**; **no Stripe keys configured** | code review; not exercised against Stripe |
| Approval gate | hashed package snapshot, typed-name declaration, IP/UA, automatic revocation on change; staff cannot mark ready/submitted without a live authorisation; agreement-gated routes refused | tests, browser flow |
| Admin | dashboard, application workspace, leads, payments, services/prices, verification queue, university publishing, redirects, users/roles, audit log | browser flow |
| Notifications / jobs | one notification per real event; reminders (2/7/14/30, 3/10, 2/7 days), review-due flagging, payment expiry; queue worker via scheduler | commands run locally |
| Brand | symbol + wordmark, favicon set, OG image, email header, generator script | files |

## 3. Known gaps (ordered by the brief's priority scale)

- **P0** ~~Staff TOTP enforcement~~ done (stage 7); ~~CSP report-only~~ enforced with nonce (stage 8); off-site encrypted backup job built (stage 9), first run depends on server access. ClamAV availability depends on hosting tier.
- **P1** Server unknown; `.env` and database do not exist on the server; staging subdomain not created; cron not installed. All prepared in `ops/` and the Actions workflows; blocked only on Actions settings.
- **P2** Service prices unset (by design until the owner decides); Stripe keys and SMTP password not configured; eligibility logic is rule-based and cautious but not yet reviewed by a qualified admissions reviewer.
- **P4** Research gaps: 20 schools with identity-only records; fee year unconfirmed for several; visa fee, living costs, UCAT fee not found; all facts need on-page verification (admin queue).
- **P5** Semrush/GSC/GA4 unavailable in this environment → demand evidence is SERP observation only; asset-register rows marked † need re-confirmation.
- **P6** Portal and admin have no dark mode; some admin tables are dense on mobile.
- **P7** ~~server-side analytics table~~ implemented (stage 10: `funnel_events` + Admin → Funnel); GA4 loader is consent-gated and switches on only when the owner supplies `SITE_GA4_ID` (no property yet).

## 4. What must not be rebuilt

The brand, design tokens, SEO layer, reference-fact model, state machines, approval gate, document pipeline and the 27 pages are working and tested. Future work extends them; it does not replace them.
