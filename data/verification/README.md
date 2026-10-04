# Fact verification worksheet (reviewer's browser → repository → every environment)

Every requirement, fee and date on the site is a `reference_facts` row with a verification status. Facts located during
research but not yet read on the official page are `VERIFY-ON-PAGE`; those not found are `NOT_FOUND`. In production an
unverified fact is hidden (or shown as pending). The admin verification queue does this in the browser once the site is
deployed; this worksheet does the same job **now**, from any browser, without deployment or special network access.

1. Export (already done for 2026-10-04, superseding the 2026-10-03 sheet; references are stable, so decisions already made against the old sheet still import; `worksheet-2026-10-04-priority1.csv` holds just the priority-1 rows if you want to start small; regenerate any time): `php artisan smukn:facts-export data/verification/worksheet-YYYY-MM-DD.csv`
   Rows are ordered by priority: **1** cycle dates, fees and visa figures; **2** Nigerian-applicant statements; **3** the rest.
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
   decisions apply on staging and production.

Rules: a fact is `verified` only when you read it on the official page named in `source_url` (or `new_source_url`);
never from a consultancy, agent or forum page. Never type a value you did not see. `verified` without a date is refused.
