# Page asset register

Status: DECISION (brief sections 2, 57). Every candidate page is listed with the evidence we have and a decision. **Semrush, Search Console and Trends columns are DATA UNAVAILABLE for this pass** (see `docs/DATA-AVAILABILITY.md`); demand evidence is SERP observation (US-indexed engine, 2026-10-03), forum evidence and official-source gaps, all labelled INFERENCE. Decisions marked † must be re-confirmed when Semrush `ng` data is available; none of them depends on volume to be *useful*, which is why they can proceed.

Evidence keys: `02` = research/02 search demand · `05` = competitor research · `06` = school database · `07` = qualifications · `08` = fees · `09` = application process · `10` = working/registration · `03` = adjacent courses · `11` = terminology.

## A. BUILD NOW — core assets (release 1)

| # | Page | URL | Intent | Nigerian demand evidence | SERP / competition (observed) | Unique value we add | CTA | Funnel role | Sources required | Decision |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Home | `/` | navigational | — | — | clear journey, Apply Online | APPLY ONLINE | entry | — | BUILD NOW |
| 2 | Study Medicine in the UK from Nigeria (core landing) | `/study-medicine-in-the-uk/from-nigeria/` | informational→commercial | Queries 1–4 in `02`; Nairaland "How to study medicine in UK", "Is medicine the right course to study in the UK" | consultancies (theukcatpeople) + programmatic directories + North Cyprus agents; no official source ranks | one honest end-to-end answer for a WAEC/NECO/A-level/degree holder with routes, costs, timeline, linked to verified data | APPLY ONLINE | top of funnel | MSC, UCAS, universities | BUILD NOW |
| 3 | What do I need to study Medicine in the UK? (requirements hub) | `/requirements/` | informational | Query 5; forum "Can I study medicine and surgery with this result?" | mixed consultancies; contradictory answers | structured checklist (brief 107) linked to per-school data | CHECK YOUR ELIGIBILITY | consideration | universities, MSC | BUILD NOW |
| 4 | WAEC (WASSCE) and UK Medicine | `/requirements/waec/` | informational, high anxiety | Query 6; Nairaland "Can WAEC and NECO be used to study abroad"; TSR "Is WAEC accepted as GCSE equivalents?" | two consultancy guides, forum threads, contradictory | per-university published statements table with source + verified date (gap 1 in `05`) | CHECK YOUR ELIGIBILITY | consideration | university Nigeria pages (`07`) | BUILD NOW |
| 5 | NECO and UK Medicine | `/requirements/neco/` | informational | Query 7 (thinner than WAEC) | near-empty | same per-university table filtered to NECO; honest "most schools name WASSCE and are silent on NECO" | CHECK YOUR ELIGIBILITY | consideration | `07` | BUILD NOW† (may MERGE into WAEC page if `07` shows universities never distinguish NECO; keep separate URL only if statements differ) |
| 6 | A-levels for UK Medicine (Cambridge International from Nigeria) | `/requirements/a-levels/` | informational→commercial | Query 9: real Nigerian sub-topic (A-level school choice) | Nigerian school directories, news | typical A100 grade requirements, subject rules, how international applicants are assessed; neutral note on A-level provision in Nigeria (no school recommendations) | APPLY ONLINE | consideration | universities, UCAS | BUILD NOW |
| 7 | Graduate Entry Medicine with a Nigerian degree | `/requirements/nigerian-degree-graduate-entry/` | informational | Query 10: unowned; TSR "Medicine as second undergrad as international" | generic PG-equivalency pages; no GEM page exists | which GEM courses accept international students at all; degree class mapping; GAMSAT/UCAT; fees (`08` Warwick, Swansea) | APPLY ONLINE | consideration | universities (`07`) | BUILD NOW |
| 8 | English language requirements for Medicine | `/requirements/english-language/` | informational | implicit in every forum thread; WAEC-English acceptance is a recurring question | thin | per-school IELTS bands; whether WAEC English counts anywhere (per `07`); UKVI rule | CHECK YOUR ELIGIBILITY | consideration | universities, GOV.UK | BUILD NOW |
| 9 | UK medical school fees for international students (fee guide) | `/fees/` | informational→commercial | Query 11, 14; Guardian NG coverage; spam ranking = weak authority | undated, conflicting figures everywhere | dated per-school table (33 fees found in `08`), pre-clinical vs clinical, NHS levies, increase policies, approximate range clearly separated | APPLY ONLINE | consideration | university fee pages | BUILD NOW |
| 10 | Total cost of studying Medicine in the UK (5–6 years) | `/fees/cost-of-studying-medicine-in-the-uk/` | informational | Query 11 | consultancies | tuition range + IHS + visa + living + tests with arithmetic shown; **blocked until `08` §3–4 gaps are filled** | APPLY ONLINE | consideration | GOV.UK, UKCISA, universities | BUILD NOW (publish after gap fill) |
| 11 | UK Medical School Directory | `/medical-schools/` | discovery | Query 13 (generic well served; Nigerian angle not) | prep companies | filters: international yes/no, test, route, nation, fee band; verified dates; compare | APPLY ONLINE per card | consideration | `06`, `08` | BUILD NOW |
| 12 | University record pages (only for schools open to international applicants with ≥ 3 verified fields) | `/medical-schools/{u}/` | informational | university "Nigeria" pages rank (Aberdeen, Manchester, Cardiff, Edinburgh) → students look per school | universities themselves | Nigerian-applicant block: published WASSCE/NECO statement or "NOT PUBLISHED", fee, test, deadline, route, sources | APPLY ONLINE | consideration→action | per school | BUILD NOW for international-accepting schools; **DO NOT BUILD** for home-only schools (list them in the directory only) |
| 13 | Course record pages | `/medical-schools/{u}/{course}/` | informational | as 12 | — | the data record rendered: requirements, fee by year, deadline, structure, last verified | APPLY ONLINE | action | per course | BUILD NOW only where course has distinct data (A100 vs A101); otherwise the university page is the course page |
| 14 | University of Greater Manchester — MBChB | `/medical-schools/greater-manchester/` | informational | named in brief; international-only cohort per `02` snippet | thin, mostly agent leads | fully sourced record; no partnership claim | APPLY ONLINE | action | university pages (`06` deep dive) | BUILD NOW |
| 15 | UCAT for Nigerian students (UCAT in Nigeria) | `/admissions/ucat/` | transactional/logistics | Query 12: strongest single gap; no official page ranks | one tutoring blog | 2026 structure (/2700), 2027-cycle dates, fee, Pearson VUE Lagos/Abuja logistics, which schools need it, what a missed window means | APPLY ONLINE | consideration | ucat.ac.uk (`09`) | BUILD NOW |
| 16 | UCAS deadlines and timeline for 2027 entry (and 2028 planning) | `/admissions/ucas-deadlines-2027/` | informational, time-critical | implicit; 15 Oct 2026 is 12 days away | UCAS itself | Nigeria-specific calendar: UCAT → UCAS → interviews → offer → deposit → CAS → visa → TB test → travel; honest "if you have no UCAT 2026, plan 2028 or direct-application schools" | APPLY ONLINE | consideration | UCAS (`09`) | BUILD NOW; rollover each August |
| 17 | How to apply: UCAS vs direct-application medical schools | `/admissions/how-to-apply/` | informational | Nairaland "Study in the UK: step by step" | agents | 4+1 rule, 3-question statement, document upload, direct schools (Buckingham, Lancashire/UCLan, Greater Manchester), what we do and do not do | APPLY ONLINE | action | UCAS, universities | BUILD NOW |
| 18 | Medicine foundation and gateway routes for international students | `/study-medicine-in-the-uk/foundation-routes/` | informational | gap 4 in `05`; implicit in WAEC-only demand | none | which foundation years actually lead to Medicine (Lancashire foundation-entry MBBS, St Andrews IF Medicine, Buckingham, NCUK) — only where published | CHECK YOUR ELIGIBILITY | consideration | providers (`07`) | BUILD NOW† (needs `07` fill) |
| 19 | Apply Online (gateway) | `/apply-online/` | transactional | — | — | tiers, what we are/are not, process | APPLY ONLINE | action | `11` phrasing | BUILD NOW |
| 20 | Services & pricing | `/apply-online/services/` | transactional | — | competitors rarely show prices (`05`) | transparent deliverables; prices once set | choose tier | action | — | BUILD NOW (deliverables now, prices when set) |
| 21 | Eligibility check | `/apply-online/eligibility/` | lead capture | — | — | useful instant route-category answer | continue | lead | rules from `07` | BUILD NOW |
| 22 | FAQ hub (seeded with the 40 real questions in `02` §C, each validated) | `/faq/` | informational | forum-derived | — | each answer links into the knowledge graph | contextual | capture | — | BUILD NOW (publish only questions with a sourced answer) |
| 23 | About · Contact · Privacy · Terms · Application terms · Refund policy · Document & data policy · Editorial & verification policy | `/about/` etc. | trust | — | competitors weak on identity (`05`) | real identity; "not an agent of any university unless stated" | — | trust | `11` | BUILD NOW |

## B. BUILD LATER — validate or wait for data

| # | Page | URL | Why not now | Trigger to build |
|---|---|---|---|---|
| 24 | Why study Medicine in the UK? (factual hub) | `/study-medicine-in-the-uk/why-study-medicine-in-the-uk/` | demand is implicit (Nairaland "is UK medicine right for me"); content must be fact-led (course structure, MLA, Foundation Programme) and the prioritisation-of-UK-graduates policy is in flux (`10`) | after `10` gaps closed; keep ≤ 1 page |
| 25 | Working in the UK during and after medical school | `/working-in-the-uk/` | BMA owns the SERP (`02` q16); rules changing (Graduate visa 18 months from 1 Jan 2027; prioritisation Bill) | **built 2026-10-03** as `/working-in-the-uk/`, noindex and out of the sitemap until its three topics are VERIFIED (PublishGate); links out to GOV.UK, GMC, UKFPO, NHS England, BMA |
| 26 | Interviews (MMI) for international applicants | `/admissions/interviews/` | useful but not Nigeria-differentiated yet | after release 1, with online-interview policies per school |
| 27 | Personal statement (3 questions) and documents | `/admissions/personal-statement/`, `/admissions/documents/` | portal checklist covers documents; statement guidance is widely available | later, to support Tier 2 |
| 28 | Compare medical schools | `/medical-schools/compare/` (noindex tool) | depends on directory data density | when ≥ 20 schools have fee + test + route + deadline verified |
| 29 | Living costs in the UK for medical students | `/fees/living-costs/` | `08` §4 unsearched | after gap fill; scope tightly to Fees |
| 30 | Whitelisted directory filter pages (e.g. `/medical-schools/accepting-international-students/`, `/medical-schools/no-ucat/`) | — | need Semrush/GSC confirmation of demand to justify indexable pages | after Semrush `ng` check; otherwise keep as noindex filters |
| 31 | Public Health (MPH) in the UK for Nigerians | `/public-health/` | strongest allied SERP (`02` q15) but postgraduate and off the medicine core; see `03` | validate with Semrush; build only if `03` verdict supports and a Tier can serve it |
| 32 | Nursing in the UK for Nigerians | `/nursing/` | visible demand but split between studying and NMC migration; risk of becoming generic | validate; if built, undergraduate study only |
| 33a | Dentistry in the UK for Nigerian students (single hub page) | `/dentistry/` | `03` verdict BUILD NOW on expertise transfer (UCAT at all schools; verified international scarcity: Aberdeen 1, Dundee 11, Glasgow 15 places; fees KCL £58,200, Plymouth £41,920 — VERIFY-ON-PAGE) but `02` q15 shows an empty Nigerian SERP and no volume data | build as ONE page after release 1, or sooner if Semrush `ng` / GSC shows dentistry queries; never a section |
| 33b | Pharmacy (MPharm) for international students | `/pharmacy/` | `03` BUILD LATER: open to internationals; key story is the foundation-training year and visa before GPhC registration | after Semrush validation; one page |
| 33c | Biomedical Science / "Medical Laboratory Science UK" as an alternative route | section inside `/study-medicine-in-the-uk/foundation-routes/` or GEM page | `03`: GEM feeder; HCPC registration needs IBMS portfolio (not verified this session) | with row 7/18 content |
| 33 | JAMB and UK medicine | FAQ only | `02` q8: demand is domestic JAMB; UK angle is one sentence | never a page; one FAQ |

## C. DO NOT BUILD

| Page idea | Reason |
|---|---|
| "Best / top medical schools in the UK for Nigerians", rankings, winner badges | brief 15, 103; no defensible methodology |
| Per-city or per-region landing pages | duplication of directory data; thin |
| Generic "Study in the UK" / "Life in the UK" sections | brief 91; dilutes medical focus |
| Blog / news feed | brief 38 |
| Scholarship listicles ("10 scholarships to study abroad with WAEC") | `08` §5: only tiny partial awards exist for international medicine; listicle intent is served by spammy competitors and would mislead |
| Physiotherapy, Radiography / Medical Imaging pages | `02` q15: empty SERPs; `03` verdict DO NOT BUILD (low overlap, no transferable admissions-test expertise, no data retrieved) |
| Public Health (MPH) as a section | `03` verdict: postgraduate, non-UCAS, different funnel; serve with one FAQ answer only (supersedes row 31 above, which is kept only as a Semrush re-check) |
| "Nigerian doctor → UK (PLAB/housemanship)" content | different audience (qualified doctors), BMA/GMC own it; outside mission |
| University logo walls, testimonials, student counts, success rates | brief 114 |
| North-Cyprus/EU medicine alternatives | outside mission, even though competitors use them to capture Nigerian intent |

## D. Register maintenance

- Each row becomes a record in `asset_register` (data model 17.2) with decision date and rationale.
- Re-decide rows marked † when Semrush `ng` data and Search Console queries are available (DATA-AVAILABILITY).
- A new page proposal requires the 14-point test in brief section 2, recorded here before any content is written.
