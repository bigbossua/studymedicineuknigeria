# University fact verification: school by school (2026-10-29)

**Production state.** Operations Summary run 40939685566 (job 123679461398, 29 October 19:23 UTC, release `2026-10-28T11-12-26`), counts only:
- 39 schools are published in Admin.
- 7 have all five core facts: Brighton and Sussex, Cambridge, Cardiff, Edinburgh, King's College London, Manchester and Queen's Belfast.
- Facts by status: 295 VERIFIED, 151 SOURCE_CHANGED (flagged by the watcher), 342 VERIFY-ON-PAGE, 47 NOT_FOUND, 15 NOT_PUBLISHED, 1 ARCHIVED.
- Most flags are three weeks old: 89 of the 151 date from 7–10 October.

**Rule.** A published school is indexable only when all five core facts are VERIFIED or NOT_PUBLISHED (`University::isIndexable`, DECISION-ENGINE §8.1):
- international applicants accepted;
- the WAEC/NECO or Nigeria statement;
- the English requirement;
- the international fee;
- the application route.

A flagged fact is hidden and puts the page back to noindex. The register (`data/seo/decision-register.csv`) also asks for Nigerian demand evidence (§8.3). The code does not enforce §8.3; the owner applies it in Admin by publishing or unpublishing.

## What was re-verified today (30 decisions, all quotes checked by `ops/verification/check-quotes.py`)

Only the core facts that kept nearly complete schools out of the index were re-read, and Buckingham's and UCAS's new flags.

| Review file | Facts | Evidence | Result |
|---|---|---|---|
| `ops/verification/reviews/2026-10-29-flags.json` | UCAS Extra opens (2027); Aberdeen application route | page-excerpts run 40942424622 | still published; Aberdeen's source moves to the requirements page the quote comes from |
| `ops/verification/reviews/2026-10-29-buckingham.json` | Buckingham international accepted, international places open | run 40943430188 | the 29 October flags came from a bot-verification page, not a change |
| `ops/verification/reviews/2026-10-29-recheck.json` | 23 core facts: Aston, Brunel, Keele, Kent and Medway, Lancaster, Surrey, Swansea | run 40946710632 | recorded wording still on each page; Lancaster's two quotes reworded on the page with the same requirement and route |
| `ops/verification/reviews/2026-10-29-recheck2.json` | Chester Nigeria statement; Imperial international accepted and application route | runs 40946710632 and 40947461259 | Chester and Imperial's international statement unchanged. **Corrected:** Imperial's route now says applications for 2027 entry are closed; the UCAS code A100 and the 15 October 2026 deadline are unchanged |

The decisions are in `data/verification/decisions-2026-10-29-*.csv`. The next approved deploy imports them (`ops/deploy.sh`). A dry run against a full local import updated every fact, with 0 unknown references.

**Most flags in this sample were not real changes.** Of the 30 facts re-read:
- 27 had the same official wording;
- 2 (Lancaster) were reworded with the same value;
- 1 (Imperial's route) had really changed.

A flag means the page changed and the watcher could no longer match the recorded quote. Navigation, dates and other page furniture change often. Buckingham's flags came from a bot-check page. The watcher treated such a page as the university's own until today: `CheckSources::isChallenge` (commit 7a1372c) now records it as unreachable instead.

Flags stay until each fact is re-read. About 121 remain after today; this is an estimate, because a decision can cover more than one fact row.

## Effect of the next deploy

- **Core facts complete:** 7 schools before, 18 after.

  | Core facts | 5/5 | 4/5 | 3/5 | 2/5 | 1/5 | 0/5 |
  |---|---|---|---|---|---|---|
  | Production now | 7 | 7 | 3 | 7 | 11 | 4 |
  | After these decisions | 18 | 4 | 1 | 4 | 8 | 4 |

- **Schools that rejoin the sitemap** (published in Admin, now 5/5):
  - **with a PUBLISHED register row:** Aberdeen (D07) and Buckingham (D13);
  - **with a RESEARCH row (no Nigerian demand evidence yet):** Aston (D18), Brunel (D20), Chester (D42), Imperial (D24), Keele (D25), Kent and Medway (D26), Lancaster (D28), Surrey (D58) and Swansea (D37).
- **The cost page** `/fees/cost-of-studying-medicine-in-the-uk` also returns, through UCAS Extra opens.
- **Expected live sitemap:** 36 + 2 + 9 + 1 = **48 URLs**, if nothing else changes in Admin.

**Owner decision before or after the deploy.** The owner published the nine RESEARCH schools in Admin on 23–26 October, and that decision was recorded and kept (`docs/seo/SCHOOL-PUBLICATION-AUDIT-2026-10-26.md`). The facts on their pages are now verified, so keeping them is consistent with the code. Under §8.3, a school without Nigerian demand evidence should stay out of the index until Search Console or a Nigeria-specific search shows demand. Either:
- unpublish them in Admin → Universities, which takes effect immediately and keeps the page and its facts; or
- keep them, and let Search Console decide within a few weeks.

No code change was made to override the owner's publication.

## Cambridge, Queen's Belfast and King's College London (Phase 7 decision)

| School | Core facts | Register | Evidence of Nigerian demand | Recommendation |
|---|---|---|---|---|
| Cambridge | 5/5 verified | D14 RESEARCH | Google autocomplete only: the same seeds also complete with Queensland, Virginia, Zurich and Kentucky, so it is not evidence (`data/seo/autocomplete-ng-2026-10-06b.jsonl`) | Hold: keep indexed only if the owner accepts §8.3's risk; otherwise unpublish until Search Console shows impressions |
| Queen's University Belfast | 5/5 verified | D34 RESEARCH | none recorded | Unpublish until Search Console or a Nigeria-specific search names it |
| King's College London | 5/5 (WAEC statement recorded as NOT_PUBLISHED by the university) | D27 RESEARCH; its reason text still says core facts are incomplete, which is out of date | none recorded | Unpublish; the owner's earlier instruction was to keep KCL noindex |

None of the three is changed by this branch. Each is an Admin action for the owner. Semrush was not used (no API units), and no demand figure is invented.

## Schools published in Admin but rejected in the register

Eight schools are REJECTED in the register but published in Admin: Anglia Ruskin, Bangor, Edge Hill, Manchester Metropolitan, Northumbria, Pears Cumbria, ScotGEM and Sunderland. The reasons are typically that the course is not open to international applicants or the facts are not published. Their pages are already noindex because their core facts are incomplete, so nothing is wrong in search. Unpublishing them in Admin would also remove them from the directory listing. That is an owner decision, and nothing is removed automatically.

## School by school (production now, and after these decisions)

V = verified, NP = not published by the university, SC = flagged by the watcher, VOP = never verified on the page, NF = not found, — = no fact recorded.

| School | Intl | WAEC | Eng | Fee | Route | Core now | Core after these decisions | Flagged facts now | Re-verified 29 Oct | Register row | Next step |
|---|---|---|---|---|---|---|---|---|---|---|---|
| University of Aberdeen | V | V | V | V | SC | 4/5 | 5/5 | 1 | Route | D07 PUBLISHED | Indexed after deploy; keep under the watcher |
| Anglia Ruskin University | VOP | — | — | NP | VOP | 1/5 | 1/5 | 0 | — | D17 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| Aston University | SC | SC | SC | V | SC | 1/5 | 5/5 | 7 | Intl, WAEC, Eng, Route | D18 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| Bangor University | SC | — | — | — | — | 0/5 | 0/5 | 2 | — | D19 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| University of Birmingham | VOP/SC | SC | SC | VOP | V | 1/5 | 1/5 | 6 | — | D40 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Brighton and Sussex Medical School | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D12 PUBLISHED | Indexed; keep under the watcher |
| University of Bristol | V | — | V | V | V | 4/5 | 4/5 | 0 | — | D41 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Brunel University of London | VOP/SC | SC | SC | V | SC | 1/5 | 5/5 | 8 | Intl, WAEC, Eng, Route | D20 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| University of Buckingham | SC | V | V | V | V | 4/5 | 5/5 | 2 | Intl | D13 PUBLISHED | Indexed after deploy; keep under the watcher |
| University of Cambridge | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D14 RESEARCH | Indexed now, but the register has no Nigerian demand evidence (§8.3): owner decides (keep, or unpublish in Admin) |
| Cardiff University | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D08 PUBLISHED | Indexed; keep under the watcher |
| University of Chester | V | SC | V | V | V | 4/5 | 5/5 | 2 | WAEC | D42 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| University of Dundee | — | NP | NF | VOP | — | 1/5 | 1/5 | 0 | — | D43 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Edge Hill University | VOP | SC | — | — | VOP | 0/5 | 0/5 | 1 | — | D22 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| University of Edinburgh | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D11 PUBLISHED | Indexed; keep under the watcher |
| Hull York Medical School | V | SC | — | SC | SC | 1/5 | 1/5 | 6 | — | D23 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Imperial College London | SC | NP | V | V | SC | 3/5 | 5/5 | 7 | Intl, Route | D24 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| Keele University | V | SC | SC | V | SC | 2/5 | 5/5 | 7 | WAEC, Eng, Route | D25 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| Kent and Medway Medical School | SC | SC | SC | V | SC | 1/5 | 5/5 | 7 | Intl, WAEC, Eng, Route | D26 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| King's College London | V | NP | V | V | V | 5/5 | 5/5 | 1 | — | D27 RESEARCH | Indexed now, but the register has no Nigerian demand evidence (§8.3): owner decides (keep, or unpublish in Admin) |
| Lancaster University | V | V | SC | V | SC | 3/5 | 5/5 | 5 | Eng, Route | D28 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| University of Manchester | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D10 PUBLISHED | Indexed; keep under the watcher |
| Manchester Metropolitan University | — | — | — | VOP | — | 0/5 | 0/5 | 0 | — | D29 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| Newcastle University | V | NP | NF | V | SC | 3/5 | 3/5 | 3 | — | D30 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Northumbria University | VOP | — | — | — | — | 0/5 | 0/5 | 0 | — | D31 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| Pears Cumbria School of Medicine | V | — | — | — | V | 2/5 | 2/5 | 2 | — | D32 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| Queen Mary University of London | SC | NF | SC | VOP | V | 1/5 | 1/5 | 6 | — | D33 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Queen's University Belfast | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D34 RESEARCH | Indexed now, but the register has no Nigerian demand evidence (§8.3): owner decides (keep, or unpublish in Admin) |
| ScotGEM (St Andrews and Dundee) | V | — | — | — | — | 1/5 | 1/5 | 0 | — | D35 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| City St George's, University of London | V | — | V | V | V | 4/5 | 4/5 | 0 | — | D21 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| St Mary's University, Twickenham | V | — | — | — | — | 1/5 | 1/5 | 0 | — | D36 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| University of Sunderland | V | SC | NF | NP/VOP | V | 2/5 | 2/5 | 4 | — | D57 REJECTED | Register REJECTED: owner to unpublish in Admin; the page is already noindex |
| University of Surrey | V | SC | SC | V | SC | 2/5 | 5/5 | 5 | WAEC, Eng, Route | D58 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| Swansea University | V | SC | SC | V | SC | 2/5 | 5/5 | 3 | WAEC, Eng, Route | D37 RESEARCH | Becomes indexable on the next deploy (published in Admin); the register has no Nigerian demand evidence (§8.3): owner keeps or unpublishes |
| University College London | VOP | NP | NF | VOP | VOP | 1/5 | 1/5 | 0 | — | D39 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| Ulster University | V | — | — | V | — | 2/5 | 2/5 | 0 | — | D38 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| University of Warwick | V | — | — | V | — | 2/5 | 2/5 | 1 | — | D59 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| University of Wolverhampton | V | V | V | VOP | V | 4/5 | 4/5 | 0 | — | D60 RESEARCH | Stays noindex; re-read the flagged or missing core facts |
| University of Worcester | V | V | — | V | V | 4/5 | 4/5 | 1 | — | D61 RESEARCH | Stays noindex; re-read the flagged or missing core facts |

## Next verification round (not done here)

Re-read the remaining flagged core facts, starting with the schools one fact from 5/5:
- Bristol (WAEC statement);
- City St George's (WAEC statement);
- Wolverhampton (international fee never verified);
- Worcester (English requirement).

Then Birmingham, Hull York, Newcastle and Queen Mary. Use the same method:
1. Run `page-excerpts.yml` on the source page.
2. Run `parse-excerpts.py`.
3. Run `check-quotes.py` against the earlier quote.
4. Write a new review file.

Do not fill a missing value from a third-party site.
