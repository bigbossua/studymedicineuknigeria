# Semrush lookups (owner's browser → repository)

The Semrush MCP connection has no API units, so lookups happen in the owner's open Semrush tab and are
recorded here. Nothing on the site quotes a Semrush figure unless it appears in this folder with a date.

## Fastest route: one paste, one export (about five minutes)

1. Semrush → **Keyword Overview** → paste the 50 lines of `paste-list.txt` into the bulk box (it accepts up to 100), choose database **Nigeria**, run the analysis.
2. Click **Export** (CSV) on the results table. Save the file as it downloads into this folder, for example `data/semrush/semrush-ng-2026-10-05.csv`.
3. Run `php artisan smukn:semrush-import data/semrush/semrush-ng-2026-10-05.csv --date=2026-10-05` (the day you exported; add `--database=uk` for a UK export). The importer matches each Semrush row to the lookup sheet by keyword, writes the record as `lookups-YYYY-MM-DD.csv`, updates the research doc and the decision register, and reports how many of the 50 themes Semrush returned. Themes Semrush has no data for stay blank (a valid result).
4. Optional, for the five to ten themes that decide a page (rows 1, 13, 14, 34, 35, 42, 45, 46, 39 and 41 first): open each in Keyword Overview and add the top three organic URLs to the recorded file's `top_3_urls` column, then re-run the import on that file.
5. Commit the export and the generated files.

What the importer does with awkward exports (never a guess): "CSV" and "CSV semicolon" both work; an `.xlsx` is
refused with a message; keyword case and spacing are ignored when matching; a keyword exported twice with different
figures is left unrecorded with a warning (export it once); two lookup themes sharing a keyword are reported. The 50
keywords are distinct, each points at a live decision-register row, and `paste-list.txt` is the sheet's keyword column
in order (a test enforces all three). Semrush figures are only ever recorded next to their database and date; SERP,
People-also-ask and autocomplete observations in the register's `sources` column are labelled as observations, not volumes.

## Row by row (if you prefer)

1. Open `lookup-sheet.csv` (one row per query theme from research 02 §4). For each row, in Semrush → Keyword Overview,
   database **Nigeria (ng)** first, then **United Kingdom (uk)** if the Nigerian database returns no data:
   - `semrush_keyword_used`: the exact keyword string you typed
   - `volume`, `keyword_difficulty`, `cpc`, `intent_semrush`: as shown; leave blank when Semrush shows "n/a"
   - `top_3_urls`: the first three organic URLs, separated by spaces
   - `trend_note`: anything notable in the 12-month trend (e.g. "peaks Sep–Oct")
   - `looked_up_on`: today's date (YYYY-MM-DD)
   Save as `lookups-YYYY-MM-DD.csv` in this folder (keep the header row).
2. Run `php artisan smukn:semrush-import data/semrush/lookups-YYYY-MM-DD.csv`. It rewrites the demand table in
   `docs/research/02-nigerian-search-demand.md` from "DATA UNAVAILABLE" to the recorded figures with the lookup date,
   prints the queries whose evidence now supports or refutes a planned page in `docs/decision/page-asset-register.md`,
   and copies volume and difficulty into the SEO decision register rows named in the `register_ids` column
   (`data/seo/decision-register.csv`, see `docs/seo/DECISION-ENGINE.md`), noting database, date and keyword in each row's sources.
3. Commit the CSV and the regenerated research doc.

Rules: never type a figure you did not read in Semrush; a blank cell is a valid result; Nigerian database first.
