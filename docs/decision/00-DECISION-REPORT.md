# Decision report — StudyMedicineUKNigeria.com

Date: 2026-10-03 · Cycle in scope: 2027 entry (and 2028 planning) · Status: research pass 1 complete; **build may start on the foundation, page production waits on the gap-fill listed in §9.**

Labels used: **FACT** (sourced; status shown) · **SOURCE** · **INFERENCE** (our reasoning) · **RECOMMENDATION**. Every FACT in this report is `VERIFY-ON-PAGE` unless marked otherwise: it was located through web-search results quoting an official page, because direct page reads were blocked in this session (see `docs/DATA-AVAILABILITY.md`). Nothing has been published to students yet, so nothing yet needs to be `VERIFIED`; everything must be before it is.

---

## 1. Executive summary

1. **The market gap is real and specific.** Nigerian students asking "can my WAEC/NECO get me into UK medicine?", "what does it cost?", and "how do I sit the UCAT from Nigeria?" are served by contradictory consultancy posts, programmatic directories and North-Cyprus agents. No official source ranks for any of the 25 queries we observed (SOURCE: research 02, 05). No competitor publishes per-university qualification statements with sources and dates, dated fee tables, or a resumable application portal (INFERENCE from 05 gap analysis).
2. **The honest core answer is unwelcome but differentiating.** We found no UK medical school publishing direct A100 entry on WASSCE/NECO alone; every university with a Nigeria page routes WASSCE holders to A-levels, IB, a foundation year or part of a Nigerian degree, and two say so explicitly (Aberdeen, Leicester) (FACT, research 07, 18 of 30 universities with a statement). The site's authority will come from saying this clearly, per university, with links.
3. **Time-critical facts shape release 1.** UCAS equal-consideration deadline for medicine is **15 October 2026, 18:00 UK time**, 12 days from today, and the UCAT 2026 window closed on 24 September 2026 (FACT, research 09). A Nigerian student without a UCAT 2026 result cannot use the UCAS medicine route for 2027 entry; realistic 2027 options are the small set of direct-application or no-UCAT schools, otherwise the plan is 2028 entry (INFERENCE). The site must lead with this, not bury it.
4. **The directory can launch with 25 schools.** Of 53 school records captured, 25 are confirmed as accepting international undergraduates, 6 are home-only, 22 unknown; international fees were found for 33 schools (FACT, research 06, 08). That is enough for a useful, honest directory if every record shows its verification state.
5. **Greater Manchester needs a caution line.** The University of Greater Manchester MBChB is 5 years, international-only for the 2026 cohort, £45,000 per year, direct application, UCAT required from the September 2028 cohort; the GMC lists it among new schools **under review, not yet an awarding body** (FACT, research 06). Any page must state the GMC status plainly. There is no partnership and the site must not imply one (FACT: brief; research 11).
6. **Compliance is tighter than the brief assumed.** Home Office sponsor guidance updated 7 April 2026 requires universities to manage agents under the Agent Quality Framework; the DMCC Act 2024 bans false claims of approval or endorsement from 6 April 2025; UCAS centre registration needs a 12-month university reference the business does not yet have; ICO fee and Nigeria Data Protection Act 2023 both apply (FACT, research 11). RECOMMENDATION: position as an **independent application-support service** with the exact wording in research 11 §5.
7. **Stack and architecture are decided** (§6): a Laravel monolith on the existing Hostinger plan, Stripe Checkout, private document storage, three state machines (application, document, submission) with a recorded student authorisation gate.
8. **Data gaps are listed, not papered over.** Semrush has no API units; Search Console, Analytics and Trends were unavailable; the live domain is blocked; the search budget capped at 200 calls. The next pass needs those unlocked (§9).

---

## 2. Market and demand (research 02, 05, 03)

**FACTS (observed SERPs, 2026-10-03, US-indexed engine; may differ from google.com.ng):**
- 25 queries observed. Recurring domains: theukcatpeople.co.uk (13 of 25), leadingtuition.co.uk (8), northcypruseducation.com (8), excelsiorscholarships.com (5), gostudyin.com (5). Absent from every result: medschools.ac.uk, ucas.com, gmc-uk.org, gov.uk.
- Spam domains rank for "cost of studying medicine in UK" queries (INFERENCE: weak authoritative coverage).
- Forum demand (Nairaland, The Student Room) clusters into: "can my WAEC/this result get me in", "how do I do it step by step", "is UK medicine right for me", "I already have a Nigerian MBBS, how do I reach the UK", and A-level school choice in Nigeria.
- Allied courses: dentistry, physiotherapy, radiography, medical laboratory science and biomedical science show empty Nigeria→UK study SERPs; public health and nursing show visible demand but mostly postgraduate or migration intent.
- `site:studymedicineuknigeria.com` returned nothing (INFERENCE: not indexed, or no indexable content; confirm in Search Console).

**DATA UNAVAILABLE:** every volume, difficulty and trend figure. The 10-item Semrush `ng` re-check list is in research 02 §E.

**INFERENCE:** demand is strongest and least served for (1) WAEC/NECO acceptance per university, (2) dated fees, (3) UCAT logistics from Nigeria, (4) foundation routes that actually lead to medicine, (5) graduate entry for Nigerian degree holders. Doctor-migration (PLAB) is a different audience and is excluded.

---

## 3. The admissions facts the whole site rests on (research 06, 07, 08, 09, 10)

| Area | FACT (VERIFY-ON-PAGE) | SOURCE location |
|---|---|---|
| UCAS 2027 | Opened 12 May 2026; submissions from 1 Sep 2026; medicine deadline 15 Oct 2026 18:00; main deadline 13 Jan 2027; Extra 25 Feb; Clearing 2 Jul 2027. Max 4 medicine choices. Personal statement = 3 questions, 4,000 characters. New document upload (up to 30 files, 5 MB each). | research 09 §2 |
| UCAT 2026 | Registration 20 May–16 Sep 2026; testing 13 Jul–24 Sep 2026; results to universities early Nov. Structure VR, DM, QR + SJT; total /2700. Fee leads £70 UK / £115 international (official page not reached). | research 09 §4 |
| Qualifications | 18/30 universities publish a Nigeria/WASSCE/NECO statement; 7 are medicine-specific; none allows direct A100 entry on WASSCE alone. WAEC/NECO English accepted as English evidence at Aston (C4) and Lancashire (B3) for medicine; general-UG waivers elsewhere need medicine-applicability checks. Medicine IELTS 7.0–7.5. | research 07 |
| Schools | 53 records; 25 international-accepting; home-only: Anglia Ruskin, Edge Hill, Pears Cumbria (official), Sunderland, Lincoln, Bangor (leads). Published international places found for 16 schools (e.g. Imperial 74, Leeds 24, Exeter 10). | research 06 |
| Direct / no-UCAT routes | Brunel (UCAS or direct to 30 Jun 2027; no UCAT for international), University of Lancashire/UCLan (UCAS or direct; no UCAT for international — lead), Buckingham (outside UCAS; no UCAT — leads only), Greater Manchester (direct; UCAT from 2028 cohort). | research 06, 09 |
| Fees | 33 of 44 schools with a findable international fee. Lowest annual £30,150 (Leicester years 1–2; clinical £48,900); typical 5-year band ≈ £46,000–£50,500/yr; highest £70,554 (Cambridge, plus College fee). NHS levies at Dundee, QUB, Ulster. 5-year tuition-only range ≈ £207,000–£313,650 at frozen 2026/27 rates (OUR APPROXIMATE RANGE). | research 08 |
| Other costs | Visa fee, IHS, maintenance, UCAT fee, UCAS fee, living costs: **NOT FOUND IN THIS SESSION** (searches never ran). Leads only: maintenance £1,483/£1,136 per month rising to £1,570/£1,203 from 30 Nov 2026; IHS £1,035/yr; Graduate visa fee £880–£937 (conflict). | research 08 §3, 10 |
| Working / after | Student visa 20 h/week in term; no dependants for undergraduates. Graduate visa **18 months for applications on/after 1 Jan 2027**. GMC: MLA required for all UK graduates since 2024/25; Foundation Programme open to international UK graduates with Health and Care Worker visa sponsorship. Prioritisation of UK graduates: official policy direction plus a Bill in progress; status of international UK graduates **not found** — label "proposed / in progress". | research 10 |

---

## 4. Decisions on scope and pages

Full detail: `page-asset-register.md`. Summary:

- **BUILD NOW (23 assets):** home; core Nigerian landing; requirements hub; WAEC; NECO (†merge candidate); A-levels; Nigerian degree → graduate entry; English language; fee guide; total cost (publish after gap fill); directory; university records for international-accepting schools only; course records where data differs; Greater Manchester record with GMC-status line; UCAT in Nigeria; UCAS 2027 timeline (with 2028 planning); how to apply (UCAS vs direct); foundation routes (†needs fill); Apply Online; services; eligibility check; FAQ hub seeded from 40 sourced real questions; trust and legal pages.
- **BUILD LATER:** why study medicine in the UK; working in the UK (one dated page once GOV.UK/GMC facts are verified); interviews; personal statement and documents; compare tool (when ≥ 20 schools have four verified fields); living costs; indexable filter pages (after Semrush); dentistry single hub page (expertise transfers, demand unproven); pharmacy; nursing only after Semrush validation.
- **DO NOT BUILD:** rankings or "best" lists; per-city pages; generic study-in-UK sections; blog; scholarship listicles; physiotherapy, radiography, public health sections; doctor-migration content; logo walls, testimonials, counts.

RECOMMENDATION: release 1 is ~20 public pages plus the directory. One excellent WAEC page beats ten thin ones (brief 119).

---

## 5. Information architecture, conversion and design

Decided in `information-architecture.md`, `conversion-funnel-strategy.md`, `architecture/19-design-system.md`:
- Six-item navigation (Medicine · Medical Schools · Requirements · Fees · Admissions · Student Portal) plus APPLY ONLINE.
- Every informational page follows Answer → Evidence → Decide → Act, with one contextual CTA band and one floating CTA; no pop-ups.
- Eligibility check (5–7 questions) produces a cautious route-category answer and a *suggested* tier; never an eligibility verdict.
- Editorial, clinical, British visual language: serif display, humanist sans body, deep navy, one muted red for the single primary action; source boxes and verified chips as first-class components; no logos, no fake badges.

---

## 6. Platform and stack

Decided in `architecture/12–20`:
- **Laravel 12 / PHP 8.3 / MySQL** monolith on Hostinger, Tailwind, Blade + Livewire; Stripe Checkout (hosted) with webhook-driven payment state; private disk storage with authenticated streaming, magic-byte validation, image re-encode, malware scan (ClamAV on VPS; documented fallback on shared hosting), AES-GCM for passport and financial documents; insert-only audit tables.
- **Three state machines** (application, document, submission) and a **recorded authorisation** (timestamp, typed name, declaration version, snapshot hash) that is invalidated if the package changes. `DIRECT_AGENT` submission routes are hidden in code until a signed agreement is on file.
- **Payment gate position is configurable per tier** (at start / before review / before submission). No prices are set; deliverables per tier are drafted in 15.1 for the owner to confirm.
- **Open question for the owner:** which Hostinger plan is active (shared Business, Cloud, VPS) and whether it exposes SSH, cron and MySQL. The recommendation holds in all cases; details in 20.3 depend on it.

---

## 7. Positioning and compliance (research 11)

RECOMMENDATION (adopt verbatim until documentation changes):
> StudyMedicineUKNigeria is an independent application-support service. We are not an agent of, or affiliated with, any university, UCAS, the British Council, the GMC or any other body unless expressly stated on our "Our status" page. We do not receive commission from any university.

Banned words without documentation: partner, authorised, official representative, approved, accredited, guaranteed. Documentation ladder for each stronger claim is in research 11 §5. Register with the ICO; assess Nigeria Data Protection Act registration once data-subject counts approach the "controller of major importance" threshold; complete the free British Council agent and counsellor certificate now (RECOMMENDATION).

---

## 8. Risks

| Risk | Likelihood / impact | Mitigation |
|---|---|---|
| Publishing a `VERIFY-ON-PAGE` fact that is wrong | high if rushed / high (trust) | rendering is gated on `status == VERIFIED`; nothing here is published until the gap-fill pass |
| 2027 cycle is effectively closed for UCAS medicine without UCAT 2026 | certain / high (expectation management) | lead with a 2027-vs-2028 decision page; promote direct/no-UCAT schools factually |
| Greater Manchester GMC status misread as full approval | medium / high | mandatory GMC-status line on its page; review quarterly |
| Immigration rules shift again (Graduate visa, prioritisation Bill, levy from 1 Aug 2028) | high / medium | "Working in the UK" is one dated page with quarterly review; label proposed vs official |
| Card declines from Nigerian issuers | high / medium (revenue) | bank-transfer fallback with manual review state (15.2) |
| Document data breach | low / severe | 14.4 pipeline, insert-only logs, no public URLs, retention schedule |
| Claims of partnership creep into copy | medium / high (legal) | banned-words list in editorial policy; agreement-gated routes in code |

---

## 9. What happens next (ordered)

**A. Unblock evidence (owner actions, each a few minutes):**
1. Grant the Claude GitHub App access to `bigbossua/studymedicineuknigeria` so the branch can be pushed (push returned 403 this session).
2. Raise the session web-search cap or allow network access to `medschools.ac.uk`, `ucas.com`, `ucat.ac.uk`, `gov.uk`, `gmc-uk.org`, `*.ac.uk` so facts can be read on-page and upgraded to `VERIFIED`.
3. Add Semrush API units (https://www.semrush.com/mcp-access) and connect Search Console and GA4.
4. Confirm the Hostinger plan (§6).

**B. Research pass 2 (≈ 80 searches or direct reads):** close every Gaps list — the 20 un-researched schools; 8 unsearched fee schools (UCLan, Brunel first); all "other costs" (visa, IHS, maintenance, UCAT, UCAS, living); GEM international eligibility for 21 programmes; Glasgow's Nigeria row; Greater Manchester 2027 UCAT/IELTS/GMC progress; Search Console audit of the existing domain and redirect map.

**C. Build the foundation (no public pages yet):** repository scaffold, design tokens and components, reference-data schema (17.1) with verification workflow, admin verification queue, import of `schools.json`, `fees.json`, `university-statements.json` as `VERIFY-ON-PAGE` records.

**D. Verify, then publish release 1** in this order: UCAS-2027/2028 timeline → WAEC → requirements hub → fee guide → directory (25 schools) → UCAT in Nigeria → core landing → Apply Online + eligibility → portal.

---

## 10. Where to read more

`docs/DATA-AVAILABILITY.md` · `docs/research/02,03,05,06,07,08,09,10,11` · `docs/architecture/12–20` · `docs/decision/information-architecture.md`, `page-asset-register.md`, `conversion-funnel-strategy.md` · datasets in `data/`.
