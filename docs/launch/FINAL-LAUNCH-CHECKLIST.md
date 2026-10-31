# Final launch checklist (updated 2026-10-31)

Two separate approvals:
- **A (technical deployment)** can be given now.
- **B (commercial launch, accepting student payments)** cannot be given until every row is closed. The last step is setting `SITE_COMMERCIAL_LAUNCH_APPROVED`.

**Which commit.** The head is the commit that adds this file. A file cannot name its own commit, so the exact SHA and its CI run are in the final report. At approval time, confirm the head on GitHub (branch `claude/new-session-p6gdm6`, latest commit, green *CI*).

## A. Technical deployment (branch head → production)

| # | Check | Status | Evidence |
|---|---|---|---|
| A1 | CI green on the branch head | to confirm at approval | GitHub Actions *CI* |
| A2 | Full test suite, formatting, dependency audits | complete | `FINAL-READINESS-REPORT.md` §3 |
| A3 | Production rehearsal | complete | §3 |
| A4 | Student, staff and payment journeys (stand-in) | complete | `STUDENT-JOURNEY-TEST-REPORT.md` |
| A5 | Sitemap after the deploy: **38 URLs**. Indexed school pages: Aberdeen, Brighton and Sussex, Buckingham, Edinburgh and Manchester (register-approved), plus Cambridge, King's College London and Queen's Belfast (owner exception). Cardiff is `noindex` (fee withdrawn) | complete (production mirror) | §3 |
| A6 | No migrations; the deploy writes no `.env` | complete | `git diff 5d76858..HEAD -- database/migrations` is empty |
| A7 | The deploy only reads Stripe (`smukn:stripe-sync --check`) | complete | commit 07c1b63 |
| A8 | Rollback undoes the release's fact decisions, Cardiff's included | complete | commit 8f5fcea; the mirror proof covers any `decisions-*.csv` |
| A9 | **New payments stay closed after the deploy** (the gate), even though a live key is installed; existing payments, refunds and paid access are unaffected | complete | `CommercialLaunchGateTest`; production has 0 students today |
| A10 | Fresh *Operations summary* right before dispatch: no new flags on the indexed schools or the cost page | **owner, at approval** | run id |
| A11 | Owner's explicit approval | **owner** | — |
| A12 | Dispatch *Deploy to Hostinger*, target `production`; approve the `production` environment | **owner** | run id |
| A13 | After the deploy: *Live verification* (38 URLs; `commercial_launch_approved: false`; payments closed), *Operations summary*, Search Console sitemap submission | owner / Claude | run ids |

**What the deploy changes on the live site:**
- **Payments:** new payments close until the owner's commercial-launch approval.
- **Legal pages:** the DRAFT banner on the four legal pages, and neutral identity wording.
- **Admin:** lead reply drafts, and the overdue-lead indicator.
- **Source watcher:** follows redirects, and the earlier bot-check fix.
- **Facts:** 30 verified decisions and 3 Cardiff source-changed decisions.
- **Sitemap:** 36 → 38 URLs.
  - Added: Aberdeen, Buckingham, the cost page.
  - Removed: Cardiff.

Nothing is emailed by the deploy.

## B. Commercial launch (accepting student payments)

| # | Item | Status | Register |
|---|---|---|---|
| B1 | Legal name and business structure published | awaiting owner | O1 |
| B2 | Geographic address published | awaiting owner | O2 |
| B3 | Company number and part of the UK (if a company) | awaiting owner | O3 |
| B4 | ICO fee paid and reference published, or exemption confirmed | awaiting owner | O4 |
| B5 | Hosting region in the privacy notice | awaiting owner | O6 |
| B6 | NDPC position | awaiting legal review | O7 |
| B7 | Legal review recorded (`SITE_LEGAL_REVIEWED`); refund timing corrected | awaiting legal review | O8 |
| B8 | UK model cancellation form | awaiting legal review | O9 |
| B9 | Complaints procedure published | awaiting owner | O10 |
| B10 | ADR position | awaiting legal review | O11 |
| B11 | Contract-confirmation email built | awaiting legal review | O12 |
| B12 | Stripe Dashboard details | awaiting owner | O13 |
| B13 | Real Stripe test-mode journey passes | blocked on owner (`STRIPE_TEST_SECRET`) | O14 |
| B14 | £125 live rehearsal and refund | awaiting owner | O15 |
| B15 | Second admin with two-step verification | awaiting owner (recommended) | O18 |
| B16 | Deployment A complete | awaiting owner | O21 |
| B17 | Overdue enquiries answered | awaiting owner | O17 |
| B18 | **Set `SITE_COMMERCIAL_LAUNCH_APPROVED`** (the date), run *Update server settings*. Waiting students are told once that payment is open | **last step**, owner | O16 |

**Rule:** payments stay closed by the code until B18. Do not set B18 until B1–B17 are evidenced.
