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

Optional, later: `SMUKN_MAIL_PASSWORD` (mailbox `info@studymedicineuknigeria.com`), `SITE_WHATSAPP`, `SITE_LEGAL_NAME`,
Stripe test keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) in the `staging` environment. Staging works
without them; e-mail and payments stay disabled until they exist.

## 3. What happens next (Claude, no further owner input until the staging review)

1. *Diagnose Actions settings* → confirms every row above is PRESENT (values never printed).
2. *Inspect Hostinger (read-only)* → server report: PHP binary, MySQL, disk, existing sites, document roots, host key.
3. *Bootstrap Hostinger target* (`staging`, `link_docroot=false`) → `~/apps/smukn-staging/shared/.env` created on the
   server; nothing existing touched. The staging document root is linked only after the inspection shows where it is.
4. *Deploy to Hostinger* (`staging`) → tests, verified DB backup, migrate, reference sync, switch, smoke test,
   automatic rollback on any failure.
5. Owner reviews `https://staging.studymedicineuknigeria.com` (staging password prompt).
6. Production only after the owner's approval: backup → bootstrap production → deploy production (the required
   reviewer approves each run).
