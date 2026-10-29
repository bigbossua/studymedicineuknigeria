# University fact verification: school by school (2026-10-29)

**Production state.** Operations Summary run 40939685566 (job 123679461398, 29 October 19:23 UTC, release `2026-10-28T11-12-26`), counts only:
- 39 schools are published in Admin.
- 7 have all five core facts: Brighton and Sussex, Cambridge, Cardiff, Edinburgh, King's College London, Manchester and Queen's Belfast.
- Facts by status: 295 VERIFIED, 151 SOURCE_CHANGED (flagged by the watcher), 342 VERIFY-ON-PAGE, 47 NOT_FOUND, 15 NOT_PUBLISHED, 1 ARCHIVED.
- Most flags are three weeks old: 89 of the 151 date from 7–10 October.

**Rule (until 29 October).** A published school is indexable only when all five core facts are VERIFIED or NOT_PUBLISHED (`University::isIndexable`, DECISION-ENGINE §8.1):
- international applicants accepted;
- the WAEC/NECO or Nigeria statement;
- the English requirement;
- the international fee;
- the application route.

A flagged fact is hidden and puts the page back to noindex.

**Change of 29 October (commit ab0894f): indexing now needs three separate approvals.**

| Approval | What it means | Where it is recorded |
|---|---|---|
| Factual verification | the five core facts are verified | `reference_facts` |
| Demand evidence and publication approval | a live row (PUBLISHED, INDEXING, MEASURING or UPDATE) names `/medical-schools/<slug>` | `data/seo/decision-register.csv` (§8.3) |
| Publication | the page is put online | Admin → Universities |

Before this change, the code checked only publication and facts, so Admin publication alone indexed Cambridge, Queen's Belfast and King's College London. Their register rows are RESEARCH. `SchoolRegisterGateTest` covers the new rule.

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

Labels used below: **[Fact]** is read from production or from the code; **[Expected]** follows from the code and today's production state; **[Recommendation]** is advice; **[Owner]** marks an owner action.

- **[Fact]** The live sitemap has 36 URLs today (page-excerpts run 40949529039, read 29 October 20:29 UTC). They include Cambridge, King's College London and Queen's Belfast, but not Aberdeen, Buckingham or the cost page.
- **[Expected]** Schools with all five core facts go from 7 to 18 after the import. That is factual verification only; indexing is decided separately, as below.
- **[Expected]** After the deploy the sitemap has **36 URLs**, but not the same ones. Three leave and three join.

  | Change | URL | Why |
  |---|---|---|
  | removed | `/medical-schools/cambridge` | D14 RESEARCH: no live register row (the facts stay verified; the page stays online, noindex) |
  | removed | `/medical-schools/kings-college-london` | D27 RESEARCH: no live register row |
  | removed | `/medical-schools/queens-belfast` | D34 RESEARCH: no live register row |
  | added | `/medical-schools/aberdeen` | D07 PUBLISHED; application route re-verified |
  | added | `/medical-schools/buckingham` | D13 PUBLISHED; international acceptance re-verified (the flag was a bot check) |
  | added | `/fees/cost-of-studying-medicine-in-the-uk` | its gate's only flagged fact (UCAS Extra opens) is re-verified |

- **[Fact]** The 36 expected URLs are exactly the sitemap of a local production-mode build with every decision imported (`docs/seo/PAGE-AUDIT.md`).
- **[Assumption]** Nothing else changes in production before the deploy. The nightly watcher (03:40) can flag more facts. An owner action in Admin can publish or unpublish. Either would change the count. The deploy's Live Verification and the next Operations Summary give the real figure.
- **[Fact]** These schools have verified core facts after the import, and stay noindex because their register rows are RESEARCH: Aston, Brunel, Chester, Imperial, Keele, Kent and Medway, Lancaster, Surrey and Swansea. Their pages stay online with the corrected facts.

**Facts verified, demand not shown, publication not approved.** Twelve schools are in this position: Cambridge, Queen's Belfast and King's College London (now leaving the index), and the nine above.
- **[Owner]** Each can be indexed only by moving its register row to PUBLISHED with a recorded reason. Under §8.3 that means Search Console impressions or a Nigeria-specific search naming the school, or an explicit owner directive recorded as the reason.
- **[Recommendation]** Wait for Search Console data rather than override §8.3.
- **[Owner]** If the owner wants Cambridge, Queen's Belfast or King's College London to stay indexed through this deploy, say so before approving it. Their rows can then be updated with the owner's directive as the recorded reason; nothing in the repository supplies demand evidence for them today.

## Cambridge, Queen's Belfast and King's College London

| School | Core facts **[Fact]** | Register **[Fact]** | Evidence of Nigerian demand **[Fact]** | After deploy **[Expected]** | Recommendation |
|---|---|---|---|---|---|
| Cambridge | 5/5 verified | D14 RESEARCH | Google autocomplete only, which is not evidence: the same seeds also complete with Queensland, Virginia, Zurich and Kentucky (`data/seo/autocomplete-ng-2026-10-06b.jsonl`) | noindex, out of the sitemap | keep noindex until Search Console shows impressions |
| Queen's University Belfast | 5/5 verified | D34 RESEARCH | none recorded | noindex | keep noindex |
| King's College London | 5/5 (WAEC statement recorded as NOT_PUBLISHED by the university) | D27 RESEARCH; its reason text still says core facts are incomplete, which is out of date | none recorded | noindex | keep noindex; this matches the owner's earlier instruction for KCL |

Semrush was not used (no API units), and no demand figure is invented.

## Schools published in Admin but rejected in the register

- **[Fact]** Eight schools are REJECTED in the register but published in Admin: Anglia Ruskin, Bangor, Edge Hill, Manchester Metropolitan, Northumbria, Pears Cumbria, ScotGEM and Sunderland. They are noindex both now and after the deploy.
- **[Owner]** Unpublishing them in Admin would also remove them from the directory listing. That is an owner decision; nothing is removed automatically.

## School by school (production now, and after these decisions)

V = verified, NP = not published by the university, SC = flagged by the watcher, VOP = never verified on the page, NF = not found, — = no fact recorded. "Core after" assumes the 30 decisions import as in the dry run. "In sitemap" applies the register gate.

| School | Intl | WAEC | Eng | Fee | Route | Core now | Core after these decisions | Flagged facts now | Re-verified 29 Oct | Register row | In sitemap now → after deploy | Next step |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| University of Aberdeen | V | V | V | V | SC | 4/5 | 5/5 | 1 | Route | D07 PUBLISHED | no → yes | Indexed: register approved, facts verified; keep under the watcher |
| Anglia Ruskin University | VOP | — | — | NP | VOP | 1/5 | 1/5 | 0 | — | D17 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| Aston University | SC | SC | SC | V | SC | 1/5 | 5/5 | 7 | Intl, WAEC, Eng, Route | D18 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| Bangor University | SC | — | — | — | — | 0/5 | 0/5 | 2 | — | D19 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| University of Birmingham | VOP/SC | SC | SC | VOP | V | 1/5 | 1/5 | 6 | — | D40 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Brighton and Sussex Medical School | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D12 PUBLISHED | yes → yes | Indexed: register approved, facts verified; keep under the watcher |
| University of Bristol | V | — | V | V | V | 4/5 | 4/5 | 0 | — | D41 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Brunel University of London | VOP/SC | SC | SC | V | SC | 1/5 | 5/5 | 8 | Intl, WAEC, Eng, Route | D20 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| University of Buckingham | SC | V | V | V | V | 4/5 | 5/5 | 2 | Intl | D13 PUBLISHED | no → yes | Indexed: register approved, facts verified; keep under the watcher |
| University of Cambridge | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D14 RESEARCH | yes → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| Cardiff University | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D08 PUBLISHED | yes → yes | Indexed: register approved, facts verified; keep under the watcher |
| University of Chester | V | SC | V | V | V | 4/5 | 5/5 | 2 | WAEC | D42 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| University of Dundee | — | NP | NF | VOP | — | 1/5 | 1/5 | 0 | — | D43 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Edge Hill University | VOP | SC | — | — | VOP | 0/5 | 0/5 | 1 | — | D22 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| University of Edinburgh | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D11 PUBLISHED | yes → yes | Indexed: register approved, facts verified; keep under the watcher |
| Hull York Medical School | V | SC | — | SC | SC | 1/5 | 1/5 | 6 | — | D23 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Imperial College London | SC | NP | V | V | SC | 3/5 | 5/5 | 7 | Intl, Route | D24 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| Keele University | V | SC | SC | V | SC | 2/5 | 5/5 | 7 | WAEC, Eng, Route | D25 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| Kent and Medway Medical School | SC | SC | SC | V | SC | 1/5 | 5/5 | 7 | Intl, WAEC, Eng, Route | D26 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| King's College London | V | NP | V | V | V | 5/5 | 5/5 | 1 | — | D27 RESEARCH | yes → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| Lancaster University | V | V | SC | V | SC | 3/5 | 5/5 | 5 | Eng, Route | D28 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| University of Manchester | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D10 PUBLISHED | yes → yes | Indexed: register approved, facts verified; keep under the watcher |
| Manchester Metropolitan University | — | — | — | VOP | — | 0/5 | 0/5 | 0 | — | D29 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| Newcastle University | V | NP | NF | V | SC | 3/5 | 3/5 | 3 | — | D30 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Northumbria University | VOP | — | — | — | — | 0/5 | 0/5 | 0 | — | D31 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| Pears Cumbria School of Medicine | V | — | — | — | V | 2/5 | 2/5 | 2 | — | D32 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| Queen Mary University of London | SC | NF | SC | VOP | V | 1/5 | 1/5 | 6 | — | D33 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Queen's University Belfast | V | V | V | V | V | 5/5 | 5/5 | 0 | — | D34 RESEARCH | yes → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| ScotGEM (St Andrews and Dundee) | V | — | — | — | — | 1/5 | 1/5 | 0 | — | D35 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| City St George's, University of London | V | — | V | V | V | 4/5 | 4/5 | 0 | — | D21 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| St Mary's University, Twickenham | V | — | — | — | — | 1/5 | 1/5 | 0 | — | D36 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| University of Sunderland | V | SC | NF | NP/VOP | V | 2/5 | 2/5 | 4 | — | D57 REJECTED | no → no | Owner: register REJECTED, so consider unpublishing in Admin; already noindex |
| University of Surrey | V | SC | SC | V | SC | 2/5 | 5/5 | 5 | WAEC, Eng, Route | D58 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| Swansea University | V | SC | SC | V | SC | 2/5 | 5/5 | 3 | WAEC, Eng, Route | D37 RESEARCH | no → no | Owner: facts verified, but no demand evidence or publication approval in the register, so noindex. Index only after a live register row |
| University College London | VOP | NP | NF | VOP | VOP | 1/5 | 1/5 | 0 | — | D39 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| Ulster University | V | — | — | V | — | 2/5 | 2/5 | 0 | — | D38 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| University of Warwick | V | — | — | V | — | 2/5 | 2/5 | 1 | — | D59 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| University of Wolverhampton | V | V | V | VOP | V | 4/5 | 4/5 | 0 | — | D60 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |
| University of Worcester | V | V | — | V | V | 4/5 | 4/5 | 1 | — | D61 RESEARCH | no → no | Stays noindex; re-read the flagged or missing core facts |

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
