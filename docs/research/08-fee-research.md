# 08 — UK Medicine fee and total-cost evidence base for Nigerian students

**Cycle:** 2027 entry · **Fee years:** 2026/27 (current) and 2027/28 where published · **Researched:** 2026-10-03 · **Status:** DRAFT — every figure flagged `VERIFY-ON-PAGE` before publication.

## 1. Methodology

- Tooling constraints: WebSearch only (extended mode); WebFetch, curl and Semrush were not permitted. **The shared session search budget was exhausted after 37 searches**, so Parts 2–4 below (visa, IHS, UCAT, living costs, funding) could not be searched at all and are recorded as `NOT FOUND IN THIS SESSION`.
- Each university was searched once with a query naming the course code and "2026/27 international tuition fee". Figures were accepted as **OFFICIAL** only when a `*.ac.uk` fee/course page was returned in the result list and the figure was attributed to it. When the only carrier of the number was a prep/agent site (theukcatpeople.co.uk, collegedunia, shiksha, TopUniversities, etc.) the row is marked **LEAD (agent/prep site)** and must be re-checked on the university page.
- Status vocabulary used throughout: `OFFICIAL`, `LEAD (agent/prep site)`, `CONFLICTING - VERIFY`, `OFFICIAL - FEE YEAR UNCONFIRMED`, `NOT OPEN TO INTERNATIONAL`, `NOT FOUND IN THIS SESSION`, `NOT PUBLISHED`.
- Label vocabulary for numbers: **Official university fee** (published by the institution), **Approximate market range** (our aggregation of official fees), **Estimate** (living costs and the like).
- No figure in this document was generated from memory. Where a number is unknown it says so.
- Companion dataset: `data/medical-schools/fees.json` (44 records).

## 2. International tuition fees — Medicine (A100 or equivalent)

Count: **44 schools reviewed · 33 with a findable international fee (29 official-sourced, 4 agent-sourced leads; QMUL conflicting) · 3 closed to international applicants · 8 not reached before the search budget ran out.**

### 2a. Schools open to international applicants — fee found

| University | Course | International fee (annual, £) | Fee year | Clinical years differ? | Increase policy stated | Source | Status |
|---|---|---|---|---|---|---|---|
| Oxford | A100 BM BCh, 6 yrs | 49,400 (yrs 1–3) / **65,250** (yrs 4–6) | 2026/27 | Yes | Not captured. Quota: max 14 overseas Medicine students/yr | https://www.medsci.ox.ac.uk/study/medicine/fees-and-funding | OFFICIAL · VERIFY-ON-PAGE |
| Cambridge | A100 MB BChir, 6 yrs | **70,554** + College fee (lead: ~11,500–14,950) | 2026/27 | No | Not captured | https://www.undergraduate.study.cam.ac.uk/files/publications/undergraduate_tuition_fees_2026-27.pdf | OFFICIAL (tuition) / LEAD (college fee) · VERIFY-ON-PAGE |
| Imperial | A100 MBBS/BSc, 6 yrs | 58,600 | 2026/27 | No | Not captured; ~74 overseas places | https://www.theukcatpeople.co.uk/post/uk-medical-school-tuition-fees-for-international-students-to-study-medicine | LEAD (agent/prep) · VERIFY at imperial.ac.uk |
| UCL | A100 MBBS BSc, 6 yrs | 57,300 (instalment pattern reported 2×39,200 + 3×69,367) | 2026/27 | Yes (by instalment pattern) | Annual increase ≤ RPI-X; Medicine excluded from fee guarantee; 2027/28 NOT PUBLISHED | https://www.ucl.ac.uk/prospective-students/undergraduate/degrees/medicine-mbbs-bsc-2026 | OFFICIAL · VERIFY-ON-PAGE |
| King's College London | A100 MBBS, 5 yrs | 56,800 | 2026/27 | No | Subject to annual increases; £5,000 deposit (lead) | theukcatpeople (above) | LEAD (agent/prep) · VERIFY at kcl.ac.uk |
| Queen Mary (Barts) | A100 MBBS, 5 yrs | **49,950 or 53,950 — CONFLICTING** | 2026/27 | No | 2027/28 not yet set (QMUL statement) | https://www.qmul.ac.uk/registry-services/media/arcs/student-enquiry-centre/UG-2526-Schedule-(1).pdf (2025/26 only) | CONFLICTING - VERIFY |
| Manchester | A106 MBChB, 5 yrs | 39,900 (yrs 1–2) / **60,900** (yrs 3–5) | 2026/27 | Yes | Clinical fee set at rate applicable on entering Year 3 | https://www.manchester.ac.uk/study/undergraduate/courses/2027/01428/mbchb-medicine/ | OFFICIAL · VERIFY-ON-PAGE |
| Leeds | A100 MBChB, 5 yrs | 50,500 | **2027/28** (Sept 2027 start) | No | Fixed for duration of course | https://courses.leeds.ac.uk/5580/medicine-and-surgery-mbchb | OFFICIAL · VERIFY-ON-PAGE |
| Newcastle | A100 MB BS, 5 yrs | 47,000 · **2027/28 Yr 1: 48,600** | 2026/27 & 2027/28 | No | Not captured | https://www.ncl.ac.uk/undergraduate/degrees/a100/ | OFFICIAL · VERIFY-ON-PAGE |
| Liverpool | A100 MBChB, 5 yrs | 50,000 (year abroad China: 25,000) | 2026/27 | No | "Subject to change for 2027/28; may increase each year" | https://www.liverpool.ac.uk/courses/medicine-and-surgery-mbchb | OFFICIAL · VERIFY-ON-PAGE |
| Birmingham | A100 MBChB, 5 yrs | 30,330 (yrs 1–2) / 48,660 (yrs 3–5) | **UNCLEAR** | Yes | Not captured | https://www.birmingham.ac.uk/study/undergraduate/subjects/medicine-courses/medicine-and-surgery-mbchb | OFFICIAL - FEE YEAR UNCONFIRMED |
| Bristol | A100 MBChB, 5 yrs | 45,800 | 2026/27 | No | Not captured | https://www.bristol.ac.uk/study/undergraduate/fees-funding/ (landing page only) | LEAD (agent/prep) · VERIFY |
| Nottingham | A100 BMBS, 5 yrs | 47,000 (yrs 1–2) / **58,500** (yrs 3–5) | 2026/27 | Yes | Clinical rate rose 56,400 → 58,500 (25/26 → 26/27) | https://www.nottingham.ac.uk/fees/tuitionfees/202627/undergraduate.aspx | OFFICIAL · VERIFY-ON-PAGE |
| Leicester | A100 MBChB, 5 yrs | **30,150** (yrs 1–2) / 48,900 (yrs 3–5) | 2026/27 | Yes | Clinical fees confirmed nearer the time | https://le.ac.uk/study/undergraduates/fees-funding/tuition-fees | OFFICIAL · VERIFY-ON-PAGE |
| Southampton | A100 BM5, 5 yrs | 35,900 (non-clinical) / **64,900** (yrs 3–5) | **UNCLEAR** | Yes | Overseas fees increase every year for starts from 1 Aug 2026 | https://www.southampton.ac.uk/courses/medicine-bm5-degree-bmbs | OFFICIAL - FEE YEAR UNCONFIRMED |
| Exeter | A100 BMBS, 5 yrs | 48,900 | 2026/27 | No | Not captured | https://www.exeter.ac.uk/undergraduate-degrees/fees/ | OFFICIAL · VERIFY-ON-PAGE |
| Plymouth | A100 BMBS, 5 yrs | **41,920** | 2026/27 | No | Fee waiver up to £6,000 reported (eligibility for BMBS unconfirmed) | https://www.plymouth.ac.uk/courses/undergraduate/bmbs-bachelor-of-medicine-bachelor-of-surgery | OFFICIAL · VERIFY-ON-PAGE |
| Keele | A100 MBChB, 5 yrs | 46,700 | 2026/27 | No | Outside standard fee bands | https://www.keele.ac.uk/study/undergraduate/tuitionfeesandfunding/undergraduatetuitionfees/undergraduatetuitionfeesinternationalstudents/ | OFFICIAL · VERIFY-ON-PAGE |
| Lancaster | A100 MBChB, 5 yrs | 48,620 (+ £40 college fee) | 2026/27 | No | Reviewed annually, not fixed | https://www.lancaster.ac.uk/study/undergraduate/courses/medicine-and-surgery-mbchb-a100/2026/ | OFFICIAL · VERIFY-ON-PAGE |
| Hull York (HYMS) | A100 MB BS, 5 yrs | 49,750 | 2026/27 | No | CPI-linked increases, max 10%/yr | https://www.hyms.ac.uk/medicine/applying/fees-and-funding | OFFICIAL · VERIFY-ON-PAGE |
| UEA | A100 MBBS, 5 yrs | 47,500 (£4,000 refundable deposit, lead) | 2026/27 | No | Continuing increases capped 4% (lead) | theukcatpeople (above) | LEAD (agent/prep) · VERIFY at uea.ac.uk |
| Warwick (GRAD ENTRY) | A101 MB ChB, 4 yrs | 32,510 (yr 1) / 56,660 (yrs 2–4) | 2026/27 | Yes | Inflationary uplift possible | https://warwick.ac.uk/services/finance/studentfinance/fees/overseasfees/ | OFFICIAL · VERIFY-ON-PAGE |
| Swansea (GRAD ENTRY) | A101 MB BCh, 4 yrs | 48,350 · **Sept 2027: 50,750** | 2026/27 & 2027/28 | No | +3% each subsequent year | https://www.swansea.ac.uk/undergraduate/courses/medicine/medicine-graduate-entry-mbbch/ | OFFICIAL · VERIFY-ON-PAGE |
| Cardiff | A100 MBBCh, 5 yrs | 47,450 | 2026/27 | No | Stated same for all five years | https://www.cardiff.ac.uk/study/undergraduate/courses/2026/medicine-mbbch | OFFICIAL · VERIFY-ON-PAGE |
| Edinburgh | A100 MBChB, 6 yrs | 54,650 (incl. ACT levy) | 2026/27 | No | Not captured | https://registryservices.ed.ac.uk/tuition-fees/find/undergraduate/2026-2027/medicine | OFFICIAL · VERIFY-ON-PAGE |
| Glasgow | A100 MBChB, 5 yrs | **62,730** | 2026/27 | No | Not captured | https://www.gla.ac.uk/myglasgow/students/payingyourfees/undergraduate/2026-27/ | OFFICIAL · VERIFY-ON-PAGE |
| Aberdeen | A100 MBChB, 5 yrs | 50,100 (incl. national levy) | 2026/27 | No | Not captured | https://www.abdn.ac.uk/media/site/students/documents/UG--Full-time-Tuition-Fees-2026-27.pdf | OFFICIAL · VERIFY-ON-PAGE |
| Dundee | A100 MBChB, 5 yrs | 55,900 (= 45,900 + 10,000 ACT levy) | 2026/27 | No | Not captured | https://www.dundee.ac.uk/undergraduate/medicine/fees-and-funding | OFFICIAL · VERIFY-ON-PAGE |
| St Andrews | A100 BSc, 3 yrs → Manchester clinical | 39,620 (St Andrews yrs 1–3; incl. levy); then Manchester clinical rate (60,900 in 2026/27) | 2026/27 | Yes (different institution) | Adjusted annually, typically 3–5% | https://www.st-andrews.ac.uk/study/undergraduate/fees/world/ | OFFICIAL · VERIFY-ON-PAGE |
| Queen's Belfast | A100 MB BCh BAO, 5 yrs | ~36,900 + **clinical placement levy 12,133** each year (≈49,033 effective) | 2026/27 | No | Not captured | https://www.qub.ac.uk/Study/international-students/tuition-fees/fees-26-27/ | OFFICIAL · VERIFY-ON-PAGE |
| Ulster (GRAD ENTRY) | MBBS, 4 yrs | 39,630 + clinical placement levy 12,133 (≈51,763 effective) | 2026/27 (2027/28 page exists, figure not captured) | No | Not captured | https://www.ulster.ac.uk/student/fees/tuition-fees/international/tuition-fees-202627-international-and-eu-excluding-roi | OFFICIAL · VERIFY-ON-PAGE |
| Kent & Medway (KMMS) | A100 BM BS, 5 yrs | 49,700 | 2026/27 | No | Not captured | https://kmms.ac.uk/study/funding-your-degree/ | OFFICIAL (page captured; confirm figure) |
| Aston | A100 MBChB, 5 yrs | 47,000 ("fixed for duration", lead) | 2026/27 | No | Fixed-fee claim unverified | https://www.topuniversities.com/universities/aston-university/undergrad/medicine-mbchb | LEAD (agent/prep) · VERIFY at aston.ac.uk |

### 2b. Not open to international applicants (2026/27 or 2027/28)

| University | Evidence | Source |
|---|---|---|
| Sunderland | "Unable to accept international fee-paying applicants for MBChB" | https://www.sunderland.ac.uk/undergraduate/mbchb-medicine |
| Lincoln | Not open to international applications for 2026-27 or 2027-28; intl fee "TBC" | https://www.lincoln.ac.uk/course/mdcmbcub/ |
| Anglia Ruskin | Home fee status required at point of application; MSC lists as UK only | https://www.aru.ac.uk/study/tuition-fees |

### 2c. Not found in this session

| University | Note |
|---|---|
| Sheffield | Official overseas page gives £25,000–£32,100 *excluding* Medicine/Dentistry; Medicine figure not captured. Check https://sheffield.ac.uk/undergraduate/courses/2027/medicine-mbchb |
| Edge Hill, Buckingham, UCLan, Brunel, Chester, Greater Manchester (ex-Bolton), Wolverhampton (Black Country) | Search budget exhausted before these were searched. UCLan and Brunel are the highest-priority follow-ups because both actively recruit international medicine students. |

### 2d. Approximate market range (OUR AGGREGATION of the official figures above)

- Lowest annual figure found: **£30,150** (Leicester, pre-clinical yrs 1–2, 2026/27). Lowest *flat* full-course fee: **£41,920** (Plymouth, 2026/27).
- Highest annual figure found: **£70,554** (Cambridge, 2026/27, before College fee). Highest 5-year-course figure: **£62,730** (Glasgow); highest clinical-phase figure on a 5-year course: **£64,900** (Southampton, year unconfirmed).
- Typical band for a 5-year A100 at an established English/Welsh school: **≈£46,000–£50,500 per year** (Keele, Newcastle, Cardiff, Nottingham yrs 1–2, Aston*, UEA*, Lancaster, Exeter, HYMS, KMMS, Liverpool, Aberdeen, Leeds). *= lead.
- Scottish and NI schools embed or add an NHS teaching levy (Dundee £10,000; QUB/Ulster £12,133) — compare *effective* annual cost, not headline tuition.

## 3. Other compulsory / near-compulsory costs

**All items below: `NOT FOUND IN THIS SESSION`.** The shared WebSearch budget was exhausted before any of these could be searched; no figure is given because none was verified. The official source to check is listed for each so the next session can complete the table quickly.

| Item | Figure | Fee year | Official source to verify | Status |
|---|---|---|---|---|
| UK Student visa application fee (applying from outside UK) | — | 2026 | https://www.gov.uk/student-visa | NOT FOUND IN THIS SESSION |
| Immigration Health Surcharge (student rate, per year) | — | 2026 | https://www.gov.uk/healthcare-immigration-application/how-much-pay | NOT FOUND IN THIS SESSION |
| UKVI maintenance requirement per month — London | — | 2026 | https://www.gov.uk/student-visa/money | NOT FOUND IN THIS SESSION |
| UKVI maintenance requirement per month — outside London | — | 2026 | https://www.gov.uk/student-visa/money | NOT FOUND IN THIS SESSION |
| UCAT 2026 test fee — UK | — | 2026 | https://www.ucat.ac.uk/ucat/registration-booking/test-fees-bursaries/ | NOT FOUND IN THIS SESSION |
| UCAT 2026 test fee — outside UK (incl. Nigeria) | — | 2026 | https://www.ucat.ac.uk/ucat/registration-booking/test-fees-bursaries/ | NOT FOUND IN THIS SESSION |
| TB test requirement for Nigerian applicants | Nigeria is on the UK TB-testing country list (requirement, not a figure); clinic fee set by IOM-approved clinic in Nigeria | 2026 | https://www.gov.uk/tb-test-visa ; https://www.gov.uk/guidance/tuberculosis-test-for-a-uk-visa-clinics-in-nigeria | REQUIREMENT CONFIRMED FROM PRIOR POLICY KNOWLEDGE; FEE NOT FOUND IN THIS SESSION — VERIFY-ON-PAGE |
| English test (IELTS for UKVI / others) | — | 2026 | https://ielts.org/ ; university English requirement pages | NOT FOUND IN THIS SESSION |
| University deposit for international offer holders | Leads only: KCL £5,000 (offset against yr 1 fees); UEA £4,000 refundable; Liverpool has a deposit page (amount not captured) | 2026/27 | https://www.liverpool.ac.uk/feespayment/tuition-fee-deposits/ ; kcl.ac.uk ; uea.ac.uk | LEAD (agent/prep) · VERIFY-ON-PAGE |
| UCAS application fee (2027 entry) | — | 2027 entry | https://www.ucas.com/undergraduate/applying-university/how-apply-undergraduate-courses | NOT FOUND IN THIS SESSION |
| Other university-published direct costs | Leeds: criminal record check (variable for overseas) + elective travel/insurance; Lancaster: £40 college fee | 2026/27–2027/28 | Leeds / Lancaster course pages (Section 2) | OFFICIAL · VERIFY-ON-PAGE |

## 4. Living costs (ESTIMATE)

`NOT FOUND IN THIS SESSION` — no university cost-of-living page or UKCISA page was searched before the budget ran out. Sources to use next session, in priority order:

1. UKVI maintenance minimum (https://www.gov.uk/student-visa/money) — the legal floor, London vs. outside London, 9 months.
2. UKCISA living-cost guidance (https://www.ukcisa.org.uk/).
3. University cost-of-living pages for the schools in Section 2a (e.g. Manchester, Leeds, Newcastle, Cardiff, Glasgow, Plymouth all publish annual estimates).

Any figure placed here must be labelled **ESTIMATE**.

## 5. Scholarships / funding for international medicine students

Only items that surfaced incidentally during the fee searches are listed. None was independently verified; none is a full-fee award.

| Scheme | What was captured | Source | Status |
|---|---|---|---|
| Hull York Medical School International Excellence Scholarships | Up to 4 awards; 5% of overseas fee each year of successful study; stated for 2025 entry | https://www.hyms.ac.uk/medicine/applying/fees-and-funding | OFFICIAL · VERIFY 2027 availability |
| Plymouth international fee waiver | Up to £6,000 (£4,000 yr 1, £1,000 yr 2, £1,000 final yr); whether BMBS is eligible NOT confirmed | https://www.plymouth.ac.uk/courses/undergraduate/bmbs-bachelor-of-medicine-bachelor-of-surgery | LEAD · VERIFY |
| Newcastle international scholarship (25% off) | Mentioned alongside the A100 page; Medicine eligibility NOT confirmed | https://www.ncl.ac.uk/undergraduate/degrees/a100/ | LEAD · VERIFY |
| Leicester international UG fee waiver | Appeared only on a scholarship-aggregator site; medicine inclusion unknown | (aggregator) | LEAD · VERIFY at le.ac.uk |

**Position to publish:** funding for international undergraduate medicine is genuinely limited. Reasons evidenced in this session: (i) overseas Medicine places are capped by government quota (Oxford: 14/yr; Imperial ~74/yr), so universities have no recruitment incentive to discount; (ii) several schools explicitly exclude Medicine from international fee guarantees or standard fee bands (UCL, Keele, Sheffield); (iii) the only awards found are 5%–25% partial discounts or small first-year waivers. Nigerian applicants should budget on the full fee. Chevening / Commonwealth schemes are postgraduate-only and therefore not applicable to A100 (prior knowledge — not searched this session).

## 6. Five-year total-cost illustration — OUR APPROXIMATE RANGE

Only tuition could be computed from verified figures. Fees are held at the 2026/27 rate for all five years (**no inflation applied** — real totals will be higher; HYMS allows up to 10%/yr, Swansea 3%/yr, St Andrews 3–5%/yr).

**A. Tuition only (official 2026/27 figures):**

| Scenario | Arithmetic | 5-year tuition |
|---|---|---|
| Low — Leicester (split fee) | 2 × 30,150 + 3 × 48,900 = 60,300 + 146,700 | **£207,000** |
| Low — Plymouth (flat) | 5 × 41,920 | £209,600 |
| Mid — Cardiff (flat) | 5 × 47,450 | £237,250 |
| Mid — Manchester (split) | 2 × 39,900 + 3 × 60,900 = 79,800 + 182,700 | £262,500 |
| Mid-high — Nottingham (split) | 2 × 47,000 + 3 × 58,500 = 94,000 + 175,500 | £269,500 |
| High — Dundee (incl. levy) | 5 × 55,900 | £279,500 |
| High — Glasgow (flat) | 5 × 62,730 | **£313,650** |
| 6-year reference — Edinburgh | 6 × 54,650 | £327,900 |
| 6-year reference — Oxford | 3 × 49,400 + 3 × 65,250 = 148,200 + 195,750 | £343,950 |
| 6-year reference — Cambridge (tuition only, excl. College fee) | 6 × 70,554 | £423,324 |

**OUR APPROXIMATE RANGE — 5-year tuition at 2026/27 rates: £207,000 – £313,650** (6-year programmes: £327,900 – £423,324+).

**B. Full cost (tuition + IHS + visa + living):** `CANNOT BE COMPUTED IN THIS SESSION` — IHS, visa and living-cost figures were not verified (Sections 3–4). Template for the next session once those are verified:

```
5-yr total = 5-yr tuition
           + visa fee (one-off)
           + IHS per year × (course years + 4-month wrap-up, per UKVI rounding rule)
           + living estimate per year × 5
           + deposit timing (not additional — offset against year-1 fee)
           + UCAT + UCAS + TB test + English test (one-off pre-arrival)
```

## 7. Gaps list (for the next research session)

1. **Zero coverage of Section 3 and 4** — visa fee, IHS, maintenance thresholds, UCAT 2026 fees, UCAS 2027 fee, TB test fee, IELTS fee, living costs. Highest priority; all have single official GOV.UK / UCAT / UCAS pages.
2. **Eight schools unsearched:** Sheffield (figure), Edge Hill, Buckingham, UCLan, Brunel, Chester, Greater Manchester, Wolverhampton. UCLan and Brunel matter most for Nigerian applicants.
3. **Four agent-sourced leads to convert to official:** Imperial, KCL, Bristol, UEA (+ Aston).
4. **QMUL conflict** (£49,950 vs £53,950) to resolve on qmul.ac.uk 2026/27 schedule.
5. **Fee year unconfirmed** for Birmingham and Southampton (figures may be 2025/26).
6. **2027/28 fees** found only for Leeds (£50,500), Newcastle Yr 1 (£48,600), Swansea (£50,750); Ulster has a 2027/28 page not read. All others `NOT PUBLISHED` or not checked.
7. **Cambridge College fee** range is agent-sourced; needs the official College fee table.
8. **Deposits**: only KCL/UEA leads; most schools' international deposit amounts not captured.
9. **Scholarship eligibility for Medicine** unconfirmed at Newcastle, Plymouth, Leicester.
10. No verification was possible by opening any page (WebFetch blocked); every row carries `VERIFY-ON-PAGE`.
