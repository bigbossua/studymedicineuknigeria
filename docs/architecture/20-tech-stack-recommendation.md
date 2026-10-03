# 20. Technical stack recommendation

Status: RECOMMENDATION (needs one confirmation from the owner: which Hostinger plan is in place).

## 20.1 Constraints that decide the stack

| Constraint | Source | Implication |
|---|---|---|
| £0 capital; use Hostinger (already paid) and Stripe | brief 4, 5 | the app must run on what Hostinger provides: PHP 8.x + MySQL on shared/Business/Cloud plans; Node.js only on some plans or a VPS |
| Public SEO site + private portal + admin in one product with one design system | brief 88, 120 | a single monolith with server-rendered HTML is simpler to secure and to keep visually consistent than a static site plus a separate app |
| Sensitive documents, access logging, encryption, no public URLs | brief 29 | server-side controlled downloads, private disk, mature auth framework |
| Stripe Checkout + webhooks, transactional email, scheduled reminders, queues | brief 23, 49, 109 | framework with first-class queue/scheduler/mail |
| Not a WordPress template; must look bespoke | brief 87 | custom front-end; a CMS is optional and internal |
| Mobile-first, fast on Nigerian connections | brief 61 | minimal JS, server-rendered pages, HTTP caching, optimised images |

## 20.2 Recommendation

**Laravel 12 (PHP 8.3) monolith** · MySQL 8 · Blade templates + Livewire 3 (or Alpine.js) for interactive forms · Tailwind CSS v4 with custom design tokens · Stripe PHP SDK (Checkout) · Laravel queues (database driver on shared hosting, Redis on VPS) · Laravel scheduler via cron · Mail via SMTP (Hostinger mailbox) or a transactional provider free tier · Private filesystem disk (local on VPS / S3-compatible if available).

Why this over alternatives:

| Option | Fit | Verdict |
|---|---|---|
| **Laravel monolith** | runs on every Hostinger tier that has PHP 8.3 + MySQL + SSH + cron (Business/Cloud/VPS); Composer available; batteries included for auth, 2FA, signed URLs, file storage, queues, mail, Stripe (Cashier), policies | **Recommended** |
| Next.js + Postgres | excellent DX, but needs a Node runtime (Hostinger Node support is plan-dependent) or a second host; splits public/private into two deployables; more moving parts for £0 | not recommended now |
| WordPress + plugins | fast to start, but portal/documents/payments via plugins is insecure and un-bespoke; explicitly contrary to the brief's visual goals | rejected |
| Static site (Astro) + separate portal app | best raw performance for public pages, but two codebases, two design implementations, and the directory data must feed both | reconsider only if public traffic outgrows the monolith |

## 20.3 Hosting layout (assumption: Hostinger Business/Cloud shared plan; adjust if VPS)

- Document root → `public/` only. Application code, `storage/`, `.env` above the web root.
- HTTPS enforced (Hostinger free SSL), HSTS, security headers (CSP report-only first, then enforce), `X-Robots-Tag` on private routes.
- Cron: `* * * * * php artisan schedule:run` (reminders, verification-due job, reconciliation, sitemap regeneration).
- Queue: `queue:work --stop-when-empty` every minute from cron on shared hosting; supervisor on VPS.
- Backups: Hostinger daily backups + weekly encrypted DB dump + documents to off-site object storage (encrypted) — required because documents are irreplaceable for students.
- If the plan is **shared hosting without SSH/cron**: the portal is still buildable, but ClamAV is unavailable (14.4 fallback applies) and deployment is by rsync/FTP of a built artefact. Flag: upgrade to VPS when revenue allows, mostly for malware scanning and Redis.

## 20.4 Front-end

- Tailwind v4 with tokens in `design-tokens.css` (see 19 Design system). No component framework; hand-built component partials in Blade (`<x-card>`, `<x-source-box>`, `<x-verified-badge>`, `<x-doc-status>`, `<x-progress>`, `<x-cta>`).
- JS budget: public pages ≤ 40 KB gzipped (search/filter/compare on directory uses progressive enhancement; the directory works without JS via query-string filters). Portal ≤ 150 KB.
- Images: WebP/AVIF via build step, `srcset`, lazy loading, explicit dimensions. Unsplash downloads stored locally (never hot-linked) with attribution recorded in `image_credits` table.
- Fonts: self-hosted, two families max, `font-display: swap`.

## 20.5 Environments and delivery

- `main` = production; `develop` = staging on a Hostinger subdomain with `noindex` and HTTP basic auth.
- GitHub Actions: lint (Pint), static analysis (PHPStan level 6), tests (Pest), build assets, deploy via SSH on tag.
- Secrets only in `.env` on the server; `.env.example` committed.

## 20.6 Open question for the owner

> **Which Hostinger plan is active (Single/Premium/Business shared, Cloud, or VPS), and does it expose SSH, cron and MySQL?** The recommendation holds for all; the answer decides queue/scanner/backup details in 20.3.
