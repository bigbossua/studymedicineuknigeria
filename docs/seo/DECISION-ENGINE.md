# SEO decision engine

Status: OPERATING DOCUMENT (owner directive 2026-10-03, "Google organic growth + SEO + long-tail + internal linking"). The machine-readable register is `data/seo/decision-register.csv`; this file explains how it is used. `tests/Feature/SeoDecisionRegisterTest.php` enforces the rules marked **enforced**.

## 1. What the register is

One row per **query family** (a cluster of searches with one intent), not per keyword and not per page. A row records the evidence we have for that intent, the page that should answer it (one comprehensive page per intent), the pages that support it, and a decision with a reason. Pages are created, upgraded or rejected from this register, never from a hunch or a word count.

Columns:

| Column | Meaning |
|---|---|
| `id`, `cluster` | Cluster letter (section 2) and sequence |
| `query_family`, `example_queries` | The intent and the phrasings observed (forums, competitor pages, university country pages) |
| `intent` | informational / commercial / transactional / navigational / lead capture / tool; "(question)" marks long-tail questions |
| `country_db` | Semrush database to look the family up in (`ng` first, `uk` for diaspora and parents) |
| `nigerian_relevance` | HIGH = a Nigerian applicant's own situation (WAEC, NECO, UCAT in Lagos, naira); MEDIUM = international-applicant question with a Nigerian layer; LOW = generic or off-mission |
| `volume`, `keyword_difficulty` | Semrush figures, **only** when recorded through `php artisan smukn:semrush-import` from an owner export. Until then the cell reads `DATA UNAVAILABLE (…)` and no page decision depends on it |
| `serp_features_observed` | What the 2026-10-03 SERP review saw (research 02); PAA could not be observed |
| `competing_urls` | Pages that currently answer the intent (research 02, 05) |
| `current_url` | Our page (or fragment) that answers it today; `—` if none |
| `recommended_page` | The page that *should* answer it, or "FAQ answer only" / "none" |
| `supporting_pages` | Pages that must link to the recommended page (internal-link role) |
| `relevance_score` | 0–5 editorial priority: Nigerian relevance × funnel value × SERP weakness |
| `sources` | Research documents and sections the row rests on |
| `status`, `status_reason` | Section 3. **A reason is mandatory** (enforced) |
| `decided_on`, `review_due` | Decision date; the register is re-read on the review date or when Semrush/GSC data arrives |

### Derived columns (added 2026-10-03)

`subject` (medicine, a taxonomy slug for cluster V, or `site`), `indexation_decision` (index / index per record / anchor / noindex filter / noindex until verified / none), `internal_link_role` (entry, primary landing, hub, spoke, FAQ anchor, footer, none yet) and `conversion_role` (conversion, search-to-action, trust) are computed by the builder from the status and URL of each row, so they cannot drift from the decision itself. The search-to-action role is enforced by `test_every_live_informational_page_links_to_the_eligibility_check_or_apply_online`.

## 2. Clusters

| Cluster | Name | Scope |
|---|---|---|
| A | Commercial | our service: Apply Online, services and pricing, eligibility, agent status |
| B | Medicine | study medicine in the UK generally; adjacent-course scope decisions (dentistry, pharmacy, nursing, public health, physiotherapy, radiography); study-abroad alternatives |
| C | Nigerian core | "from Nigeria" / "for Nigerian students" modifiers of the core intent |
| D | UK medical schools | directory, per-school pages, filters, compare, rankings |
| E | Requirements | general entry requirements, A-levels, English, age/fee status |
| F | WAEC | WASSCE questions, grade-specific questions |
| G | NECO | NECO questions; JAMB/UTME |
| H | UCAT | logistics in Nigeria, scores, preparation |
| I | UCAS and process | deadlines, how to apply, personal statement, admissions hub |
| J | International students | places, acceptance rates, scholarships |
| K | Fees and costs | tuition, total cost, living costs, visa and IHS |
| L | Application (ours) | portal, documents |
| M | Eligibility | route finder, non-science backgrounds |
| N | Interviews | MMI, online interviews for international applicants |
| O | Foundation | foundation and gateway routes, pathway providers |
| P | Graduate entry | GEM with a Nigerian degree, GAMSAT, feeder degrees |
| Q | GMC and MDCN | registration after graduation, MDCN recognition, PLAB (out of scope) |
| R | Working in the UK | during and after the degree |
| S | Long-tail questions | the FAQ hub and PAA recovery |
| V | Healthcare course universe | one row per non-medicine subject plus one overview hub, generated from `data/healthcare/subjects.json` (see §7) |
| T | Trust and navigation | home, about, contact, legal pages (no keyword target; listed because every sitemap URL must have a row) |

## 3. Statuses and transitions

```
RESEARCH → VALIDATED → BUILD → DRAFT → REVIEW → PUBLISHED → INDEXING → MEASURING → UPDATE → (PUBLISHED …)
     └──────────────┴──────────────────────────────────────────────────────────────┴→ REJECTED (with reason)
```

| Status | Meaning | Exit condition |
|---|---|---|
| RESEARCH | Intent observed; evidence insufficient to decide (no volume data, unread sources) | Semrush/GSC data recorded, or sources read and facts recorded |
| VALIDATED | Evidence supports a page; not scheduled | Capacity and dependency (data density, verified facts) met → BUILD |
| BUILD | Approved and being built | Page renders locally → DRAFT |
| DRAFT | Built, `noindex` and out of the sitemap (fact-gated by `PublishGate`, or editorial) | Topic facts VERIFIED in the admin queue (automatic un-gating) → PUBLISHED |
| REVIEW | Built, awaiting editorial or legal review before un-gating | Review recorded → PUBLISHED |
| PUBLISHED | Indexable, in the sitemap, linked from its supporting pages | Site deployed and URL submitted via the sitemap → INDEXING |
| INDEXING | Live in production; Search Console shows it discovered or indexed | Impressions appear in GSC → MEASURING |
| MEASURING | Live with GSC data; queries, impressions, CTR and position recorded on review | Underperformance or new evidence → UPDATE; otherwise stays |
| UPDATE | Live page being upgraded (thin, intent drift, new facts) | Upgrade shipped → PUBLISHED/MEASURING |
| REJECTED | Will not be built, or was removed; reason recorded | Only a new row with new evidence reopens it |

**Enforced by test:**
- every status is from this vocabulary and every row has a non-empty `status_reason`;
- every URL in the sitemap has a row in a live status (PUBLISHED, INDEXING, MEASURING or UPDATE);
- every live row with a concrete `current_url` returns 200 and is indexable; every DRAFT row with a concrete URL returns 200 and is `noindex`;
- every `/faq#qNN` fragment referenced in the register exists on the FAQ page.

Pre-launch note: nothing is INDEXING or MEASURING until the first production deployment. The deployment and Search Console steps are owner actions recorded in `ops/reports/owner-cycle-2026-10-03.md`.

## 4. Rules of the engine

1. **One comprehensive page per intent.** Long-tail phrasings of the same intent are sections or FAQ answers on that page, never new URLs. A new URL needs its own row with distinct intent, Nigerian relevance and SERP evidence.
2. **Upgrade before multiplying.** A live page below the intent's depth gets status UPDATE before any sibling page is approved (2026-10-03: graduate-entry and NECO).
3. **Evidence hierarchy.** Official source > Semrush/GSC data > SERP observation > forum observation > inference. A decision may rest on inference only when it is cheap to reverse (FAQ answer) or when the page is useful at any volume (core routes).
4. **No fabricated metrics.** Semrush cells stay `DATA UNAVAILABLE` until the owner's export is imported. Volume never justifies a page the evidence hierarchy rejects.
5. **No thin university pages.** A university page is indexable only with a Nigerian-applicant block carrying published statements and at least three verified fields; home-only schools are directory rows.
6. **Search-to-action chain.** Every PUBLISHED informational page links to (a) its hub, (b) the core landing page where relevant, (c) the eligibility check or Apply Online as the next step. The register's `supporting_pages` column is the link plan; the audit script in section 6 measures it.
7. **Rejections are permanent until new evidence.** The reason is the record; see rows for rankings, scholarships listicles, PLAB/housemanship, North Cyprus, per-city and blog.

## 5. Owner inputs that change decisions

| Input | Where it goes | Rows it unblocks |
|---|---|---|
| Semrush Keyword Magic / Overview exports (`ng`, `uk`) for the 27 themes in `data/semrush/lookup-sheet.csv` | `data/semrush/lookups-YYYY-MM-DD.csv` → `php artisan smukn:semrush-import` | all RESEARCH rows; `volume`/`keyword_difficulty` columns |
| Semrush question-filter export for the 18 seeds in research 02 §E | same file | S02 (PAA recovery) |
| Search Console access (after deployment) | `docs/ops/SEARCH-CONSOLE-AND-GA4.md` | PUBLISHED → INDEXING → MEASURING |
| Verification of topic facts in the admin queue | automatic un-gating | K02, K04, Q02, R01 (DRAFT) |

## 6. Internal-link audit (2026-10-03, local build)

Method: fetch every sitemap URL, count `<a href>` links inside `<main>` (header, footer and floating CTA excluded), tally inbound links per page from other sitemap pages.

| Page | In-body inbound links before | After |
|---|---|---|
| `/study-medicine-in-the-uk/from-nigeria` (core landing) | 1 | 9 (home, every hub, FAQ, GEM and NECO pages; enforced by `test_core_landing_page_is_linked_from_the_home_page_every_hub_and_the_faq`) |
| `/study-medicine-in-the-uk` | 2 | 3 |
| `/faq` | 2 | 4 |
| `/requirements/neco` | 3 | 5 |
| `/admissions`, `/admissions/how-to-apply` | 4 | 4, 5 |
| Highest: `/medical-schools` 15, `/how-we-verify` 15, `/apply-online/eligibility` 13 | | |

Decision: the core landing page is the primary asset of cluster C and must be reachable in one click from the home page body, every hub (Medicine, Requirements, Fees, Admissions, Medical Schools) and the FAQ. Implemented 2026-10-03 (see IMPLEMENTATION-LOG stage 26).

## 7. Subject layer (healthcare course universe, 2026-10-03)

The master taxonomy in `data/healthcare/subjects.json` (seeded into the `professions` table, Admin → Subjects) is the source of truth for every subject other than Medicine. `ops/seo/build-register.py` reads it and emits cluster V, so a subject's taxonomy status and its register status can never disagree.

- A subject uses the same status vocabulary as the register (§3). Only PUBLISHED, INDEXING, MEASURING and UPDATE allow a public page (`Profession::mayHavePublicPage()`); RESEARCH, VALIDATED, BUILD, DRAFT, REVIEW and REJECTED subjects exist only in the database and the admin screen.
- A subject moves RESEARCH → VALIDATED on evidence of Nigerian demand or a direct owner directive, VALIDATED → BUILD only when its regulator, international-availability and entry facts are recorded as `reference_facts` with sources, and BUILD → DRAFT → PUBLISHED through the normal page process (asset register row, decision-register row with a URL, tests, noindex until verified).
- The taxonomy's sample-university values carry research labels (FACT / LEAD / NOT FOUND / VERIFY-ON-PAGE) and are never rendered on a public page; the production-mode sweep test and the admin-only route guarantee this.
- "Never infer" rules: availability, fees, English and A-level requirements are per university, never per profession; a profession's requirements are never derived from Medicine's or from another university's.
- `docs/seo/KNOWLEDGE-GRAPH.md` (generated by `ops/seo/build-knowledge-graph.py`) maps the owner's topical-authority chain per subject: built nodes for Medicine, taxonomy status and unlock condition for every other subject.
- One overview hub (V01) is the only planned page for the 20 RESEARCH subjects; a subject gets its own page only after it reaches BUILD on its own evidence. Medicine stays the flagship and keeps clusters B–S.
