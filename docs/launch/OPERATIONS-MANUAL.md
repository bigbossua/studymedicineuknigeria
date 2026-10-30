# Operations manual (2026-10-30)

How to run StudyMedicineUKNigeria.com day to day. Everything is done from **GitHub → Actions** (workflows on branch `claude/new-session-p6gdm6`) or **Admin** on the site. No step needs a server password, and none prints a secret.

## 1. Daily (about 10 minutes)

1. **Admin → Dashboard.**
   - Launch checklist: red items.
   - Overdue leads: leads still "new" after 2 working days.
   - Documents waiting for review.
   - Payments under review.
2. **Admin → Leads → Show only overdue leads.** Reply to each one, using the draft reply under the lead (after this release), and set the status to "contacted". The Contact page promises a reply within 2 working days.
3. **Admin → Messages:** answer portal messages, including complaints and cancellation requests (§5).
4. **Email:** GitHub sends failure emails for scheduled workflows. Check for any from *Uptime check*, *Backup Hostinger*, *Live verification* or *Sitemap and robots probe*.

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

**On GitHub (scheduled workflows; recent results on 30 October):**

| Workflow | Schedule | Last results |
|---|---|---|
| *Backup Hostinger (encrypted, off-site)* | daily database; full copy (database, shared `.env`, documents) Sundays 03:20 UTC | success 27, 28 and 29 October |
| *Uptime check* | every 30 minutes | success, latest 30 Oct 08:32 |
| *Live verification (read-only)* | daily 06:23 UTC | success, 28 and 29 October |
| *Sitemap and robots probe* | daily | **failed** 28 and 29 October: a false alarm (the CDN refuses fake Googlebots from GitHub). The fix is commit 05eb2cd, pushed after the last run; the next run should pass |
| *Search report* | weekly | waits for Search Console (§7) |

## 3. Backups and restore

- **What:** the daily encrypted database backup off-site (GitHub, encrypted before it leaves the server; the key is not on GitHub), a weekly full backup, and a dump before every deploy (`~/backups/production-db-<timestamp>.sql.gz`, verified before migrating). Encrypted backups expire within 30 days.
- **Restore:** `ops/RESTORE.md` step by step. The first production launch used a restore-tested backup (`launch-production.yml`).
- **Owner check, monthly:** run a restore test from the latest off-site backup into a throwaway database, following `ops/RESTORE.md`, and record the date. This is recommended rather than automated, because a restore test touches nothing in production.

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
- **Known now (30 October):** Cardiff's 2026/27 overseas fee page now redirects to the 2027-entry page, which says the 2027/28 fee is "awaiting confirmation" (`evidence/REVERIFY-2026-10-30.md`). The watcher will probably flag it, and Cardiff then goes noindex (38 URLs) until a reviewer decides what to record.

## 5. Students: enquiries, cancellations, refunds, complaints

| Situation | Who | What to do |
|---|---|---|
| New lead | staff | reply within 2 working days; set the status |
| Document uploaded | staff | Admin → application → review; accept or return it with a reason |
| Profile ready | staff | approve for service selection; the student then sees the prices |
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
  - impressions and clicks for the 39 indexed URLs, by query and country (Nigeria first);
  - for the 30 noindex schools, impressions of directory and requirement pages that mention them.

  This is the demand evidence the decision register needs (§8).
- **Indexing:** submit `/sitemap.xml` and run a URL Inspection live test of it, plus the nine school pages, after the deploy.
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
- **Semrush:** the account has a subscription but **no API units**, so `no_api_units` was returned on 30 October and no data was obtained. Buying units is excluded. Use the Semrush web interface instead: export keyword reports for the Nigeria database and import them with `php artisan smukn:semrush-import`.
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
