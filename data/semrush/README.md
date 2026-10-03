# Semrush lookups (owner's browser → repository)

The Semrush MCP connection has no API units, so lookups happen in the owner's open Semrush tab and are
recorded here. Nothing on the site quotes a Semrush figure unless it appears in this folder with a date.

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
   and prints the queries whose evidence now supports or refutes a planned page in `docs/decision/page-asset-register.md`.
3. Commit the CSV and the regenerated research doc.

Rules: never type a figure you did not read in Semrush; a blank cell is a valid result; Nigerian database first.
