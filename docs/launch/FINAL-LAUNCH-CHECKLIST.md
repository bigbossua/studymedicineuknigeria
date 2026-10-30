# Final launch checklist (2026-10-30)

Two separate approvals. **A** can be given now. **B** cannot be given until every L item in `OWNER-ACTION-REGISTER.md` is closed.

**Which commit.** The head is the commit that adds this file. A file cannot name its own commit, so the exact SHA and its CI run are stated in the report to the owner. At approval time, confirm the head: GitHub → branch `claude/new-session-p6gdm6` → latest commit, with a green *CI* check.

## A. Technical deployment (branch head → production)

| # | Check | Status | Evidence |
|---|---|---|---|
| A1 | CI green on the branch head | to confirm at approval | GitHub Actions *CI* |
| A2 | 288 tests, formatting, dependency audits | complete | `FINAL-READINESS-REPORT.md` §3 |
| A3 | Production rehearsal | complete: 44 ok, 0 fail | §3 |
| A4 | Student, staff and payment journeys (stand-in) | complete: 0 problems | `STUDENT-JOURNEY-TEST-REPORT.md` |
| A5 | Sitemap: 39 URLs; nine schools indexed (six approved in the register, three by the owner's exception); robots and canonicals correct | complete | §3; `docs/audit/PRE-DEPLOYMENT-CHECKLIST.md` §3 |
| A6 | No migrations; no `.env` written by the deploy | complete | `git diff 5d76858..HEAD -- database/migrations` is empty |
| A7 | Deploy reads Stripe only (`smukn:stripe-sync --check`) | complete | commit 07c1b63; test pins it |
| A8 | Rollback undoes the release's fact decisions automatically | complete | commit 8f5fcea; mirror proof |
| A9 | Fresh *Operations summary* right before dispatch: no new flags on the nine schools or the cost page | **owner, at approval** | run id. Note: Cardiff may be flagged by the watcher (O21); if so, expect 38 URLs and record it, as the gate is working |
| A10 | Owner's explicit approval | **owner** | — |
| A11 | Dispatch *Deploy to Hostinger*, target `production`; approve the `production` environment | **owner** | run id |
| A12 | After the deploy: *Live verification* (sitemap count as A9 predicts), *Operations summary*, Search Console sitemap submission | owner / Claude | run ids |

**What the deploy changes on the live site:**
- **Pages and settings:**
  - the DRAFT banner on the four legal pages;
  - neutral identity wording;
  - the lead reply drafts in Admin;
  - the fallback text when no legal name is set;
  - the overdue-lead indicator;
  - the source-watcher fix.
- **Facts and sitemap:**
  - 30 verified fact decisions;
  - the sitemap grows from 36 to 39 (Aberdeen, Buckingham and the cost page added).
- **Not changed:**
  - no legal wording beyond the banner and the identity line;
  - no price, no Stripe setting, no email sent.

## B. Commercial launch (taking students' money)

| # | Item | Status | Register |
|---|---|---|---|
| B1 | Legal name and business structure published | awaiting owner | O1 |
| B2 | Geographic address published | awaiting owner | O2 |
| B3 | Company number and part of the UK (if a company) | awaiting owner | O3 |
| B4 | ICO fee paid and registration published, or exemption confirmed | awaiting owner | O4 |
| B5 | Hosting region in the privacy notice | awaiting owner | O6 |
| B6 | NDPC position | awaiting legal review | O7 |
| B7 | Legal review of the four pages; `SITE_LEGAL_REVIEWED` set | awaiting legal review | O8 |
| B8 | Model cancellation form (UK Schedule 3 Part B) | awaiting legal review | O9 |
| B9 | Complaints procedure approved and published | awaiting owner | O10 |
| B10 | ADR position | awaiting legal review | O11 |
| B11 | Contract-confirmation email (CCR reg. 16) built | awaiting legal review | O12 |
| B12 | Refund timing corrected to the 14-day rule (pack C4) | awaiting legal review | O8 |
| B13 | Stripe support email, descriptor, business details | awaiting owner | O13 |
| B14 | Real Stripe test-mode journey passes (needs `STRIPE_TEST_SECRET`) | blocked on owner | O14 |
| B15 | £125 live payment and refund rehearsal | awaiting owner | O15 |
| B16 | Payments-open decision recorded | awaiting owner | O16 |
| B17 | Second admin with two-step verification | awaiting owner (recommended) | O18 |
| B18 | Deployment A complete | awaiting owner | O20 |

**Rule until B is complete:** do not approve any student for service selection. A student sees prices and the pay button only after that approval, so no payment can happen without the admin's own action.
