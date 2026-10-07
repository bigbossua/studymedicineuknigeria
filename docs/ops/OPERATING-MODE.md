# Operating mode: launch, organic growth, maintenance (from 2026-10-07)

The build is finished. From now on, changes come from evidence: search data, a changed official source or a real
defect. Nothing is built or deployed to keep busy. When every check passes and no data asks for a change, the
right action is none.

## 1. Production baseline (release `2026-10-07T05-08-04`, commit `2977857`)

This release is the 2026-10-06 baseline plus the read-only Search Console feed, which is the last infrastructure
addition. Its re-verification on 2026-10-07 found the same results:
- Live Verification run 37575126964: all checks passed, Google-readiness audit 83/0, journey passed.
- Probe run 37575129985: passed.
- Search report run 37575132180: the feed runs and reports "not connected".
- Server state: 0 pending migrations, 0 failed jobs, GD present, payments open, webhook self-test passed, `gsc_connected: false` (awaiting the owner's key).

| Area | Baseline | Evidence |
|---|---|---|
| Sitemap | 36 URLs, each 200, indexable, self-canonical to Googlebot | `sitemap-probe.yml` run 37476042962 |
| Google readiness | 83 passed, 0 failed (85 internal URLs) | `live-verify.yml` run 37476513161 |
| Live verification | all checks passed: 65 legacy 301s, retired URLs 404, no service fee public, headers, portal/admin/documents behind sign-in, searcher's journey on phone and desktop | same run |
| Tests | 248 passed (253 with the Search Console feed) | CI |
| Security | HSTS, CSP with nonce, nosniff, frame and referrer policy, Secure and HttpOnly session, no public storage link, debug off | same run |
| Accessibility and mobile | axe 0 violations on every sitemap page at 390 and 1366 px; no overflow at 320/390/768/1366 including portal and admin (144 checks) | stage 64 |
| Stripe | live key, charges enabled, webhook self-test passed, unsigned POST refused (400), catalogue T1 £125 / T2 £695 / T3 £1,295 matches | same run |
| Server | PHP GD present; 0 pending migrations; 0 failed jobs; queue empty; scheduler heartbeat current; 0 open deletion requests | same run |
| Search Console | Domain property verified by DNS; sitemap read (*Success*). Indexed count **not established** until Google's own inspection says so | `SEARCH-CONSOLE-AND-GA4.md` |
| Schools | 6 published; 22 verified but without Nigerian demand evidence stay `noindex`; 27 others are not publishable | `docs/seo/UNIVERSITY-ASSESSMENT.md` |

Known false alarm: Hostinger's CDN sometimes shows one GitHub runner IP a "Checking your browser" page (403) and drops
its SSH. Every check on that run fails at once, and the H1 reads "Checking your browser". When the probe or a re-run
from another runner passes, it is not a site fault.

## 2. What watches the site (no action unless it fails)

| Check | When | Covers |
|---|---|---|
| `uptime-check.yml` | every 30 min | smoke tests; opens one issue while failing |
| `sitemap-probe.yml` | daily 05:41 | sitemap and robots as Googlebot; every URL 200, indexable, self-canonical |
| `live-verify.yml` | daily 06:23 | redirects, 404s, fee leak, canonicals, JSON-LD, staging/test/placeholder text, headers, private areas, journey, Google-readiness audit, server state, Stripe and webhook, cron and queue, GD, Search Console connection |
| `launch-checks.yml` | Mondays | DNS, mail records, certificates, Search Console TXT |
| `performance-check.yml` | Mondays | Lighthouse on landing pages |
| `facts-evidence.yml` | Mondays | evidence for verified facts |
| `search-report.yml` | Mondays 06:43 | Search Console summary (once connected) |
| `smukn:sources-check` (server) | nightly 03:40 | official pages behind verified facts. A fact whose value is gone becomes SOURCE_CHANGED: hidden and back in the review queue. Staff are emailed only when something changed |
| `smukn:gsc-sync` (server) | nightly 05:20 | Search Console performance and index status (read only) |
| `smukn:retention` (server) | Sundays 04:10 | privacy-notice retention rules |
| backups | daily 02:50, weekly full | encrypted, restore-tested |

## 3. The growth loop

**Search data → opportunity → evidence → decision → change → deploy → measure → record**

1. **Search data.** The weekly search report and Admin → Search, with Semrush only when real API units exist.
   Autocomplete alone is never evidence of demand (DECISION-ENGINE §8.3).
2. **Opportunity.** Only these signals start work:
   - a page Google shows on page one that searchers rarely click (title or description);
   - a query split across two of our pages (cannibalisation: one intent, one page);
   - a Nigerian query the site answers poorly or not at all;
   - a page gaining impressions that the content can serve better;
   - Nigerian searches naming an unpublished school;
   - a sitemap URL Google still has not indexed after 6 weeks.
3. **Evidence.** The query volume and source, and the official sources for any fact involved. Facts go through
   `reference_facts`, never Blade prose.
4. **Decision.** A register row in `data/seo/decision-register.csv` (via `ops/seo/build-register.py`) with the
   reason and the data behind it:
   - upgrade a page before adding a sibling;
   - a new page needs its own intent and evidence;
   - a school page needs the full §8 threshold.
5. **Change.** Pass the tests, the production-mode audit and the asset register, then ship one release for the whole
   change. Deploys are approved by the owner.
6. **Measure.** Compare the same query and page in the report 4 and 8 weeks later. Record the outcome in the
   register row.

Never: mass or AI-generated page sets, doorway or duplicate school pages, keyword stuffing, invented statistics or
demand, paid links, PPC, artificial traffic.

## 4. Source changes

When `smukn:sources-check` flags a fact:
1. read the official page;
2. verify the new value through the excerpt runner and the review JSON (`data/verification/README.md`);
3. apply it with `smukn:facts-import`, which records the date and source.

A value the source no longer supports stays hidden, never silently kept. Blocked pages (Cloudflare and similar) are
not retried in a loop. They stay controlled until someone checks them by hand. The owner hears about a source change
only when a published page is affected and the new value cannot be verified.

## 5. Application system

Left alone unless a check or a user shows a defect. A defect is fixed at once, with a test, then shipped. Prices
(T1 £125, T2 £695, T3 £1,295) do not change and stay invisible to anyone not approved for service selection. No real
payment happens without the owner (`docs/ops/LIVE-PAYMENT-REHEARSAL.md`).

## 6. Legal identity

Company name, company number, address, ICO and VAT numbers, accreditation, partnerships and agent status are never
invented. The fields stay empty until the owner supplies them as repository Variables and runs
`update-env-hostinger.yml`.

## 7. Report format

```
LIVE STATUS     release · tests · sitemap · Google readiness · security · Stripe · cron · application funnel
ORGANIC SEO     GSC data · new opportunities · pages requiring improvement · university demand evidence
SOURCE CHANGES  important changes · action taken
OWNER ACTIONS   only what needs the owner's account, money, legal information, approval or a decision
```

If nothing needs the owner: "No owner action required. System healthy; continuing organic monitoring."
