# Pre-deployment checklist: `claude/new-session-p6gdm6` → production (2026-10-29)

**Status: prepared, not deployed.** A deploy needs the owner's approval on the `production` environment.

**Labels:**
- **[Fact]**: checked, with the command or run cited;
- **[Expected]**: follows from the code and today's production state;
- **[Owner]**: needs the owner;
- **[Recommendation]**: advice.

## 1. What would be deployed

- **[Fact] Deployed now:** release `2026-10-28T11-12-26` = commit `5d76858`. Deploy run 40666236293 on 28 October; the latest *Deploy to Hostinger* run.
- **[Fact] Branch:** `claude/new-session-p6gdm6`. Local and `origin` are at the same commit, and nothing is unpushed (`git status -sb`, `git rev-parse HEAD origin/claude/new-session-p6gdm6`). The head is the commit that adds this file.
- **[Fact] Commits since `5d76858`:**
  - 15 are documentation or research only (`research/`, `docs/`);
  - the code, data and workflow commits are below.

| Commit | Change | Runtime effect in production |
|---|---|---|
| b92d1e3 | Empty legal-identity settings count as unset; templates fall back with `?:`; admin checklist item; Live Verification reports presence only | the footer, Privacy, Terms, Our status, About, Contact and the email footer show "Study Medicine UK Nigeria" / "To be published" instead of a blank. No legal wording or identity value changes |
| d663616 | Overdue-lead indicator | Admin only: a dashboard tile, a banner on Leads, an `?overdue=1` filter, and a per-row marker. No email, no change to leads |
| 7a1372c | Source watcher: a bot-check page counts as unreachable | nightly `smukn:sources-check` stops flagging facts from bot-check pages |
| 2fc065e, f61987e, 48a2f9a, 1d3e72a | 30 fact decisions | `ops/deploy.sh` imports four new files `data/verification/decisions-2026-10-29-*.csv`; earlier files are skipped by the import ledger |
| 05eb2cd | Sitemap probe and its self-test | GitHub Actions only, no server change |
| efa5fbd | `symfony/mime` 7.4.19 → 7.4.22, `symfony/polyfill-mbstring` 1.43.0 → 1.43.3 | security fix, CVE-2026-45070 |
| ab0894f | A school is indexed only with a live decision-register row | sitemap and robots meta for school pages (§3); Admin → Universities shows each school's index status and reason |
| 41e2a80 | Test only | none |

- **[Fact]** There are no database migrations in the diff (`git diff --name-only 5d76858..HEAD -- database/migrations` is empty).
- **[Fact]** No change to:
  - `.env` handling or `config/services.php`;
  - Stripe code or prices;
  - routes, middleware, two-step login, CSP or the document pipeline;
  - legal page wording beyond the fallback text above.

## 2. Checks run on the branch (29 October)

| Check | Command or run | Result |
|---|---|---|
| Code style | `vendor/bin/pint --test` | passed |
| Full test suite (with the register gate) | `php -d memory_limit=1G vendor/bin/phpunit` | **278 passed**, 19,374 assertions, 0 failed, 0 risky |
| Register gate | `phpunit --filter SchoolRegisterGateTest` | 4 passed |
| Payments (mocked) | `phpunit --filter 'Stripe\|Payment\|Pricing\|Checkout\|Webhook'` | 48 passed (run on `22e71d9`; no payment code changed since) |
| Production rehearsal | `mv .env aside; ops/production-rehearsal.sh 8090; restore .env` | "rehearsal: all production checks passed" (44 ok), on `ab0894f` |
| Sitemap probe self-test | `ops/qa/sitemap-probe-selftest.sh` | open, block and all: 3/3 |
| Browser journeys (local, log mailer, Stripe stand-in) | `node ops/qa/journey.cjs`, `staff-journey.cjs`, `payment-journey.cjs` (phone and desktop), `stripe-e2e.cjs` | 0 problems (run on `22e71d9`, before the register gate, which does not touch these paths) |
| Dependency audit | `composer audit --locked`; `npm audit --omit=dev --audit-level=high` | no advisories; 0 vulnerabilities |
| Lock file matches the install | `composer.lock` against `vendor/composer/installed.json` | 111 packages, 0 mismatches; `symfony/mime` v7.4.22 in both; the dist zip URL is present |
| `composer.json` | `composer validate --no-check-publish` | valid; `symfony/mime` is a transitive dependency (`laravel/framework`, `symfony/mailer`), so no direct constraint is needed |
| Quotes against official pages | `ops/verification/check-quotes.py` for the four review files of 29 October | 0 problems each |
| Decision import | `php artisan smukn:facts-import <file> --dry-run` | 0 unknown references in every file |
| CI | GitHub Actions *CI* | green on `c5105a9`, `f12fac8` and `22e71d9`; must be green on the head before approval (§6) |

## 3. Expected sitemap after the deploy

**[Fact] Production now:** 36 URLs (live `sitemap.xml` read in page-excerpts run 40949529039, 29 October 20:29 UTC).

**[Expected] After the deploy:** 36 URLs. This is identical to the sitemap of a local production-mode build with every decision imported and the register gate on (diffed: 0 differences).

| Change | URL | Reason |
|---|---|---|
| removed | `/medical-schools/cambridge` | register D14 RESEARCH: no demand evidence and no publication approval |
| removed | `/medical-schools/kings-college-london` | register D27 RESEARCH |
| removed | `/medical-schools/queens-belfast` | register D34 RESEARCH |
| added | `/medical-schools/aberdeen` | register D07 PUBLISHED; application route re-verified (run 40942424622) |
| added | `/medical-schools/buckingham` | register D13 PUBLISHED; international acceptance re-verified (run 40943430188) |
| added | `/fees/cost-of-studying-medicine-in-the-uk` | gate `topics-verified:student-visa,costs-2026,ucat-2026,ucas-2027`; its only flagged fact (UCAS Extra opens) is re-verified |

**Unchanged (33):**
- `/`, `/about`, `/admissions`, `/admissions/how-to-apply`, `/admissions/ucas-deadlines-2027`, `/admissions/ucat`, `/application-terms`;
- `/apply-online`, `/apply-online/eligibility`, `/apply-online/services`;
- `/contact`, `/faq`, `/fees`, `/how-we-verify`;
- `/medical-schools`, `/medical-schools/brighton-sussex`, `/medical-schools/cardiff`, `/medical-schools/edinburgh`, `/medical-schools/manchester`;
- `/our-status`, `/privacy`, `/refund-policy`;
- `/requirements`, `/requirements/a-levels`, `/requirements/english-language`, `/requirements/neco`, `/requirements/nigerian-degree-graduate-entry`, `/requirements/waec`;
- `/study-medicine-in-the-uk`, `/study-medicine-in-the-uk/foundation-routes`, `/study-medicine-in-the-uk/from-nigeria`;
- `/terms`, `/working-in-the-uk`.

**Not added, although their facts become verified:** Aston, Brunel, Chester, Imperial, Keele, Kent and Medway, Lancaster, Surrey and Swansea. Their register rows are RESEARCH. Verified facts alone never index a page.

**[Assumption]** Production does not change before the deploy. The nightly watcher (03:40 UTC) could flag more facts, and the owner could publish or unpublish in Admin. Either would change the count. Live Verification after the deploy reports the real figure.

**[Owner] Decision before approving.** Approving this deploy also removes Cambridge, King's College London and Queen's Belfast from the index. Their pages stay online, noindex.
- To keep any of them indexed, the owner gives an explicit directive. Its register row then moves to PUBLISHED with that directive as the recorded reason, in a commit before the deploy.
- Nothing in the repository supplies Nigerian demand evidence for them today.
- **[Recommendation]** Accept the removal and decide again with Search Console data.

## 4. Confirmed unchanged in production (29 October)

**[Fact]** Every workflow run since the last deploy (28 October 11:16 UTC) was listed: 39 runs.

**None of these ran in that window:**
- `deploy-hostinger`, `rollback-hostinger`, `launch-production`, `bootstrap-hostinger`;
- `update-env-hostinger` (last run 2026-10-04);
- `stripe-catalog` (last run 2026-10-04);
- `mail-hostinger` (never run), `account-role-hostinger` (last run 2026-10-04);
- `design-photos`, `diagnose-settings`, `inspect-hostinger`, `docroot-probe`.

**The runs in the window were read-only checks:** CI, uptime, Live Verification, sitemap probe, backup, page excerpts, launch checks, operations summary, performance and the production review. In addition:
- `mail-diagnose` run 40691174950 tested SMTP sign-in only and sent nothing;
- `stripe-test-journey` run 40942779040 stopped at its "Test key only" check because `STRIPE_TEST_SECRET` is missing, so no Stripe object was created.

**Therefore no change was made to:**
- production environment variables or DNS;
- Stripe live settings or the catalogue;
- live database records, outside the application's own scheduled jobs;
- student or lead emails.

The local journeys used a throwaway local database, the `log` mailer and a Stripe stand-in.

## 5. Unresolved blockers (not fixed by this deploy)

All need the owner; details in `OWNER-LEGAL-CHECKLIST.md`. The deploy does not make any of them worse.
- **Legal identity:** legal name, geographic address, company number if incorporated.
- **ICO registration.**
- **Model cancellation form:** the statutory text must be copied by a person.
- **Complaints procedure:** draft ready; two time limits to choose.
- **Legal review of the four legal pages.**
- **Stripe:** support email; test key `STRIPE_TEST_SECRET`; the £125 live rehearsal.
- **Three leads** unanswered for 9 days.
- **Search Console data feed.**
- **Index decisions** on the 12 schools with verified facts but no demand evidence (§3).

**Residual risk, follow-up:** a university page that keeps serving a bot check is now counted as unreachable every night. Its facts stay as they are and are not re-checked. The count appears only in the `sources.check` log line.
- **[Recommendation]** In the next cycle, show pages unreachable for 7 days or more in Admin → Sources.

## 6. Deployment steps (only after approval)

1. **Confirm CI** is green on the branch head (Actions → CI, latest run on `claude/new-session-p6gdm6`).
2. **[Owner]** Decide on Cambridge, King's College London and Queen's Belfast (§3).
3. **Deploy:** Actions → **Deploy to Hostinger** → Run workflow:
   - branch `claude/new-session-p6gdm6`;
   - target `production`;
   - `cutover_docroot` left empty.

   The production reviewer approves. The workflow then:
   - runs `ops/production-rehearsal.sh` before building, and any failure stops it;
   - backs up the database and refuses to continue without a verified dump (`ops/deploy.sh`);
   - runs `migrate` (no new migrations), `reference-sync` and the four new decision imports;
   - caches config, switches the release and smoke-tests it.
4. **Afterwards (read-only):**
   - **Live Verification:** expect 36 sitemap URLs and `legal_name_set: false`.
   - **Operations Summary:** expect 5/5 core facts for Aberdeen, Buckingham and the nine RESEARCH schools.
   - **Sitemap probe:** expect a pass, with warnings only for the fake-Googlebot 403.
   - Open `/privacy`: "Study Medicine UK Nigeria ("we") is the data controller".
   - Open Admin → Universities: Cambridge shows "noindex: no live decision-register row".

## 7. Rollback procedure

1. **Code:** Actions → **Roll back Hostinger release**:
   - target `production`;
   - `restore_previous_site` = `false`.

   The reviewer approves. `current` is repointed to the previous release (`5d76858`) and smoke-tested. There are no migrations to undo.
2. **Database: the code rollback does not undo the 30 imported fact decisions.** Under the old code (no register gate), the nine RESEARCH schools would then become indexable, because they are published and now 5/5. To avoid that, do one of:
   - **(a)** In Admin → Universities, unpublish those nine schools (owner action, reversible). **Recommended** if only the code is at fault.
   - **(b)** Restore the database from the dump the deploy made just before migrating: `~/backups/production-db-<timestamp>.sql.gz` (the name is printed as "db backup verified:" in the deploy log). This also undoes anything students or staff did after the deploy. Use it only if the data itself is wrong.
3. **Verify:** run Live Verification and compare its sitemap count with the 36 URLs listed for production now in §3.
