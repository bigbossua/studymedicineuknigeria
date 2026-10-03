# Data availability record — research session 2026-10-03

This file records, honestly, which evidence sources were and were not available
during the first research pass. Nothing below has been estimated or back-filled.

| Source | Status in this session | Consequence |
|---|---|---|
| Semrush (MCP connector) | **DATA UNAVAILABLE** — subscription active, but zero API units (`no_api_units`). Top-up page: https://www.semrush.com/mcp-access | No keyword volume, keyword difficulty, intent, or competitor-traffic metrics are quoted anywhere in this repo. Every table has an empty "Semrush" column to be filled once units exist or via the Semrush web UI. |
| Google Search Console | **DATA UNAVAILABLE** — no connector attached to this session | No historical query, impression, click or indexed-URL audit of the existing domain was possible. |
| Google Analytics (GA4) | **DATA UNAVAILABLE** — no connector attached | Measurement plan is designed but not wired to a property. |
| Google Trends | **DATA UNAVAILABLE** — network policy blocks trends.google.com | Seasonality statements are inferences from UCAS cycle dates, not Trends data. |
| Existing website studymedicineuknigeria.com | **UNAVAILABLE** — host blocked by the session's network egress policy (HTTP CONNECT 403) | No audit of existing pages, URLs or redirects. Treat the live site as unknown until audited. |
| Direct page fetch of official sources (medschools.ac.uk, ucas.com, gov.uk, *.ac.uk, gmc-uk.org) | **BLOCKED** by network egress policy | Facts were gathered through web-search results that quote those official pages. Each fact carries its source URL and the flag `VERIFY-ON-PAGE` meaning "located via search snippet; open the official page and confirm before publishing". |
| Web search | Available | Primary evidence channel for this pass. |

## Verification states used throughout this repository

- `VERIFIED` — read directly from the official page (none in this session; see above).
- `VERIFY-ON-PAGE` — fact and URL located via search; must be confirmed on the page before it is shown to a student.
- `NOT PUBLISHED` — the official source does not state this (e.g. a university publishes no Nigerian-specific requirement).
- `DATA UNAVAILABLE` — the tool/metric could not be accessed in this session.
- `INFERENCE` — our reasoning from facts; never shown to students as fact.

## Session search budget (added during the session)

The session had a hard cap of 200 web searches shared across all research agents. It was exhausted
part-way through the research pass. Each research document therefore has a **Gaps** section listing
exactly what was not reached. Nothing in a Gaps list has been filled from memory. A second research
pass needs either a raised search cap (`CLAUDE_CODE_MAX_WEB_SEARCHES_PER_SESSION`) or network access
to the official domains so pages can be read directly (which would also upgrade every `VERIFY-ON-PAGE`
fact to `VERIFIED`).

## Addendum — research pass 2 (2026-10-03, docs 03 and 11)

| Source | Status | Consequence |
|---|---|---|
| Web search | **EXHAUSTED mid-task** — the session's budget of 200 WebSearch calls (shared across the session) was used up after the Task 1 searches and the Dentistry/Pharmacy/Nursing searches of Task 2 | In `docs/research/03-adjacent-courses-assessment.md` the MPH, Biomedical Science, Physiotherapy, Radiography, Graduate Entry Medicine, foundation-year, NHS LSF, NMC/GDC/HCPC/UKPHR, England 7.5% cap and all Nigerian-forum (Nairaland/HESA) checks were **not run**. Those cells are marked `NOT VERIFIED THIS SESSION` or `DATA UNAVAILABLE`. In `docs/research/11-agent-partner-terminology.md` the referral/introducer definition, FCCPA section numbers, Consumer Contracts Regulations 2013 and British Council Nigeria training page were not retrieved and are marked likewise. Re-run these in a fresh session (or raise `CLAUDE_CODE_MAX_WEB_SEARCHES_PER_SESSION`). |

Additional label introduced in pass 2: `NOT VERIFIED THIS SESSION` — a position stated from general knowledge with the official URL to check; **must not be published** until it becomes `VERIFY-ON-PAGE` or `VERIFIED`.
