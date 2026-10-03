# 18. Technical SEO architecture

Status: ARCHITECTURE. Derived from brief sections 40, 97, 100, 101. Implemented in the Laravel monolith (20).

## 18.1 URL design (clean, stable, lowercase, hyphenated, no dates)

```
/                                              Home
/study-medicine-in-the-uk/                     Pillar
/study-medicine-in-the-uk/from-nigeria/        Core Nigerian landing (primary query cluster)
/requirements/                                 Hub: what you need
/requirements/waec/                            Nigerian qualification pages
/requirements/neco/
/requirements/a-levels/
/requirements/nigerian-degree-graduate-entry/
/requirements/english-language/
/admissions/ucat/                              Admissions tests & process
/admissions/interviews/
/admissions/ucas-deadlines-2027/               (year in slug ONLY for cycle-specific pages; redirect on rollover)
/fees/                                         Fee guide
/fees/cost-of-studying-medicine-in-the-uk/
/medical-schools/                              Directory (filters as query string: ?nation=england&test=ucat&international=yes)
/medical-schools/compare/?c=slug1,slug2,slug3  Compare (noindex)
/medical-schools/{university-slug}/            University record
/medical-schools/{university-slug}/{course-slug}/   Course record (e.g. /medical-schools/greater-manchester/mbchb/)
/working-in-the-uk/                            Validated section (if built)
/faq/                                          FAQ hub (each FAQ also appears in context)
/apply-online/                                 Commercial gateway
/apply-online/services/                        Tiers
/apply-online/eligibility/                     Eligibility check (lead capture)
/about/ /contact/ /privacy/ /terms/ /application-terms/ /refund-policy/
/login /register /portal/*                     private, noindex
```

Rules: **no trailing slash** is canonical (decided 2026-10-03: Laravel strips trailing slashes natively, so `/x/` 301s to `/x`; the URL examples above are shown with slashes only for readability); www→non-www (or vice versa, pick one) 301; HTTP→HTTPS 301; lowercase enforced; query-string filtered directory pages are `canonical` to the unfiltered directory except a small whitelist of valuable filter combinations that get static friendly URLs (e.g. `/medical-schools/accepting-international-students/`, `/medical-schools/no-ucat/` — only if the asset register approves).

## 18.2 Indexation controls

- `robots.txt`: allow public; `Disallow: /portal/ /login /register /admin/ /medical-schools/compare/`; sitemap reference.
- Meta robots `noindex,follow` on: compare, filtered directory pages not whitelisted, search results, pagination beyond page 1 of FAQ lists (or use rel canonical to page 1 only if content is identical), thank-you pages.
- `X-Robots-Tag: noindex` header on all auth routes and documents.
- Sitemaps: `sitemap.xml` index → `sitemap-pages.xml`, `sitemap-medical-schools.xml`, `sitemap-faq.xml`; `lastmod` from `last_reviewed_at`/`verified_at` (real dates only).

## 18.3 Structured data (JSON-LD, per template)

| Template | Schema.org types |
|---|---|
| All pages | `Organization` (site-wide, with `sameAs` only for real profiles), `BreadcrumbList`, `WebSite` with `SearchAction` (directory search) |
| Guide / requirements / fees | `Article` (with `dateModified` = last reviewed) + `FAQPage` only where the FAQs are visible on the page |
| University record | `CollegeOrUniversity` (name, url = official site, address city) — we describe, we do not claim affiliation |
| Course record | `Course` + `CourseInstance` (courseMode onsite, startDate intake) + `Offer` **only** for the university's published fee with `priceCurrency=GBP` and `validThrough` = fee year end; omit if fee `NOT_PUBLISHED` |
| Service tiers | `Service` + `Offer` with our prices once set |
| FAQ hub | `FAQPage` |

No `AggregateRating`, no `Review` (no fake social proof — brief 114). No `EducationalOccupationalCredential` claims we cannot source.

## 18.4 Metadata and Open Graph

Per page: unique `<title>` ≤ 60 chars pattern `{Page} | StudyMedicineUKNigeria`; meta description ≤ 155; OG title/description/image (generated image per template with page title, no photos of universities), `og:type=article|website`, Twitter card summary_large_image. `hreflang` not needed (single English edition); `<html lang="en-GB">`.

## 18.5 Freshness signals (brief 100–101)

- Visible "Last reviewed: {Month YYYY}" on every guide; "Last verified: {date} · Official source ↗" on every fee, deadline, test, eligibility and qualification fact (rendered from `verified_at`).
- Cycle-specific pages carry "For 2027 entry" in H1 and body; a `cycle_rollover` job each August creates the next-cycle page and 301s the old slug or keeps it as an archived page with a banner — decided per page.
- `dateModified` in JSON-LD and sitemap `lastmod` only change when content actually changes (hash comparison), never on deploy.

## 18.6 Performance (Core Web Vitals targets: LCP < 2.0 s on 4G, CLS < 0.05, INP < 200 ms)

Server-rendered HTML, full-page cache for public pages (invalidated on publish/verify), HTTP/2, Brotli, preconnect to nothing external except Stripe on checkout pages, self-hosted fonts, critical CSS inline for above-the-fold, responsive images with width/height, no third-party scripts except GA4 loaded after consent and after interaction/idle.

## 18.7 Internal linking engine (brief 95–96)

- **Entity links** (automatic, reasoned): course → university, fee page, test page, qualification pages matching `course_requirements`, Apply Online. University → its courses, nation directory filter. Qualification page → every course with a statement for that qualification (from `university_qualification_statements`), the requirements hub, fees, Apply Online.
- **Curated links** (`page_links` with `reason`): editorial contextual links inside body copy; the editor must give a reason; reports list pages with < 3 inbound links (orphans) and > 60 outbound.
- **Related content** (`related_content`): hand-picked "You may also need", 4–6 items, no tag-based automation.
- **FAQ interlinking**: each FAQ answer stores links to the pages it depends on; FAQs are rendered in context on those pages and in the hub.

## 18.8 Breadcrumbs

Rendered from the URL hierarchy with page titles: `Home → Medical Schools → University of X → MBChB`; `Home → Requirements → WAEC`. Visible and in `BreadcrumbList`.

## 18.9 Error and redirect handling

Custom 404 with search + top paths; 410 for deliberately removed pages; `redirects` table checked before routing; redirect map imported from the existing site audit once Search Console access exists (brief 8) — **blocked this session, see DATA-AVAILABILITY**.

## 18.10 Measurement (brief 9, 55)

GA4 with consent mode (implemented stage 10: optional `SITE_GA4_ID`, consent banner, public pages only); events in 12.9; server-side mirror table (`funnel_events`, Admin → Funnel); Search Console property verified via DNS; monthly export of GSC queries filtered `country=NGA` into `asset_register.semrush_json`/notes for the research loop (brief 54, 99).
