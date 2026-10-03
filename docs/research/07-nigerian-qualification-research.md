# 07 — Nigerian qualifications vs UK medical school requirements (2027 entry)

Research date: 2026-10-03. Cycle: **2027 entry** (UCAS deadline for Medicine 15 October 2026).
Author: research agent, StudyMedicineUKNigeria.com.

> **Read this first.** Every fact below was located through web-search result snippets that quote official pages. Direct page fetches were blocked by the session network policy (see `docs/DATA-AVAILABILITY.md`). Therefore **every statement carries `VERIFY-ON-PAGE`**: open the cited URL and confirm before the statement is shown to a student. Nothing has been generalised across universities: where a university publishes nothing Nigeria-specific, the table says `NOT PUBLISHED — confirm directly with the university`.

## 1. Methodology

| Step | What was done | Limits |
|---|---|---|
| Scope | 30 UK medical schools named in the brief, A100 (undergraduate Medicine) first; GEM (A101/A102), foundation routes, English, age/fee status as secondary topics. | — |
| Channel | `WebSearch` (extended mode), one query per university of the form "<University> Nigeria entry requirements WAEC/WASSCE medicine", plus topical queries. 30 university queries completed. | 36 further planned queries (GEM per university, pathway providers, GOV.UK SELT, age, fee status, UK ENIC) were **refused: session web-search budget exhausted (200/200)**. Those topics are marked `DATA UNAVAILABLE (search budget)`. |
| Direct fetch | Not attempted: external WebFetch/curl blocked by policy. | No statement is `VERIFIED`. |
| Source grading | **Authoritative**: university `.ac.uk` pages, pathway-provider pages owned by the university (e.g. `isc.cardiff.ac.uk`, `upic.navitas.com`), UCAS, Medical Schools Council, UK ENIC, GOV.UK. **Lead only**: agent/tutoring sites (theukcatpeople, themediclife, medichut, future-doc, medentry, uniadmissions, bluepeanut, collegedunia, shiksha etc.) — used only to flag a claim that must then be checked on the official page; labelled `AGENT-LEAD`. | — |
| Labels | `FACT` = statement attributed to an official page via snippet; `SOURCE` = URL; `INFERENCE` = our reasoning; `RECOMMENDATION` = what the site should do. Status values: `VERIFY-ON-PAGE`, `NOT PUBLISHED`, `AGENT-LEAD`, `DATA UNAVAILABLE`, `CONFLICT`. | — |

### Headline count (for the master table below)

- **Any Nigeria / WAEC / NECO statement located on an official page: 18 of 30** — Manchester, Sheffield, Birmingham, Liverpool, Nottingham, Leicester, Plymouth, QMUL (IFY page only — partial), Edinburgh, Glasgow, Aberdeen, Cardiff, Queen's Belfast, Lancashire (UCLan), Aston, Brunel, Lincoln (English only, course closed to international — partial), Wolverhampton.
- **Of those, medicine-specific (the statement sits on a Medicine page or names Medicine): 7** — Aberdeen ("cannot be considered for Medicine"), Leicester ("WASSCE/WAEC alone is not sufficient" on the Medicine international page), Lancashire (MBBS international page: Nigeria → recognised foundation; WAEC/NECO English B3), Aston (WASSCE English C4 for MBChB), Brunel (MBBS international PDF: Nigerian ND/HND at Distinction), Cardiff (Nigeria page names "Medicine and Dental Surgery" subject rule — partial), Glasgow (Medical School international table carries a WAEC row for Ghana; Nigeria row not captured — unconfirmed).
- **No Nigeria-specific statement found: 12** — Leeds, Newcastle, Exeter, KCL, UCL, Imperial, Dundee, Buckingham, Greater Manchester, Sunderland (closed to international), Kent & Medway, Chester (GEM only; country page general).
- These counts match `data/qualifications/university-statements.json` (`waec_statement_is_medicine_specific` = true on 7 records).

### The pattern (FACT, per-university; INFERENCE in the summary sentence)

Every university that *does* publish a Nigeria page treats WASSCE/NECO SSCE as **below A-level** and routes the holder to a **foundation year / A-levels / IB / first year of a Nigerian degree** (Manchester, Liverpool, Nottingham, Edinburgh, Plymouth, QUB, Wolverhampton, Lancashire, Aberdeen). **Not one official page located says WASSCE/NECO qualifies for direct entry to A100.** INFERENCE: the site should say exactly that — "we found no UK medical school that publishes direct A100 entry on WASSCE/NECO alone; here is what each one says" — and *never* the generalisation "WAEC is/isn't accepted by UK medical schools".

A second, more useful pattern: several universities **do accept WAEC/NECO English** as evidence of English language for undergraduate entry (Manchester, Sheffield, Nottingham, Aberdeen, Cardiff, QUB, Lincoln, Lancashire B3, Aston C4 for MBChB, Glasgow C6 for Gateway). Whether the general rule applies to Medicine, which usually carries a higher IELTS bar (7.0–7.5), is stated clearly only by **Aston (C4 for MBChB)** and **Lancashire (B3 for MBBS)**; elsewhere it must be checked.

## 2. Master table

Columns: University | Course | WAEC/NECO statement | A-level requirement (A100 unless stated) | GEM open to international? | English requirement | Foundation route | Source | Status

| University | Course | WAEC/NECO statement (as published) | A-level requirement | GEM international? | English requirement | Foundation route | Source | Status |
|---|---|---|---|---|---|---|---|---|
| Manchester | MBChB A100 | Nigeria page: WASSCE holders "will need to successfully complete a University-recognised foundation programme before joining an undergraduate course". | AAA incl. Chemistry + one of Biology/Physics/Maths; IB 36 (6,6,6 HL). | A101 page exists for 2027; international eligibility DATA UNAVAILABLE. | IELTS/TOEFL etc.; Nigeria page: "can usually accept a high score in English in WAEC exams (if taken within the last seven years)" — check whether this applies to MBChB. | University-recognised foundation (INTO Manchester not confirmed this session). | https://www.manchester.ac.uk/study/international/country-specific-information/nigeria/entry-requirements/ ; https://www.manchester.ac.uk/study/undergraduate/courses/2027/01428/mbchb-medicine/ ; https://www.manchester.ac.uk/study/undergraduate/courses/2027/21177/mbchb-medicine-graduate-entry/ | VERIFY-ON-PAGE |
| Leeds | MBChB A100 | NOT PUBLISHED — no Leeds Nigeria page located (Leeds Beckett page is a different university). | AAA incl. Chemistry and Biology; 6 GCSEs at 6+ incl. Maths, English, Biology, Chemistry. | DATA UNAVAILABLE | IELTS 7.5 (snippet says 7.5 in spoken English; confirm component rule). | NOT PUBLISHED | https://courses.leeds.ac.uk/5580/medicine-and-surgery-mbchb | VERIFY-ON-PAGE / NOT PUBLISHED (Nigeria) |
| Sheffield | MBChB A100 | Nigeria page: "accepts a number of different qualifications from Nigeria"; WASSCE English grade C or above "can be accepted in place of IELTS or equivalent" (general UG statement). | AAA (official medicine-admissions page); Chemistry or Biology + second science. AGENT-LEAD claims A*AA/IB 36 for international — CONFLICT, verify. | DATA UNAVAILABLE | Medicine: IELTS 7.5, no element below 7.0 (AGENT-LEAD; verify on medicine-admissions page). WASSCE English C per Nigeria page — check if Medicine is excluded. | NOT PUBLISHED for Medicine. | https://sheffield.ac.uk/international/entry-requirements/nigeria ; https://sheffield.ac.uk/smph/undergraduate/medicine-admissions | VERIFY-ON-PAGE / CONFLICT |
| Newcastle | MBBS A100 | NOT PUBLISHED — no Newcastle Nigeria page located. | AAA; IB reported as 36 with HL Bio or Chem + second HL science (snippet; Newcastle has historically asked 38 — verify). International applicants ranked on UCAT only. | A101 exists; international eligibility DATA UNAVAILABLE. | IELTS 7.5 overall, 7.0 each (AGENT-LEAD). | NOT PUBLISHED | https://www.ncl.ac.uk/international/language/ ; course page not captured — locate on ncl.ac.uk | NOT PUBLISHED (Nigeria) |
| Birmingham | MBChB A100 | Birmingham lists "West Africa Examinations Council (WAEC) Senior School Certificate" among accepted qualifications for international applicants, and says it "has a number of agreements with foundation providers in Nigeria". Context (GCSE-equivalent vs A-level-equivalent) not visible in snippet — verify. | A*AA (standard; verify on five-year entry page). International: threshold check → ranked by total UCAT (excl. SJT) → personal statement. | A101 exists; international eligibility DATA UNAVAILABLE. | IELTS 7.0 with no band below 7.0; TOEFL 95 (23 each); PTE 67 all skills. | Agreements with Nigerian foundation providers (names not published in snippet). | https://www.birmingham.ac.uk/about/college-of-medicine-and-health/birmingham-medical-school/applying-to-medicine/entry-requirements-medicine-and-surgery-five-year ; https://www.birmingham.ac.uk/study/undergraduate/subjects/medicine-courses/medicine-and-surgery-mbchb | VERIFY-ON-PAGE |
| Liverpool | MBChB A100 | Nigeria page: holders of the Senior Secondary School Certificate "are advised to complete a Foundation Certificate at the University of Liverpool International College (or an equivalent foundation programme) or the first year of a bachelor's degree from a recognised university in Nigeria". | AAA incl. Chemistry + one of Biology/Physics/Maths + third academic subject. International places limited; international deadline 15 Oct 2026. | A101 page exists; international eligibility DATA UNAVAILABLE. | IELTS 7.0 overall, 7.0 each component. | Liverpool International College Foundation Certificate (progression to Medicine NOT confirmed). | https://www.liverpool.ac.uk/international/countries/nigeria.php ; https://www.liverpool.ac.uk/courses/medicine-and-surgery-mbchb ; https://www.liverpool.ac.uk/courses/medicine-and-surgery-graduate-entry-mbchb | VERIFY-ON-PAGE |
| Nottingham | BMBS A100 | Nigeria page: "If you have completed the standard year 12 examinations (SSC/WAEC) you must complete a foundation course"; accepts "foundation certificates from selected providers in the UK and Nigeria"; foundation entry typically "three grade Bs and two Cs" at WAEC; students with one good year at a Nigerian university may be considered for direct entry (general UG). | AAA; IB reported 34 (verify); 6 GCSEs at 7 incl. Biology & Chemistry, 6 in Maths & English. | A101 exists; international eligibility DATA UNAVAILABLE. | "can accept WAEC or iGCSE English language qualification at grade C or above" (general) — confirm for BMBS. | Selected foundation providers in UK and Nigeria (list not in snippet). | https://www.nottingham.ac.uk/studywithus/international-applicants/country-info/countryinformation/nigeria.aspx ; https://www.nottingham.ac.uk/studywithus/ugstudy/courses/UG/Medicine-BMBS.html | VERIFY-ON-PAGE |
| Leicester | MBChB A100 | Medicine international page: WASSCE/WAEC "alone is not sufficient. To apply to Leicester Medical School, you would first need to take additional qualifications such as A-levels or the International Baccalaureate." | A*AA incl. Chemistry or Biology + one of Bio/Chem/Phys/Maths/Psych, A* in a science; IB 34 with 7,6,6 HL; GCSE B/6 in English Language, Maths, two sciences; 18 International/EU places; UCAT required. | A101? Not researched. DATA UNAVAILABLE. | IELTS 7.5 (7.0 each); PTE 68. | NOT PUBLISHED | https://le.ac.uk/study/medicine/entry-requirements/international ; https://le.ac.uk/study/medicine/entry-requirements/mbchb-2026 | VERIFY-ON-PAGE (medicine-specific) |
| Exeter | BMBS A100 | NOT PUBLISHED — no WAEC statement found. | A*AA incl. Chemistry and Biology; IB 38 (7,6,6) with 6 in HL Bio & Chem; GCSE English B/6. ~30 overseas places (AGENT-LEAD). UCAT for school leavers. | DATA UNAVAILABLE | IELTS 7.0, no component below 6.5. | NOT PUBLISHED | https://www.exeter.ac.uk/undergraduate-degrees/bmbs-medicine/faqs/ ; https://www.exeter.ac.uk/v8media/recruitmentsites/documents/BMBS_Admissions_Policy_2026_(V4).pdf | NOT PUBLISHED (Nigeria) |
| Plymouth | BMBS A100 | Nigeria page: UG applicants should have "a good Senior Secondary School Certificate (SSSC) or West African Senior School Certificate (WASSC) and an acceptable International Foundation Year or equivalent". BMBS page: applicants with overseas qualifications "should contact the Admissions Team at admissions@plymouth.ac.uk prior to submitting an application". UPIC (Navitas): WAEC 5 passes at D7 for foundation; 5 at C6 for first-year direct (general, not Medicine). | Home criteria apply equivalently to international (A*AA–AAA incl. Biology + second science — verify), plus UCAT. | DATA UNAVAILABLE | IELTS 7.5 with 7.0 in Speaking and Listening, taken within 12 months of entry. | UPIC foundation (progression to BMBS NOT confirmed); BMBS with Foundation (Year 0) page exists — international eligibility DATA UNAVAILABLE. | https://www.plymouth.ac.uk/international/study/international-students-country-guides/africa/nigeria ; https://www.plymouth.ac.uk/courses/undergraduate/bmbs-bachelor-of-medicine-bachelor-of-surgery/entry-requirements ; https://www.plymouth.ac.uk/courses/undergraduate/bmbs-bachelor-of-medicine-bachelor-of-surgery-with-foundation-year-0/entry-requirements ; https://upic.navitas.com/admission/academic-requirements/ | VERIFY-ON-PAGE |
| QMUL (Barts) | MBBS A100 | Only on the International Foundation Year page: WAEC "minimum of 6 credits at C4 or higher" for IFY entry. No WAEC statement on A100 selection criteria. | A*AA incl. Biology or Chemistry + second science; UCAT 3rd decile or above. Up to 24 overseas places for 2027 (AGENT-LEAD). | A101 GEP exists; international eligibility DATA UNAVAILABLE. | IELTS 7.0 incl. 6.5 Writing, 5.5 other components (snippet — verify; unusually low). | QMUL IFY (progression to MBBS NOT confirmed). | https://www.qmul.ac.uk/fmd/study/undergraduate/courses/a100/selection-criteria/ ; https://www.qmul.ac.uk/international-students/pathway-programmes/ify/ify-entry-requirements/ | VERIFY-ON-PAGE (partial) |
| KCL | MBBS A100 | NOT PUBLISHED — no Nigeria statement; KCL assesses international qualifications via UK ENIC (snippet). | A*AA incl. Biology and Chemistry; IB 38 with HL 6 in Bio & Chem; UCAT compulsory. International students "cannot defer entry and must be 18 years old before commencing Year 2" (snippet — verify). | GPEP page exists; international eligibility DATA UNAVAILABLE. | King's Band B. | King's International Foundation Programme (Health, Life & Biosciences pathway) students "can now apply to Medicine MBBS"; eligibility excludes those who already hold A-levels/IB. | https://www.kcl.ac.uk/study/undergraduate/courses/medicine-mbbs/requirements ; https://www.kcl.ac.uk/assets/pdf24/kings-international-foundation-programme-medicine-mbbs-factsheet-2025-26.pdf ; https://www.kcl.ac.uk/study/undergraduate/courses/medicine-graduate-professional-entry-mbbs/requirements | NOT PUBLISHED (Nigeria) / VERIFY-ON-PAGE |
| UCL | MBBS BSc A100 | NOT PUBLISHED — UCL "does not consider international qualifications unless explicitly listed on the online prospectus". | A*AA; GCSE B/6 in English Language and Maths; 24 overseas places and higher UCAT bar for overseas (AGENT-LEAD, 3060 cited for 2025). | DATA UNAVAILABLE (UCL has no GEM). | IELTS 7.5 overall, 7.0 each (AGENT-LEAD). | UPCSE (UCL's own one-year foundation) — official PDF "Applying to UCL Medicine MBBS BSc from UPCSE". | https://www.ucl.ac.uk/medical-sciences/divisions/medical-school/study/undergraduate/mbbs-admissions/entry-requirements/2027-entry ; https://www.ucl.ac.uk/languages-international-education/sites/languages-international-education/files/Applying_to_UCL_Medicine_MBBS_BSc_from_UPCSE.pdf | NOT PUBLISHED (Nigeria) / VERIFY-ON-PAGE |
| Imperial | MBBS/BSc A100 | NOT PUBLISHED. | A*AA incl. Chemistry and Biology, A* in one; IB 38. International subject to same academic and UCAT requirements; 74 overseas places (AGENT-LEAD). | A109 GEM exists; international eligibility DATA UNAVAILABLE. | Imperial "higher" English requirement (score not captured). | NOT PUBLISHED | https://www.imperial.ac.uk/study/courses/undergraduate/medicine/ | NOT PUBLISHED (Nigeria) |
| Edinburgh | MBChB A100 | Nigeria page: "Applicants with either the West African Examinations Council (WAEC) or the Nigerian Examinations Council (NECO) Senior School Certificate will usually be required to complete a foundation year." Admits Nigerians with A-levels, IB, SAT/ACT/AP. | IB 38 (6,6,6 HL); A-level AAA (verify). | DATA UNAVAILABLE | IELTS 7.5 with 7.5 in each component. | Edinburgh International Foundation Programme (progression to MBChB NOT confirmed). | https://www.ed.ac.uk/studying/international/country/africa/nigeria ; https://study.ed.ac.uk/programmes/undergraduate/354-mbchb-medicine-6-year-programme/entry-requirements | VERIFY-ON-PAGE |
| Glasgow | MBChB A100 | Medical School international entry requirements page: country table. For **Ghana** WAEC: "majority A grades, including in required subjects, with no other grades lower than a B" — suggests Glasgow may list WASSCE for Medicine; **Nigeria row not captured** — verify. Gateway to Medical Studies: WAEC/SSSC English C6 accepted as English qualification. | AAA (A-level) / AAAAB Highers equivalent; UCAT; international UCAT ~2220 (AGENT-LEAD). | DATA UNAVAILABLE | Per Glasgow English table; Gateway accepts WAEC English C6. | Gateway to Medical Studies (check international eligibility). | https://www.gla.ac.uk/schools/medicine/mus/admissions/internationalentryrequirements/ ; https://www.gla.ac.uk/undergraduate/degrees/gateway-to-medical-studies/ ; https://www.gla.ac.uk/undergraduate/degrees/medicine/entry-requirements/ | VERIFY-ON-PAGE (potential exception — high priority) |
| Aberdeen | MBChB A100 | Nigeria entry page: "The WAEC Senior School Certificate cannot be considered for Medicine"; WAEC with at least one A and two Bs → direct Year 1 "for all programmes except Medicine". | AAA incl. Chemistry + one of Biology/Maths/Physics + one other; IB 36 with 3 HL at 6 incl. HL Chemistry. | DATA UNAVAILABLE | IELTS 7.0 (L 5.5, R 5.5, S 7.0, W 6.0); Nigeria page: WAEC/NECO English C6 or Medium-of-Instruction letter may waive IELTS — confirm whether Medicine excluded. | Aberdeen ISC (general). | https://www.abdn.ac.uk/study/international/inmycountry/nigeria/entry/ ; https://www.abdn.ac.uk/smmsn/undergraduate/medicine/international-requirements.php | VERIFY-ON-PAGE (medicine-specific) |
| Dundee | MBChB A100 | NOT PUBLISHED. | AAA; IB 37 with 6,6,6 HL incl. Chemistry + Bio/Phys/Maths. | ScotGEM — DATA UNAVAILABLE. | IELTS 7.0 / TOEFL 95 / PTE 76 (AGENT-LEAD). | NOT PUBLISHED | Official course page not captured — locate on dundee.ac.uk | NOT PUBLISHED (Nigeria) |
| Cardiff | MBBCh A100 | Nigeria entry page: WAEC "successful completion with grades 1-6 (A-C) in 4 or more relevant subjects"; for "Health and Life Sciences including Medicine and Dental Surgery", Chemistry and two other Sciences (Maths counts). INFERENCE: this reads as a GCSE-level subject rule, not A100 direct entry — verify. | A*AA incl. Chemistry and Biology (verify). | A101 page exists; international eligibility DATA UNAVAILABLE. | WAEC or IGCSE English "can be considered at the appropriate grade, in lieu of IELTS" for UG programmes. | Cardiff ISC International Foundation Year (Medicine) "is accepted for entry to a medical degree at Cardiff University, subject to grades and other conditions". | https://www.cardiff.ac.uk/study/international/your-country/africa/nigeria/entry-requirements ; https://isc.cardiff.ac.uk/how-to-apply/entry-requirements ; https://www.cardiff.ac.uk/study/undergraduate/courses/course/medicine-mbbch | VERIFY-ON-PAGE |
| Queen's Belfast | MB A100 | Nigeria page: "Applicants with the West African Examinations Council (WAEC) Senior Secondary School Certificate will be required to complete a Foundation Programme." | A*AA or AAA + A at AS; same for international; international fee-payers interviewed online. | DATA UNAVAILABLE | QUB accepts WAEC or NECO for English language requirements (general). | Foundation Programme (INTO Queen's not confirmed). | https://www.qub.ac.uk/Study/international-students/your-country/nigeria ; https://www.qub.ac.uk/courses/undergraduate/medicine-mb-a100/ | VERIFY-ON-PAGE |
| Buckingham | MB ChB (4.5 yr, private) | NOT PUBLISHED — no WAEC statement; "alternative secondary school qualifications ... at a level equivalent to A-level ... pattern of grades equivalent to ABB". | ABB incl. Chemistry or Biology (or equivalent); Buckingham Pre-Med (Cert HE Medical Science) at 70%+. No UCAT; outside UCAS; same fee home/international (AGENT-LEAD ~£43k). | n/a | IELTS 7.0 (AGENT-LEAD). | Buckingham Pre-Med; Kings Education "University of Buckingham Medical pathway" factsheet. | https://medvle.buckingham.ac.uk/course/section.php?id=10 ; https://www.kingseducation.com/assets/pdf/uk-uni/factsheets/university-of-buckingham-medical-pathway.pdf | VERIFY-ON-PAGE |
| Lancashire (UCLan) | MBBS A100; MBBS with Foundation Entry | MBBS international requirements page, Nigeria: "successful completion of a recognised international foundation programme" required; English "IELTS 7.0 (7.0 in each component) or WAEC/NECO B3". | Foundation Entry: ~ABB-equivalent in two sciences incl. Chemistry; IELTS 6.5; progression to MBBS on ~70% in foundation year (AGENT-LEAD figures — verify). | n/a | IELTS 7.0 each; WAEC/NECO English B3 accepted. | 6-year MBBS with Foundation Entry (international only), Westlakes campus; ONCAMPUS UCLan Medicine Undergraduate Foundation Programme. | https://www.lancashire.ac.uk/undergraduate/how-to-apply/mbbs/international-requirements ; https://www.lancashire.ac.uk/international-students/country/nigeria ; https://www.lancashire.ac.uk/undergraduate/courses/medicine-mbbs | VERIFY-ON-PAGE (medicine-specific) |
| Greater Manchester (Bolton) | MBChB | NOT PUBLISHED — "normally uses UK ENIC to assess for equivalence". 2027 cohort international-only (no DHSC/NHSE home allocation). | AAB incl. Chemistry or Biology + one of Chem/Bio/Phys/Maths; IB 34 (6,6,5); 5 GCSEs at 6–9 incl. Maths, English, two sciences. | n/a | Not captured. | Not captured. | https://medicine.greatermanchester.ac.uk/medicine-mbchb-2026-academic-requirements/ ; https://medicine.greatermanchester.ac.uk/undergraduate-medical-school/ | NOT PUBLISHED (Nigeria) / VERIFY-ON-PAGE |
| Sunderland | MBChB | Official pages: "unable to accept international applicants"; does not assess international equivalences. An AGENT-LEAD claims a small overseas intake — CONFLICT; trust official. | AAA incl. Biology and Chemistry; 5 GCSEs at 6. | n/a | n/a | n/a | https://www.sunderland.ac.uk/undergraduate/mbchb-medicine ; https://www.sunderland.ac.uk/how-to-apply/applying-for-medicine | CLOSED TO INTERNATIONAL — VERIFY-ON-PAGE |
| Lincoln | MBBS | Medicine "not currently open for international applications" for 2026-27 and 2027-28. Nigeria page (general): WAEC/NECO English C6+ accepted. | AAA / IB 35 when open (AGENT-LEAD). | n/a | IELTS 7.5 (7.0) when open (AGENT-LEAD). | n/a | https://www.lincoln.ac.uk/course/mdcmbcub/ ; https://www.lincoln.ac.uk/studywithus/internationalstudents/entryrequirementsandyourcountry/nigeria/ | CLOSED TO INTERNATIONAL — VERIFY-ON-PAGE |
| Aston | MBChB A100 | Nigeria page: "For entry onto the MBChB, a minimum grade of C4 in English Language in the West African Secondary School Certificate" is required (English evidence). Overseas qualifications equated to GCSE/A-level grades. | AAA (verify) incl. Chemistry & Biology; 6 GCSEs at B/6 incl. Maths, English, Chemistry, Biology; UCAT. | n/a | IELTS 7.0 each (AGENT-LEAD) or WASSCE English C4 (official). | Aston International Foundation Programme (progression to MBChB NOT confirmed). | https://www.aston.ac.uk/international/aston-in-your-country/africa/nigeria ; https://www.aston.ac.uk/study/courses/medicine-mbchb | VERIFY-ON-PAGE (medicine-specific English rule) |
| Brunel | MBBS | MBBS international entry requirements PDF (2025/6): for Nigeria, "National Diploma or Higher National Diploma in a subject cognate to medicine", minimum grade Distinction. No WASSCE route listed in snippet. International applicants do not require UCAT/GAMSAT. Email BMS-Admissions@brunel.ac.uk if qualification not listed. (Snippet's "77666/66666/85%" rows belong to other countries — ignore.) | Subject criteria: Biology or Chemistry + one of Bio/Chem/Maths/Physics. | n/a | IELTS 7.0 in each component. | Brunel pathway college not researched. | https://www.brunel.ac.uk/brunel-medical-school/documents/pdf/MBBS-international-entry-requirements-2025-entry-approved.pdf ; https://www.brunel.ac.uk/study/courses/medicine-mbbs | VERIFY-ON-PAGE (medicine-specific, 2027 version needed) |
| Chester | Graduate Entry Medicine MBChB (A101) | Country page is general (no WAEC/A100 — Chester has no A100). GEM: 2:1 "or overseas equivalent" in any subject; 2:2 holders need Masters/Doctorate. | n/a | **YES — international applicants accepted** (dedicated "Applying for Graduate Entry Medicine" international page). 70 hrs healthcare experience in last 3 years; MMI; apply via UCAS; Enhanced DBS plus overseas evidence. No UCAT/GAMSAT mentioned in snippet — verify. | IELTS 7.0 with max two components at 6.0/6.5. | n/a | https://www.chester.ac.uk/study/course-search/graduate-entry-medicine-mbchb/ ; https://www.chester.ac.uk/international/how-to-apply/applying-for-graduate-entry-medicine/ ; https://www.chester.ac.uk/international/countries/nigeria/ | VERIFY-ON-PAGE |
| Kent & Medway (KMMS) | BM BS A100 | NOT PUBLISHED — international qualifications considered "according to their UK equivalencies (to GCSE and/or A level) as per the University of Kent policies"; international applicants assessed as Group E, AAB equivalent. | AAB-equivalent for international. | Graduate international applicants need UK 2:1-equivalent (same A100 programme). | IELTS 7.0 overall, 7.0 each section. | NOT PUBLISHED | https://kmms.ac.uk/entry-requirements-2027/ ; https://kmms.ac.uk/study/applying/ | NOT PUBLISHED (Nigeria) / VERIFY-ON-PAGE |
| Wolverhampton (Black Country MS) | MBChB | Nigeria page (general UG): WAEC/NECO "Grade B profile" or WAEC/NECO plus recognised foundation or year one at a Nigerian university. MBChB 2027 international-only, 50 places. | AAA incl. Chemistry and Biology; IB 37 (7,6,6) with HL Chem & Bio; 6 GCSEs at 6/B. | n/a | Not captured. | Not captured. | https://www.wlv.ac.uk/courses/mbchb-medicine/ ; https://www.wlv.ac.uk/international/your-country/nigeria/ | VERIFY-ON-PAGE |

## 3. Detailed per-university notes

Each bullet = FACT as quoted in a search snippet of the cited page, status `VERIFY-ON-PAGE` unless marked.

### Manchester
- FACT: Nigeria entry-requirements page — "Due to differences in the Nigerian and UK education systems, applicants who have completed the WASSCE will need to successfully complete a University-recognised foundation programme before joining an undergraduate course." SOURCE: https://www.manchester.ac.uk/study/international/country-specific-information/nigeria/entry-requirements/
- FACT: MBChB A100 standard offer AAA incl. Chemistry + Biology/Physics/Maths; IB 36 (6,6,6 HL). SOURCE: 2027 course page.
- FACT: English — "can usually accept a high score in English in WAEC exams (if taken within the last seven years)". Whether MBChB (which carries a higher English bar) accepts it: NOT STATED in snippet.
- FACT: Medicine (MBChB) not eligible for international scholarships. SOURCE: https://www.manchester.ac.uk/study/international/country-specific-information/nigeria/scholarships/
- GEM: 2027 A101 page exists; international eligibility DATA UNAVAILABLE (search budget).

### Leeds
- NOT PUBLISHED: no Leeds Nigeria page surfaced. Course page gives AAA incl. Chemistry and Biology; 6 GCSEs at grade 6 incl. Maths, English Language, Biology, Chemistry. IELTS 7.5 (component rule to verify). SOURCE: https://courses.leeds.ac.uk/5580/medicine-and-surgery-mbchb
- RECOMMENDATION: email ugmadmissions@leeds.ac.uk (address quoted in snippet) and record the answer as a dated email, not as "published".

### Sheffield
- FACT: Nigeria page — accepts "a number of different qualifications from Nigeria"; WASSCE English grade C or above accepted in place of IELTS (general). SOURCE: https://sheffield.ac.uk/international/entry-requirements/nigeria
- FACT: Medicine admissions page — AAA; Chemistry or Biology + second science; three-stage selection (academic, UCAT, MMI). SOURCE: https://sheffield.ac.uk/smph/undergraduate/medicine-admissions
- CONFLICT: AGENT-LEAD (theukcatpeople) says international A*AA / IB 36 and IELTS 7.5 (7.0). Verify on official page before use.

### Newcastle
- NOT PUBLISHED (Nigeria). FACT (snippets): AAA; international applicants' academic scoring "based only on UCAT results"; IELTS typically 7.5/7.0 (AGENT-LEAD). IB figure in snippet (36) conflicts with memory of 38 — verify.

### Birmingham
- FACT: "The University of Birmingham accepts West Africa Examinations Council (WAEC) Senior School Certificate as an accepted qualification for international applicants" and "has a number of agreements with foundation providers in Nigeria which allow students to be considered for admission to undergraduate programmes." Level of equivalence not visible — INFERENCE: GCSE-level, as elsewhere; must verify.
- FACT: international English IELTS 7.0 no band <7.0; TOEFL 95 (23); PTE 67. International selection: threshold → UCAT total excl. SJT → personal statement.

### Liverpool
- FACT: Nigeria page — SSSC holders advised to complete Liverpool International College Foundation Certificate (or equivalent) or first year of a Nigerian bachelor's. SOURCE: https://www.liverpool.ac.uk/international/countries/nigeria.php
- FACT: A100 AAA incl. Chemistry + one of Bio/Phys/Maths; IELTS 7.0 each; international places limited; international deadline 15 October 2026; "A100 Departmental Supplement Entry 2027" referenced — obtain it.

### Nottingham
- FACT: Nigeria page — SSC/WAEC → foundation required; selected foundation providers in UK and Nigeria accepted; typical foundation entry 3 Bs + 2 Cs; WAEC/iGCSE English grade C+ accepted for English (general). SOURCE: https://www.nottingham.ac.uk/studywithus/international-applicants/country-info/countryinformation/nigeria.aspx
- FACT: BMBS AAA; 6 GCSEs at 7 incl. Biology and Chemistry; 6 in Maths & English. IB figure (34) in snippet — verify.

### Leicester
- FACT (medicine-specific): "West African Senior School Certificate Examination (WASSCE) or WAEC alone is not sufficient. To apply to Leicester Medical School, you would first need to take additional qualifications such as A-levels or the International Baccalaureate." SOURCE: https://le.ac.uk/study/medicine/entry-requirements/international
- FACT: A*AA (A* in a science) incl. Chemistry or Biology; IB 34 with 7,6,6; GCSE B/6 English Language (first language), Maths, two sciences; 18 International/EU places; UCAT; IELTS 7.5 (7.0), PTE 68.

### Exeter
- NOT PUBLISHED (Nigeria). FACT: A*AA incl. Chemistry and Biology; IB 38 (7,6,6) with 6 in HL Bio and Chem; GCSE English B/6; IELTS 7.0 (6.5); UCAT for school leavers. SOURCE: FAQs page and Admissions Policy 2026 PDF.

### Plymouth
- FACT: Nigeria page — WASSC + acceptable International Foundation Year; UPIC foundation offered. SOURCE: https://www.plymouth.ac.uk/international/study/international-students-country-guides/africa/nigeria
- FACT: BMBS entry page — overseas-qualified applicants must contact admissions@plymouth.ac.uk before applying via UCAS; IELTS 7.5 with 7.0 Speaking and Listening within 12 months.
- FACT: UPIC — WAEC 5 passes at D7 (foundation) / C6 (first-year direct), general programmes. SOURCE: https://upic.navitas.com/admission/academic-requirements/
- GAP: BMBS with Foundation (Year 0) — is it open to international? DATA UNAVAILABLE.

### QMUL (Barts and The London)
- FACT: IFY entry — WAEC 6 credits at C4 or higher. SOURCE: https://www.qmul.ac.uk/international-students/pathway-programmes/ify/ify-entry-requirements/
- FACT: A100 — A*AA incl. Bio/Chem + second science; UCAT third decile+. SOURCE: selection criteria page. English IELTS 7.0 (6.5 W, 5.5 others) per snippet — unusually low for Medicine; verify.
- AGENT-LEAD: up to 24 overseas places for 2027.

### KCL
- NOT PUBLISHED (Nigeria). FACT: A*AA incl. Biology & Chemistry; IB 38 (HL 6 Bio & Chem); UCAT compulsory; Band B English; international students "cannot defer entry and must be 18 years old before commencing Year 2" (verify wording).
- FACT: King's IFP Health, Life & Biosciences pathway students may apply to Medicine MBBS; must not already hold A-levels/IB. SOURCE: KCL IFP Medicine factsheet 2025-26 PDF.

### UCL
- NOT PUBLISHED (Nigeria). FACT: UCL does not consider international qualifications not listed in the prospectus. A*AA; GCSE B/6 English & Maths. UPCSE → Medicine route documented in official PDF. AGENT-LEAD: 24 overseas places; higher UCAT bar for overseas.

### Imperial
- NOT PUBLISHED (Nigeria). FACT: A*AA incl. Chemistry & Biology (A* in one); IB 38; international same academic/UCAT requirements. AGENT-LEAD: 74 overseas places.

### Edinburgh
- FACT: Nigeria page — WAEC or NECO SSC holders "will usually be required to complete a foundation year". SOURCE: https://www.ed.ac.uk/studying/international/country/africa/nigeria
- FACT: MBChB IB 38 (6,6,6); IELTS 7.5 with 7.5 in each component.

### Glasgow
- FACT: Medical School international entry requirements page has a country table; Ghana WAEC row: "majority A grades, including in required subjects, with no other grades lower than a B". **Nigeria row not captured.** If Glasgow lists Nigerian WASSCE similarly, it would be the only A100 we found that scores WASSCE directly — HIGH-PRIORITY VERIFY. SOURCE: https://www.gla.ac.uk/schools/medicine/mus/admissions/internationalentryrequirements/
- FACT: Gateway to Medical Studies accepts WAEC/SSSC English C6 as English qualification. SOURCE: https://www.gla.ac.uk/undergraduate/degrees/gateway-to-medical-studies/

### Aberdeen
- FACT (medicine-specific): "The WAEC Senior School Certificate cannot be considered for Medicine"; WAEC 1A+2B → direct Year 1 for all programmes except Medicine. SOURCE: https://www.abdn.ac.uk/study/international/inmycountry/nigeria/entry/
- FACT: AAA incl. Chemistry + Bio/Maths/Phys; IB 36 (3 HL at 6 incl. HL Chemistry); IELTS 7.0 (L5.5 R5.5 S7.0 W6.0); WAEC/NECO English C6 or MOI letter may waive IELTS (check Medicine exclusion). SOURCE: https://www.abdn.ac.uk/smmsn/undergraduate/medicine/international-requirements.php

### Dundee
- NOT PUBLISHED (Nigeria). FACT: AAA; IB 37 (6,6,6 HL incl. Chemistry + science). AGENT-LEAD: IELTS 7.0 / TOEFL 95 / PTE 76.

### Cardiff
- FACT: Nigeria entry page — WAEC grades 1-6 (A-C) in 4+ relevant subjects; for Health & Life Sciences incl. Medicine: Chemistry + two other sciences (Maths counts). INFERENCE: GCSE-equivalence rule. SOURCE: https://www.cardiff.ac.uk/study/international/your-country/africa/nigeria/entry-requirements
- FACT: Cardiff ISC International Foundation Year (Medicine) "accepted for entry to a medical degree at Cardiff University, subject to grades and other conditions". SOURCE: https://isc.cardiff.ac.uk/how-to-apply/entry-requirements
- FACT: WAEC/IGCSE English may be considered in lieu of IELTS for UG.

### Queen's Belfast
- FACT: Nigeria page — WAEC SSC holders "will be required to complete a Foundation Programme"; WAEC/NECO accepted for English. SOURCE: https://www.qub.ac.uk/Study/international-students/your-country/nigeria
- FACT: A*AA or AAA + A at AS; international fee-payers interviewed online.

### Buckingham
- FACT: ABB incl. Chemistry or Biology or equivalent; Pre-Med Cert HE at 70%; no UCAT; outside UCAS. SOURCE: https://medvle.buckingham.ac.uk/course/section.php?id=10 ; Kings Education pathway factsheet. AGENT-LEAD: same fee home/international, ~40-50% international cohort, IELTS 7.0.

### Lancashire (UCLan)
- FACT (medicine-specific): MBBS international requirements, Nigeria: recognised international foundation programme required; English IELTS 7.0 (7.0 each) **or WAEC/NECO B3**. SOURCE: https://www.lancashire.ac.uk/undergraduate/how-to-apply/mbbs/international-requirements
- FACT: MBBS with Foundation Entry (6 yr) — international only; Westlakes campus; AGENT-LEAD figures ABB-equivalent, IELTS 6.5, ~70% progression threshold. ONCAMPUS UCLan Medicine Undergraduate Foundation Programme also exists.

### Greater Manchester
- NOT PUBLISHED (Nigeria); uses UK ENIC. FACT: AAB; IB 34 (6,6,5); 5 GCSEs 6–9. 2027 international-only. SOURCE: https://medicine.greatermanchester.ac.uk/medicine-mbchb-2026-academic-requirements/ (2027 page to locate).

### Sunderland
- FACT: not accepting international applicants; does not assess international equivalences. CONFLICT with one agent site. SOURCE: https://www.sunderland.ac.uk/how-to-apply/applying-for-medicine

### Lincoln
- FACT: Medicine closed to international applications 2026-27 and 2027-28. SOURCE: https://www.lincoln.ac.uk/course/mdcmbcub/ . Nigeria page: WAEC/NECO English C6+ (general).

### Aston
- FACT (medicine-specific English): "For entry onto the MBChB, a minimum grade of C4 in English Language in the West African Secondary School Certificate in Education is required." SOURCE: https://www.aston.ac.uk/international/aston-in-your-country/africa/nigeria
- FACT: 6 GCSEs/IGCSEs at B/6 incl. Maths, English, Chemistry, Biology (or overseas equivalents); UCAT; IFP available (progression to MBChB not stated).

### Brunel
- FACT (medicine-specific): 2025/6 MBBS international requirements PDF, Nigeria: ND or HND cognate to medicine at Distinction. International applicants do not need UCAT/GAMSAT. IELTS 7.0 each. SOURCE: https://www.brunel.ac.uk/brunel-medical-school/documents/pdf/MBBS-international-entry-requirements-2025-entry-approved.pdf — obtain 2027 edition.

### Chester
- FACT: GEM MBChB open to international applicants; 2:1 or overseas equivalent (any subject); 2:2 + Masters/PhD considered; IELTS 7.0 (max two components at 6.0/6.5); 70 hrs healthcare experience in 3 years; MMI; UCAS; Enhanced DBS plus overseas evidence per DBS guidance. SOURCE: https://www.chester.ac.uk/international/how-to-apply/applying-for-graduate-entry-medicine/

### Kent & Medway
- NOT PUBLISHED (Nigeria). FACT: international quals by UK equivalency per University of Kent; Group E AAB-equivalent; IELTS 7.0 each; graduate international 2:1-equivalent. SOURCE: https://kmms.ac.uk/entry-requirements-2027/

### Wolverhampton
- FACT: Nigeria page (general UG) — WAEC/NECO Grade B profile, or WAEC/NECO + recognised foundation or year one at a Nigerian university. SOURCE: https://www.wlv.ac.uk/international/your-country/nigeria/
- FACT: MBChB 2027 international-only, 50 places; AAA Chem & Bio; IB 37 (7,6,6); 6 GCSEs at 6. SOURCE: https://www.wlv.ac.uk/courses/mbchb-medicine/

## 4. Topic notes beyond A100

### 4.1 A-levels / IB sat in Nigeria (Cambridge International)
- FACT (per university, above): A100 offers range AAA (Manchester, Leeds, Sheffield, Newcastle, Liverpool, Nottingham, Aberdeen, Dundee, Glasgow, Wolverhampton, Sunderland) to A*AA (Leicester, Exeter, QMUL, KCL, UCL, Imperial, Birmingham, QUB, Cardiff), with AAB at Greater Manchester and KMMS (international), ABB at Buckingham. Chemistry and/or Biology always among required subjects (exact combination differs — see table).
- FACT: Exeter, Plymouth, Imperial, QUB, KMMS state international applicants meet the equivalent of the home academic criteria; Newcastle and Birmingham rank international applicants by UCAT after a threshold check.
- DATA UNAVAILABLE: no university page captured that names "Cambridge International A Level taken in Nigeria" specifically. INFERENCE: Cambridge International A Levels are GCE A Levels and are listed as "A-level" on every course page; no evidence that a Nigerian exam centre changes this. RECOMMENDATION: state that Cambridge International A Levels are A Levels, link each course page, and avoid claiming any school-specific treatment.

### 4.2 Nigerian bachelor's degree → GEM
- FACT: Chester GEM accepts international applicants (details above). KMMS accepts graduate international applicants to its A100 with a 2:1-equivalent degree.
- DATA UNAVAILABLE (search budget): Warwick, Swansea, Nottingham A101, Southampton BM4, St George's, QMUL GEP, Newcastle A101, KCL GPEP, Cambridge, Oxford, ScotGEM, Ulster, Worcester, Sheffield A101, Liverpool A101, Cardiff A101, Birmingham A101, Surrey, Anglia Ruskin, Imperial A109, Manchester A101 — international eligibility, degree class, UK ENIC acceptance and GAMSAT/UCAT not researched in this session. Programme list recorded in `data/qualifications/gem-followup.json` for follow-up.
- DATA UNAVAILABLE: UK ENIC statement of comparability for Nigerian bachelor's degrees. RECOMMENDATION: tell students to obtain a UK ENIC Statement of Comparability (https://www.enic.org.uk/ — URL not verified this session) and that each GEM course page defines "overseas equivalent" itself.

### 4.3 Foundation / Gateway / International Foundation routes
Published, with the university naming Medicine as a destination:
- Lancashire MBBS with Foundation Entry (international only) and ONCAMPUS UCLan Medicine foundation — see Lancashire note.
- Buckingham Pre-Med (Cert HE Medical Science) → MB ChB at 70%+.
- Cardiff ISC International Foundation Year (Medicine) → Cardiff MBBCh "subject to grades and other conditions".
- King's IFP (Health, Life & Biosciences) → may apply to Medicine MBBS.
- UCL UPCSE → may apply to Medicine MBBS BSc (official PDF).
General foundations where progression to Medicine is NOT stated in captured material: Liverpool International College, UPIC Plymouth, QMUL IFY, Edinburgh IFP, Aston IFP, Aberdeen ISC, Birmingham's Nigerian partner providers, Nottingham's selected providers, Manchester "University-recognised foundation". Glasgow Gateway to Medical Studies and Plymouth BMBS with Foundation Year — international eligibility DATA UNAVAILABLE.
- DATA UNAVAILABLE (search budget): INTO / Kaplan / Study Group medicine-progression guarantees. RECOMMENDATION: never write "guaranteed progression to Medicine" unless the provider page says so in those words.

### 4.4 English language
Per-university scores are in the master table. WAEC/NECO English accepted as English evidence (official pages): Manchester (high score, within 7 years, general), Sheffield (grade C, general), Nottingham (grade C, general), Aberdeen (C6 or MOI letter, general), Cardiff (appropriate grade, general), QUB (general), Lincoln (C6, general; Medicine closed), Glasgow (C6, Gateway), **Lancashire (B3, MBBS — medicine-specific)**, **Aston (C4, MBChB — medicine-specific)**.
- DATA UNAVAILABLE (search budget): GOV.UK Student-visa English rule. INFERENCE from prior knowledge (UNVERIFIED THIS SESSION): for degree-level study a Higher Education Provider with a track record of compliance may assess English itself, so a SELT is not automatically required; confirm at https://www.gov.uk/student-visa/knowledge-of-english before publishing.

### 4.5 Age / DBS / occupational health / fee status
- FACT: KCL — international students "must be 18 years old before commencing Year 2" (snippet; verify).
- FACT: Chester GEM — Enhanced DBS required; international applicants provide overseas evidence per DBS guidance.
- DATA UNAVAILABLE (search budget): age-18 rules at other schools; Medical Schools Council guidance on DBS/occupational health/immunisations; UKCISA fee-status rules (3-year ordinary residence, settled status). RECOMMENDATION: cite UKCISA (https://www.ukcisa.org.uk/ — unverified URL) and each university's fee-status page; state that fee status is assessed by the university, not chosen by the applicant.

## 5. Myths vs published facts

| Myth heard from Nigerian applicants | What the published pages actually say (per university) |
|---|---|
| "UK medical schools accept WAEC for Medicine." | No official page located says WASSCE/NECO is sufficient for A100. Aberdeen: "cannot be considered for Medicine". Leicester: "alone is not sufficient". Manchester, Liverpool, Nottingham, Edinburgh, QUB, Plymouth, Lancashire, Wolverhampton: foundation (or A-level/IB/first year of degree) required. |
| "UK medical schools reject WAEC completely." | Also not what pages say: WAEC/NECO English is accepted as English evidence at Aston (C4, MBChB), Lancashire (B3, MBBS) and, for general UG entry, Manchester, Sheffield, Nottingham, Aberdeen, Cardiff, QUB, Lincoln, Glasgow (Gateway). WAEC is treated as the Level-2 (GCSE-type) layer of the application. |
| "A foundation year guarantees Medicine." | Only Lancashire (own Foundation Entry MBBS), Buckingham (Pre-Med), Cardiff ISC, King's IFP and UCL UPCSE publish Medicine as a destination, each with grade conditions. No "guarantee" wording captured. |
| "Any UK medical school takes international students." | Sunderland: not accepting international applicants. Lincoln: closed to international 2026-27 and 2027-28. Greater Manchester and Wolverhampton: 2027 international-only. Leicester 18, QMUL ~24, UCL ~24, Exeter ~30, Imperial ~74 overseas places (last four AGENT-LEAD). |
| "Graduate Entry Medicine is open to Nigerian graduates everywhere." | Confirmed international-open this session: Chester GEM. All others DATA UNAVAILABLE — do not state. |
| "IELTS 6.5 is enough." | Published Medicine requirements range from IELTS 7.0 (Liverpool, Birmingham, Exeter, Aberdeen, KMMS, Brunel, Chester, Lancashire) to 7.5 (Leicester, Plymouth, Sheffield, Edinburgh 7.5 each). Lancashire Foundation Entry: 6.5 (AGENT-LEAD). |

## 6. Gaps

1. **Search budget**: 36 planned queries refused (GEM × 21, pathway providers × 6, GOV.UK SELT, age, fee status, UK ENIC, MSC DBS/OH, Cambridge A-level in Nigeria, Lancaster, Edge Hill). All marked DATA UNAVAILABLE.
2. **No page was read directly**; every row is VERIFY-ON-PAGE.
3. **Glasgow Nigeria row** in the Medical School international table — the only possible direct-WASSCE scoring for A100; unresolved.
4. **2027-specific documents** not obtained: Liverpool "A100 Departmental Supplement Entry 2027", Brunel 2027 international PDF, Greater Manchester 2027 requirements page, Exeter 2027 admissions policy.
5. **Medicine exclusions from general WAEC-English waivers** (Manchester, Sheffield, Nottingham, Aberdeen, Cardiff, QUB) unconfirmed.
6. Lancaster, Edge Hill, Warwick, Swansea, St George's, Southampton, Oxford, Cambridge, Norwich (UEA), Hull York, Keele, Bristol, Southampton, Central Lancashire London etc. not in scope of the 30 and not searched.
7. Place numbers for overseas applicants mostly AGENT-LEAD.

## 7. Recommendations (RECOMMENDATION)

1. Phrase every qualification statement as "**<University> says …** (link, checked <date>)". Never "UK medical schools accept/reject WAEC".
2. Lead with the two sentences the evidence supports: "We found no UK medical school that publishes direct entry to Medicine (A100) on WASSCE/NECO alone. Several say so explicitly (Aberdeen, Leicester); the rest route WASSCE holders to A-levels, IB or a foundation year." Then the per-university table.
3. Present WAEC/NECO as **Level-2 (GCSE-layer) evidence**: subjects/grades and English. Show the medicine-specific English acceptances (Aston C4, Lancashire B3) and label the general ones "general undergraduate rule — confirm Medicine is not excluded".
4. Separate three routes with their own pages: (a) Cambridge International A Levels/IB in Nigeria → A100; (b) foundation routes that publish Medicine as a destination (Lancashire, Buckingham, Cardiff ISC, King's IFP, UCL UPCSE) with the exact conditions; (c) Nigerian degree → GEM, listing only Chester as confirmed and everything else as "to be confirmed".
5. Show an "international places open?" flag per school (Sunderland no; Lincoln no for 2027; Greater Manchester and Wolverhampton international-only in 2027).
6. Build the verification workflow: each JSON record has `status`; nothing with `VERIFY-ON-PAGE` or `AGENT-LEAD` renders to students until a human sets `VERIFIED` with a date.
7. Re-run the 36 refused queries in a fresh session before writing the GEM, visa-English, age and fee-status pages.
