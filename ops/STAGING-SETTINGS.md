# Settings that unlock real staging (owner checklist)

Nothing private ever goes into chat, an issue, a commit or a file in this repository. Every value below is typed
by the owner into GitHub or hPanel directly. GitHub hides secret values after saving; the *Diagnose Actions settings*
workflow then reports only `PRESENT` / `missing` and lengths.

GitHub paths: **Repository → Settings → Secrets and variables → Actions** (tabs *Secrets* and *Variables*) for
repository level; **Settings → Environments → `staging` / `production`** for environment level. Both environments
already exist.

## 1. On Hostinger (hPanel) first

| What | Where in hPanel (names may differ slightly) | Note |
|---|---|---|
| Authorise the GitHub Actions key | Websites → Manage → Advanced → **SSH Access** → SSH keys → Add | Paste only the **public** half (`smukn_deploy.pub`, name `SMUKN-GitHub-Actions`). Leave the existing `Claude–StudyMedicineUKNigeria` key untouched. |
| SSH host, port, username | same SSH Access page | Port is usually 65002. |
| Staging subdomain | Domains → **Subdomains** → `staging` | Then Security → SSL for `staging.studymedicineuknigeria.com`. |
| Two MySQL databases | Websites → Manage → **Databases** → Management | One for staging, one for production, each with its own user. Use a long generated password **without** a single quote `'` (refused by bootstrap). |

## 2. In GitHub

| Name | Type | Scope | Value from |
|---|---|---|---|
| `HOSTINGER_SSH_KEY` | Secret | Repository | The **private** half of the GitHub Actions key (`smukn_deploy`), whole file including the BEGIN/END lines. Never the Claude key. |
| `BACKUP_PASSPHRASE` | Secret | Repository | A new long passphrase. Also store it in your password manager: without it no backup can be decrypted. |
| `HOSTINGER_SSH_HOST` | Variable | Repository | hPanel SSH Access (IP or hostname) |
| `HOSTINGER_SSH_PORT` | Variable | Repository | hPanel SSH Access (e.g. 65002) |
| `HOSTINGER_SSH_USER` | Variable | Repository | hPanel SSH Access (e.g. `u123456789`) |
| `HOSTINGER_SSH_KNOWN_HOSTS` | Variable | Repository | Recommended: the lines printed by the *Inspect Hostinger* run under "Server host key", after comparing the fingerprint with `ssh-keyscan -p <port> <host> \| ssh-keygen -lf -` run on your own computer. Once set, every workflow refuses a different server key. |
| `STAGING_URL` | Variable | Repository | `https://staging.studymedicineuknigeria.com` |
| `STAGING_BASIC_USER` | Variable | Repository | Any username for the staging password prompt |
| `STAGING_BASIC_PASSWORD` | Secret | Repository | Any long password (no `'`) |
| `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` | Secrets | **Environment `staging`** | The staging database from step 1 |
| `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` | Secrets | **Environment `production`** | The production database from step 1 (only needed before the production bootstrap) |
| Required reviewers | Protection rule | **Environment `production`** | Tick *Required reviewers*, add yourself, save. Production deploys refuse to run without it. |

**Stripe test mode on staging (for the payment review).** The fees are in the service records (T1 £125, T2 £695,
T3 £1,295) and are shown only in the portal, to a student whose profile staff approved. In the Stripe Dashboard switch
to **Test mode**, then:
1. Developers → API keys: copy the *publishable* key (`pk_test_…`) and the *secret* key (`sk_test_…`).
2. Developers → Webhooks → Add endpoint: URL `https://staging.studymedicineuknigeria.com/webhooks/stripe`, events
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.expired`,
   `payment_intent.payment_failed`, `charge.refunded`, `charge.dispute.created`; then reveal its signing secret (`whsec_…`).
3. GitHub → Settings → Environments → `staging` → add secrets `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`.
4. Actions → *Update server settings* (`staging`) writes them into the server's `.env` (a live key is refused on staging).
5. Actions → *Stripe test-mode journey* proves the whole paid journey against Stripe itself on a GitHub runner (no
   server needed): catalogue sync, three students through staff approval, service choice, Stripe's hosted Checkout with
   test cards and Stripe-signed webhooks, each charge read back from Stripe, then the public price-leakage checks.
6. Actions → *Stripe catalogue* (`staging`, `create`) creates the three products (`smukn_t1`, `smukn_t2`, `smukn_t3`) and
   their one-time GBP prices in test mode, or finds them if they exist (never duplicates; any other active price on
   those products is switched off; a Payment Link selling them fails the run). Every deploy repeats this, and the first
   checkout would do it too. With `webhook=create` it also creates the webhook endpoint when none exists and writes its
   signing secret straight into the server's `.env` (Stripe shows it only once; it never appears in a log). Then you
   need not create the endpoint by hand; if you did, put its signing secret in `STRIPE_WEBHOOK_SECRET` as above.
Pay with Stripe's test card `4242 4242 4242 4242` (any future date, any CVC); `4000 0000 0000 0002` is declined.

Optional, later: `SMUKN_MAIL_PASSWORD` (mailbox `info@studymedicineuknigeria.com`), `SITE_WHATSAPP`, `SITE_LEGAL_NAME`,
Stripe test keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) in the `staging` environment (applied with *Update server settings*). Staging works
without them: without a mail password, emails (verification links, resets) are written to the server log instead of
sent, and registration still works; card payments stay disabled until the Stripe keys exist.

## 3. What happens next (Claude, no further owner input until the staging review)

1. *Diagnose Actions settings* → confirms every row above is PRESENT (values never printed).
2. *Inspect Hostinger (read-only)* → server report: PHP binary, MySQL, disk, existing sites, document roots, host key.
3. *Bootstrap Hostinger target* (`staging`, `link_docroot=true`, `docroot` = the staging subdomain's folder the
   inspection reports, typically `~/domains/staging.studymedicineuknigeria.com/public_html`) → `shared/.env` created on
   the server; the subdomain's placeholder folder is archived to `~/backups` and replaced by a link to the release that
   the first deploy creates. Nothing belonging to the main domain is touched. The link must exist before the first
   deploy, otherwise its smoke test meets Hostinger's placeholder page and rolls the deploy back.
4. *Deploy to Hostinger* (`staging`) → tests, verified DB backup, migrate, reference sync, switch, smoke test,
   automatic rollback on any failure; then *Review staging* runs on its own (every page on desktop and mobile:
   status, CSP, accessibility, H1, noindex).
5. *Backup Hostinger* (`staging`, `full`) → encrypted backup, then a restore test into a throwaway database.
6. First admin: you register at the staging site like a student; then *Grant account role* (`staging`, your email,
   `admin`, `verify_email=true` while staging cannot send mail) promotes you. Your first admin sign-in asks you to
   set up an authenticator app.
7. Owner reviews `https://staging.studymedicineuknigeria.com` (staging password prompt).
8. Production only after the owner's approval: backup → bootstrap production → deploy production (the required
   reviewer approves each run).

## 4. Production launch (after the staging review)

Production is refused by the scripts unless all of this is true, so nothing half-configured can go live: `APP_ENV=production`,
`APP_DEBUG=false`, `APP_URL=https://studymedicineuknigeria.com`, real email (`MAIL_MAILER=smtp` with a password),
Stripe keys either absent (payment closed) or live ones only (test keys are refused in production, live keys on
staging), unverified facts hidden, and a successful staging deploy of the same commit.

**Stripe is a post-launch step (owner decision 2026-10-04).** Production launches without Stripe keys: an approved
student can see the fees and choose a service, but no payment button or bank-transfer form is shown and the server
refuses any payment request until live keys are written with *Update server settings*; that run then emails each
waiting student once. Bank transfer stays off unless `SITE_BANK_TRANSFER=true` is set in the server's `.env`.

**Owner inputs, all in GitHub → Settings → Environments → `production` → secrets** (typed there, never in chat):

| Name | Value from |
|---|---|
| `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` | the production database in hPanel → Databases (a new, empty one; never the old site's database) |
| `SMUKN_MAIL_PASSWORD` | the password of the mailbox `info@studymedicineuknigeria.com` (hPanel → Emails) |
| `STRIPE_KEY`, `STRIPE_SECRET` (after launch) | Stripe Dashboard in **live mode** → Developers → API keys: `pk_live_…` and `sk_live_…` (a restricted `rk_live_…` key with Checkout Sessions write access also works) |
| `STRIPE_WEBHOOK_SECRET` (after launch) | Stripe (live mode) → Developers → Webhooks → Add endpoint `https://studymedicineuknigeria.com/webhooks/stripe`, the six events listed in section 2 → reveal the signing secret `whsec_…` |
| Required reviewers | protection rule on `production` (yourself) |

Stripe products and prices need no manual setup: *Stripe catalogue* (`production`, `create`), every deploy and the first
checkout create or find one product per service (`smukn_t1`..`t3`) with one active one-time GBP price matching the
service record (T1 £125, T2 £695, T3 £1,295). Checkout charges that price, chosen by the server; an admin price change
creates a new Stripe price and switches the old one off. Do not create Payment Links: they would bypass the profile
review.

**Already checked from outside (Launch checks workflow, 2026-10-04):** the domain's email DNS is in place (Hostinger MX,
one SPF record `include:_spf.mail.hostinger.com`, DMARC `p=none`, DKIM keys at `hostingermail-a/-b/-c`); the certificate
covers the domain and `www` until 22 Nov 2026; `staging.studymedicineuknigeria.com` does not exist yet; the current site
is a PHP 8.2 application behind Hostinger's CDN whose sitemap lists 76 URLs, each of which the new site answers
(`data/seo/legacy-redirects.csv`: 65 permanent redirects; 4 same paths; 7 with no equivalent answer 404).

**Sequence (Claude runs each step; you approve every production run as its required reviewer):**
1. *Diagnose Actions settings* → every staging and production row PRESENT.
2. *Inspect Hostinger (read-only)* → which folder serves `studymedicineuknigeria.com`, what application it is, where its
   settings and database are.
3. *Backup Hostinger* (`production`, scope `site`, `site_docroot` = that folder) → the current site's files plus its
   database (WordPress `wp-config.php`, or an application `.env` in or above the folder), encrypted, downloaded,
   decrypted and restored into a throwaway database on the runner. The production cutover refuses to run without such
   a backup from the last 7 days.
4. *Bootstrap* and *Deploy to Hostinger* (`staging`) of the release commit → HTTPS staging (refused otherwise) behind its
   password and noindex; smoke test and full page review (status, CSP, accessibility, noindex). Production then
   accepts only that same commit.
5. *Bootstrap Hostinger target* (`production`, `app_url` = `https://studymedicineuknigeria.com`, `link_docroot=false`) →
   needs the new production database and the mailbox password; student registration starts **closed**.
6. *Deploy to Hostinger* (`production`, `cutover_docroot` = the folder from step 2) → preflight of the server settings
   before anything changes, database backup, migrations, reference sync (including the old-site redirects), release
   switch, then the folder is archived to `~/backups`, moved aside (never deleted) and replaced by a folder holding the release's public files (Hostinger does not serve a linked document root); the
   production smoke test runs (HTTPS, one host with `www` redirected, no noindex, HSTS, CSP, no exposed files, no
   debug output, no staging password); **if it fails, the old site is put back automatically**. The production page
   review follows.
7. You flush the CDN cache once: hPanel → Websites → your site → **CDN** → flush/purge cache (so no visitor is served
   a cached page of the old site). Then *Launch checks* confirms what the domain serves.
8. *Send test email* (`production`, `to` = an inbox you can check, `registration=unchanged`) → you confirm the
   message arrived (not in spam; in Gmail "Show original" shows SPF, DKIM and DMARC PASS). Then *Send test email* again
   with `registration=open` → registration opens only if the message is accepted and the email DNS checks pass.
9. First admin: you register at the live site, confirm your email, then *Grant account role* (`production`, your
   email, `admin`, `verify_email=false`); your first admin sign-in sets up the authenticator app.
10. *Backup Hostinger* (`production`, `full`) → first backup of the live database, `.env` and documents, restore-tested.
11. Stripe, after launch (payment stays closed until then): live keys in Environments → `production`, *Update server
    settings*, *Stripe catalogue* (`production`, `create`); waiting students are emailed once.

To undo: *Roll back Hostinger release* (`production`) returns to the previous release (code only; the database is
never restored automatically), or with `restore_previous_site=true` serves the old site again exactly as it was.
