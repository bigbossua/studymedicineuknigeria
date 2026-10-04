# StudyMedicineUKNigeria.com — working notes for Claude sessions

Laravel 13 (PHP 8.3) monolith, Blade + Tailwind v4 + Vite. Read `README.md`, then `docs/BASELINE-ASSESSMENT.md` and the latest stages in `docs/IMPLEMENTATION-LOG.md`. The owner's standing directive is in `ops/reports/owner-cycle-2026-10-03.md` (what is done, what only the owner can do).

## Non-negotiable rules
- Never fabricate fees, dates, requirements, rankings, partnerships, testimonials, student numbers or Semrush figures. Facts live in `reference_facts` with a verification status; unverified values are hidden in production. New dated facts go through `database/seeders/TopicFactsSeeder.php` or the admin verification queue, never into Blade prose.
- Never submit a student application without the recorded student approval; never weaken `EnsureTwoFactor`, `EnsureStaff`, `StagingGate`, the CSP nonce, or the document pipeline (`App\Services\Documents\DocumentStore`).
- Never commit `.env`, keys, or anything from `storage/app/private`. Secrets live only in GitHub Actions Secrets.
- No deployment to production except by explicit dispatch with the owner's approval on the `production` environment: the first launch through `launch-production.yml` (rehearsal → restore-tested backup → approval → cutover with automatic restore), later releases through `deploy-hostinger.yml`. There is no staging site (owner decision 2026-10-04); `ops/production-rehearsal.sh` serves the commit in production mode on the runner instead, and must pass first. Read-only inspection first.
- Every public page must have a row in `docs/decision/page-asset-register.md`; do not add pages without evidence.
- Healthcare subjects other than Medicine live in `data/healthcare/subjects.json` → `professions` (Admin → Subjects); only a PUBLISHED/INDEXING/MEASURING/UPDATE subject may have a public page, and its facts still go through `reference_facts`. Regenerate the register, knowledge graph and subject scorecard with `python3 ops/seo/build-register.py && python3 ops/seo/build-knowledge-graph.py && python3 ops/seo/build-subject-scorecard.py` after editing the taxonomy; never edit the CSV or `docs/seo/KNOWLEDGE-GRAPH.md` by hand.
- Every indexable URL must have a live row in `data/seo/decision-register.csv` (one page per intent; statuses and reasons in `docs/seo/DECISION-ENGINE.md`); a test enforces it. Upgrade a thin page before adding a sibling.

## Commands
```bash
composer install && npm ci && cp .env.example .env && php artisan key:generate && php artisan migrate --seed
npm run build && php artisan serve --host=127.0.0.1 --port=8000     # stop with: fuser -k 8000/tcp
vendor/bin/pint --dirty && php -d memory_limit=1G vendor/bin/phpunit   # 209 tests; run before every commit
php artisan smukn:og          # regenerate Open Graph cards after changing a public title
php artisan smukn:images      # build photo derivatives from brand/photos
php artisan smukn:facts-export data/verification/worksheet-YYYY-MM-DD.csv --sources=data/verification/sources-YYYY-MM-DD.csv && php artisan smukn:facts-import data/verification/decisions-YYYY-MM-DD.csv   # verification round trip (data/verification/README.md)
php artisan smukn:reference-sync   # repository reference data into this database (deploy runs it; never touches reviewed facts)
php artisan smukn:sources-check --dry-run   # official-page change watcher (nightly on the server)
node ops/qa/seo-crawl.cjs     # see ops/qa/README.md for the browser QA scripts (journey.cjs, staff-journey.cjs, public-journey.cjs)
npm i --no-save world-atlas@2 d3-geo@3 topojson-client@3 lucide-static && node ops/design/build-maps.mjs && node ops/design/build-icons.mjs   # map illustrations and icon set (public-domain / ISC data; no stock photos)
python3 ops/seo/google-audit.py      # Google-readiness audit of the live site (--connect http://127.0.0.1:8090 for a production-mode server; docs/seo/GOOGLE-READINESS.md)
python3 ops/seo/page-audit.py # regenerate docs/seo/PAGE-AUDIT.md (every indexable page: query, intent, links, routes to eligibility/apply)
```
Local demo accounts: `student@example.test` / `Testpass12345`, `admin@example.test` / `Adminpass12345` (admin has TOTP enrolled; compute codes with `App\Support\Totp::code($secret)` in tinker).

## Conventions
- Titles ≤ 65 characters with the brand suffix, descriptions 100–165 (tested across the sitemap). British English, no superlatives, "published requirement", "official source", "last verified".
- Fact-driven pages declare a `gate` in their sitemap route default (`PublishGate`) and stay `noindex` until verified.
- Funnel events through `App\Support\Funnel`; never store PII there.
- Service fees are never public (owner decision 2026-10-04): no fee in any public view, JSON-LD or JSON; only staff and a student whose application is approved for service selection (`User::canSeeServicePrices`) see them. `tests/Feature/PricingVisibilityTest.php` crawls the sitemap for leaks. Stripe charges a catalogue Price chosen by the server (`App\Services\Payments\StripeCatalog`); never create Payment Links. Until a usable Stripe key exists (and `SITE_BANK_TRANSFER` is off) no payment action is offered or accepted (`StripeService::paymentsOpen`); students are told once when it opens (`smukn:payments-open-notify`).
- The previous site's URLs (its sitemap of 2026-10-04) are answered by `data/seo/legacy-redirects.csv` (seeded by reference-sync; Admin → Redirects wins) or listed in `data/seo/legacy-gone.txt` (404); `LegacyRedirectsTest` keeps every one working. Student registration is closed on production until *Send test email* opens it.
- Commit messages: imperative, no model names; CI must stay green on every push to `claude/new-session-p6gdm6`.
- Pint formats imports; keep `routes/web.php` imports at the top (a missing import there once broke email verification: see stage 25).
