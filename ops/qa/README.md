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
| `journey.cjs` | fresh-account student journey on a phone viewport: eligibility → register → verify (local shortcut) → start → autosave → passport upload → documents → payments, messages, submissions → data export; reports console/CSP problems | `node ops/qa/journey.cjs <output-dir>` |

Local demo accounts (seeded only in local databases): `student@example.test` / `Testpass12345`, `admin@example.test` / `Adminpass12345` (admin has two-step verification enrolled; the scripts compute the code with `php artisan tinker`).

Against staging, add the basic-auth credentials to the context (`httpCredentials`) and never run `journey.cjs` against production: it registers accounts.
