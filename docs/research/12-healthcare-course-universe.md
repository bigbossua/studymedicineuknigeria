# 12 — Healthcare and allied-health course universe for Nigerian applicants

**Research date:** 2026-10-03. **Purpose:** the master taxonomy behind `data/healthcare/subjects.json` (seeded into the `professions` table, visible at Admin → Subjects) and the subject rows (cluster V) of `data/seo/decision-register.csv`. Medicine remains the flagship; every other subject is classified by evidence and gets a public page only when its status reaches BUILD and a register row justifies one.

**Method and limits:** WebSearch snippets only (direct fetch of official domains is blocked from this environment). Every regulator, availability and requirement statement is `VERIFY-ON-PAGE` until read on the official page; demand observations come from a US-indexed engine, not google.com.ng, and carry no volume data (Semrush `ng` lookups are the owner's). Labels: FACT (observed, with URL) · LEAD (non-official) · INFERENCE · NOT FOUND.

**Source policy (owner directive):** regulators first (GMC, GDC, GPhC, NMC, HCPC, GOC, GOsC, GCC, AHCS), then UCAS and official university pages, GOV.UK for immigration. Wikipedia only for discovering subjects, terminology and institutions, never as the sole authority for requirements, fees, deadlines, immigration, registration or availability. No profession's rules are inferred from another's; no university's rules from another's.

## A. Regulators, terminology and registration routes

44 WebSearch queries; FACT = regulator, professional-body, government or university programme page snippet; LEAD = non-official; NF = not found. Full field values are in `data/healthcare/subjects.json`; the table gives the register route.

| Profession | Regulator / register | Registration route (VERIFY-ON-PAGE) | Body | UG entry |
|---|---|---|---|---|
| Medicine | GMC | PMQ → provisional registration with licence → F1 → full registration; MLA from 2024-25 | BMA (NF) | yes, 5–6 |
| Dentistry | GDC 'Dentist' | approved BDS/BChD → register; DFT for NHS performers list (LEAD) | BDA (NF) | yes, 5 |
| Pharmacy | GPhC | MPharm → foundation training year → registration assessment | RPS (not confirmed) | yes, 4 (+1) |
| Nursing | NMC, four fields | approved programme → register in field(s) | RCN | yes, 3 |
| Midwifery | NMC 'Registered Midwife' | approved programme → register | RCM | yes, 3 |
| Physiotherapy | HCPC 'Physiotherapist' | approved, CSP-accredited (1,000 hours) → register | CSP | yes, 3 (MSc 2) |
| Occupational Therapy | HCPC | approved, RCOT-accredited (1,000 hours) → register | RCOT | yes, 3 |
| Diagnostic Radiography | HCPC 'Radiographer' | approved → register | SoR / CoR | yes, 3 (MSc 2) |
| Therapeutic Radiography | HCPC 'Therapeutic radiographer' | approved → register | SoR / CoR | yes, 3 |
| Paramedic Science | HCPC 'Paramedic' | approved, CoP-endorsed → register | College of Paramedics | yes (length NF) |
| Operating Department Practice | HCPC 'ODP' | approved, CODP-endorsed → register (full BSc at one provider) | CODP | yes, 3 / DipHE |
| Optometry | GOC; student registration compulsory | BSc + Scheme for Registration (phasing out) or 4-year MOptom → register | College of Optometrists | yes, 3+1 or 4 |
| Audiology | no statutory regulation; voluntary AHCS/RCCP register; HCPC for hearing aid dispensers | accredited BSc → AHCS/RCCP | BAA | yes, 3 |
| Speech and Language Therapy | HCPC | approved, RCSLT-accredited → register | RCSLT | yes, 3–4 (MSc 2) |
| Dietetics | HCPC 'Dietitian' | approved, BDA-accredited (1,000 hours) → register | BDA | yes, 3 |
| Podiatry | HCPC 'Chiropodist / podiatrist' | approved, RCPod-accredited → register | RCPod | yes (length NF) |
| Prosthetics and Orthotics | HCPC (dual) | approved → register; four UK providers | BAPO | yes, 3–4 |
| Orthoptics | HCPC 'Orthoptist' | approved → register; Liverpool, GCU, Sheffield | BIOS | yes, 3 |
| Healthcare Science | not statutory at practitioner level; AHCS register; clinical scientists HCPC (STP) | PTP degree (50 weeks placement) → AHCS | AHCS | yes, 3 |
| Biomedical Science | HCPC 'Biomedical scientist' | IBMS-accredited degree + portfolio → Certificate of Competence → register (three routes) | IBMS | yes, 3 |
| Dental Hygiene / Therapy | GDC DCP titles | approved qualification → register (often dual) | BSDHT / BADT | yes, 2–3 |
| Dental Technology | GDC 'Dental technician' | approved Level 5 → register; BSc top-up does not confer registration | DTA (NF) | FdSc yes |
| Clinical Dental Technology; Orthodontic Therapy | GDC | post-registration only | NF | no |
| Pharmacy Technician | GPhC | Level 3 + 24 months' experience → register | APTUK (NF) | no (not a degree) |
| Osteopathy | GOsC | Recognised Qualification (M.Ost 4 years) → register | Institute of Osteopathy | yes, 4 |
| Chiropractic | GCC | recognised MChiro (4 years) → register | BCA (NF) | yes, 4 |
| Arts therapies; Clinical Psychology | HCPC | postgraduate only | BAAT/BAMT/BADth; BPS | no |

Not found or unclear (must be verified before any page states it): the GMC's own list of PMQ titles; dentistry's "no provisional year" and DFT detail (LEAD only); several professional bodies not confirmed from their own sites; course lengths for paramedic science, podiatry and the DipHE ODP route; individual HCPC programme approvals (check hcpc-uk.org/education/approved-programmes per course); the end date of the optometry BSc + Scheme route; AHCS/RCCP registration is voluntary, not statutory.

## B. UK undergraduate availability and international eligibility (sample universities)

Source: a 60-search WebSearch sweep (WebFetch is blocked for official domains in this environment), so every value rests on a search snippet of the named page. **FACT** = snippet of an official university or UCAS page; **LEAD** = aggregator (unienrol, hotcourses, studee, topuniversities, prospects, idp, theuniguide); **NOT FOUND** = not seen. Nothing is inferred across universities or professions. Every value is VERIFY-ON-PAGE: it enters `reference_facts` only after the official page has been read and the source URL and date recorded. Fee years are as stated; per year unless noted. No course page in the sweep mentioned Nigeria or WAEC; the only Nigeria statements are university-level (Manchester: WASSCE holders need a recognised foundation programme, direct entry needs A-levels or IB; Cardiff: SSCE plus International Foundation Programme, or IB / West African GCE A-levels / OND, HND or a first year of a Nigerian degree considered).

| Subject | International eligibility | A-level / tariff | English | Admissions test · interview | International fee |
|---|---|---|---|---|---|
| Dentistry | FACT Manchester BDS: 'approximately 85 home and approximately 15 international places per year'; overseas applicants interviewed online. Cardiff BDS accepts 'acceptable alternative international qualifications'; place numbers NOT FOUND (VERIFY-ON-PAGE) | FACT Manchester BDS AAA incl Chemistry and Biology/Human Biology; FACT Cardiff BDS AAA incl Biology and Chemistry, GCSE English B/6 (VERIFY-ON-PAGE) | FACT Manchester IELTS 7.0 overall, no component below 6.5, one sitting; Cardiff NOT FOUND | UCAT (FACT Manchester 'soft threshold' plus interview; FACT Cardiff UCAT must be sat before applying) | FACT Manchester BDS £36,500 year 1 (2025 entry), clinical years from year 2 £58,000; LEAD Cardiff BDS £30,700 (VERIFY-ON-PAGE) |
| Pharmacy | Fee published for international students at Nottingham and Cardiff; explicit eligibility or quota statement NOT FOUND (VERIFY-ON-PAGE) | FACT Nottingham MPharm AAA incl Chemistry plus one of Biology/Maths/Further Maths/Physics (page states 2027 entry); Cardiff NOT FOUND (VERIFY-ON-PAGE) | FACT Nottingham IELTS 7.0, no element below 6.0; Cardiff NOT FOUND | No admissions test seen; FACT Cardiff online interview ('we don't make offers without an interview'); Nottingham interview NOT FOUND | FACT Nottingham MPharm £33,000 provisional (2026 entry); FACT Cardiff MPharm £30,700 (2026 entry) (VERIFY-ON-PAGE) |
| Nursing (adult, child, mental health, learning disability) | FACT MMU publishes an international fee and an International College foundation route; explicit eligibility statement NOT FOUND; Coventry/Salford NOT FOUND (VERIFY-ON-PAGE) | FACT MMU typical 104 UCAS tariff points; FACT Coventry 112 points / BBC with GCSE Maths, English and a science at 4/C (VERIFY-ON-PAGE) | FACT MMU IELTS 6.5 overall (6.5 Reading/Listening/Speaking, 6.0 Writing, within 2 years); LEAD Coventry 7.0 (6.5); LEAD Salford 6.0 (5.5) | No admissions test; FACT MMU interview; FACT Coventry interview online or in person, caring experience 'very desirable' | FACT MMU Adult Nursing £21,500; LEAD Coventry £17,100; LEAD Hertfordshire £19,450; LEAD Salford £15,240 (fee years not stated) (VERIFY-ON-PAGE) |
| Midwifery | FACT Birmingham BSc Midwifery 'does not currently consider applicants who would be considered as overseas for fee purposes'; FACT Northumbria 'welcomes applicants with a range of qualifications from outside the UK', no quota stated; BCU/Hertfordshire NOT FOUND (VERIFY-ON-PAGE) | FACT Birmingham ABB with B in a science/health/social-science subject, five GCSEs incl English, Maths, science; Northumbria grade NOT FOUND (science/health subject required) (VERIFY-ON-PAGE) | FACT Birmingham IELTS 7.0 (6.5 writing, 7.0 others); FACT Northumbria IELTS 7.0 (6.5 each) or OET | No admissions test; FACT Northumbria interview, DBS, occupational health, NMC suitability; Birmingham NOT FOUND | Birmingham n/a (home-only); Northumbria international fee NOT FOUND; LEAD BCU £21,690; LEAD Hertfordshire £19,450 (VERIFY-ON-PAGE) |
| Physiotherapy | FACT Brunel publishes a 2026/27 international fee; explicit eligibility statement NOT FOUND (VERIFY-ON-PAGE) | FACT Brunel ABB–BBB (2026/27); BTEC DDD–DDM; IB 31–30 (VERIFY-ON-PAGE) | FACT (UCAS, Brunel) IELTS Academic 7.0, no less than 6.5 in each | No admissions test; FACT (UCAS) interview and criminal-records declaration | FACT Brunel £21,795 (2026/27) (VERIFY-ON-PAGE) |
| Occupational Therapy | FACT Brunel publishes an international fee; eligibility statement NOT FOUND (VERIFY-ON-PAGE) | FACT Brunel AAB–BBB; BTEC DDM; IB 30 (VERIFY-ON-PAGE) | LEAD Brunel IELTS 7.0 (6.5 each) | No admissions test seen; interview NOT FOUND | FACT Brunel £24,795 (2025/26); LEAD Coventry £17,600 (VERIFY-ON-PAGE) |
| Diagnostic Radiography | International eligibility statements NOT FOUND (Bradford, Liverpool, Cardiff); FACT Bradford page states the course is 'no longer accepting applications for September 2026 entry' (VERIFY-ON-PAGE) | FACT Bradford 128 tariff / ABB incl a science, maths or technology subject; FACT Liverpool BBB incl a science; FACT Cardiff BBB–BCC incl B in Biology/Chemistry/Physics/Psychology (VERIFY-ON-PAGE) | FACT Bradford IELTS 7.0, no subtest below 6.5; FACT Cardiff IELTS 7; Liverpool NOT FOUND | No admissions test; FACT Bradford interviews short-listed candidates, occupational health and DBS | International fees NOT FOUND for all three sample universities |
| Therapeutic Radiography (Radiotherapy) | FACT Cardiff Radiotherapy and Oncology: 'The NHS only makes placements available to students who are eligible to pay UK fees. Therefore, this course is not available for international students.' Liverpool/Sheffield Hallam NOT FOUND (VERIFY-ON-PAGE) | FACT Cardiff BBB–BCC incl B in one science; FACT Liverpool BBB (VERIFY-ON-PAGE) | LEAD Sheffield Hallam IELTS 6.5 (5.5); Cardiff/Liverpool NOT FOUND | No admissions test; FACT Cardiff interview (reasoning, profession awareness, communication) | Cardiff n/a (home-only); Liverpool NOT FOUND |
| Paramedic Science | FACT Plymouth requires a tuition-fee deposit before a CAS is issued (visa route exists); explicit eligibility statement NOT FOUND; Hertfordshire NOT FOUND (VERIFY-ON-PAGE) | FACT Plymouth five GCSEs at 4/C incl Maths, English, science; A-level grades NOT FOUND (Plymouth, Hertfordshire) (VERIFY-ON-PAGE) | FACT Hertfordshire IELTS 7.0 (6.5 each); FACT Plymouth IELTS 7.0 (6.5 each) if no GCSE English 4/C | No admissions test; FACT Hertfordshire interview, DBS, health checks | International fees NOT FOUND (Hertfordshire shows UK fee only); LEAD Coventry £16,900 |
| Operating Department Practice | NOT FOUND (Northumbria lists international IELTS and an EU fee; no eligibility statement seen) (VERIFY-ON-PAGE) | FACT Northumbria 112 UCAS tariff points, GCSE Maths and English 4/C (VERIFY-ON-PAGE) | FACT Northumbria IELTS Academic 7.0 with 6.5 in each | No admissions test; FACT Northumbria interview; (UCAS) DBS and health checks | FACT LSBU £16,900 per year (3-year total £50,700); Northumbria international fee NOT FOUND (VERIFY-ON-PAGE) |
| Optometry | NOT FOUND (Cardiff MOptom, Bradford BSc, Manchester MSci) | FACT Cardiff MOptom AAA–ABB incl two of Biology, Chemistry, Maths, Physics (4-year, GOC-accredited); Bradford A-level NOT FOUND (VERIFY-ON-PAGE) | FACT Bradford IELTS 7.0, no sub-test below 6.5; LEAD Cardiff IELTS 6.5 | No admissions test seen; interview NOT FOUND | LEAD Cardiff £30,700; Bradford NOT FOUND (VERIFY-ON-PAGE) |
| Audiology | FACT Manchester BSc Healthcare Science (Audiology) publishes an international fee; eligibility statement NOT FOUND (VERIFY-ON-PAGE) | FACT Manchester ABB incl a science (Biology, Chemistry, Physics, Maths, Psychology or Applied Science); IB 34 (VERIFY-ON-PAGE) | NOT FOUND for the BSc | No admissions test seen; interview NOT FOUND | FACT Manchester £31,000 per annum (2025 entry) (VERIFY-ON-PAGE) |
| Speech and Language Therapy | FACT Manchester: international applicants must submit an overseas police check (admits internationals by implication; no quota stated) (VERIFY-ON-PAGE) | FACT Manchester AAB, no specific subjects; GCSE English Language 5/B (VERIFY-ON-PAGE) | FACT Manchester IELTS 8.0, no component below 7.5 (2021 entry-requirements page; re-read current year); LEAD MMU 8.0 (7.5) | No admissions test; FACT Manchester shortlisted applicants interviewed, enhanced DBS, occupational health | FACT Manchester £33,600 (2026/27); LEAD MMU figure implausible and unverified (VERIFY-ON-PAGE) |
| Dietetics | FACT Plymouth 'welcomes applications from suitably qualified international students'; deposit before CAS; Nottingham NOT FOUND (VERIFY-ON-PAGE) | FACT Plymouth 112–128 points incl BB at A-level with Biology plus a second science (Chemistry preferred); FACT Nottingham Biology or Chemistry plus a second science, grades NOT FOUND (VERIFY-ON-PAGE) | FACT Plymouth IELTS 7.0 (6.5 each); FACT Nottingham IELTS 7.0 (6.5 each) | No admissions test seen; interview NOT FOUND | NOT FOUND (LEAD Nottingham c.£33,000) |
| Podiatry | NOT FOUND (Huddersfield, Salford, Plymouth) | FACT Huddersfield BBB–BBC / 120–112 tariff, GCSE English and Maths 4/C, DBS and occupational health (VERIFY-ON-PAGE) | LEAD Salford IELTS 6.5 (6.0); Huddersfield NOT FOUND | No admissions test seen; interview NOT FOUND | LEAD Huddersfield £18,700; LEAD Salford £16,380 |
| Prosthetics and Orthotics | NOT FOUND (Strathclyde, Salford) | FACT (UCAS) Strathclyde ABB–BBB with Maths or Physics; IB 32; 4-year (VERIFY-ON-PAGE) | NOT FOUND (LEAD Salford IELTS 6.5) | No admissions test; FACT (UCAS) Strathclyde 'may need to attend an interview' | LEAD Strathclyde £86,000 (unclear if total); LEAD Salford £18,300 |
| Orthoptics | FACT Liverpool maintains a dedicated international page for the course; quota NOT FOUND; Sheffield NOT FOUND (VERIFY-ON-PAGE) | FACT Liverpool BBB incl one of Biology, Chemistry, Physics, Psychology, Maths; LEAD Sheffield BBB incl a science (VERIFY-ON-PAGE) | FACT Liverpool IELTS 7.0, no component below 6.5; FACT Sheffield IELTS 7.0 (6.5 each) | No admissions test; FACT Sheffield interview, DBS, occupational health | LEAD Liverpool £29,100; LEAD Sheffield £32,100; LEAD GCU £15,700 |
| Healthcare Science (incl. cardiac and clinical physiology) | FACT Cardiff Met Healthcare Science (Life Sciences): 'not able to consider international applicants for entry due to the nature of the programme'; FACT Wolverhampton Cardiac Physiology 'unable to take applications for entry onto the Healthcare Science course at this time' (VERIFY-ON-PAGE) | LEAD Cardiff Met CCC; Wolverhampton NOT FOUND | LEAD Cardiff Met IELTS 7.0 (6.5) | No admissions test seen | LEAD Cardiff Met £16,000 (moot: internationals not considered) |
| Biomedical Science | NOT FOUND (KCL, Birmingham, Sheffield publish international fees via aggregators only) | FACT KCL AAA incl A in Biology and Chemistry; LEAD Birmingham AAB with two sciences; LEAD Sheffield AAB (VERIFY-ON-PAGE) | NOT FOUND for the named course (LEAD KCL IELTS 6.5 (6.0)) | No admissions test; interview NOT FOUND | LEAD KCL £33,450 (Sept 2026) or £35,800 (conflicting aggregators); LEAD Birmingham £31,050 |
| Dental Hygiene and Dental Therapy | FACT Cardiff Dental Therapy and Hygiene 'does not currently accept international students'; Portsmouth/Edinburgh NOT FOUND (VERIFY-ON-PAGE) | FACT Cardiff BBB–BCC incl B Biology/Human Biology; FACT (UCAS) Portsmouth ABB–BBB / 120–128 points incl a pure science at B (VERIFY-ON-PAGE) | LEAD Edinburgh IELTS 6.5; Portsmouth NOT FOUND | No admissions test seen; interview NOT FOUND | Cardiff n/a (home-only); LEAD Portsmouth £19,950; LEAD Edinburgh £30,400 |
| Dental Technology | FACT Cardiff Met page lists international English tests (IELTS, PTE, TOEFL, CAE); eligibility statement NOT FOUND (VERIFY-ON-PAGE) | FACT Cardiff Met CC / 96 UCAS points; video task submission; occupational health; foundation-year route (VERIFY-ON-PAGE) | FACT Cardiff Met Academic IELTS 6.5, no element below 6.0 | No admissions test; FACT Cardiff Met applicants submit a video of a relevant task | LEAD Cardiff Met £16,000 |

Not covered by the sweep (still NOT RESEARCHED): Osteopathy, Chiropractic.

### What §B changes
- **Availability is a per-university fact, not a per-subject fact.** Birmingham Midwifery, Cardiff Radiotherapy, Cardiff Dental Therapy and Hygiene and Cardiff Met Healthcare Science do not admit international students, while Northumbria Midwifery and Plymouth Dietetics say they welcome them. Any subject page must list availability university by university and may never say "available in the UK" for the profession as a whole.
- **Explicit international place numbers were published for one course only** (Manchester BDS, about 15 a year). Everywhere else a published international fee is the only evidence that a visa route exists; that is not an eligibility statement and will not be presented as one.
- **English requirements are higher than Medicine for Speech and Language Therapy** (IELTS 8.0 at Manchester) and differ across subjects (6.5 for nursing at MMU, 7.0 for most HCPC professions). No page may reuse Medicine's IELTS figures for another subject.
- **Gaps before any subject can reach BUILD:** fees with a fee year for most subjects (NOT FOUND or LEAD-only), course-level IELTS for Cardiff BDS and MPharm, Manchester Audiology, KCL Biomedical Science, and interview or test evidence for pharmacy, OT, optometry, audiology, dietetics, podiatry, biomedical science and dental hygiene. Several official snippets came from older-year URLs (Cardiff DT&H 2023, Cardiff MOptom 2024, Manchester SLT 2021) and must be re-read on the current-year page.
- **Statuses unchanged.** Dentistry, Nursing and Biomedical Science stay VALIDATED; the 20 RESEARCH subjects stay RESEARCH; the single allied-health overview hub is register row V01 (VALIDATED, no URL). The evidence recorded here is sufficient to draft an honest hub but not to state a single fee or requirement publicly.

## C. Nigerian search demand by subject (SERP observation, 39 searches)

Cross-cutting FACTS:
1. One agent (gostudyin.com/nigeria/study-in-uk/popular-courses/…) holds Nigeria-specific template pages for Nursing, Dentistry, Pharmacy, Physiotherapy, Audiology and generic "Healthcare"; no other domain shows a Nigeria + subject URL for any of the 19 subjects.
2. Nigerian blogs and press appear only for Nursing (studentship.com.ng, nairametrics, legit.ng cost story, guardian.ng).
3. Course aggregators (hotcoursesabroad, idp, thecompleteuniversityguide, studee, topuniversities) dominate everything below Pharmacy; none is Nigeria-specific.
4. University Nigeria country pages (Birmingham, Manchester, Hertfordshire, Solent, London Met, Bucks, Northumbria, ARU) surface for WAEC/NECO-shaped queries; snippets there carry "WAEC English C6 accepted as English evidence" and "NECO may not be acceptable for professional/pre-registration health courses" (bucks.ac.uk, birmingham.ac.uk) — VERIFY-ON-PAGE.
5. Regulator pages ranked only for Optometry (optical.org overseas-qualification PDFs) and the Nigerian NMCN; no NMC, HCPC, GDC or GPhC page ranked for any query.
6. Migration and registration intent crowds out study intent for Nursing, Radiography, MLS and Physiotherapy (candidate CVs, NIRAD, NHS trust recruitment, HCPC/GPhC migration guides, nurse-migration news).
7. Recurring question shapes: "can I use WAEC/NECO", "is WAEC accepted as GCSE equivalent", "fees in naira", "NMC registration after study / ONP", "HCPC registration", "work after graduation / visa sponsorship", "study in UK without IELTS", "X vs Y which is more lucrative", "funding as an international student".

| Subject | Nigeria-specific subject page? | Dominant result types | Visible student questions | INFERENCE: demand |
|---|---|---|---|---|
| Nursing | Yes (agent, Nigerian blog, Nigerian news) | blogs, agent, university Nigeria pages, Nairaland, news | WAEC/NECO, IELTS, ONP/NMC, top-up, cost in naira, work after | **Evident** |
| Midwifery | No | aggregators; nurse-migration news | none midwifery-specific | Some signals |
| Dentistry | Yes (gostudyin only) | agents, aggregators, university Nigeria pages, UCAT blog | WAEC English grade; UCAT | Some signals |
| Pharmacy | Yes (gostudyin only) | Nigerian university pages, OSPAP listings, GPhC guides | OSPAP vs MPharm; GPhC; foundation year; work | Some signals (mostly OSPAP) |
| Physiotherapy | Yes (gostudyin only) | aggregators, university course pages, papers | HCPC; competitiveness; MSc pre-reg | Some signals (comparison threads) |
| Radiography | Partial (idp country filter; migration article) | SoR NIRAD, NHS recruitment, CVs | HCPC route; fees | Some signals (practitioners) |
| Biomedical Science / MLS | No | university student profiles, Nigerian MLS departments, JAMB brochure | HCPC; degree evaluation | Some signals (Nairaland MLS forum) |
| Optometry | No | Nigerian news, regulator PDFs, course pages | GOC overseas route; visa restriction snippet | Thin |
| Paramedic Science | No | aggregators | none | None |
| Occupational Therapy | No | academic papers | none | None |
| Speech and Language Therapy | No | aggregators, course pages | high IELTS | None |
| Dietetics / Nutrition | No | aggregators, course pages | none | None |
| Audiology | Yes (gostudyin, position ~9) | Nigerian academic PDFs | none | Thin |
| Podiatry | No | aggregators | none | None |
| Operating Department Practice | No | aggregators, course pages | none | None |
| Healthcare Science | Yes (generic "healthcare") | ARU Nigeria pages, blogs, agents | none | Thin (ambiguous term) |
| Dental Hygiene / Therapy | No | aggregators, course pages | IELTS; NHS bursary (home) | None |
| Prosthetics and Orthotics | No | one Nigerian PhD profile, department pages | none | None |
| Orthoptics | No | aggregators, course pages | none | None |

Forum threads observed (titles as returned): Nairaland — How To Study Nursing In The UK (5530820); RN-BSc Nursing In The UK (4031695); 49 UK Universities For Master's In Nursing (7314656); How To Migrate To The UK As A Nurse (3500363, 8179414); Pharmacy Or Physiotherapy (2295201); Medicine, Pharmacy, Nursing, Medical Laboratory Science, Or Physiotherapy? (3903461); Optometry, Dentistry Or Physiotherapy? (2184643); Dentistry Or Pharmacy, Which Is More Lucrative (7692672); Study In The UK Without IELTS (8413067); Should I Go For Radiography, Medical Lab Science Or Biochemistry? (6232306); Medical Laboratory Scientists Forum (261398); How Lucrative Is Optometry (2158641). The Student Room — Is WAEC accepted as GCSE equivalents? (7404771); WAEC and UK universities (6289054); Study dentistry abroad (7190259, 4654456); How to get funding to study nursing as an international student (7582018); Physiotherapy or pharmacy, Nigerian student (7456896); International students applied pharmacy (2287886).

### Decisions taken from §C (recorded in `subjects.json` and register cluster V)
- **Nursing → VALIDATED.** The only subject with evident Nigerian study demand; the owner's directive brings it into scope. BUILD waits for regulator (NMC), international-availability and WAEC/NECO facts; the page must keep migration (NMC/ONP for qualified nurses) out of scope and answer the observed questions.
- **Dentistry → VALIDATED** (research 03 verdict plus some signals). **Biomedical Science → VALIDATED** as a section of the graduate-entry page (research 03), not a standalone page.
- **Pharmacy, Physiotherapy, Radiography, Midwifery → RESEARCH** with the observed signals recorded; none has study-from-WAEC content to compete with yet.
- **Everything from Optometry down → RESEARCH, no standalone page.** INFERENCE: a single allied-health overview page (HCPC/GOC/GDC professions, how registration works, which courses admit international students) would serve these clusters better than thin pages; proposed as register row V (hub), VALIDATED, built once §A and §B facts exist.
- **Public Health, Clinical Psychology, Arts therapies → REJECTED** (postgraduate entry; outside the undergraduate healthcare-application mission).

## E. Subject long-tail SERP observation: Nursing and Dentistry (36 searches, 2026-10-03)

Method: WebSearch result composition for 18 query shapes per subject (COURSE + NIGERIA + UK + requirements / WAEC / NECO / fees / universities / IELTS / foundation / deadline / interview / visa / scholarship / forum / study-abroad). The tool is US-geolocated and exposes no rank or People-Also-Ask box, so entries are result composition, not Google Nigeria positions; no volumes are estimated. Each family below is a cluster V register row (ids in `subjects.json`); none has a URL.

### Nursing families (register rows V31, V32, V33, V34, V35, V36, V37, V38, V39)

| Family | Intent read by the SERP | Nigeria-specific results | Official UK page answers it? | Decision |
|---|---|---|---|---|
| Eligibility from WAEC/NECO → BSc Adult Nursing | informational | low–medium: Bucks Nigeria country page; North Cyprus agent captures "NECO" | partly (Bucks maps WAEC/NECO grades; nothing nursing-specific mentions WASSCE) | VALIDATED: the question the nursing node must answer per university |
| How to study nursing in the UK from Nigeria | **migration** (NMC, CBT/OSCE, IELTS 7, ONP, HCA visa) | high: nairametrics, studentship, CV sites, gostudyin | no | RESEARCH: SERP reads it as qualified-nurse migration |
| Fees and cost for Nigerians | commercial / news | high: Nigerian news totals and anecdotes; pathway-college marketing; North Cyprus agent | no fee table | RESEARCH: per-university fees only |
| Universities that accept Nigerian students | commercial listicle | high: Nigerian listicles, IDP Nigeria, ARU country page | partly (country pages, not nursing-specific) | RESEARCH: per-university availability list from read pages |
| IELTS for a nursing degree | informational, conflated with NMC IELTS | none | UWS only | RESEARCH |
| Foundation / pathway year | informational | none | yes (BCU, Sheffield Hallam, UCAS; NCUK → Huddersfield) | RESEARCH: section, not page |
| Application mechanics (deadline, interview, placement visa hours) | navigational | none | yes (UCAS, Birmingham 13 January 2027 nursing deadline, Swansea international nursing guidance, Surrey visa hours) | RESEARCH: link out |
| Scholarships | informational | high: Nigerian scholarship blogs | yes (Southampton Global Talent in Adult Nursing; Stirling) | RESEARCH: named awards as facts |
| Nairaland and generic study-abroad | migration / multi-country agents | high | no | REJECTED |

### Dentistry families (register rows V40, V41, V42, V43, V44, V45, V46)

| Family | Intent read by the SERP | Nigeria-specific results | Official UK page answers it? | Decision |
|---|---|---|---|---|
| Eligibility from WAEC/NECO → BDS | informational | very low: one agent page; Nigerian academic papers off-target | no result mentions WAEC, WASSCE or NECO; KCL and Aberdeen generic requirements | VALIDATED: zero Nigeria-specific content for the core intent |
| How to study dentistry in the UK from Nigeria | commercial-agent plus dentist job-seeking | medium–high: gostudyin, dentist CVs, leadership.ng, Bristol Nigeria leaflet | partly (generic country pages) | RESEARCH: served by the dentistry node |
| Fees and international places | informational | none | yes for fees (Manchester, UCLan); places only on Manchester's statistics page (§B) | RESEARCH: per-school facts |
| UCAT for Nigerian dentistry applicants | informational | high, but the Nigeria UCAT guides are for Medicine (leadingtuition, theukcatpeople) | dental school UCAT pages exist; no official UCAT consortium page surfaced | VALIDATED: a dentistry section on the live UCAT page, not a new page |
| IELTS, IFP, deadline, MMI | informational / navigational | none | yes (Bristol IFP for Dentistry, Aberdeen IELTS 7.0, QUB admissions policy, UCAS 15 October, Manchester and Birmingham interview pages) | RESEARCH: link out |
| Scholarships | informational | medium: Birmingham Nigeria award excludes BDS | yes (negative answer) | RESEARCH: record as a fact |
| Nairaland and generic study-abroad | dentist migration / human interest | none; Canada and US advanced-standing dominate | no | REJECTED |

### Official statements seen (search snippets; VERIFY-ON-PAGE before any becomes a reference_fact)
- Bucks New University Nigeria page: foundation entry accepts a WAEC/NECO grade D profile; year 1 entry a grade C profile (five subjects at C or above); IELTS 6.0 with no element under 5.5, or WAEC/NECO English C6 within ten years; BSc Adult Nursing IELTS 6.0 with no section below 6.0. https://www.bucks.ac.uk/study/international/your-country/nigeria
- KCL Adult Nursing: IELTS 7.0 overall "in line with the professional body"; 575 hours of healthcare-related experience. https://www.kcl.ac.uk/study/undergraduate/courses/nursing-with-registration-as-an-adult-nurse-bsc/entry-requirements
- UCAS: 15 October deadline for medicine, dentistry, veterinary and Oxford/Cambridge; 30 June for most other international applications. https://www.ucas.com/advisers/guides-and-resources/adviser-news/news/supporting-international-students-applying-courses-october-deadline
- Birmingham nursing key dates: nursing deadline 13 January 2027. https://www.birmingham.ac.uk/about/college-of-medicine-and-health/nursing-and-midwifery/applying-to-nursing/how-to-apply-and-key-dates
- Swansea: "International Nursing: Application Guidance" page for BSc Adult Nursing. https://www.swansea.ac.uk/undergraduate/courses/health-social-care/adult-nursing-swansea-bsc-hons/international-nursing
- Surrey: placement hours forming part of the degree are unrestricted on a Student visa; other work 20 hours a week in term. https://my.surrey.ac.uk/node/9665
- Southampton: Global Talent in Adult Nursing Scholarship, £5,000 a year off tuition for up to three years, automatic. https://www.southampton.ac.uk/study/fees-funding/scholarships/global-talent-in-adult-nursing-scholarship
- NCUK (pathway provider, not a university): IFY in Nursing with guaranteed progression to BSc Adult Nursing at Huddersfield; £17,600; UKVI IELTS 5.0. https://ncuk.malverninternational.com/?p=19517
- Bristol IFP for Dentistry: one year, for overseas students, completion guarantees a BDS interview; IELTS 7.5 overall on the progression page. https://www.bristol.ac.uk/health-life-sciences/international-foundation-programme/
- Aberdeen BDS: IELTS 7.0 overall with 7.0 in speaking; English qualification within two years. https://www.abdn.ac.uk/dental/study/bds/entrance-requirements/academic-requirements/
- Queen's Belfast Dentistry Admissions Policy 2025: 15 October 6 pm deadline; two-stage selection with UCAT for UK and Republic of Ireland applicants. https://www.med.qub.ac.uk/download/Dentistry-Admissions-Policy-2025.pdf
- Birmingham Nigeria Outstanding Achievement Scholarships: £4,000 for Nigeria-domiciled undergraduates; BDS applicants not eligible. https://birmingham.ac.uk/funding/undergraduate/university-of-birmingham-nigeria-outstanding-achievement-scholarships
- Bristol Nigeria leaflet 2025–26: SSCE (WASSCE or NECO) minimum five subjects at grade B or above (context unclear; not BDS-specific). https://bristol.ac.uk/media-library/sites/international/documents/10003_BU_International%20Leaflets%202025-26%20NIGERIA%20-%20web.pdf
- NOT FOUND: any official page stating Nigerian UCAT test centres; any official count of international BDS places other than Manchester (§B); any UK university page mapping WAEC/NECO grades to BDS entry. Aggregator fee figures (Bristol £27,500, QUB £35,800) are LEAD only.

### What §E changes
- **Nursing's study intent is a minority inside a migration SERP.** The node, when built, must say in its first lines that it covers undergraduate entry from school, not NMC registration, and must never imitate migration content. Decision on the route family waits for Semrush `ng`.
- **Dentistry's core intent is empty.** No page anywhere maps WAEC/NECO to BDS entry; the dentistry node's first job is that per-school answer, built from Manchester, Cardiff, KCL and Aberdeen pages once read.
- **Upgrade before multiplying.** The dental UCAT family is a section on the live UCAT page (after the dental schools' UCAT pages are read), not a new URL.
- **Statuses unchanged** for the subjects; eleven new VALIDATED/RESEARCH/REJECTED family rows in cluster V, none with a URL.

## F. Allied-health long-tail SERP observation: Pharmacy, Midwifery, Physiotherapy, Radiography, Biomedical Science / MLS (49 searches, 2026-10-04)

Method as §E (WebSearch result composition; US-geolocated; no ranks, no People-Also-Ask boxes; no volumes). Register rows V53–V68.

**Cross-subject finding: wording decides intent.** Professional-title wording ("Nigerian pharmacist", "B.Pharm", "radiographer", "medical laboratory scientist", "midwife NMC") returns migration of qualified professionals; degree wording ("MPharm", "BSc", "with foundation year", "fees international") returns official UK university pages. "WAEC/NECO" alone returns Nigerian domestic admissions; paired with "UK university entry requirements" it returns UK university Nigeria country pages with generic WAEC rules, never subject-specific ones.

| Subject | Study intent from Nigeria | International availability (official snippets, VERIFY-ON-PAGE) | Decision |
|---|---|---|---|
| Pharmacy | gostudyin Nigeria pages, one Nairaland post, parent questions answered by agents; no official WAEC → MPharm answer | no NHS-placement restriction seen; UCL integrated foundation route "aimed primarily at international students"; Manchester: international graduates need a visa for the training year | Pharmacy → **VALIDATED** (V03); WAEC/entry family V53 VALIDATED; OSPAP migration V56 REJECTED |
| Midwifery | none found; WAEC/NECO wording returns Nigerian basic-midwifery schools | Cardiff, Cumbria, Manchester (and Birmingham, §B) closed to international fee status; LJMU, York publish international fees | stays RESEARCH; eligibility answered in the overview hub's availability table (V57) |
| Physiotherapy | Nigeria-targeted agent plus Nigerian domestic admissions noise | Brunel places international students with private providers; one UCAS listing UK-fee only | stays RESEARCH (V60–V62) |
| Radiography | migration and Nigerian domestic blogs | diagnostic often closed to international fee status (UCAS, Cardiff); Liverpool therapeutic takes international applicants (30 June 2027 international deadline) | both stay RESEARCH (V63–V65) |
| Biomedical science / MLS | the MLS term returns only CVs and migration; "biomedical science" returns study content | no restriction seen; IBMS portfolio placement open to international students with IELTS 7.0 (UCAS listing, provider not named) | stays VALIDATED; MLS terminology family V66 VALIDATED |

Official statements seen (search snippets; each needs its page read before it becomes a reference_fact):
- Cardiff BMid: "unable to accept applications from international fee-status applicants because of its association with the NHS and the restrictions on funding and clinical placements." https://www.cardiff.ac.uk/study/undergraduate/courses/2026/midwifery-bmid
- Cumbria Midwifery: unable to accept international applications. https://www.cumbria.ac.uk/study/courses/undergraduate/midwifery/
- UCAS listings (several providers): "an NHS placement is a required part of the course and the NHS only makes placements available to students who are eligible to pay UK fees."
- Manchester MPharm: international graduates must apply for a visa to do the foundation training year; a training place is competitive and not guaranteed; GPhC registration follows 52 weeks of training and the registration assessment.
- UCL Pharmacy with Integrated Foundation Training: "aimed primarily at international students", overseas fee £35,400 (2026/27).
- Brunel Physiotherapy: international students complete 1,000+ placement hours across private healthcare providers and other settings; fee £21,795 (2026/27).
- Liverpool Therapeutic Radiography and Oncology (2027): vacancies for international applicants; international deadline 30 June 2027; IELTS 7.0 with 7.0 in each skill.
- IBMS: the Certificate of Competence makes the holder eligible for HCPC registration as a biomedical scientist; non-accredited and overseas degrees go through IBMS degree assessment (last ten years only); certificate by equivalence needs UK experience.
- Fees seen (per year, VERIFY-ON-PAGE): Sunderland MPharm £20,000 (2026/27); Kent Pharmacy £23,500; LJMU Midwifery £18,250 (2025/26); York BMid £32,350; Sheffield Hallam Physiotherapy £19,500; Keele Radiography £24,900 (2025-26); LSBU Biomedical Sciences £15,900; Westminster Biomedical Science with Foundation £17,600 (2026-27).

## G. Taxonomy gaps (45 searches, 2026-10-04)

| Subject | Finding | Decision |
|---|---|---|
| Cardiac, respiratory, sleep and neurophysiology | pathways of the NHS Practitioner Training Programme BSc Healthcare Science (NSHCS-accredited, ~50 weeks of placement); registration voluntary on the AHCS register (PSA-accredited); RCCP merged into AHCS; one HEIW snippet mentions HCPC (conflicting) | folded into **Healthcare Science** (one subject, one intent); stays RESEARCH |
| Life-sciences healthcare science | NSHCS + IBMS-accredited degrees lead to HCPC biomedical scientist | belongs with Biomedical Science |
| Radiotherapy and Oncology | HCPC approves programmes under that name; graduates register as therapeutic radiographers | alias of **Therapeutic Radiography**, not a new subject |
| Dental hygiene vs dental therapy | two GDC titles; one combined three-year BSc is the norm | one subject, records both titles |
| Osteopathy (GOsC), Chiropractic (GCC) | statutory; four-year degrees; Nescot, ESO and BCOM state they sponsor or welcome international students; six GCC providers, HSU publishes an international fee | stay RESEARCH (no Nigerian demand observed) with availability recorded |
| Ophthalmic dispensing (GOC) | three-year BSc at Glasgow Caledonian; ARU accelerated route appears UK-only | **new subject**, RESEARCH (V47) |
| Hearing aid dispenser (HCPC) | two-year FdSc, mostly apprenticeship | **new subject**, RESEARCH (V48) |
| Nursing associate (NMC, England only) | foundation degree, mostly apprenticeship; BCU excludes international students | **new subject**, REJECTED (V49) |
| Physician associate (GMC since December 2024) | mainly postgraduate; title and scope changing after the 2025 review | **new subject**, REJECTED (V50) |
| Clinical scientist (HCPC) | postgraduate STP only | **new subject**, REJECTED (V51) |
| Sonography | not a protected title; mostly postgraduate | **new subject**, REJECTED (V52) |

The legacy bundled rows B04–B06 contradicted these per-subject decisions (B06 rejected physiotherapy and radiography while V06/V08/V09 held them at RESEARCH); they are now marked superseded and point to cluster V.

## D. Open items
1. ~~Regulator and availability research (§A, §B)~~ done 2026-10-03 at snippet level; each FACT still needs the official page read before it becomes a `reference_fact` (owner network allow-list or worksheet round trip).
2. Owner Semrush lookups (database `ng`, then `uk`) for every subject's query family (nursing and dentistry families now in `data/semrush/lookup-sheet.csv`); the SERP labels above are observation, volumes unknown until then.
3. Facts for any subject page go through `reference_facts` with sources, never from the snippets in this document.
4. Osteopathy and Chiropractic availability not yet swept (private providers; low Nigerian relevance).
5. Per-university availability must be modelled before any subject page: the `courses.profession` column exists; a non-medicine `courses` row is created only from a read official page.
