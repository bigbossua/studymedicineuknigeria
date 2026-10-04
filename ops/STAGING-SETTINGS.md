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
