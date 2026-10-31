# Operations manual (updated 2026-10-31)

How to run StudyMedicineUKNigeria.com day to day. Everything is done from **GitHub → Actions** (workflows on branch `claude/new-session-p6gdm6`) or **Admin** on the site. No step needs a server password, and none prints a secret.

## 1. Daily (about 10 minutes)

1. **Admin → Dashboard.**
   - Launch checklist: red items.
   - Overdue leads: leads still "new" after 2 working days.
   - Documents waiting for review.
   - Payments under review.
2. **Admin → Leads → Show only overdue leads.** Reply to each one, using the draft reply under the lead (after this release), and set the status to "contacted". The Contact page promises a reply within 2 working days.
3. **Admin → Messages:** answer portal messages, including complaints and cancellation requests (§5).
4. **Email:** GitHub sends failure emails for scheduled workflows. Check for any from:
   - *Uptime check*;
   - *Backup Hostinger*;
   - *Live verification*;
   - *Sitemap and robots probe*;
   - *Blocked sources watch*. A failure there means a recorded official quote is gone: a reviewer re-reads the page.

## 2. What runs automatically

**On the server (Laravel scheduler, `routes/console.php`):**

| Time (server) | Job | What it does |
|---|---|---|
| every minute | `queue:work` | sends queued emails |
| every minute | scheduler heartbeat | lets *Live verification* prove the scheduler runs |
| 08:10 Lagos | `smukn:reminders` | reminders to students with unfinished applications |
| 02:30 | `smukn:flag-review-due` | marks facts whose review date has passed (earliest due 2027-04-05) |
| 03:40 | `smukn:sources-check` | source watcher: un-verifies a fact whose official page changed, so its page goes noindex until re-verified |
| hourly | `smukn:expire-payments` | expires abandoned checkouts |
| 05:20 | `smukn:gsc-sync` | Search Console data, read-only; does nothing until connected (§7) |
| Sunday 04:10 | `smukn:retention` | applies the privacy notice's retention periods |
| weekly | `queue:prune-failed` | removes failed jobs older than 30 days |

**On GitHub (scheduled workflows; latest results on 31 October):**

| Workflow | Schedule | Last results |
|---|---|---|
| *Backup Hostinger (encrypted, off-site)* | daily database; full copy (database, shared `.env`, documents) Sundays 03:20 UTC. Every run also restore-tests the backup | success 25–29 October. **30 October failed**: SSH from the runner timed out, not a backup fault. Re-run on 31 October: success, restore test included (run 41030369264, attempt 2) |
| *Uptime check* | every 30 minutes | success, latest 30 Oct 23:43 |
| *Live verification (read-only)* | daily 06:23 UTC | success, latest 30 October |
| *Sitemap and robots probe* | daily | success 30 October, after the false-alarm fix 05eb2cd |
| *Blocked sources watch* (new) | Tuesdays and Fridays 05:23 UTC | reads, in Chrome on a runner, the official pages that refuse the server's watcher, and fails when a recorded quote is gone. 31 October: fails, as expected, on the Cardiff, Hertfordshire and City St George's changes, and reports Leicester as unreadable (bot check) |
| *Lead reply drafts* (new, dispatch only) | on demand | encrypted drafts for leads still "new"; nothing sent (`STUDENT-JOURNEY-TEST-REPORT.md` §3) |
| *Search report* | weekly | waits for Search Console (§7) |

## 3. Backups and restore

- **What:** the daily encrypted database backup off-site (GitHub, encrypted before it leaves the server; the key is not on GitHub), a weekly full backup, and a dump before every deploy (`~/backups/production-db-<timestamp>.sql.gz`, verified before migrating). Encrypted backups expire within 30 days.
- **Restore:** `ops/RESTORE.md` step by step. The first production launch used a restore-tested backup (`launch-production.yml`).
- **Automatic restore test:** every daily backup run decrypts its backup on the runner and loads it into a throwaway MariaDB before it counts as a success.
- **Owner check, monthly:** restore the latest off-site backup by hand following `ops/RESTORE.md` and record the date. This proves the owner can do it without Claude.
- **If a run fails with an SSH timeout:** re-run the failed job once. If it fails again, check hPanel (server up, SSH enabled, IP blocks). Pin the host key with `HOSTINGER_SSH_KNOWN_HOSTS` (`OWNER-ACTION-REGISTER.md` O20).

## 4. Facts, the source watcher and the audit trail

- Every published fact is in `reference_facts` with a status: VERIFIED, SOURCE_CHANGED, VERIFY-ON-PAGE, NOT_FOUND, NOT_PUBLISHED or ARCHIVED. An unverified value is hidden in production.
- **When the watcher flags a fact**, *Operations summary* lists it under `flagged_facts`, and the page that depends on it turns noindex automatically.
- **To re-verify:**
  1. *Official page excerpts* workflow: read the page.
  2. Write a review file in `ops/verification/reviews/`.
  3. Run `check-quotes.py`.
  4. Produce a `data/verification/decisions-*.csv`.
  5. The next deploy imports it. See `data/verification/README.md`.
- **Audit:**
  - `fact_changes` records every fact change (before/after, by whom or which import);
  - `fact_imports` records each decision file;
  - `admin_actions` records staff actions;
  - `application_events`, `document_events`, `document_access_log` and `submission_events` cover student files.
- **Watcher coverage (found 31 October).** The server's watcher is refused (HTTP 403) by:
  - Cambridge, Cardiff and Queen's Belfast (three indexed schools);
  - Leicester, Hertfordshire, Ulster, City St George's and the GMC.

  It has never fingerprinted those pages. *Operations summary* now prints what the watcher last saw for every core source of the indexed schools (`source_watcher`). *Blocked sources watch* covers the refused hosts from GitHub runners. Leicester also refuses the runners ("Just a moment…"), so its facts must be read by hand.
- **Redirects:** since 31 October the watcher follows a redirect and checks each value on the page it lands on, every run.
- **Cardiff (31 October):** the 2026/27 overseas fee is withdrawn. The 2027/28 fee is "awaiting confirmation". Three facts are recorded as source changed (`decisions-2026-10-31-cardiff.csv`), register D08 is DRAFT, and Cardiff is noindex after the deploy (38 URLs). When Cardiff publishes the 2027/28 fee:
  1. read it with *Official page excerpts*;
  2. record it as a new fact;
  3. return D08 to PUBLISHED and Cardiff to `publication.json`.
- **Open reviews:** Hertfordshire (3 quotes), City St George's (2), Cardiff's IB wording. None is on an indexed page.

## 5. Students: enquiries, cancellations, refunds, complaints

| Situation | Who | What to do |
|---|---|---|
| New lead | staff | reply within 2 working days; set the status |
| Document uploaded | staff | Admin → application → review; accept or return it with a reason |
| Profile ready | staff | approve for service selection; the student then sees the prices. **After the deploy, payment stays closed until the owner sets `SITE_COMMERCIAL_LAUNCH_APPROVED`** (the student sees "Payment is not open yet") |
| Payment "Manual review" | admin | the amount, currency or service differed, or it is a bank transfer: check it in Stripe; confirm or reject |
| "Duplicate payment to refund" alert | admin | refund the second payment in the Stripe Dashboard |
| Cancellation request (within 14 days) | admin | refund in the Stripe Dashboard **within 14 days of the request** (CCR 2013 reg. 34; the refund policy text is under legal review). The webhook records it and paid access ends |
| Complaint | owner | until the procedure is approved (`LEGAL-REVIEW-PACK.md` §5), acknowledge within 2 working days and answer in writing; keep the dates in the message thread |
| Data request (export or delete) | admin | the student can export from Profile; deletion within 30 days (privacy notice) |

## 6. Access and security

- **Staff accounts** need the staff or admin role and two-step verification (TOTP); an admin can reset another account's two-step. Keep at least two admins (`OWNER-ACTION-REGISTER.md`).
- **Secrets** live only in GitHub Actions Secrets and the server's `.env`. Never paste them into an issue, a workflow log or chat.
- **Security headers and controls are tested in CI:** the CSP nonce, `EnsureStaff`, `EnsureTwoFactor`, private documents and the document pipeline.
- **Error logging:** Laravel writes errors to `storage/logs/laravel.log` on the server; read it in hPanel → File Manager. No workflow reads this log. *Live verification* checks the scheduler heartbeat (≤ 5 minutes) and that queued emails wait no more than 10 minutes, and fails otherwise.
- **Email delivery:** SMTP through Hostinger; *Send test email* and *Mail delivery diagnosis* (read-only) check it. SPF, DKIM and DMARC are covered in `docs/ops/EMAIL-DELIVERABILITY.md`.

## 7. Search Console and GA4: connection and measurement plan

- **Search Console (owner, about 15 minutes; `docs/ops/SEARCH-CONSOLE-AND-GA4.md`, "Data feed"):**
  1. Verify the property.
  2. Create a service account with read access; save its JSON key as the secret `GSC_SERVICE_ACCOUNT_JSON`; set the variable `SITE_GSC_PROPERTY`.
  3. Run *Update server settings*.
  4. `smukn:gsc-sync` then fills Admin → Search daily, and *Search report* writes the weekly report.
- **What to measure first (8 weeks after the deploy):**
  - impressions and clicks for the 38 indexed URLs, by query and country (Nigeria first);
  - for the 30 noindex schools, impressions of directory and requirement pages that mention them.

  This is the demand evidence the decision register needs (§8).
- **Indexing:** after the deploy, submit `/sitemap.xml` and run a URL Inspection live test of it, plus the eight indexed school pages. Cardiff is noindex until its fee is re-verified.
- **Connection steps** (from `docs/ops/SEARCH-CONSOLE-AND-GA4.md`, "Data feed"; owner, about 15 minutes). The Domain property already exists and is DNS-verified: do not create another or change DNS.
  1. Google Cloud console → create a project → enable the **Google Search Console API**.
  2. Create a service account (`search-reader`, no roles) → *Keys* → *Add key* → JSON.
  3. Search Console → property → *Settings* → *Users and permissions* → add the service account's email with **Restricted** permission. Change it to *Full* only if the weekly report says URL inspection lacks permission; the key is read-only either way.
  4. GitHub → repository secret `GSC_SERVICE_ACCOUNT_JSON` = the whole key file; then delete the downloaded file.
  5. Run *Update server settings* (production), then *Search report* with `sync` = `480` to read the full 16 months once.
- **GA4:** loads only after consent on public pages, never in the portal. The owner records `SITE_ANALYTICS_DECISION` (`first_party` or `ga4`). Funnel events hold no personal data.

## 8. SEO evidence rules (do not bypass)

- A school page is indexed only when it is **published**, its **five core facts are verified** (or "not published" by the school) and **its decision-register row approves it**. Cambridge, King's College London and Queen's Belfast are the owner's exception of 2026-10-30; it is not demand evidence and must not be described as such.
- **Evidence needed for the 30 noindex schools** (after this deploy, from the mirror):
  - **Waiting only on demand evidence (core facts 5/5):** Aston, Brunel, Chester, Keele, Kent and Medway, Lancaster, Surrey, Swansea. They need Search Console impressions or clicks from Nigeria, or a Semrush Nigeria export, recorded in their register row, then the owner's approval.
  - **4/5:**
    - Bristol, St George's (WAEC/NECO statement);
    - Imperial, Worcester (English requirement);
    - Wolverhampton (fee).
  - **3/5:**
    - Newcastle (English, route);
    - Sunderland (WAEC, English).
  - **2/5 or less** (need a full verification round first):
    - Anglia Ruskin, Bangor, Birmingham, Dundee, Edge Hill, Hull York;
    - Manchester Metropolitan, Northumbria, Pears Cumbria, Queen Mary;
    - ScotGEM, St Mary's, Ulster, UCL, Warwick.
- **Semrush is the central SEO research input.** The account has a subscription but **no API units** (`no_api_units`, 30 October), and buying units is excluded. Use the web interface instead (`data/semrush/README.md`):
  - **main themes:** paste `paste-list.txt` (50 keywords) into Keyword Overview, database Nigeria, and export CSV;
  - **school evidence:** paste `school-paste-list.txt` (36 keywords) and import with `--sheet=data/semrush/school-lookup-sheet.csv`.

  `php artisan smukn:semrush-import` writes each figure next to its database and date into the research doc and the school's register row. A volume is evidence for a decision, never the decision.
- **Do not create pages without a register row, evidence and the owner's approval.**

## 9. Incidents and rollback

| Incident | Action |
|---|---|
| Site down (uptime alert) | Check hPanel; run *Live verification*; if a deploy caused it, *Roll back Hostinger release* with `revert_facts=true` (the default) |
| Bad deploy | The deploy's smoke test rolls back automatically, undoing the release's fact decisions too. Manual rollback: *Roll back Hostinger release* (`PRE-DEPLOYMENT-CHECKLIST.md` §5, §7) |
| Data loss | `ops/RESTORE.md` from the pre-deploy dump or the off-site backup |
| Suspected breach | Rotate the affected secrets (GitHub and server `.env`); review `document_access_log` and `admin_actions`; if personal data is affected, assess the 72-hour ICO reporting duty with an adviser |
| Payment anomaly | See §5. Never mark a payment paid by hand unless Stripe shows it paid |

## 10. Deploying

Only with the owner's explicit approval: *Deploy to Hostinger*, target `production`, approved on the `production` environment. It runs:
1. the tests and the production rehearsal;
2. a verified database backup;
3. migrations;
4. reference sync;
5. a read-only Stripe check;
6. the fact decisions;
7. the switch;
8. the smoke test, with automatic rollback if it fails;
9. a page review.

Afterwards run *Live verification* and *Operations summary*.
