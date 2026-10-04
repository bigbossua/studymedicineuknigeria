# StudyMedicineUKNigeria.com

Evidence-led platform for Nigerian students applying to study Medicine in the United Kingdom:
public information resource → UK medical-school directory → online application → private student
portal → document centre → student-approved university submission → tracking.

**Phase (2026-10-03): application built and tested; first deployment waits on Hostinger access.**
Laravel 13 (PHP 8.3) monolith, Blade + Tailwind v4 + Vite, MySQL in production, SQLite locally.
Deployment, inspection, backups and uptime checks all run from GitHub Actions over SSH; nothing is
deployed until the inspection report has been reviewed (`docs/architecture/21-deployment-and-rollback.md`).

## Read first

| Document | Purpose |
|---|---|
| `docs/decision/00-DECISION-REPORT.md` | consolidated decisions from the research phase |
| `docs/BASELINE-ASSESSMENT.md` | what exists, how it is verified, known gaps by priority |
| `docs/IMPLEMENTATION-LOG.md` | stage-by-stage record of what was built and tested |
| `ops/reports/owner-cycle-2026-10-03.md` | autonomous owner cycles and the owner actions still required |
| `docs/architecture/12–21` | workflow and state machines, portal, documents, payment, submission, data model, technical SEO, design system, tech stack, deployment |
| `docs/research/*`, `docs/DATA-AVAILABILITY.md` | sourced evidence; every fact carries a URL and a verification state |

## Principles encoded in the code

- **Nothing unverified reaches students in production.** Reference facts (fees, deadlines, statements) carry `VERIFIED / VERIFY-ON-PAGE / NOT_PUBLISHED / NOT_FOUND / REVIEW_DUE / SOURCE_CHANGED / ARCHIVED`; unverified values are hidden unless `SITE_PUBLISH_UNVERIFIED=true`. Fact-driven pages stay `noindex` and out of the sitemap until their topics are verified (`App\Support\PublishGate`). Staff verify in Admin → Verification, fastest via *Verify by source*.
- **No submission without the student's explicit approval.** The approval hashes the package; any later change revokes it. Agreement-gated routes are refused until agreements exist.
- **Student documents are private.** MIME sniffing, re-encoding, active-content rejection, encryption at rest for passports and financial documents, logged streamed downloads, ClamAV when present.
- **Staff accounts need two-step verification.** TOTP enrolment is enforced before any admin page; recovery codes; admin reset; `php artisan smukn:two-factor-reset <email>` for lock-outs.
- **Measurement is first-party by default.** `funnel_events` mirrors the funnel without PII; GA4 only loads when `SITE_GA4_ID` is set and the visitor consents, never in the portal or admin.
- **No invented prices, rankings, partnerships or testimonials.** Prices are null until set in Admin → Services.

## Repository map

```
app/                      Laravel application (controllers, models, services, middleware, support, console commands)
resources/views|css|js    Blade templates, Tailwind v4 tokens and components, progressive-enhancement JS
database/migrations|seeders   schema; reference-data importer and seeders (universities, courses, facts, topics, tiers)
data/                     research datasets (`smukn:import-reference`), Semrush lookups (50 themes for the owner), the SEO decision register (`data/seo`), the healthcare course-universe taxonomy (`data/healthcare`), fact verification worksheets (`data/verification`)
brand/                    logo/favicon/OG generator (`brand/build.py`) and masters
docs/                     research, decisions, architecture, logs, ops checklists, the SEO decision engine (`docs/seo`)
ops/                      deploy.sh, server-bootstrap.sh, backup.sh, inspect-hostinger.sh, smoke.sh, RESTORE.md, reports/
.github/workflows/        ci, inspect-hostinger, bootstrap-hostinger, deploy-hostinger, backup-hostinger, uptime-check
tests/Feature             145 tests (SEO and decision register, healthcare taxonomy, workflow, documents, approval gate, two-step, CSP, funnel, staging gate, verification, worksheet round trip)
```

## Local development

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
php artisan migrate --seed            # ReferenceDataSeeder, PlatformSeeder, TopicFactsSeeder
npm run build                         # or: npm run dev
php artisan serve
vendor/bin/pint --test && php artisan test
```

## Operating the site (GitHub Actions)

| Workflow | When | Needs |
|---|---|---|
| CI | every push | – |
| Inspect Hostinger | manual | `HOSTINGER_SSH_KEY` secret; `HOSTINGER_SSH_HOST/PORT/USER` variables |
| Bootstrap Hostinger target | manual, once per target | the above + `SMUKN_DB_*` secrets, optional mail/Stripe secrets, `STAGING_BASIC_*` for staging |
| Deploy Hostinger | manual (production) / push to `staging` branch | the above + `STAGING_URL` variable |
| Backup Hostinger | daily db, weekly full, manual | the above + `BACKUP_PASSPHRASE` secret; restore guide in `ops/RESTORE.md` |
| Uptime check | every 30 min | `PRODUCTION_URL` variable (after first production deploy) |

Private keys live only in GitHub Actions Secrets. Never commit `.env`, keys or student data.

## Owner actions outstanding

Listed, with exact names, at the end of `ops/reports/owner-cycle-2026-10-03.md`. The one that needs only a browser: verify the pending facts with the worksheet in `data/verification/` (README there). Otherwise: (GitHub Actions settings for Hostinger access, backup passphrase, staging credentials, email authentication in `docs/ops/EMAIL-DELIVERABILITY.md`, service prices, Stripe and GA4 when wanted).
