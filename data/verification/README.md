# Fact verification worksheet (reviewer's browser → repository → every environment)

Every requirement, fee and date on the site is a `reference_facts` row with a verification status. Facts located during
research but not yet read on the official page are `VERIFY-ON-PAGE`; those not found are `NOT_FOUND`. In production an
unverified fact is hidden (or shown as pending). The admin verification queue does this in the browser once the site is
deployed; this worksheet does the same job **now**, from any browser, without deployment or special network access.

1. Export (already done for 2026-10-04; references are stable, so decisions made against an older sheet still import; regenerate any time):
   `php artisan smukn:facts-export data/verification/worksheet-YYYY-MM-DD.csv --sources=data/verification/sources-YYYY-MM-DD.csv`
   - **Start with `worksheet-2026-10-04-p0.csv` (71 facts)**, then `-p1.csv` (285). The full sheet holds all 675.
   - **Work page by page, not row by row.** Rows are grouped by `source_group` (S001, S002…): every fact taken from one official page sits together, and `facts_on_source` says how many one visit resolves. `sources-2026-10-04.csv` lists each page once (202 pages; the 46 pages with five or more facts cover 413 of them) in the order to open them.
   - `priority` follows the owner's order: **1** UCAS Medicine deadlines · **2** UCAT dates and rules · **3** GMC status and registration · **4** medical school eligibility · **5** Nigerian qualifications and entry · **6** international fees and costs · **7** visa and immigration · **8** graduate and work rules · **9** application routes · **10** course availability · **11** other · **12** other healthcare subjects (after every Medicine fact). A page is placed at the highest priority of any fact on it.
   - `status` shows why a row is here: `VERIFY-ON-PAGE` (found in research, never read on the page), `SOURCE_CHANGED` (the page appears to have changed: read it again and record what it says now), `REVIEW_DUE` (verified before, review date passed), `NOT_FOUND` (research did not locate it).
2. Open the CSV in a spreadsheet. For each row, open `source_url` in your browser and compare `current_value` with the page.
   Fill in:
   - `decision`: `verified` (the page says this), `not_published` (the page does not state it), `source_changed`
     (the page says something different: put the new wording in `verified_value`), or `archive` (no longer relevant).
   - `verified_value`: only when the page wording differs; copy it exactly as published (numbers as digits).
   - `new_source_url`: only if the fact lives on a different official page than recorded.
   - `reviewer_note`: anything the next reviewer should know (optional).
   - `verified_on`: the date you read the page, `YYYY-MM-DD` (required for `verified`).
   Leave rows you did not check empty; they are ignored.
3. Save as `data/verification/decisions-YYYY-MM-DD.csv` (keep the header). Run
   `php artisan smukn:facts-import data/verification/decisions-YYYY-MM-DD.csv --dry-run`, read the summary, then run it
   without `--dry-run`. Commit the file: the deploy script replays every `decisions-*.csv` after migrations, so the same
   decisions apply on staging and production. Each file is applied once per environment (the `fact_imports` ledger keys it by content hash), so a replay never reverses a later review in the admin queue; `--force` re-applies deliberately. `new_source_url` must be an `https://` address. The exported worksheet escapes any cell that would start a spreadsheet formula with a leading apostrophe.

Rules: a fact is `verified` only when you read it on the official page named in `source_url` (or `new_source_url`);
never from a consultancy, agent or forum page. Never type a value you did not see. `verified` without a date is refused.

## What keeps a verified value honest

- **Verified facts are never overwritten by data syncs.** Every deploy runs `smukn:reference-sync`, which creates new facts and refreshes facts nobody has reviewed, but never touches a fact a person has decided on (`reviewed_at`).
- **Editing a verified value un-verifies it.** Changing the wording, number, year or source of a verified fact in the admin queue returns it to `VERIFY-ON-PAGE` until someone verifies the new value on the page.
- **Review dates.** Fees and deadlines are due for review six months after verification, everything else after twelve; `smukn:flag-review-due` (nightly) moves overdue facts to `REVIEW_DUE`, which hides them in production until re-verified.
- **Changed pages are caught between reviews.** `smukn:sources-check` (nightly on the server, 03:40) fingerprints every official page behind a verified fact. If a page disappears, or changes and the verified wording or figure is no longer on it, the fact becomes `SOURCE_CHANGED` (hidden in production) and admins are emailed. Unchanged values on a changed page stay verified with a note; network errors change nothing.
- **Changed sources are confirmed one at a time.** The admin "verify by source" page never bulk-verifies a `SOURCE_CHANGED` fact: it carries the old value, so it is confirmed alone with the page's current wording.
- **Decisions apply once.** The `fact_imports` ledger stops a deploy replay from reversing a later review.
