# Technical SEO audit — 2026-10-03 (local build, pre-deployment)

Scope: every URL in `sitemap.xml` (28), the gated pages, the directory and university pages, served by `php artisan serve` from the seeded database. Production-only items (TLS, HTTP/2, real TTFB, Search Console, Core Web Vitals field data) are marked **production** and belong to the post-deployment checklist. Method: `ops/qa/seo-crawl.cjs`, `ops/qa/axe-audit.cjs`, the link-graph and page-weight scripts described in `docs/seo/DECISION-ENGINE.md` §6, and direct header inspection.

| # | Check | Result | Evidence / fix |
|---|---|---|---|
| 1 | Crawlability: robots.txt | Pass | Allows all outside production; in production allows public paths, disallows `/portal/`, `/admin/`, `/login`, `/register`, `/password/`, `/*?*`; names the sitemap. Test `robots_disallows_everything_outside_production`. |
| 2 | XML sitemap | Pass | 28 URLs, only indexable routes, `lastmod` from route defaults, gated pages excluded automatically (`PublishGate`). Test `pending_pages_are_noindex_and_absent_from_the_sitemap`. |
| 3 | Canonical tags | Pass | Every page emits one self-referencing canonical built from the route; query strings do not change it (`/requirements?utm_source=x` → canonical `/requirements`). |
| 4 | Trailing slash, case, scheme, host | Pass | `/requirements/` → 301 `/requirements`; `/REQUIREMENTS` → 301 lowercase (test); https and non-www 301s in `public/.htaccess` (smoke test checks them on the server, **production**). |
| 5 | Indexation controls | Pass | `index, follow, max-image-preview:large` on public pages; `noindex, nofollow` on gated, filtered-directory, unpublished-university, auth and portal pages, plus `X-Robots-Tag` and `no-store` on private routes (test). |
| 6 | 404 handling | Pass | Unknown paths return a real 404 with a branded page linking to the hubs; no soft 404s. |
| 7 | Titles and descriptions | Pass | Sitemap-wide test: title 25–65 characters (brand suffix dropped automatically when it would overflow), description 100–165, unique per page. |
| 8 | Headings | Pass (fixed today) | Exactly one H1 per page (test). Fee guide had one H2 for 1,573 words and the FAQ none: table and range card headed, each FAQ question is an H2 inside its summary. |
| 9 | Structured data | Pass (extended today) | `Organization` + `BreadcrumbList` everywhere; `WebSite` on home; `FAQPage` only where questions are visible (core landing, FAQ hub, and the eight upgraded pages); `Service` on tiers; `CollegeOrUniversity` on university pages; `ItemList` on the unfiltered directory (added). JSON encoded with `JSON_HEX_TAG | JSON_HEX_AMP`. No ratings, reviews or claims we cannot source. |
| 10 | Open Graph and Twitter cards | Pass | Per-page branded 1200×630 card (`smukn:og`, 30 static + on-demand university cards), `og:image:alt`, `og:locale en_GB`, `summary_large_image`. |
| 11 | Internal linking | Pass (fixed today) | In-body graph measured from `<main>` only. Core landing page had 1 inbound link; now 9 (home, every hub, FAQ, GEM, NECO, UCAT, English, timeline, A-levels, foundation pages). Every informational page links to its hub, the core landing page and a conversion step; enforced by test for the core landing page. Remaining low-inbound pages are legal and trust pages reached from the footer. |
| 12 | Broken links | Pass | Crawl of every internal link from home and the sitemap: 0 broken. |
| 13 | Images | Pass (by absence) | No `<img>` without `alt` or dimensions; photo slots render nothing until the owner's Unsplash picks are built (WebP + JPEG at three widths, blurred placeholder, lazy except the home hero). Logo SVG has width/height and `fetchpriority="high"`. |
| 14 | Page weight (Core Web Vitals proxy) | Pass | Home 22 KB HTML (5 KB gzip); core landing 36 KB (7 KB); directory 147 KB (9 KB gzip; 53 school cards); UCAT 39 KB (7 KB). One CSS file (73 KB) and one JS bundle, fingerprinted and cached for a year; two preloaded Latin fonts (48 KB Inter, 122 KB Source Serif 4) with `font-display` handled by the CSS; zero inline scripts; no third-party script unless GA4 is configured and consent given. Field data: **production**. |
| 15 | Compression and caching | Pass (server rules) | Brotli/gzip for text, one-year immutable caching for fingerprinted assets, 30-day for images in `.htaccess`; HTML is `no-cache, private` because every response carries a session cookie (needed for forms). **Production**: confirm LiteSpeed honours the rules. |
| 16 | Security headers affecting SEO trust | Pass | Enforced nonce CSP, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS on https; `X-Powered-By` removed (middleware and `.htaccess`, test). |
| 17 | Mobile | Pass | Viewport meta; axe-core mobile and desktop passes with zero violations across public, portal and admin pages; fresh-account mobile journey clean. |
| 18 | hreflang / language | Not needed | Single English edition, `<html lang="en-GB">`; documented in architecture 18.4. |
| 19 | Pagination and faceted URLs | Pass | Directory filters are query strings that render `noindex` and canonicalise to the directory; no paginated lists exist yet. Whitelisted filter URLs stay in RESEARCH (register row D03) until demand evidence exists. |
| 20 | Dated content and rollover | Pass | Cycle-specific pages carry the entry year in H1 and, for the UCAS deadline page, the slug; every dated statement is a `ReferenceFact` with verification status and a review date; the rollover plan is architecture 18.5. |
| 21 | Duplicate or thin pages | Pass (upgraded today) | Eight requirements and admissions pages were 317–643 words and carried a single statement list; each now answers its intent in full (950–1,540 words) with visible FAQs. University pages (290–440 words) remain `noindex` until staff mark the record published with a Nigerian-applicant block (register rule 5). |
| 22 | Search Console, GA4, field CWV | **Production** | Owner steps in `docs/ops/SEARCH-CONSOLE-AND-GA4.md`; register statuses move PUBLISHED → INDEXING → MEASURING once data exists. |

## Open items

1. **Production checks** after the first deployment: TLS and HTTP/2, real TTFB from Lagos (the host is shared Hostinger), the https/non-www redirects, LiteSpeed caching of static assets, Search Console property and sitemap submission, field Core Web Vitals.
2. **Photographs**: the owner's Unsplash picks per `docs/design/IMAGERY-BRIEF.md`; every image is recorded with source, credit, page, filenames, dimensions, alt and optimisation.
3. **Semrush**: owner exports per `data/semrush/README.md`; `smukn:semrush-import` fills research 02 and the decision register; RESEARCH rows are then re-decided.
4. **Guest HTML caching**: every public response sets a session cookie, which prevents shared caching of HTML. Acceptable at launch (HTML is 5–9 KB gzipped); revisit only if TTFB from Nigeria proves slow in field data.
