# 06 — UK medical school landscape dataset (2027 entry)

**Research date:** 2026-10-03  **Cycle:** 2027 entry (UCAS medicine deadline 15 Oct 2026, 18:00 UK)
**Companion data file:** `data/medical-schools/schools.json` (53 records)
**Status of every fact in this document:** `VERIFY-ON-PAGE` unless marked `NOT FOUND IN THIS SESSION` / `NOT PUBLISHED`. Nothing here has been read directly from an official page (direct fetch is blocked by network policy); every value was located through a web-search snippet and must be confirmed on the cited URL before it is shown to a student.

---

## 1. Methodology and honest limits

- **Channel:** WebSearch only (extended mode for specifics). ~30 searches were completed in this pass before the session-wide budget of 200 WebSearch calls was exhausted (earlier research tasks in the same session had consumed the remainder). No curl, no WebFetch, no Semrush.
- **Authority hierarchy used:** (1) university's own domain; (2) Medical Schools Council (medschools.ac.uk), UCAS, UCAT Consortium (ucat.ac.uk); (3) GMC (regulator) for school/awarding-body status; (4) prep/agent/competitor sites (theukcatpeople, bluepeanut, medichut, themedicportal, myhsn, etc.) treated as **leads only**, labelled `lead (non-authoritative)` in the JSON `source_type` field.
- **Consequence of the budget cut-off:** 33 schools carry at least one substantive course-level field; **20 schools** (Lancaster, Oxford, Plymouth, Sheffield, Southampton, City St George's, Newcastle, Nottingham, UCL, Warwick, Aberdeen, Dundee, Edinburgh, Glasgow, ScotGEM, Swansea, QUB, Ulster, St Mary's, Wolverhampton) carry only identity, nation, GMC status and (where the UCAT consortium list named them) admissions test. Their other fields are `NOT FOUND IN THIS SESSION`, not "no".
- **No field is guessed.** Where a snippet summarised several sources together, the value is attributed to the most plausible official URL in that result set and flagged for verification; where the only plausible origin was a prep site, it is labelled as a lead.

## 2. Establishing the list (FACT / SOURCE)

| Item | Finding | Source |
|---|---|---|
| MSC membership page | Exists; snippet enumerates members alphabetically (Aberdeen, Anglia Ruskin, Aston, Birmingham, BSMS, Bristol, Brunel, Buckingham, Cambridge, Cardiff, Chester …) and names Hertfordshire (Dean: Prof Matt Morgan) and Surrey (Head of Graduate Entry Medicine) as represented | https://www.medschools.ac.uk/about-us/our-members/ `VERIFY-ON-PAGE` |
| MSC "Medical Schools" student page | Lists Edge Hill, HYMS, Imperial, Keele, KMMS, KCL GKT … ; notes HYMS = Hull + York joint, KMMS = Kent + Canterbury Christ Church joint | https://www.medschools.ac.uk/for-students/applying-to-medical-school/medical-schools/ `VERIFY-ON-PAGE` |
| Total count | **Discrepant across sources:** 46 (36 England / 5 Scotland / 3 Wales / 2 NI — Wikipedia-derived snippet), 51 (theukcatpeople), 53 (myhsn.co.uk). MSC's own count was not captured. | Leads only — `NOT FOUND` on MSC page |
| GMC awarding bodies (full-approval route) | Aberdeen, Aston, Anglia Ruskin, Birmingham, Bristol, Buckingham, Cambridge, Cardiff, City St George's, Dundee, UEA, Edge Hill, Edinburgh, Exeter, Glasgow, Imperial, Keele, KCL, Lancashire (UCLan), Lancaster, Leeds, Leicester, Liverpool, London, Manchester, Newcastle, Nottingham, Oxford, Plymouth, QMUL, QUB, Sheffield, Southampton, Sunderland, Swansea, UCL, Ulster, Warwick, BSMS, KMMS, ScotGEM, HYMS | https://www.gmc-uk.org/.../list-of-awarding-bodies-and-new-schools `VERIFY-ON-PAGE` |
| GMC new schools/programmes under review | Anglia Ruskin (MD apprenticeship), Bangor (North Wales), Brunel, Hertfordshire, Imperial/Pears Cumbria, KCL GEP at Portsmouth, St Mary's Twickenham, Chester, **University of Greater Manchester**, Lincoln, St Andrews (ScotCOM), Surrey, Worcester (Three Counties) | https://www.gmc-uk.org/.../new-schools-and-programmes-under-review `VERIFY-ON-PAGE` |
| Wolverhampton / Black Country | Announced as planned by Black Country ICS; not on either GMC list snippet; lead says first intake 2027/8 | https://www.blackcountryics.org.uk/news-and-documents/latest-news/new-medical-school-planned-wolverhampton `VERIFY-ON-PAGE` |

**Reconciled working list (53 records):** 40 England, 6 Scotland (incl. ScotGEM as a programme record), 3 Wales, 2 NI, plus St Mary's and Wolverhampton as pre-opening entries. INFERENCE: the MSC "51" figure most likely counts Hertfordshire/Surrey/Brunel/Chester/Greater Manchester-type new schools but not Wolverhampton or St Mary's; confirm on the members page.

## 3. Cross-cutting facts

- UCAT 2026 is required for 2027 entry (or deferred 2028) at UCAT Consortium universities. Consortium snippet named: Aberdeen, Anglia Ruskin, Aston, Birmingham, BSMS, Bristol, Brunel, Cambridge, Cardiff, Chester, Dundee, Edge Hill, Edinburgh, Exeter, Glasgow, Hertfordshire, HYMS, Imperial, Keele, KMMS, KCL, Lancaster, Leicester, Liverpool, Manchester, Newcastle, Nottingham, Oxford, Plymouth, QMUL, Sheffield, Southampton, St Andrews, Sunderland, UCL, Warwick, Worcester. — https://www.ucat.ac.uk/about-ucat/universities/ `VERIFY-ON-PAGE`. (Leeds, Cardiff-GEM, Swansea, QUB, Ulster, Lancashire, Buckingham, Greater Manchester were *not* named in the captured snippet — treat as `NOT FOUND`, not "no UCAT".)
- Cap on international places: leads (theukcatpeople; Brunel FAQ snippet) describe a government limit of roughly 7.5% of each school's intake, ~500 international medicine places nationally vs >7,000 home. `lead (non-authoritative)` — confirm against OfS/DHSC guidance before publishing.
- MSC international-applicants page exists but gives no school-by-school list; it defers to each university. https://www.medschools.ac.uk/for-students/applying-to-medical-school/international-applicants/ `VERIFY-ON-PAGE`

## 4. Full table

Legend: **Intl** = international students accepted; **#** = published international places; **Test**; **Int.** = interview; **Route**; **Len** = years. `—` = NOT FOUND IN THIS SESSION. `(L)` = lead source only. All values `VERIFY-ON-PAGE`; source URLs are in `schools.json`.

| # | School (University) | Nation | UG course / UCAS | Len | GEM | Intl | # intl | Test | Int. | Route | Flag |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Anglia Ruskin | Eng | MBChB A100 | 5 (L) | — | **No** | 0 | UCAT | MMI (L) | UCAS | home-only |
| 2 | Aston | Eng | MBChB A100 | 5 | — | Yes | 30 | UCAT (intl ranked on UCAT only from 2027) | MMI online 7×7min | UCAS | |
| 3 | Barts (QMUL) | Eng | MBBS A100 | 5 | — | Yes | up to 24 (311 home) | UCAT ≥4th decile | Online panel (2 staff ± lay) | UCAS | |
| 4 | Birmingham | Eng | MBChB A100 | 5 | — | Yes | up to 28 | UCAT | — | UCAS | |
| 5 | Brighton & Sussex (BSMS) | Eng | BM BS A100 | 5 | — | Yes | 10 (~197 UK) | UCAT, SJT 1–3 | MMI Dec–Feb, in person or Zoom | UCAS | fee £49,000 (L) |
| 6 | Bristol | Eng | MB ChB A100 | — | — | Yes | 30 (~220 home) | UCAT | — | UCAS | |
| 7 | Brunel | Eng | MBBS A100 (B84) | — | — | Yes | — | Home: UCAT/GAMSAT; **intl: none** | MMI online async | UCAS **or direct** (to 30 Jun 2027) | fee £49,395 (26/27); GMC review |
| 8 | Buckingham | Eng | MB ChB (L) | 4.5 (L) | — | Yes (L) | — (~40–50% cohort, L) | **None** (L) | — | UCAS or direct (L) | no official page captured |
| 9 | Cambridge | Eng | MB BChir A100 | — | — | Yes | 22 (~270 total) | UCAT (no SJT) | Interviews 7–18 Dec 2026, in Cambridge | UCAS | |
| 10 | Chester | Eng | GEM MBChB **A101** | 4 | GEM-only | Yes | ~25 of ~80 (L) | UCAT / GAMSAT / MCAT | — | UCAS | graduates only; GMC review |
| 11 | Edge Hill | Eng | MBChB A100 (+A110) | 5 (L) | — | **No** | 0 | UCAT | MMI (L) | UCAS | home-only |
| 12 | East Anglia (Norwich) | Eng | MBBS A100 | 5 | Yes | Yes | 13 (subject to OfS) | UCAT, no cut-off | MMI in person | UCAS | |
| 13 | Exeter | Eng | BMBS A100 | — | — | Yes | 10 | UCAT (75/25 weighting) | MMI Dec–Mar | UCAS | |
| 14 | Hull York (HYMS) | Eng | MB BS A100 | — | — | Yes | 11 (220 home) | UCAT points + GCSEs | Graded stations + group; overseas Zoom Dec | UCAS | IELTS 7.5 (7.0 min) |
| 15 | Imperial | Eng | MBBS/BSc A100 | — | Yes (A102 at Pears Cumbria) | Yes | **74** (271 home) | UCAT; overseas threshold 2310 (2026) | Async + live MMI 7×5min | UCAS | |
| 16 | Keele | Eng | MBChB A100 (+A104) | — | — | Yes | up to 10 of 171 | UCAT ≥1950 + SJT 1–3 for intl | MMI | UCAS | |
| 17 | Kent & Medway (KMMS) | Eng | BM BS A100 (K31) | 5 | — | Yes | — (~110 total) | UCAT | MMI 6×7min + group | UCAS | fee £49,700 |
| 18 | King's College London | Eng | MBBS A100 (L) | — | Yes (GEP Portsmouth, GMC review) | Yes (L) | — | UCAT | MMI 7 stations, Teams (L) | UCAS | no kcl.ac.uk page captured |
| 19 | Lancaster | Eng | — | — | — | — | — | UCAT | — | — | not researched |
| 20 | Leeds | Eng | MBChB A100 (+I900) | — | — | Yes | 24 (~250 home) | — | — | UCAS | |
| 21 | Leicester | Eng | MBChB A100 | — | — | Yes (L) | — | UCAT | — | UCAS | 2027 req. page exists |
| 22 | Lincoln (w/ Nottingham) | Eng | — | — | — | **No (L)** | — | — | — | — | home-only (L); GMC review |
| 23 | Liverpool | Eng | MBChB A100 | 5 | — | Yes | 23 of 315 | UCAT | — | UCAS | E2027 supplement PDF |
| 24 | Manchester | Eng | MBChB A100 | — | Yes | Yes | 30 (L) (~390 home) | UCAT | — | UCAS | 2027 course page exists |
| 25 | **Greater Manchester** (ex-Bolton) | Eng | MBChB (no UCAS code found) | 5 | — | **Yes – intl only (2026)** | — | UCAT from Sept 2028 cohort; 2027 — | Online mini-interviews + tests | **Direct** | fee £45,000; GMC review |
| 26 | Newcastle | Eng | MB BS A100 | — | — | — | — | UCAT | — | UCAS | not researched |
| 27 | Nottingham | Eng | BMBS | — | — | — | — | UCAT | — | UCAS | not researched |
| 28 | Oxford | Eng | — | — | — | — | — | UCAT | — | — | not researched |
| 29 | Plymouth (Peninsula) | Eng | — | — | — | — | — | UCAT | — | — | not researched |
| 30 | Sheffield | Eng | — | — | — | — | — | UCAT | — | — | not researched |
| 31 | Southampton | Eng | — | — | — | Yes (L) | — | UCAT | — | — | not researched |
| 32 | City St George's | Eng | — | — | — | — | — | — | — | — | not researched |
| 33 | Sunderland | Eng | — | 5 (L) | — | **No (L)** | — | UCAT | — | — | home-only (L) |
| 34 | UCL | Eng | MBBS | — | — | — | — | UCAT | — | UCAS | 2027 req. page exists |
| 35 | Lancashire (UCLan) | Eng | MBBS | 5 (L) | — | Yes | — | UK: UCAT; **intl: none** (L) | — | UCAS **or direct** | fee £49,950 (L); £15k deposit (L) |
| 36 | Warwick | Eng | — | — | — (GEM not confirmed) | — | — | UCAT | — | — | not researched |
| 37 | Worcester (Three Counties) | Eng | GEM MBChB **A101** | 4 | GEM-only | Yes | — | UCAT / GAMSAT / MCAT | — | UCAS | graduates only; GMC review |
| 38 | Pears Cumbria (Imperial) | Eng | GEM MBBS **A102** | 4 | GEM-only | **No** (no Student-visa applicants) | 0 | GAMSAT or UCAT | — | UCAS | home-only; GMC review |
| 39 | Hertfordshire | Eng | MBBS (from Sept 2026) | — | — | — | — | UCAT | — | — | GMC review |
| 40 | Surrey | Eng | — | — | Yes (implied) | — | — | — | — | — | GMC review |
| 41 | St Mary's Twickenham | Eng | — | — | — | — | — | — | — | — | GMC review; planning |
| 42 | Wolverhampton (Black Country) | Eng | — | — | — | — | — | — | — | — | planned; not on GMC lists |
| 43 | Aberdeen | Scot | — | — | — | — | — | UCAT | — | — | not researched |
| 44 | Dundee | Scot | — | — | — | — | — | UCAT | — | — | not researched |
| 45 | Edinburgh | Scot | — | — | — | — | — | UCAT | — | — | not researched |
| 46 | Glasgow | Scot | — | — | — | — | — | UCAT | — | — | not researched |
| 47 | St Andrews | Scot | Medicine BSc A100 | — | — | — | — | UCAT | — | — | pre-clinical BSc |
| 48 | ScotGEM (St Andrews/Dundee) | Scot | GEM | — | GEM-only | — | — | — | — | — | programme record |
| 49 | Cardiff | Wales | MBBCh A100 | — | — | Yes | 22 (L) (~300 home) | UCAT | — | UCAS | |
| 50 | Swansea | Wales | — | — | — | — | — | — | — | — | not researched |
| 51 | Bangor (North Wales) | Wales | — | — | — | **No (L)** | — | — | — | — | home-only (L); GMC review |
| 52 | Queen's Belfast | NI | — | — | — | — | — | — | — | — | not researched |
| 53 | Ulster | NI | — | — | — | — | — | — | — | — | not researched |

## 5. Deep dive — University of Greater Manchester Medical School

Treat the university's own pages as authoritative. **No partnership with any other institution is stated or implied.**

| Field | Finding | Status | Source |
|---|---|---|---|
| Identity | University of Greater Manchester (formerly University of Bolton). GMC lists "University of Greater Manchester School of Medicine"; the school's site uses "University of Greater Manchester Medical School" | VERIFY-ON-PAGE | https://medicine.greatermanchester.ac.uk/undergraduate-medical-school/ ; GMC under-review list |
| Course / length | MBChB Medicine, full-time **5 years** | VERIFY-ON-PAGE | https://greatermanchester.ac.uk/course/mbchb-medicine-full-time-5-years-university-of-greater-manchester/2026-27 |
| Who may apply | **2026 entry: international applicants only; Home (UK) applications not accepted** | VERIFY-ON-PAGE | https://medicine.greatermanchester.ac.uk/undergraduate-medical-school/ |
| International fee | **£45,000 per year; £225,000 over five years** | VERIFY-ON-PAGE | https://medicine.greatermanchester.ac.uk/mbchb-medicine-programme-details/ |
| Academic entry (2026) | A-level **AAB**: one of Chemistry/Biology + one of Chemistry/Biology/Physics/Maths + any third. **IB 34** with 6,6,5 HL incl. Chem or Bio and one of Bio/Chem/Phys/Maths. Standard XII (ISC/CBSE) 80% incl. 80% in two sciences. **AP 4,4,4** incl. Bio or Chem. Must be **18 by 1 September** of enrolment year | VERIFY-ON-PAGE | https://medicine.greatermanchester.ac.uk/medicine-mbchb-2026-academic-requirements/ |
| Nigerian qualifications (WAEC/NECO/JAMB) | Not surfaced | NOT FOUND IN THIS SESSION | — |
| Admissions test | **UCAT required for the September 2028 cohort onwards**; site says requirements may differ for earlier cohorts. 2027-cohort requirement not captured | VERIFY-ON-PAGE / 2027: NOT FOUND | https://medicine.greatermanchester.ac.uk/undergraduate-medical-school/ |
| Interview | Shortlisted applicants attend an **online/virtual interview comprising a series of mini-interviews and tests** (MMI-style) | VERIFY-ON-PAGE | 2026 academic requirements page |
| Application route | **Direct** via the medical school's registration portal; no UCAS listing found (lead sites label it "A100" but no UCAS record surfaced) | VERIFY-ON-PAGE | https://medicine.greatermanchester.ac.uk/undergraduate-medical-school/register/ |
| GMC status | On GMC "new schools and programmes under review" list; university states programme is "subject to the GMC's quality assurance process" (i.e., not yet fully approved) | VERIFY-ON-PAGE | GMC under-review list; programme details page |
| First intake year | 2026 is the earliest entry year referenced ("For 2026 entry…") | **INFERENCE** — not stated as "first intake" in captured text | undergraduate-medical-school page |
| English requirement | Site has an "International Language Qualifications" section; threshold (IELTS etc.) not surfaced | NOT FOUND IN THIS SESSION | — |
| Contact | medicineadmissions@greatermanchester.ac.uk | VERIFY-ON-PAGE | site snippet |

**RECOMMENDATION:** this is the single most important school to verify by hand (open all four URLs above) because it is international-only, direct-application and carries a published fee — exactly the profile a Nigerian self-funded applicant will ask about — but it is also *not yet fully GMC-approved*. Any page about it must state the GMC status plainly and must not describe it as a route to UK registration until the GMC lists it as an awarding body.

## 6. Schools that do NOT accept international undergraduates (as found)

Officially sourced (`VERIFY-ON-PAGE`):
- **Anglia Ruskin** — MSC course listing: "UK only"; home fee status required at application. https://www.medschools.ac.uk/courses/anglia-ruskin-medicine-a100/
- **Edge Hill** — MSC course listing: "UK only". https://www.medschools.ac.uk/courses/edge-hill-medicine-a100/
- **Pears Cumbria (Imperial GEM A102)** — UCAS: "not accepting applications from students requiring a Student visa". https://www.ucas.com/explore/courses/57772005-274b-4cb3-a141-e51d2290535e/medicine-graduate-entry-pears-cumbria-school-of-medicine?studyYear=2027

Lead-only (prep site; **must be confirmed officially before publication**):
- **Sunderland**, **Lincoln**, **Bangor (North Wales)** — listed with the three above as "six home-only schools". https://www.theukcatpeople.co.uk/post/uk-medical-school-tuition-fees-for-international-students-to-study-medicine

Reverse case — **home applicants not accepted:** University of Greater Manchester (2026 entry, international-only).

## 7. Relevance for Nigerian international applicants (INFERENCE)

Reasoning criteria: accepts international (officially sourced) ∧ publishes an international allocation or fee ∧ clear test/route. Ordered by strength of evidence in this session, not by quality.

**Tier A — strong, officially sourced international route with a published number of places**
Imperial (74 — by far the largest), Aston (30), Bristol (30), Birmingham (up to 28), Barts/QMUL (up to 24), Leeds (24), Liverpool (23), Cambridge (22), UEA (13), HYMS (11), BSMS (10), Exeter (10), Keele (up to 10). All UCAT; all UCAS. Reason: a published allocation lets us show realistic odds (e.g., Barts ~23 applicants per interview; Exeter ~28.8 applicants per intl place — leads).

**Tier B — officially accepts international, allocation not captured**
KMMS (fee £49,700 published), Manchester, Cardiff (22 — lead figure), Leicester, Southampton (both named as lower-fee by a lead), KCL (lead), UCL/Newcastle/Nottingham/Sheffield/Oxford/Scottish schools (not researched — likely relevant but unverified).

**Tier C — alternative-route schools especially pertinent to Nigerian self-funders**
- **Brunel**: no UCAT for international applicants; UCAS *or direct* application to 30 June 2027; fee published. GMC approval still under review — must be disclosed.
- **University of Lancashire (UCLan)**: no UCAT for international (lead); UCAS or direct; fee £49,950 (lead); £15k deposit (lead). GMC-listed awarding body.
- **Buckingham**: no UCAT; UCAS or direct; 4.5 years; large international share (all leads — no official page captured).
- **University of Greater Manchester**: international-only, direct, £45,000; GMC review pending (see §5).
INFERENCE: these four answer the common Nigerian query "missed the 15 October deadline / no UCAT — can I still apply?", and therefore deserve dedicated pages, each carrying an explicit GMC-status line.

**Tier D — graduate-entry only (relevant to Nigerian degree-holders, not school leavers)**
Chester (A101; UCAT/GAMSAT/MCAT; ~25 intl places — lead), Worcester Three Counties (A101; UCAT/GAMSAT/MCAT; open to intl), Warwick/Swansea/Ulster/ScotGEM/Surrey (not confirmed this session).

**Exclude from international content:** Anglia Ruskin, Edge Hill, Pears Cumbria (official); Sunderland, Lincoln, Bangor (lead — verify, then exclude or include).

## 8. Gaps list (priority order for the next research pass)

1. **20 un-researched schools** (table rows marked "not researched") — need international acceptance, places, test, interview, course URL. Highest value: Oxford, UCL, Newcastle, Nottingham, Sheffield, Southampton, Edinburgh, Glasgow, Aberdeen, Dundee, St Andrews, QUB, City St George's, Lancaster, Plymouth.
2. **Course length** for 18 schools where the snippet did not state it (never assume 5 or 6).
3. **Interview format** missing for Birmingham, Bristol, Leeds, Leicester, Liverpool, Manchester, Cardiff, Chester, Worcester, UCLan, Buckingham.
4. **International fee** captured for only 7 schools (BSMS, Brunel, KMMS, UCLan, Greater Manchester + Buckingham/ARU as leads); most 2027/28 fees are published Oct 2026–spring 2027.
5. **English-language thresholds** captured only for HYMS (IELTS 7.5/7.0). Needed for every Tier A–C school.
6. **MSC's own membership count** and confirmation of membership for Greater Manchester, Wolverhampton, St Mary's.
7. **Greater Manchester**: UCAT requirement for the 2027 cohort; IELTS; whether home applicants are admitted from 2027; GMC progress; Nigerian qualification equivalence.
8. **Home-only status** of Sunderland, Lincoln, Bangor from official pages.
9. **Graduate-entry flags** (A101/A102) for schools where GEM is widely known but not sourced here: Barts, Cambridge, Southampton, Warwick, Swansea, Ulster, Newcastle, Nottingham, Oxford, KCL, Cardiff, Sheffield, Liverpool, Birmingham.
10. Whether Worcester's `worc.ac.uk/courses/medicine-mbchb` URL is a 5-year A100 route.

## 9. Source register (official / regulator unless marked lead)

- MSC members: https://www.medschools.ac.uk/about-us/our-members/
- MSC medical schools (students): https://www.medschools.ac.uk/for-students/applying-to-medical-school/medical-schools/
- MSC international applicants: https://www.medschools.ac.uk/for-students/applying-to-medical-school/international-applicants/
- GMC awarding bodies: https://www.gmc-uk.org/education/how-we-quality-assure-education-and-training/approving-education-and-training/institutions-awarding-uk-medical-degrees/list-of-awarding-bodies-and-new-schools
- GMC under review: https://www.gmc-uk.org/education/how-we-quality-assure-education-and-training/approving-education-and-training/institutions-awarding-uk-medical-degrees/new-schools-and-programmes-under-review
- UCAT consortium universities: https://www.ucat.ac.uk/about-ucat/universities/
- Per-school official URLs: see `source` fields in `data/medical-schools/schools.json`.
- Lead sources used (non-authoritative): theukcatpeople.co.uk (fees article; rankings; per-school guides), bluepeanut.com, medichut.com, themedicportal.com, nextgenmedprep.com, myhsn.co.uk, Wikipedia.
