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
| Headings | PASS | One H1 per page and no skipped heading levels (checked on all 28 pages). |
| Structured data | PASS | Organization (`@id`, 512 px logo), WebSite (home), a WebPage per indexable page with `dateModified` = the page's last-reviewed date, BreadcrumbList from Home, ItemList (directory), Service (the three support levels, no prices), FAQPage only where every question is printed on the page. No ratings, reviews, offers, prices, partnerships or student numbers. |
| Breadcrumbs | PASS | Visible on every page below the home page; the JSON-LD trail matches the visible one. |
| Internal links | PASS | Every sitemap page is reachable from the home page and has at least 2 in-body links from other pages. Each guide page ends with a "Next step" card along Medicine → Requirements → Nigerian qualifications → Medical schools → Fees → How to apply → Eligibility → Apply Online. School pages link back to Requirements, WAEC, English, graduate entry, foundation, Fees, UCAT and How to apply. |
| Mobile | PASS | Every page reviewed at 390 px (layout, navigation, filters, stepper); axe runs at mobile width too. |
| Accessibility | PASS | axe-core WCAG 2.x A/AA + best practice: 0 violations on public, portal and admin pages, desktop and mobile. Maps are decorative (`aria-hidden`) with the same facts in text; the hero globe has descriptive alt text. |
| Performance | PASS | CSS 19 KB and JS 1.3 KB (gzip); HTML 10–19 KB gzip; self-hosted fonts with the two text faces preloaded; the monospace face is no longer loaded on public pages; phones receive a 1-pixel placeholder instead of the 38 KB globe; fingerprinted assets cached for a year; no third-party script without analytics consent. |
| HTTPS | PASS | Certificate valid for the apex and `www` (until 22 Nov 2026); HSTS `max-age=31536000; includeSubDomains`. |
| Security | PASS | CSP with nonces, `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, no `X-Powered-By`, foreign hosts refused. |
| Search Console readiness | OWNER ACTION | Everything technical is ready. Verification needs your Google account and a DNS record (below). |
| GA4 readiness | OWNER ACTION | Code ready and tested. Needs a GA4 property, its Measurement ID and a Measurement Protocol API secret (below). |
| Analytics events | PASS (code) / NOT YET AVAILABLE (data) | Events are defined and tested; no data flows until the GA4 ID is set. |
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
7. Once events have arrived, mark these as **Key events**: `lead_created` (eligibility check completed), `account_created`, `application_started`, `student_approved`, `payment_completed`.

## GA4 events

| Event | Sent | Parameters (nothing else ever travels) |
|---|---|---|
| `page_view` | Browser, automatic, after consent | page location and title |
| `apply_click` | Browser | `location` (header, footer, floating, cta_band, content), `page` |
| `eligibility_started` | Browser | `page` |
| `lead_created` (eligibility check completed) | Browser, on the next page | `qualification`, `intake_year`, `tier` (the suggested level) |
| `account_created` | Browser, on the next page | none |
| `course_viewed` (school page) | Browser | `school` (slug) |
| `apply_viewed` | Browser | none |
| `contact_click` | Browser | `method` (email, whatsapp), `page` |
| `official_source_click` | Browser | `domain` of the official page, `page` |
| `directory_filter` | Browser | `filters` (which filters were used, never values typed in search), `searched` (yes/no) |
| `application_started` | Server (Measurement Protocol) | `tier` if chosen, `intake_year` |
| `step_completed` | Server | `tier`, `intake_year` |
| `document_uploaded` | Server | `tier`, `intake_year`. Never the document, its name or type. |
| `service_chosen` | Server | `tier` (T1, T2, T3) |
| `student_approved` (application completed by the student) | Server | `tier`, `intake_year` |
| `submitted` | Server | `tier`, `intake_year` |
| `payment_started`, `payment_completed` | Server | `tier`. These start once Stripe is configured. |

Every event is also stored first-party in `funnel_events` (Admin → Funnel).

**Privacy rules:**
- GA4 never receives names, email addresses, application numbers, document names or contents, or free text.
- No third-party script ever loads in the portal or admin.
- Server-sent events go only for visitors who accepted analytics, keyed by GA4's own anonymous client id.

## Indexing strategy

- **Indexed now:** the 28 sitemap pages.
- **Waiting for verification:** the 53 school pages, total cost, and working in the UK. They are linked and crawlable but answer `noindex` until their facts are verified (fact-verification policy). Each joins the sitemap automatically when it passes its publish gate.
- **Never indexed:** sign-in, registration, portal, admin, password, email-verification, two-factor, start-with-service and webhook URLs, and directory filter views.
