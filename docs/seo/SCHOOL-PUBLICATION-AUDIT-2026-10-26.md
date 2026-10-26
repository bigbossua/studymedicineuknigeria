# Published school pages: audit against DECISION-ENGINE §8 (2026-10-26)

**Sources:**
- Production database: Operations Summary runs 40393175797 and 40403495546. These report core-fact status, verified and flagged counts, and the latest verification date.
- Live Verification run 40329348715: sitemap and links.
- `data/seo/decision-register.csv`: register row and demand evidence.

**§8 rule.** A school page is indexable only when the five core facts are verified, or recorded as not published by the university, **and** a register row documents Nigerian search evidence. The five core facts are:
- international applicants accepted;
- the WAEC/NECO or Nigeria statement;
- the English requirement;
- the international fee with its year;
- the application route.

**Publication.** The owner published 19 further schools in Admin → Universities between 23 and 26 October 2026. That decision is recorded and kept: nothing below deletes a page or its content.

## The two failing Live Verification checks (2026-10-26)

1. **every sitemap URL has a W3C lastmod date: 45/51.** No lastmod (no verified university-level fact):
   - `/medical-schools/anglia-ruskin`
   - `/medical-schools/bangor`
   - `/medical-schools/edge-hill`
   - `/medical-schools/kent-medway`
   - `/medical-schools/buckingham`
   - `/medical-schools/manchester`
2. **every sitemap page has at least 2 in-body links from other pages.** Each of these has 1:
   - `/medical-schools/bangor`
   - `/medical-schools/edge-hill`

## Per school

Core = core facts verified or recorded not published, out of 5. Flagged = facts the source watcher took off the site. Links = in-body links from other sitemap pages (the audit names only pages with fewer than 2).

| School | Register | Core | Verified / flagged | Latest verified | Demand evidence | Links | Core-fact gate | Recommended action |
|---|---|---|---|---|---|---|---|---|
| Cardiff | D08 PUBLISHED | 5/5 | 12 / 0 | 2026-10-05 | yes (§8 met 2026-10-05) | ≥2 | indexable | keep indexed |
| King's College London | D27 RESEARCH | 5/5 (WAEC not published) | 9 / 1 | 2026-10-05 | none | ≥2 | indexable | owner decision: noindex until Search Console shows Nigerian demand |
| Aberdeen | D07 PUBLISHED | 1/5 | 3 / 4 | 2026-10-05 | yes | ≥2 | noindex | re-verify the 4 flagged core facts; indexed again only if supported |
| Brighton and Sussex | D12 PUBLISHED | 3/5 | 8 / 2 | 2026-10-05 | yes | ≥2 | noindex | re-verify acceptance and route |
| Buckingham | D13 PUBLISHED | 1/5 | 3 / 6 | 2026-10-05 | yes | ≥2 | noindex | re-verify 4 core facts |
| Edinburgh | D11 PUBLISHED | 2/5 | 3 / 5 | 2026-10-05 | yes | ≥2 | noindex | re-verify 3 core facts |
| Manchester | D10 PUBLISHED | 1/5 | 3 / 9 | 2026-10-05 | yes | ≥2 | noindex | re-verify 4 core facts |
| Aston | D18 RESEARCH | 1/5 | 5 / 7 | 2026-10-05 | none | ≥2 | noindex | unpublish (noindex) until core facts and demand evidence |
| Brunel | D20 RESEARCH | 2/5 | 3 / 7 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| Hull York | D23 RESEARCH | 1/5 | 1 / 6 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| Imperial | D24 RESEARCH | 3/5 | 5 / 7 | 2026-10-06 | none | ≥2 | noindex | unpublish |
| Keele | D25 RESEARCH | 2/5 | 3 / 7 | 2026-10-06 | none | ≥2 | noindex | unpublish |
| Kent and Medway | D26 RESEARCH | 1/5 | 1 / 7 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| Lancaster | D28 RESEARCH | 3/5 | 5 / 5 | 2026-10-06 | none | ≥2 | noindex | unpublish |
| Newcastle | D30 RESEARCH | 3/5 | 4 / 3 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| St George's | D21 RESEARCH | 4/5 | 7 / 0 | 2026-10-06 | none | ≥2 | noindex | unpublish |
| Surrey | D58 RESEARCH | 2/5 | 2 / 5 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| Warwick | D59 RESEARCH | 2/5 | 2 / 1 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| Wolverhampton | D60 RESEARCH | 4/5 | 9 / 0 | 2026-10-05 | none | ≥2 | noindex | unpublish |
| Worcester | D61 RESEARCH | 4/5 | 7 / 1 | 2026-10-06 | none | ≥2 | noindex | unpublish |
| Anglia Ruskin | D17 REJECTED (home students only) | 1/5 | 0 / 0 | none | n/a | ≥2 | noindex | unpublish |
| Bangor | D19 REJECTED (home students only) | 0/5 | 0 / 2 | none | n/a | **1** | noindex | unpublish |
| Edge Hill | D22 REJECTED (home students only) | 0/5 | 0 / 1 | none | n/a | **1** | noindex | unpublish |
| Manchester Metropolitan | D29 REJECTED (not a medical school) | 0/5 | 0 / 0 | none | n/a | ≥2 | noindex | unpublish |
| Northumbria | D31 REJECTED (not a medical school) | 0/5 | 0 / 0 | none | n/a | ≥2 | noindex | unpublish |

## Groups

- **A. Verified and with evidence:** Cardiff. Keep it indexed.
- **B. Verified, no demand evidence:** King's College London. §8.3 says noindex; the decision is the owner's.
- **C. Evidence of demand, core facts flagged:** Aberdeen, Brighton and Sussex, Buckingham, Edinburgh, Manchester. Most flags came from the source-watcher false positive (stage 66).
  - Re-read each flagged core fact on its official page and restore only what the page still supports.
  - The gate re-indexes the page automatically once all five are verified again.
- **D. Core facts incomplete or flagged, no demand evidence:** 13 schools. The gate keeps them out of the index now. Set them to noindex in Admin → Universities so that a later re-verification does not index them without demand evidence.
- **E. Rejected (home students only, or not a medical school):** 5 schools. Set to noindex in Admin → Universities; the pages stay online.

## Corrections in the repository (not yet deployed)

- **Core-fact gate.** `University::isIndexable()`: a published school is indexed and listed in the sitemap only while all five core facts are verified, or recorded as not published. Otherwise the page stays online with noindex. This puts §8.1 into code, the same way the topic pages' PublishGate works.
  - Effect: only Cardiff and King's College London stay indexable; 23 school URLs leave the sitemap.
  - It resolves both failing checks: all six URLs without a date, and both under-linked pages, leave the sitemap.
- **Source watcher (stage 66).** A summarised value stays verified only while its whole recorded official quote is still on the page.
  - If only part of the quote is still there, the fact goes to manual review (SOURCE_CHANGED with a note).
  - If neither value nor quote is found, the fact is un-verified as before.
- **Operations Summary.** A read-only workflow with counts only, plus each published school's core-fact status.
