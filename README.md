# StudyMedicineUKNigeria.com

An evidence-led platform for Nigerian students who want to study Medicine (and directly related
healthcare courses) in the United Kingdom: public information resource → UK medical-school
directory → online application → private student portal → document centre → student-approved
university submission → tracking.

**Current phase: research and architecture (pre-build).** The master brief requires the research
and decision report to be completed before page production begins. Nothing in this repository is
yet a public web page.

## Repository map

```
docs/
  DATA-AVAILABILITY.md           what evidence was / was not available, and the verification vocabulary
  decision/
    00-DECISION-REPORT.md        the consolidated decision report (read this first)
    information-architecture.md  site shape, navigation, breadcrumb hierarchy, knowledge model
    page-asset-register.md       every candidate page with evidence and BUILD NOW / LATER / DO NOT BUILD
    conversion-funnel-strategy.md
  research/                      evidence documents (web-search sourced; every fact carries a URL + status)
    02-nigerian-search-demand.md
    03-adjacent-courses-assessment.md
    05-competitor-research.md
    06-uk-medical-school-database.md
    07-nigerian-qualification-research.md
    08-fee-research.md
    09-application-process-research.md
    10-working-and-registration-research.md
    11-agent-partner-terminology.md
  architecture/                  platform design
    12-application-workflow-and-state-machine.md
    13-student-portal-architecture.md
    14-document-architecture.md
    15-payment-architecture.md
    16-university-submission-workflow.md
    17-data-model.md
    18-technical-seo-architecture.md
    19-design-system.md
    20-tech-stack-recommendation.md
data/
  medical-schools/schools.json   directory dataset (per-field source + status)
  medical-schools/fees.json      international fee dataset
  qualifications/university-statements.json   per-university statements on WAEC/NECO/A-level/degree
```

## Non-negotiable rules carried into everything here

- Every page must be an asset with a documented reason to exist.
- No fabricated fees, rankings, acceptance rates, student numbers, partnerships, testimonials or outcomes.
- Every dynamic fact (fee, deadline, test, eligibility, qualification statement) shows an official source and a last-verified date.
- No university submission without the student's explicit, recorded approval.
- Documents are private, access-controlled and never served from public URLs.
- Organic-first; no paid acquisition.
- We do not call ourselves an agent, partner or representative of any university without a signed agreement on file.

## Verification vocabulary

See `docs/DATA-AVAILABILITY.md`. In short: `VERIFIED` (read on the official page), `VERIFY-ON-PAGE`
(found via search; confirm before publishing), `NOT PUBLISHED`, `NOT FOUND IN THIS SESSION`,
`DATA UNAVAILABLE`, `INFERENCE`, `RECOMMENDATION`.
