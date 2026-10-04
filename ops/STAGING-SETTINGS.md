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

**Stripe test mode on staging (for the payment review).** Prices are already set (T1 £125, T2 £695, T3 £1,295). In the
Stripe Dashboard switch to **Test mode**, then:
1. Developers → API keys: copy the *publishable* key (`pk_test_…`) and the *secret* key (`sk_test_…`).
2. Developers → Webhooks → Add endpoint: URL `https://staging.studymedicineuknigeria.com/webhooks/stripe`, events
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.expired`,
   `payment_intent.payment_failed`, `charge.refunded`, `charge.dispute.created`; then reveal its signing secret (`whsec_…`).
3. GitHub → Settings → Environments → `staging` → add secrets `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`.
4. Actions → *Update server settings* (`staging`) writes them into the server's `.env` (a live key is refused on staging).
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
`APP_DEBUG=false`, `APP_URL=https://studymedicineuknigeria.com`, real email (`MAIL_MAILER=smtp` with a password), live
Stripe keys only (test keys are refused in production, live keys on staging), unverified facts hidden, and a
successful staging deploy of the same commit.

**Owner inputs, all in GitHub → Settings → Environments → `production` → secrets** (typed there, never in chat):

| Name | Value from |
|---|---|
| `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` | the production database in hPanel → Databases (a new, empty one; never the old site's database) |
| `SMUKN_MAIL_PASSWORD` | the password of the mailbox `info@studymedicineuknigeria.com` (hPanel → Emails) |
| `STRIPE_KEY`, `STRIPE_SECRET` | Stripe Dashboard in **live mode** → Developers → API keys: `pk_live_…` and `sk_live_…` (a restricted `rk_live_…` key with Checkout Sessions write access also works) |
| `STRIPE_WEBHOOK_SECRET` | Stripe (live mode) → Developers → Webhooks → Add endpoint `https://studymedicineuknigeria.com/webhooks/stripe`, the six events listed in section 2 → reveal the signing secret `whsec_…` |
| Required reviewers | protection rule on `production` (yourself) |

Stripe prices need no setup: Checkout is created from the service records (T1 £125, T2 £695, T3 £1,295, GBP), so the
website price and the charge are always the same number.

**Sequence (Claude runs each step; you approve every production run as its required reviewer):**
1. *Diagnose Actions settings* → every production row PRESENT, Stripe mode "live".
2. *Inspect Hostinger (read-only)* → what `studymedicineuknigeria.com` serves today (folder, WordPress or static, database).
3. *Backup Hostinger* (`production`, scope `site`, `site_docroot` = that folder) → the existing site's files and, if it is
   WordPress, its database, encrypted, downloaded, decrypted and restored into a throwaway database on the runner.
4. *Deploy to Hostinger* (`staging`) of the release commit → smoke and full page review on staging.
5. *Bootstrap Hostinger target* (`production`, `app_url` = `https://studymedicineuknigeria.com`, `link_docroot=false`).
6. *Deploy to Hostinger* (`production`, `cutover_docroot` = the folder from step 2) → database backup, migrations,
   reference sync, release switch, then the folder is archived to `~/backups`, moved aside (never deleted) and
   linked to the release; the production smoke test runs (HTTPS, canonical host, no noindex, HSTS, CSP, no exposed
   files, no debug output, no staging password); **if it fails, the old site is put back automatically**. The production
   page review follows (every sitemap page indexable with its own https canonical; CSP; accessibility).
7. First admin: you register at the live site, confirm your email, then *Grant account role* (`production`, your
   email, `admin`, `verify_email=false`); your first admin sign-in sets up the authenticator app.
8. *Backup Hostinger* (`production`, `full`) → first backup of the live database, `.env` and documents, restore-tested.
9. Payment check, only with your agreement: you pay the smallest service (T1, £125) with your own card, we confirm
   the webhook marks it paid and the amounts match, and you refund it in the Stripe Dashboard (the refund then shows
   in the admin). Stripe keeps its processing fee on a refunded payment. Without this, the first real student
   payment is the first live one.

To undo: *Roll back Hostinger release* (`production`) returns to the previous release (code only; the database is
never restored automatically), or with `restore_previous_site=true` serves the old site again exactly as it was.
