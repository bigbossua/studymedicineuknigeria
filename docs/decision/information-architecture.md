# Information architecture

Status: DECISION DRAFT (brief 58 item 14; 89). Final per-page build decisions live in `page-asset-register.md`. This document fixes the *shape* of the site so that pages, links and breadcrumbs stay coherent as content is added.

## 1. Primary navigation (6 + CTA)

```
Medicine | Medical Schools | Requirements | Fees | Admissions | Student Portal      [APPLY ONLINE]
```
Rationale: matches the brief's homepage secondary navigation (brief 19) and the student decision journey (brief 121). "Working in the UK" and "FAQ" live in the footer and in-context until demand is validated (brief 89, 92).

## 2. Sections and their roles

| Section | Pillar URL | Role in funnel | Core child pages (v1 candidates; see register) |
|---|---|---|---|
| **Medicine** (pillar) | `/study-medicine-in-the-uk/` | answer "can I, how, what does it take" | `/from-nigeria/` (core landing), `/why-study-medicine-in-the-uk/` (factual hub), `/course-structure/`, `/graduate-entry/`, `/foundation-routes/` |
| **Medical Schools** (directory) | `/medical-schools/` | discovery and comparison; data asset | university records, course records, whitelisted filter pages |
| **Requirements** (hub) | `/requirements/` | eligibility understanding; biggest Nigerian intent | `/waec/`, `/neco/`, `/a-levels/`, `/nigerian-degree-graduate-entry/`, `/english-language/`, `/what-do-i-need-to-study-medicine-in-the-uk/` (core asset, brief 107) |
| **Fees** | `/fees/` | cost planning | `/cost-of-studying-medicine-in-the-uk/` (total cost), `/visa-and-immigration-health-surcharge/` |
| **Admissions** | `/admissions/` | process and tests | `/ucat/`, `/ucas-deadlines-2027/`, `/interviews/`, `/personal-statement/`, `/documents/` |
| **Working in the UK / After graduation** | `/working-in-the-uk/` | trust + honest expectations | validate first (brief 92); children: `/during-study/`, `/after-graduation-gmc-registration/` |
| **FAQ** | `/faq/` | query capture, interlinking | questions from research 02 |
| **Apply Online** | `/apply-online/` | commercial gateway | `/services/`, `/eligibility/`, then auth routes |
| **About / Trust** | `/about/`, `/contact/`, legal | identity, policies | privacy, terms, application terms, refund policy, document policy, editorial & verification policy |

## 3. Breadcrumb hierarchy (canonical parents)

- Home → Medicine → {child}
- Home → Medical Schools → {University} → {Course}
- Home → Requirements → {Qualification}
- Home → Fees → {child}
- Home → Admissions → {child}
- Home → FAQ → {question}

Every page has exactly one parent (no poly-hierarchy in breadcrumbs); cross-links handle the rest.

## 4. The connected knowledge model (brief 95–96)

Entities: Course · University · Qualification · Requirement · Test · Fee · Deadline · FAQ · Service. Pages are views over entities; links are relationships with reasons:

```
Qualification(WAEC) ─has statements from→ University(n) ─offers→ Course(n) ─requires→ Test(UCAT) ─has→ Deadline
        │                                       │                     │
        └─explained on→ Requirements hub        └─charges→ Fee        └─leads to→ Apply Online (Service)
FAQ ─depends on→ {any page}  ·  Every informational page ─next step→ Apply Online / Check eligibility
```

## 5. Page-type templates (brief 77) → URL families

search landing (`/study-medicine-in-the-uk/from-nigeria/`), medical course guide (`/study-medicine-in-the-uk/*`), medical school (`/medical-schools/{u}/`), university course (`/medical-schools/{u}/{c}/`), requirements (`/requirements/*`), fees (`/fees/*`), admissions test (`/admissions/ucat/`), Nigerian qualification (`/requirements/waec/` etc.), comparison (`/medical-schools/compare/`), directory (`/medical-schools/`), application (`/apply-online/*`), FAQ (`/faq/*`), dashboard, document centre, payment (portal).

## 6. What is deliberately NOT in the IA (v1)

- Generic "Study in the UK" or "Life in the UK" sections (brief 91, 89): only a tightly scoped "Living costs" sub-page under Fees.
- A blog/news feed (brief 38).
- Per-city pages, "best medical schools" lists, rankings (brief 15).
- Adjacent-course sections until validated (see research 03).
- Scholarship pages unless a verified, currently open scheme exists.

## 7. Rollover and dating

Cycle-specific content carries the entry year in H1/body (and in slug only for deadline pages). Every guide shows "Last reviewed"; every fact block shows "Last verified". Annual August rollover job per 18.5.
