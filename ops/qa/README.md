# Browser QA scripts (local or staging)

Playwright scripts used throughout the build to verify the site the way a reader or a crawler sees it. They
run against `http://127.0.0.1:8000` by default (edit `base` or pass `BASE_URL` where supported) with
`php artisan serve` running and the seeded database. Requirements: Node, a global Playwright
(`npm i -g playwright`) and a Chromium binary (`CHROME_PATH`, default is the cloud environment's path).

| Script | What it checks | Run |
|---|---|---|
| `seo-crawl.cjs` | crawls every internal URL from the home page and the sitemap: broken links, sitemap coverage, title/description lengths, one H1, alt text, JSON-LD, canonical | `node ops/qa/seo-crawl.cjs > crawl.json` |
| `axe-audit.cjs` | axe-core WCAG 2.x A/AA + best-practice on public (desktop and mobile), portal and admin pages; needs the admin TOTP secret as the first argument (local demo admin only) | `npm i -g axe-core; node ops/qa/axe-audit.cjs <secret>` |
| `csp-sweep.cjs` | loads public, portal and admin pages and reports Content Security Policy violations and console errors | `node ops/qa/csp-sweep.cjs <secret>` |
| `link-graph.py` | in-body internal link graph for every sitemap URL (links inside `<main>` only): outbound count per page and inbound count from other sitemap pages, to find weakly linked pages | `python3 ops/qa/link-graph.py [BASE_URL] [out.json]` |
| `snapshot.sh` | static snapshot of every public page with the real stylesheet and fonts (sitemap URLs, sample university records, gated pages, filter and sort views), for visual review when nothing is deployed; the 2026-10-03 snapshot is published as a private artifact for the owner | `ops/qa/snapshot.sh snapshot` |
| `public-journey.cjs` | the searcher's path by clicking visible links only: home (Google referrer) → Medicine → Requirements → Medical schools → Fees → Eligibility → Apply Online → Registration; fails on a missing link, an error, or a service fee on the page. Read-only (stops at the registration form), so it may run against production | `node ops/qa/public-journey.cjs <dir> [base-url] [desktop]` |
| `journey.cjs` | fresh-account student journey on a phone viewport: eligibility → register → verify (local shortcut) → start → autosave → passport upload → documents → payments, messages, submissions → data export; reports console/CSP problems | `node ops/qa/journey.cjs <output-dir>` |
| `staff-journey.cjs` | continues the latest application in two browsers: admin two-step sign-in → document review → submission proposal → ready for approval → student approves the exact package → package ready → submitted with a reference → student tracking; waives (never accepts) documents the student run did not upload; prints every flash message and any console/CSP/5xx problem | `node ops/qa/journey.cjs <dir> && node ops/qa/staff-journey.cjs <dir>` |
| `payment-journey.cjs` | paid-service journey (phone, or `desktop`): pricing page prices and "Most popular" → choose T2 → register → start with T2 preselected → confirmation page → cancel at checkout → retry → pay → return page waits → signed webhook → paid; prints the amount on the page and the amount sent to checkout and fails the comparison if they differ | see "Payments locally" below |
| `stripe-e2e.cjs` | real Stripe test-mode journey for T1, T2, T3 (run by the *Stripe test-mode journey* workflow, which forwards Stripe-signed webhooks with `stripe listen`): staff approval in the admin → fees shown → choice → Stripe's hosted Checkout with test cards (T1 also a declined card) → signed webhook → paid → each charge read back from Stripe | locally against the stand-in: `CHECKOUT_HOST_RE='127\.0\.0\.1:12111' node ops/qa/stripe-e2e.cjs /tmp/qa` |
| `fake-stripe.php` | local stand-in for Stripe Checkout (this environment cannot reach stripe.com): records each session's line items and serves Pay/Cancel buttons. The app uses it only with `APP_ENV=local` and `STRIPE_API_BASE` set | `php -S 127.0.0.1:12111 ops/qa/fake-stripe.php` |

Local demo accounts (seeded only in local databases): `student@example.test` / `Testpass12345`, `admin@example.test` / `Adminpass12345` (admin has two-step verification enrolled; the scripts compute the code with `php artisan tinker`).

Against staging, add the basic-auth credentials to the context (`httpCredentials`) and never run `journey.cjs` or `staff-journey.cjs` against production: they register accounts and change applications.

## Payments locally

```bash
php -S 127.0.0.1:12111 ops/qa/fake-stripe.php &
cd public && STRIPE_SECRET=sk_test_local STRIPE_WEBHOOK_SECRET=whsec_local_qa STRIPE_API_BASE=http://127.0.0.1:12111 \
  php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php &   # artisan serve drops these variables
cd .. && node ops/qa/payment-journey.cjs /tmp/qa && node ops/qa/payment-journey.cjs /tmp/qa desktop
```
On staging the real Stripe Checkout (test mode) replaces the stand-in; see `ops/STAGING-SETTINGS.md`.

