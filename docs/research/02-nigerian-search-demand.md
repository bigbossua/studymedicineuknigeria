# 02 — Nigerian search demand evidence

**Research date:** 2026-10-03
**Method:** web search only. All SERP observations below are *search results observed on 2026-10-03 via web search (US-indexed engine; may differ from google.com.ng)*. The search tool returns a ranked link list plus a synthesised summary; it does **not** expose Google's "People also ask" box, so the PAA column records only whether question-shaped pages/threads surfaced, never a real PAA panel.
**Not available this session:** Semrush (no API units), Google Search Console, GA4, Google Trends, direct page fetch of any external domain. See `docs/DATA-AVAILABILITY.md`.
**Search budget note:** the session-wide web-search cap (200 calls) was reached after 45 searches by this agent. Items marked `NOT SEARCHED (budget)` were planned but could not be run.

Label key: **FACT** = observed in results; **SOURCE** = URL; **INFERENCE** = our reasoning; **RECOMMENDATION** = what to do.

---

## A. Per-query SERP observations

Columns: top ~5 URLs/domains as observed → page type → evident intent → Nigeria-specific result present? → question-shaped results present?

### 1. "study medicine in UK from Nigeria"
- theukcatpeople.co.uk/application-guide/ucas/study-medicine-in-the-uk-from-nigeria — **medicine admissions consultancy (UK)**, Nigeria-specific landing page
- leadership.ng/?p=183121 — Nigerian newspaper
- translayte.com/travel-advisory/study-abroad-in-nigeria-for-united-kingdom-citizens — translation-service blog (off-intent: UK citizens → Nigeria)
- nairametrics.com/?p=487860 — Nigerian business news
- excelsiorscholarships.com/top-9-medical-scholarships-nigerian-students-study-abroad/ — scholarship listicle blog
- also: northcypruseducation.com (agent, redirects intent to North Cyprus), bellanaija.com, abstechconnect.com (blog; URL contains typo "nogeria")
- Intent: informational. Nigeria-specific: **yes** (theukcatpeople page is explicitly for Nigerians). Question-shaped results: no PAA observable; snippet raises "WAEC alone cannot get you in", "most foundation years do not lead to medicine", "UCAT slots in Nigeria disappear within days".
- INFERENCE: only one page (theukcatpeople) is both medicine-specific and Nigeria-specific; the rest are news/listicle/off-topic — a weak SERP.

### 2. "medicine in UK for Nigerian students"
- theukcatpeople.co.uk (Nigeria guide) — consultancy
- icirnigeria.org/?p=127493 — Nigerian news ("Brilliant Nigerian girl offered admission to study Medicine in UK university")
- leadingtuition.co.uk/blog/ucat-nigerian-students-uk-medicine — UK tutoring company blog, Nigeria-specific
- excelsiorscholarships.com — scholarship listicle
- northcypruseducation.com — agent (North Cyprus)
- also: abstechconnect.com (Nile University GMC approval), gabble.ai/blog/study-in-uk-for-nigerian-students
- Intent: informational. Nigeria-specific: **yes** (2 dedicated pages). Snippet surfaces: UCAT required by ~30 of 41 schools; Nigeria not on majority-English list so SELT (IELTS UKVI) needed for visa; Graduate Route → Skilled Worker.

### 3. "MBBS UK for Nigerian students"
- honoris.net (x4 near-duplicate URLs) — university network press release: Nile University of Nigeria MBBS gains GMC approval
- dailytrust.com/?p=1745421 — Nigerian newspaper (same story)
- abstechconnect.com (x2) — blog (same story)
- theukcatpeople.co.uk (Nigeria guide)
- globaladmissions.com/study/mbbs-abroad/for-nigerian-students — multi-country MBBS-abroad directory
- Intent: informational, partly navigational (news). Nigeria-specific: **yes**, but mostly about a *Nigerian* MBBS being GMC-recognised, not studying in the UK.
- INFERENCE: the term "MBBS" in a Nigerian context pulls the "Nile University GMC approval" news cluster; the query is ambiguous between "study in UK" and "Nigerian MBBS → UK practice". Content should disambiguate.

### 4. "UK medical school Nigeria" / "medical universities in UK for Nigerians"
- theukcatpeople.co.uk (Nigeria guide)
- studentship.com.ng/best-uk-universities-for-nigerian-students/ — Nigerian education blog/directory
- twinkl.pt/blog/top-10-british-universities-for-nigerian-students — edtech blog
- dailytrust.com (Nile/GMC story)
- excelsiorscholarships.com, dofmar.com/scholarships-for-nigerian-students-to-study-medicine-abroad/ — scholarship listicles
- northcypruseducation.com
- Intent: informational/commercial (list-seeking). Nigeria-specific: **yes**. Snippet names Manchester, Southampton (GREAT Scholarships), St George's, BSMS without sourcing per-university Nigerian entry policy.

### 5. "medicine entry requirements UK for Nigerian students"
- theukcatpeople.co.uk (Nigeria guide)
- **abdn.ac.uk/study/international/country-territory/nigeria/entry/** — university country page
- gostudyin.com/nigeria/study-in-uk/study-guides/uk-university-entry-requirements-guide-nigerian-students/ — multi-country study directory
- **manchester.ac.uk/study/international/country-specific-information/nigeria/entry-requirements/** — university country page
- leadingtuition.co.uk (UCAT Nigeria blog)
- **cardiff.ac.uk/.../nigeria/entry-requirements**, **ed.ac.uk/studying/international/country/africa/nigeria** — university country pages
- Intent: informational. Nigeria-specific: **yes** (4 university country pages + 2 consultancies). Snippet: "Aberdeen require GCE A-levels, IB or Scottish Highers for Medicine"; three routes = CAIE A-levels (AAA–A*AA), IB (36–38), foundation-entry medicine.
- INFERENCE: the strongest "official" SERP in the set; universities rank with generic Nigeria pages that typically *exclude* medicine from WASSCE equivalency rather than explain a route.

### 6. "WAEC medicine UK" / "can I study medicine in UK with WAEC"
- theukcatpeople.co.uk (Nigeria guide)
- leadingtuition.co.uk (UCAT Nigeria blog)
- **thestudentroom.co.uk/showpost.php?p=80424048** — forum
- pearsonpte.com/articles/studying-medicine-in-the-uk/ (x6 paginated duplicates) — test-provider article
- Intent: informational (yes/no question). Nigeria-specific: **yes**. Question-shaped: **yes** (forum).
- FACT (as summarised by results; VERIFY-ON-PAGE): "No UK medical school accepts WASSCE or NECO alone for direct entry; WASSCE treated as GCSE-equivalent." Note the two consultancies give *contradictory framing* ("many UK medical schools do accept WAEC … A1/B2 in sciences" vs "WAEC alone cannot get you in") — INFERENCE: a verified per-university statement table would resolve a real confusion.

### 7. "NECO medicine UK"
- leadingtuition.co.uk (UCAT Nigeria blog)
- von.gov.ng (NECO opens London exam centre) — Nigerian government media
- **assist.applyboard.com/.../Studying-in-the-UK-with-NECO-What-Nigerian-Students-Need-to-Know** — agent help-centre
- teezab.com ("Can NECO be used to study abroad?") — Nigerian blog
- theukcatpeople.co.uk; arise.tv (NECO London centre news); nec.ac.uk (irrelevant); northcypruseducation.com (x2: "NECO Result 2025…North Cyprus")
- Intent: informational. Nigeria-specific: **yes**. Snippet: Birmingham City and Leeds Trinity accept NECO (not for medicine); many accept C6 NECO English in lieu of IELTS; medicine requires A-levels/foundation.
- INFERENCE: NECO + medicine has no dedicated page anywhere in results — thin, winnable.

### 8. "JAMB medicine UK"
- mumble.maidsafe.net blog ("JAMB 150 score…") — low-quality/spam blog
- northcypruseducation.com
- theukcatpeople.co.uk
- biu.edu.ng (Benson Idahosa pre-degree medicine) — Nigerian university
- my.oncampus.global/uk/study-medicine-uk.htm — pathway provider
- pearsonpte.com (x3), uk-education-centre.com
- Intent: informational (mixed Nigeria-domestic). Nigeria-specific: partial. Snippet (leadingtuition): "JAMB and UTME scores play no role at all in a UCAS application".
- INFERENCE: demand is largely domestic-JAMB; UK-angle is a single-sentence answer. Low priority as a page, high priority as an FAQ line.

### 9. "A-level medicine UK Nigeria"
- icirnigeria.org (x2) — news (Nigerian girl admitted to UK medicine)
- leadingtuition.co.uk
- **nigeriaprivateschools.com** (x2): "Difference Between UK Foundation Year and A-Level"; "Top 15 A-Level Schools in Nigeria for UK University Admission" — Nigerian directory
- theukcatpeople.co.uk
- allschool.ng (Nigerian girl → Manchester medicine after A-levels) — news
- test1.buckingham.ac.uk PreMed Entry Requirements PDF (staging subdomain) — university
- Intent: informational/commercial (choosing A-level route/provider). Nigeria-specific: **yes**. Snippet names Bridge House College, Oxbridge Tutorial College, Westerfield College.
- INFERENCE: "A-levels in Nigeria for UK medicine" is a real commercial sub-topic (school choice) with only directory-grade content.

### 10. "graduate entry medicine UK Nigerian students" / "study medicine in UK after Nigerian degree"
- **ed.ac.uk/studying/international/postgraduate-entry/africa/nigeria** — university PG page
- theukcatpeople.co.uk
- doaj.org / ajol.info — academic journal articles (off-intent)
- **shu.ac.uk** Nigeria entry requirements (x2)
- universitydb.io/study-in-uk/from/nigeria — directory
- abstechconnect.com (Nile/GMC)
- Intent: informational. Nigeria-specific: yes but **not medicine-specific**: results are generic PG equivalency (GPA 3.5/5.0, 60%, 2:1) and PLAB news.
- INFERENCE: no page addresses GEM (A101) for Nigerian degree holders — GAMSAT/UCAT, degree class mapping, fees. Thin and unowned.

### 11. "cost of studying medicine in UK for Nigerian students" / "medical school fees UK for international students"
- Nigerian-angled query: gostudyin.com (cost guide), guardian.ng (x3: "Nigerians pay N152b tuition in UK"), excelsiorscholarships.com, studentship.com.ng, icirnigeria.org (cost of becoming doctor in Nigeria), northcypruseducation.com
- International query: themedicportal.com (x2: finances guide), theukcatpeople.co.uk (x4: fees post; "how much does it cost to become a doctor"), **bsms.ac.uk/undergraduate/fees-and-funding**, leadingtuition.co.uk, plus two spam domains (fapet.ipb.ac.id, db-03.ringfree.com) — INFERENCE: spam presence indicates a SERP with weak authoritative coverage.
- Snippet figures (VERIFY-ON-PAGE; stated as "2026-27 rates" by theukcatpeople/leadingtuition): international medicine fees £30,150 (Leicester) to £70,554 (Cambridge); Manchester £39,900; Bristol £45,800; KCL £56,800; up to £312,645 total over five years. Nigerian-angled pages quote outdated generic figures ("up to £32,000/yr"; "£13,394 classroom").
- Intent: informational → commercial. Nigeria-specific: yes for the Nigerian query but with stale/generic numbers. INFERENCE: a dated, per-school fee table in GBP + NGN is a clear gap.

### 12. "UCAT Nigeria" / "UCAT test centre Lagos"
- leadingtuition.co.uk (UCAT Nigeria blog) — only relevant result
- ielts.org test-centre pages (Ikeja, Ikoyi, Yaba, Mafit) x4 — off-intent
- blueprint.ng, thecable.ng, ripplesnigeria.com — JAMB CBT-centre news — off-intent
- Intent: transactional/navigational (find centre, book). Nigeria-specific: yes but **no official UCAT/Pearson VUE page ranked**. Snippet: UCAT sat in person at Pearson VUE centres, "Lagos or Abuja being the practical options, centre capacity being the single biggest operational risk".
- INFERENCE: strongest single gap in the set — an official-sourced, dated "UCAT in Nigeria" logistics page has no competitor.

### 13. "UK medical schools that accept international students"
- timeshighereducation.com (x3; new schools/cap news) — news
- themedicportal.com (x2; international guide) — consultancy content
- medify.co/admissions-guide/international-students-medicine-dentistry — prep company
- bluepeanut.com (international acceptance rates 2025) — consultancy blog
- nextgenmedprep.com/medical-schools/international-students (places, eligibility, offer rates, 2027 entry) — prep company
- gabble.ai
- Intent: informational/commercial. Nigeria-specific: **no**. Snippet (VERIFY-ON-PAGE): 47 of 51 UK schools take internationals for 2027 entry; 3 international-only (Hertfordshire, St Mary's Twickenham, University of Greater Manchester); 4 do not (Anglia Ruskin, Edge Hill, Pears Cumbria, Sunderland); ~500 international seats vs ~7,000+ home; ~7.5% cap; Buckingham uncapped.
- INFERENCE: well-served generically by UK prep companies; the Nigerian angle (which of these accept WASSCE+foundation / Nigerian A-levels) is not.

### 14. "cheapest medical school in UK for international students"
- theukcatpeople.co.uk (x2)
- gostudyin.com (x3 country-path duplicates) — programmatic directory
- bluepeanut.com (cheapest UK med schools)
- gorod.it.com (spam), yocket.com (India-targeted), leadingtuition.co.uk
- Intent: commercial. Nigeria-specific: no. Snippet figures conflict (Lincoln £28,700 but "home students only"; Southampton £28,900; Leicester £29,000 pre-clinical / £47,000 clinical vs £30,150 elsewhere) — INFERENCE: inconsistent, undated numbers across competitors → verified-date fee data is differentiating.

### 15. Allied courses — "X UK Nigerian students"
| Course | Observed top domains | Nigeria→UK study page present? |
|---|---|---|
| Dentistry | ir.unilag.edu.ng (x4 academic), gostudyin.com (**India** dentistry page), ajol.info, nigeriandentaljournal.ng | **No** — SERP is Nigerian dental-education research |
| Pharmacy | sunderland.ac.uk (OSPAP case study, Nigerian student), blogs.brighton.ac.uk (OSPAP, x3), gostudyin.com/nigeria/.../pharmacy/, fip.org, pmc | Partial — OSPAP (registered pharmacists) dominates, not undergraduate MPharm |
| Nursing | nairametrics.com, upic.navitas.com, **aru.ac.uk/international/information-by-country/nigeria**, studentship.com.ng (how to become a nurse in UK from Nigeria), rcn.org.uk (history lecture x2), northcypruseducation.com | Yes (studentship, ARU) — but intent split between *studying* and *migrating as a registered nurse* (NMC, IELTS 7) |
| Public health | manchester.ac.uk (x2; Nigerian MPH graduate), nigeriahealthwatch.com (x2), sheffield.ac.uk, hub.salford.ac.uk, excelsiorscholarships.com (x2), study-uk.britishcouncil.org, advance-africa.com | Yes — strongest allied SERP; snippet: "70% of the class at one UK MPH were Nigerians"; Chevening; Commonwealth distance-learning MPH |
| Biomedical science | southampton.ac.uk (2010 news), staffs.ac.uk blog, careers.nhs.scot, warwick.ac.uk alumni, studyinternational.com, skills-provision.com (CV directory x2) | Weak — anecdotes, no guide |
| Physiotherapy | e-space.mmu.ac.uk (thesis), ius.edu.ba, corahealth.co.uk, player.fm podcast (x2), academic PDFs | **No** — academic/anecdotal |
| Radiography | medicalmirror.org, sor.org/NIRAD (x2), preprints.org, uniben/unilag repositories, studentship.com.ng (radiography in Nigeria) | **No** — diaspora-professional, not study |
| Medical lab science | von.gov.ng (brain-drain news), skills-provision.com (x8 CV listings), kiu.ac.ug | **No** — migration/jobs intent, not study |
- INFERENCE: for dentistry, physiotherapy, radiography, MLS and biomedical science there is effectively *no* Nigeria→UK study content ranking. Demand is unproven (no volume data) but the SERPs are empty, so even modest demand would be capturable. Public health and nursing show visible demand but intent is heavily postgraduate/migration.

### 16. "can I work in UK after studying medicine"
- bma.org.uk (x10 — entire first page) — professional body
- Intent: informational. Nigeria-specific: no. Snippet: Health and Care Worker visa (Skilled Worker route), sponsor needed, up to 5 years, no IHS; Graduate Route → Skilled Worker.
- INFERENCE: BMA owns this; our page should link out and add the Nigeria-specific layer (MDCN return route vs staying).

### 17. Forum sweep — Nairaland and The Student Room
Thread titles observed (post bodies could not be retrieved — fetch blocked; titles only):
- https://www.nairaland.com/4071072/how-study-medicine-uk — "How To Study Medicine In UK" (Career)
- https://www.nairaland.com/8665379/medicine-right-course-study-uk — "Is Medicine The Right Course To Study In The UK?" (Education; high ID → recent)
- https://www.nairaland.com/7408807/best-medical-schools-uk — "Best Medical Schools In UK"
- https://www.nairaland.com/5022863/route-working-doctor-uk-nigerian — "Route To Working As A Doctor In The UK For Nigerian Graduates"
- https://www.nairaland.com/5530820/how-study-nursing-uk — "How To Study Nursing In The UK"
- https://www.nairaland.com/6081067/waec-neco-waec-neco-used — "Waec And Neco (Can Waec And NECO Be Used To Study Abroad)"
- https://www.nairaland.com/5329415/study-medicine-surgery-result/3 and /3934860/study-medicine-surgery-result — "Can I Study Medicine And Surgery With This Result?"
- https://www.nairaland.com/6606774/should-continue-studying-medicine-nigeria/3 — "Should I Continue Studying Medicine In Nigeria Or Should I Start Afresh Abroad"
- https://www.nairaland.com/843062/fresh-graduate-medicine-surgerywhich-country — "I Am A Fresh Graduate Of Medicine And Surgery, Which Country Should I Migrate To?"
- https://www.nairaland.com/4224770/3-reasons-why-nigerians-choose — "3 Reasons Why Nigerians Choose To Study Medicine Abroad"
- https://www.nairaland.com/8622717/study-medicine-eu-complete-2026 — "Study Medicine In EU: A Complete 2026 Guide" (agent-style post)
- https://www.nairaland.com/1948639/study-uk-step-step-process — "Study In The UK: Step By Step Process"
- https://www.nairaland.com/6323922/arts-medicine — "From Arts To Medicine"
- https://www.nairaland.com/8031932/10-scholarship-study-abroad-waec — "10 Scholarship To Study Abroad With WAEC Result"
- https://www.nairaland.com/5263343/nigeria-europe-plab-experience — "From Nigeria To Europe (PLAB experience)"
- https://www.nairaland.com/1837366/level-school-lekki — "A Level School In Lekki"
- The Student Room: t=7404771 "Is WAEC accepted as GCSE equivalents?"; t=6990701 "Housemanship in UK, US and Asia for a newly graduated medical doctor in Nigeria"; t=7524422 "Medicine as second undergrad – entry requirements as international"; t=4635968 "FY1 for international medical graduates"; t=7495476 "A100 Medicine for International Students 2025 Entry"; t=7171589 "Medicine international work experience"; t=7582018 "How to get funding to study nursing as an International student".
- INFERENCE: forum demand clusters into (a) "can my WAEC/this result get me in", (b) "how do I do it step by step", (c) "is UK medicine worth it / right for me", (d) "I already have a Nigerian MBBS — how do I get to the UK (PLAB/housemanship)", (e) A-level school choice. Cluster (d) is a different audience (doctors, not students) and should be scoped deliberately.

---

## B. Keyword / intent table

All Semrush columns: `DATA UNAVAILABLE (Semrush — no API units)`. "SERP strength" is an INFERENCE from observed results only.

| # | Keyword / cluster | Intent | Nigeria-specific page ranking? | Observed SERP strength (INFERENCE) | Semrush vol (ng) | Semrush KD | Semrush intent |
|---|---|---|---|---|---|---|---|
| 1 | study medicine in UK from Nigeria | Informational | Yes (1 consultancy) | Weak — news/listicles | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 2 | medicine in UK for Nigerian students | Informational | Yes (2 consultancies) | Weak–moderate | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 3 | MBBS UK for Nigerian students | Informational (ambiguous) | Yes (news) | Weak, off-intent (Nile/GMC news) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 4 | medical universities in UK for Nigerians / UK medical school Nigeria | Informational/Commercial | Yes (blogs) | Weak — unsourced lists | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 5 | medicine entry requirements UK for Nigerian students | Informational | Yes (4 universities + 2 consultancies) | Moderate — universities rank | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 6 | can I study medicine in UK with WAEC / WAEC medicine UK | Informational (Q) | Yes | Weak — contradictory answers | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 7 | NECO medicine UK | Informational (Q) | Yes (not medicine) | Very weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 8 | JAMB medicine UK | Informational (Q) | Partial | Very weak / spam | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 9 | A-level medicine UK Nigeria / A-level schools Nigeria UK medicine | Informational→Commercial | Yes (directory, news) | Weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 10 | graduate entry medicine UK Nigerian students / after Nigerian degree | Informational | Yes (generic PG pages) | Very weak — no GEM page | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 11 | cost of studying medicine in UK for Nigerian students | Informational/Commercial | Yes (stale figures) | Weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 12 | medical school fees UK for international students | Informational/Commercial | No | Moderate (UK prep cos.) + spam | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 13 | UCAT Nigeria / UCAT test centre Lagos | Transactional/Navigational | Yes (1 blog) | Very weak — no official page | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 14 | UK medical schools that accept international students | Informational/Commercial | No | Strong (prep cos., THE) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 15 | cheapest medical school in UK for international students | Commercial | No | Moderate, inconsistent data | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 16 | dentistry UK Nigerian students | Informational | No | Empty | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 17 | pharmacy UK Nigerian students | Informational | Partial (OSPAP) | Weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 18 | nursing UK Nigerian students | Informational (split: study vs migrate) | Yes | Moderate | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 19 | public health UK Nigerian students | Informational (PG) | Yes | Moderate — universities + NHW | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 20 | biomedical science UK Nigerian students | Informational | Anecdotal | Weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 21 | physiotherapy UK Nigerian students | Informational | No | Empty | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 22 | radiography UK Nigerian students | Informational | No (diaspora prof.) | Empty | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 23 | medical laboratory science UK for Nigerians | Informational (migration) | No | Empty / jobs boards | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 24 | can I work in UK after studying medicine | Informational | No | Strong (BMA) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 25 | MDCN recognition of UK medical degree (emergent from results) | Informational | Mention only | Empty | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 26 | foundation year medicine UK international / which foundation leads to medicine (emergent) | Informational/Commercial | Mention only | Weak | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |
| 27 | scholarships to study medicine in UK for Nigerian students (emergent) | Informational/Commercial | Yes (listicles) | Weak — unverified listicles | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) | DATA UNAVAILABLE (Semrush — no API units) |

---

## C. Real questions Nigerians ask (40) — each with a source URL

Sources are thread titles, article headings or question-shaped snippets observed on 2026-10-03. Where the source is a forum, only the title was observable.

1. Can I study medicine in the UK with WAEC? — https://www.theukcatpeople.co.uk/application-guide/ucas/study-medicine-in-the-uk-from-nigeria
2. Is WAEC accepted as a GCSE equivalent in the UK? — https://www.thestudentroom.co.uk/showthread.php?t=7404771
3. Can WAEC and NECO be used to study abroad? — https://www.nairaland.com/6081067/waec-neco-waec-neco-used
4. Can NECO be used to study abroad? — https://www.teezab.com/?p=8037
5. Which UK universities accept NECO, and does NECO English replace IELTS? — https://assist.applyboard.com/hc/en-us/articles/46184046558861-Studying-in-the-UK-with-NECO-What-Nigerian-Students-Need-to-Know
6. Does my JAMB/UTME score count for anything in a UCAS application? — https://www.leadingtuition.co.uk/blog/ucat-nigerian-students-uk-medicine
7. Can I study Medicine and Surgery with this (WAEC) result? — https://www.nairaland.com/5329415/study-medicine-surgery-result/3 and https://www.nairaland.com/3934860/study-medicine-surgery-result
8. Which foundation years actually lead to medicine (and which don't)? — https://www.theukcatpeople.co.uk/application-guide/ucas/study-medicine-in-the-uk-from-nigeria
9. What is the difference between a UK Foundation Year and A-levels? — https://www.nigeriaprivateschools.com/index.php/en/post-detail/168/Difference-Between-UK-Foundation-Year-and-A-Level
10. Which A-level schools in Nigeria are best for UK university admission? — https://www.nigeriaprivateschools.com/index.php/en/post-detail/149/Top-15-A%E2%80%91Level-Schools-in-Nigeria-for-UK-University-Admission
11. Is there an A-level school in Lekki? — https://www.nairaland.com/1837366/level-school-lekki
12. How do I study medicine in the UK (step by step)? — https://www.nairaland.com/4071072/how-study-medicine-uk
13. What is the step-by-step process to study in the UK? — https://www.nairaland.com/1948639/study-uk-step-step-process
14. Is medicine the right course to study in the UK? — https://www.nairaland.com/8665379/medicine-right-course-study-uk
15. What are the best medical schools in the UK? — https://www.nairaland.com/7408807/best-medical-schools-uk
16. Why do Nigerians choose to study medicine abroad? — https://www.nairaland.com/4224770/3-reasons-why-nigerians-choose
17. Can I switch from Arts to Medicine? — https://www.nairaland.com/6323922/arts-medicine
18. Where can I sit the UCAT in Nigeria (Lagos/Abuja), and how do I book? — https://www.leadingtuition.co.uk/blog/ucat-nigerian-students-uk-medicine
19. How quickly do UCAT test slots in Nigeria run out? — https://www.theukcatpeople.co.uk/application-guide/ucas/study-medicine-in-the-uk-from-nigeria
20. What UCAT score do I need as a Nigerian applicant? — https://www.leadingtuition.co.uk/blog/ucat-nigerian-students-uk-medicine
21. Do I need IELTS UKVI as a Nigerian (is Nigeria a majority-English country for visa purposes)? — https://www.leadingtuition.co.uk/blog/ucat-nigerian-students-uk-medicine
22. How much does it cost to study medicine in the UK as an international student? — https://www.theukcatpeople.co.uk/post/uk-medical-school-tuition-fees-for-international-students-to-study-medicine
23. What does it cost in naira? — https://www.theukcatpeople.co.uk/application-guide/ucas/study-medicine-in-the-uk-from-nigeria
24. What is the cheapest UK medical school for international students? — https://bluepeanut.com/medical-school-blog/cheapest-uk-medical-schools-for-international-students
25. How much does it cost to become a doctor in the UK in total? — https://theukcatpeople.co.uk/post/how-much-does-it-cost-to-become-a-doctor-in-the-uk
26. Which UK medical schools accept international students, and how many places are there? — https://nextgenmedprep.com/medical-schools/international-students
27. What are international acceptance rates at UK medical schools? — https://bluepeanut.com/medical-school-blog/uk-medical-school-international-acceptance-rates-a-complete-2025-guide
28. Are there scholarships for Nigerian students to study medicine abroad/in the UK? — https://www.excelsiorscholarships.com/top-9-medical-scholarships-nigerian-students-study-abroad/ ; https://dofmar.com/scholarships-for-nigerian-students-to-study-medicine-abroad/
29. Are there scholarships to study abroad with a WAEC result? — https://www.nairaland.com/8031932/10-scholarship-study-abroad-waec
30. Can I get medicine work experience as an international applicant? — https://www.thestudentroom.co.uk/showthread.php?t=7171589
31. Can I do medicine as a second undergraduate degree as an international student? — https://www.thestudentroom.co.uk/showthread.php?t=7524422
32. Will the MDCN recognise my UK degree if I return to practise in Nigeria? — https://www.theukcatpeople.co.uk/application-guide/ucas/study-medicine-in-the-uk-from-nigeria
33. Can I work in the UK after studying medicine, and on what visa? — https://www.bma.org.uk/advice-and-support/international-doctors/training-in-the-uk/studying-in-the-uk-and-your-visa
34. What is the route to working as a doctor in the UK for Nigerian graduates (PLAB)? — https://www.nairaland.com/5022863/route-working-doctor-uk-nigerian
35. Can a newly graduated Nigerian doctor do housemanship in the UK? — https://www.thestudentroom.co.uk/showthread.php?t=6990701
36. Should I continue studying medicine in Nigeria or start afresh abroad? — https://www.nairaland.com/6606774/should-continue-studying-medicine-nigeria/3
37. Is the Nile University (Nigeria) MBBS recognised by the GMC / can graduates sit PLAB? — https://dailytrust.com/?p=1745421 ; https://honoris.net/nile-university-of-nigerias-mbbs-degree-gains-uk-gmc-approval-expanding-global-opportunities-for-graduates/
38. How do I study nursing in the UK from Nigeria? — https://www.nairaland.com/5530820/how-study-nursing-uk ; https://studentship.com.ng/how-to-become-a-nurse-in-the-uk-from-nigeria-year/
39. How can an international student get funding to study nursing in the UK? — https://www.thestudentroom.co.uk/showthread.php?t=7582018
40. Which UK universities have agents in Nigeria? — https://studentship.com.ng/uk-universities-agents-nigeria/

---

## D. Demand assessment (INFERENCE — no volume data)

**Evident demand (multiple independent signals: dedicated competitor pages + forum threads + university country pages):**
1. "Can my WAEC/NECO get me into UK medicine?" — forum threads, two consultancy guides, an agent help-centre article, and contradictory answers in the SERP. Highest-confidence demand.
2. Costs/fees for Nigerian/international students — Nigerian news (N152b tuition), consultancy fee posts, cost guides, spam presence (spam follows demand).
3. Entry requirements by country — four universities maintain Nigeria entry pages; consultancies target it.
4. UCAT logistics in Nigeria — two consultancies independently flag centre scarcity as the key risk; zero official coverage.
5. "Already a Nigerian doctor → UK" (PLAB/housemanship/visa) — several forum threads and all-BMA SERP. Strong demand but a *different audience*; decide scope deliberately.
6. Public health MSc and nursing — visible Nigerian cohort evidence (Manchester "70% Nigerians" claim, NHW article) but postgraduate/migration intent.

**Looks thin / unproven (few or no signals):**
- JAMB + UK medicine (one-line answer); NECO + medicine specifically; GEM for Nigerian graduates (no page exists — could be thin demand or an opportunity; needs Semrush); dentistry, physiotherapy, radiography, MLS, biomedical science for Nigerians (empty SERPs — demand unknown).
- MDCN recognition of a UK degree — mentioned by one competitor; no forum thread observed under that phrasing. Likely a low-volume but high-value trust topic.

**Caveats:** US-indexed engine; google.com.ng rankings may differ; no PAA data; forum post bodies not read; no volume data. None of the above should be presented to students as fact.

---

## E. Must re-check in Semrush (database `ng`) when units are available

1. Keyword Overview / Magic Tool for every row in Table B, plus modifiers: "from nigeria", "for nigerian students", "waec", "neco", "jamb", "lagos", "abuja", "naira", "2026", "2027".
2. Phrase-match and question-filter exports for seeds: "study medicine uk", "medicine uk", "medical school uk", "ucat", "waec uk", "neco uk", "a level nigeria", "foundation medicine uk", "graduate entry medicine", "mdcn", "plab", "dentistry uk", "nursing uk", "public health uk", "physiotherapy uk", "radiography uk", "pharmacy uk", "biomedical science uk".
3. Compare `ng` vs `uk` databases for the same seeds (Nigerian diaspora/parents in UK may search from `uk`).
4. Domain Overview + Organic Research (`ng` and `uk`) for: theukcatpeople.co.uk, leadingtuition.co.uk, gostudyin.com, studentship.com.ng, northcypruseducation.com, excelsiorscholarships.com, themedicportal.com, bluepeanut.com, nextgenmedprep.com, medify.co, medicmind.co.uk, studyin-uk.com (SI-UK), ukeas.com, pfl.ie / preparationforlife, abstechconnect.com.
5. Keyword Gap: studymedicineuknigeria.com vs the top 5 above (`ng`).
6. Topic Research for "study medicine in UK Nigeria" to recover PAA/headline data that this session could not observe.
7. SERP feature presence (PAA, featured snippet, video) per keyword in `ng`.
8. Seasonality (12-month trend) for UCAT/UCAS-cycle terms to validate the inferred July–October peak.
9. Backlink Analytics for theukcatpeople.co.uk and leadingtuition.co.uk Nigeria pages (link sources to target).
10. Position Tracking project seeded with Table B keywords, location Nigeria (Lagos, Abuja), device mobile.

## F. Planned but NOT SEARCHED (budget)
gostudyin.com Nigeria section detail; studentship.com.ng medicine content; northcypruseducation.com scale; Eduplan Africa; Y2GO; PFL partner list; Aspire Global Pathways; medschools.ac.uk international page; UCAS international deadlines page; official UCAT test-centre list for Nigeria; NCUK Nigeria centres; University of Lancashire MBBS foundation entry; Buckingham fees; St Andrews International Foundation for Medicine; Manchester/Aberdeen Nigeria medicine wording; "study medicine UK without A levels"; course duration queries; undergraduate medicine scholarships for Nigerians; MDCN foreign-graduate assessment; Nairaland thread bodies.
