# Google readiness: checklist and owner actions

Status of https://studymedicineuknigeria.com before submission to Google Search Console. The checks are re-run by
`python3 ops/seo/google-audit.py`, by the *Live verification* workflow (which runs it against production) and by
`tests/Feature/GoogleReadinessTest.php` on every push. The machine-readable form is
`data/seo/google-launch-checklist.json`.

Statuses: **PASS** verified · **FAIL** broken · **OWNER ACTION** needs your Google or DNS account · **NOT YET AVAILABLE** depends on something not yet live.

| Area | Status | Evidence |
|---|---|---|
| robots.txt | PASS | 200 text/plain. Names `https://studymedicineuknigeria.com/sitemap.xml`. Disallows `/portal/`, `/admin`, `/password/`, `/email/`, `/two-factor/`, `/apply-online/start/`, `/webhooks/` and query URLs. `/login` and `/register` stay crawlable so Google can read their `noindex`. Nothing in the sitemap is blocked. |
| Sitemap | PASS | 28 URLs, all `https://studymedicineuknigeria.com/…`, no query strings, no trailing slashes, a `lastmod` on each, no private URL. School pages and the two fact-gated pages are excluded until their facts are verified (they answer `noindex`). |
| Canonicals | PASS | Exactly one self-referencing, absolute production canonical on each of the 28 pages. Filtered directory views are `noindex`. |
| Redirects | PASS | All 65 old-site URLs answer 301 in one hop to a 200 page, and the 7 retired URLs answer 404. `www` → apex 301; `/page/` → `/page` 301; upper-case paths are not duplicates. http → https 301 is done by the host and checked on the live site. |
| Indexability | PASS | 28 sitemap pages are `index, follow` with no `X-Robots-Tag`. Login, register, portal, admin, password, email-verification, two-factor and start-with-service URLs are `noindex` or redirect to sign-in. |
| Titles | PASS | 28 unique titles, each at most 65 characters with the brand suffix where it fits. Five were retargeted on 2026-10-04 to the intent each page serves (requirements, directory, fees, eligibility checker, about). |
| Descriptions | PASS | 28 unique descriptions of 100–165 characters. |
| H1 | PASS | Exactly one H1 on each of the 28 pages, matching the page's intent (e.g. "What do I need to study Medicine in the UK?"). |
| Headings | PASS | No skipped heading levels (checked on all 28 pages). |
| Structured data | PASS | Organization (`@id`, 512 px logo), WebSite (home), a WebPage per indexable page with `dateModified` = the page's last-reviewed date, BreadcrumbList from Home, ItemList (directory), Service (the three support levels, no prices), FAQPage only where every question is printed on the page. No ratings, reviews, offers, prices, partnerships or student numbers. |
| Breadcrumbs | PASS | Visible on every page below the home page; the JSON-LD trail matches the visible one. |
| Internal links | PASS | Every sitemap page is reachable from the home page and has at least 2 in-body links from other pages. Each guide page ends with a "Next step" card along Medicine → Requirements → Nigerian qualifications → Medical schools → Fees → How to apply → Eligibility → Apply Online. School pages link back to Requirements, WAEC, English, graduate entry, foundation, Fees, UCAT and How to apply. |
| Open Graph | PASS | `og:title`, `og:description`, `og:url`, `og:type`, `og:image` (1200×630 card per page, `php artisan smukn:og`) and `twitter:card` on all 28 pages; every card file exists. |
| Images | PASS | 57 images across the 28 pages, all with `width`/`height` (no layout shift). The hero globe and maps are SVG; phones get a 1-pixel placeholder instead of the globe. Photographs, when added, are built as WebP + JPEG at 480/960/1440 px with `srcset`, lazy-loaded below the fold, self-hosted (no third-party image host). |
| Photography | OWNER ACTION | Unsplash blocks its website to GitHub's servers; its official API answers and needs a free Access Key (secret `UNSPLASH_ACCESS_KEY`). No photograph is used until it is chosen, credited and given honest alt text. |
| Alt text | PASS | Every `<img>` has `alt`; informative images describe what they show, decorative ones are `alt=""` or `aria-hidden`. |
| Mobile | PASS | Every page reviewed at 390 px (layout, navigation, filters, stepper); axe runs at mobile width too. |
| Accessibility | PASS | axe-core WCAG 2.x A/AA + best practice: 0 violations on public, portal and admin pages, desktop and mobile. Maps are decorative (`aria-hidden`) with the same facts in text; the hero globe has descriptive alt text. |
| Performance | PASS | CSS 19 KB and JS 1.3 KB (gzip); HTML 10–19 KB gzip; self-hosted fonts with the two text faces preloaded; the monospace face is no longer loaded on public pages; phones receive a 1-pixel placeholder instead of the 38 KB globe; fingerprinted assets cached for a year; no third-party script without analytics consent. |
| HTTPS | PASS | Certificate valid for the apex and `www` (until 22 Nov 2026); HSTS `max-age=31536000; includeSubDomains`. |
| Security | PASS | CSP with nonces, `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, no `X-Powered-By`, foreign hosts refused. |
| Search Console | PASS | Property set up by the owner; `sitemap.xml` submitted 2026-10-04 and read by Google: **Success, 28 discovered pages**. `ops/sitemap-probe.sh` (daily, *Sitemap and robots probe*) fetches it as Googlebot and Googlebot Smartphone, plain and gzip: 200 `application/xml`, `<?xml` first, well-formed, sitemaps.org namespace, 28 URLs each 200/indexable/self-canonical; robots.txt names it. Indexing itself is reported only when Search Console shows it. |
| GA4 readiness | OWNER ACTION | Code ready and tested. Needs a GA4 property, its Measurement ID and a Measurement Protocol API secret (below). |
| Analytics events | PASS (code) / NOT YET AVAILABLE (data) | Events are defined and tested; no data flows until the GA4 ID is set. |
| WhatsApp | PASS | Floating "WhatsApp us" (+44 7842 292527) above Apply Online, pre-filled "Hello, I would like help studying Medicine in the UK from Nigeria."; tooltip; icon-only on phones; lifts above the consent banner; hidden while a form field has focus and near the footer; kept on the eligibility and Apply pages, absent on sign-in. Counted as `contact_click` with no personal data. |
| Application funnel | PASS | `ops/qa/public-journey.cjs` (phone and desktop) clicks visible in-page links Home → Medicine → Requirements → Medical schools → Fees → Eligibility → Apply Online → Registration with no error and no service fee shown; `ops/qa/journey.cjs` continues register → verify → start → autosave → upload → documents locally. Registration is open on production; payment is closed. |
| Privacy | PASS with OWNER ACTION | Privacy notice, consent banner before any analytics, no third-party script in the portal or admin, documents encrypted at rest, GA4 never receives personal data. Owner-only: ICO registration number. |
| Legal | OWNER ACTION | Terms, Application terms, Refund policy and Privacy are published and cross-linked, marked "under legal review". Owner-only: registered company name and address (`SITE_LEGAL_NAME`, `SITE_COMPANY_NUMBER`, `SITE_ADDRESS`), ICO number, and legal sign-off. |
| Admin | OWNER ACTION | Roles, two-step sign-in for staff and admin, audit log and least privilege are in place; the production database has no account yet. Runbook: `ops/NEXT-PHASE.md` step 1. |
| Trust / quality | PASS with OWNER ACTION | About, Our status, How we verify, Contact and the legal pages state who we are, our independence, what we do and do not do, how facts are verified and dated, that admissions decisions are the universities', and that no university agreement exists. Owner-only: the registered company name and address, and ICO registration, are still marked "to be published"; the terms and policies are marked "under legal review". |

## Search Console: what you do (about 5 minutes)

1. Go to https://search.google.com/search-console, then **Add property** → **Domain**, and enter `studymedicineuknigeria.com`.
2. Google shows a TXT record that begins `google-site-verification=`. The value is unique to your Google account; nobody else can know it in advance. Copy it.
3. In hPanel, go to **Domains** → `studymedicineuknigeria.com` → **DNS / Nameservers** → **Add record**:
   - **Type:** TXT
   - **Name:** `@`
   - **TXT value:** paste the whole `google-site-verification=…` string
   - **TTL:** leave the default

   Save. Do not change or delete any other record.
4. Back in Search Console, click **Verify**. If it says the record is not found yet, wait 10–30 minutes and try again.
5. **Sitemaps** → submit `https://studymedicineuknigeria.com/sitemap.xml`.
6. **URL Inspection**, in this order. For each URL, run *Test live URL* and then *Request indexing*:
   - `/`
   - `/study-medicine-in-the-uk/from-nigeria`
   - `/study-medicine-in-the-uk`
   - `/requirements`
   - `/requirements/waec`
   - `/medical-schools`
   - `/fees`
   - `/admissions`
   - `/admissions/ucat`
   - `/apply-online`
7. Tell Claude when it is verified. Claude then confirms the TXT record from public DNS and records the date.

**Alternative, if you cannot edit DNS:** add a **URL prefix** property for `https://studymedicineuknigeria.com/` and choose **HTML tag**. Then:

1. Copy only the `content="…"` value.
2. Put it in the GitHub repository variable `SITE_GOOGLE_VERIFICATION`.
3. Claude runs *Update server settings*, and the tag appears on the home page.
4. Click **Verify**.

The Domain property is better because it covers http, https and www together.

## GA4: what you do (about 10 minutes)

1. Go to https://analytics.google.com and select **Admin** → **Create** → **Property**:
   - **Name:** "Study Medicine UK Nigeria"
   - **Time zone:** your reporting time zone
   - **Currency:** GBP
2. Create a **Web** data stream for `https://studymedicineuknigeria.com`.
   - Leave Enhanced measurement on, except **Form interactions**; turn that off, since our own events cover forms.
3. Copy the **Measurement ID** (`G-…`). In GitHub → Settings → Secrets and variables → Actions → **Variables**, add `SITE_GA4_ID` = that ID. The ID is public, not a secret.
4. In the same data stream, go to **Measurement Protocol API secrets** → **Create**. Copy the secret into GitHub → Environments → **production** → secret `SITE_GA4_API_SECRET`.
5. **Admin** → **Data retention**: 14 months. Leave **Google signals** off; the site never enables it.
6. Tell Claude. Claude then:
   - runs *Update server settings* (production);
   - checks that the consent banner appears, that nothing loads before consent, that events arrive in **DebugView** after consent, and that CSP stays clean.
7. Once events have arrived, mark these as **Key events**: `lead_created` (eligibility check completed), `whatsapp_click`, `account_created`, `application_started`, `student_approved`, `payment_completed`.

## GA4 events

| Event | Sent | Parameters (nothing else ever travels) |
|---|---|---|
| `page_view` | Browser, automatic, after consent | page path and title. The address is cleaned first: only `utm_*` tags survive from the query string, and reset/verify tokens are masked. Never sent from sign-in-security pages (password, email verification, two-step). |
| `apply_click` | Browser | `location` (header, footer, floating, cta_band, content), `page` |
| `eligibility_started` | Browser | `page` |
| `lead_created` (eligibility check completed) | Browser, on the next page | `qualification`, `intake_year`, `tier` (the suggested level) |
| `account_created` | Browser, on the next page | none |
| `course_viewed` (school page) | Browser | `school` (slug) |
| `apply_viewed` | Browser | none |
| `whatsapp_click` | Browser | `location` (floating, header, footer, content), `page` |
| `contact_click` | Browser | `method` (email), `location`, `page` |
| `official_source_click` | Browser | `domain` of the official page, `page` |
| `directory_filter` | Browser | `filters` (which filters were used, never values typed in search), `searched` (yes/no) |
| `application_started` | Server (Measurement Protocol) | `tier` if chosen, `intake_year` |
| `step_completed` | Server | `step` (form-section key: personal, study, secondary, post_secondary, english, tests, experience, referees, declarations), `tier`, `intake_year` |
| `document_uploaded` | Server | `tier`, `intake_year`. Never the document, its name or type. |
| `service_chosen` | Server | `tier` (T1, T2, T3) |
| `student_approved` (application completed by the student) | Server | `tier`, `intake_year` |
| `submitted` | Server | `tier`, `intake_year` |
| `payment_started`, `payment_completed` | Server | `tier`. These start once Stripe is configured. |

Every event is also stored first-party in `funnel_events` (Admin → Funnel). `ops/qa/ga4-events.cjs` replays the browser events against a server started with a test ID and fails on any parameter outside this table; `tests/Feature/FunnelTest.php` covers the server events and the parameter allow-list.

**Privacy rules:**
- GA4 never receives names, email addresses, application numbers, document names or contents, or free text.
- No third-party script ever loads in the portal or admin.
- Server-sent events go only for visitors who accepted analytics, keyed by GA4's own anonymous client id.

## Indexing strategy

- **Indexed now:** the 28 sitemap pages.
- **Waiting for verification:** the 53 school pages, total cost, and working in the UK. They are linked and crawlable but answer `noindex` until their facts are verified (fact-verification policy). Each joins the sitemap automatically when it passes its publish gate.
- **Never indexed:** sign-in, registration, portal, admin, password, email-verification, two-factor, start-with-service and webhook URLs, and directory filter views.
