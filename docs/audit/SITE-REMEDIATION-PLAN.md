# Site remediation plan (2026-10-29)

**Starting point.** This plan works from the 40 items of `docs/audit/LAUNCH-READINESS-AUDIT-2026-10-29.md`. It sorts every open item by who can close it.

**The result** is in `docs/audit/SITE-COMPLETION-REPORT.md`.

**Status words:**
- **DONE (branch):** fixed and tested on `claude/new-session-p6gdm6`; reaches the live site only with the next approved deploy.
- **OWNER:** needs information, an account or a decision only the owner has.
- **OPEN:** repository work still to do.

## A. Done in the repository (needs the next approved deploy to reach production)

| Audit item | Fix | Files | Proof |
|---|---|---|---|
| 2: blank legal name | Empty or whitespace identity settings now count as unset, and templates fall back with `?:` | `config/site.php`, 7 Blade views | `tests/Feature/LegalIdentityTest.php` (6 tests) |
| 1, 3: identity not visible to the admin | Launch checklist item "Trading identity published". Live Verification reports `legal_name_set`, `address_set`, `ico_number_set`, `legal_reviewed_set` and `analytics_decision` without printing values | `app/Http/Controllers/Admin/DashboardController.php`, `.github/workflows/live-verify.yml` | `LegalIdentityTest`, `VerificationAdminTest` |
| 6, 40: leads waiting unseen | Overdue marker: a lead still `new` after 2 working days. Shown as a dashboard tile, a banner and `?overdue=1` filter in Admin → Leads, and a per-row "Overdue: N working days" | `app/Models/Lead.php`, `Admin/MiscAdminController.php`, `admin/leads.blade.php`, `admin/dashboard.blade.php` | `tests/Feature/LeadFollowUpTest.php` (5 tests) |
| 12, 37: watcher false flags | A bot-verification page counts as unreachable, not as a page change | `app/Console/Commands/CheckSources.php` | 3 new tests in `SourceWatcherTest` |
| 12, 37, 11: flagged facts | 30 facts re-verified from official pages; Imperial's route corrected. 18 schools reach 5/5 core facts (7 today), and the cost page returns. Indexing still needs a live register row | `ops/verification/reviews/2026-10-29-*.json`, `data/verification/decisions-2026-10-29-*.csv` | `check-quotes.py`: 0 problems; import dry run: 0 unknown. `docs/audit/UNIVERSITY-VERIFICATION-REPORT-2026-10-29.md` |
| 24: sitemap probe failing daily | The CDN's 403 to a fake Googlebot from GitHub's addresses is now a warning that points to URL Inspection. Content is judged as a browser receives it, and a real outage still fails | `ops/sitemap-probe.sh`, `.github/workflows/sitemap-probe.yml` | `ops/qa/sitemap-probe-selftest.sh` (3 cases, in CI) |
| 33: stale SEO report | `docs/seo/PAGE-AUDIT.md` regenerated: 39 sitemap URLs of a production-mirror build, 0 flagged | — | `python3 ops/seo/page-audit.py` |
| (new) security advisory | `symfony/mime` 7.4.19 → 7.4.22 (CVE-2026-45070, email header injection). CI's dependency audit had failed on it since the advisory of 28 October | `composer.lock` | `composer audit`: none; CI green |
| 7: indexing without demand evidence | Indexing needs a live decision-register row as well as verified facts and publication; Admin shows each school's index status and why | `app/Support/DecisionRegister.php`, `app/Models/University.php`, Admin universities view | `SchoolRegisterGateTest` (4 tests) |
| (new) vacuous test | The course-to-subject test ran on an empty table and proved nothing | `tests/Feature/HealthcareTaxonomyTest.php` | 11 assertions, no risky test |

## B. Owner actions (in order)

1. **Reply to the three waiting leads** (Admin → Leads; 9 days, against a promise of 2 working days). No deploy needed.
2. **Approve a deploy** of this branch: Actions → *Deploy to Hostinger* (`deploy-hostinger.yml`), production, reviewer approval. It:
   - fixes the blank legal name;
   - adds the overdue-lead indicator;
   - applies the security update;
   - imports the 30 decisions;
   - adds Aberdeen, Buckingham and the cost page to the sitemap (36 → 39 URLs); nothing is removed (`PRE-DEPLOYMENT-CHECKLIST.md` §3).
3. **Index decisions for schools without Nigerian demand evidence.**
   - **Cambridge, Queen's Belfast and King's College London: DECIDED (owner, 2026-10-30).** They stay indexed as an owner-approved exception. Register D14, D34 and D27 keep status RESEARCH with no demand claimed. Revisit when Search Console shows data.
   - **Aston, Brunel, Chester, Imperial, Keele, Kent and Medway, Lancaster, Surrey and Swansea** stay noindex: facts verified, but no register approval. **[Recommendation]** wait for Search Console evidence; any exception needs an explicit owner decision.
4. **Trading identity, ICO registration and legal review:** `docs/audit/OWNER-LEGAL-CHECKLIST.md` §1 and §6. Then set the production variables and run *Update server settings*.
5. **Statutory model cancellation form** (CCR 2013 reg. 13(1)(b)) and **complaints procedure** (Schedule 2 (o)): checklist §2 and §3. The form text must be copied from legislation.gov.uk's image of Schedule 3 Part B. The complaints draft needs two time limits chosen.
6. **Stripe:**
   - set the support email (Dashboard → Settings → Public details);
   - add the `STRIPE_TEST_SECRET` repository secret so the test-mode journey can run;
   - then run the £125 live rehearsal (pay, webhook, admin confirmation, refund) with the owner's own card.
7. **Search Console feed:** `docs/ops/SEARCH-CONSOLE-AND-GA4.md`, "Data feed". It needs a Google Cloud project, a read-only service account added to the property, the `GSC_SERVICE_ACCOUNT_JSON` secret and *Update server settings*. Once connected, the weekly report replaces guesswork about Nigerian demand (the §8.3 decisions above).
8. **Confirm real Googlebot access:** Search Console → URL Inspection → *Test live URL* on `https://studymedicineuknigeria.com/sitemap.xml` and on one school page. The probe can no longer prove this from GitHub's addresses.
9. **Analytics decision:** set `SITE_ANALYTICS_DECISION` to `first_party` (recommended) or `ga4` (checklist §7).
10. **Staffing:** a second staff account (Admin → Users), so a lead is answered when the owner is away.

## C. Repository work still open

| Item | What | Blocked by |
|---|---|---|
| Model cancellation form on `/refund-policy` and the payment confirmation | Add the statutory text once supplied, with a test that it renders and that the "To" line uses the published identity | owner (B5) |
| Complaints section in the application terms, linked from Contact | Publish the approved draft | owner (B5) |
| About 121 remaining flagged facts | Re-read them in batches, starting with Bristol, City St George's, Wolverhampton and Worcester (one fact from 5/5 each) | none: next verification round |
| Refund policy version line | Update after the legal review | owner (B6 of the audit, checklist §6) |
