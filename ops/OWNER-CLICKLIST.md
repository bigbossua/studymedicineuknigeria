# Owner click-list: Hostinger + GitHub (one sitting, about 20 minutes)

Everything here is typed by you into hPanel or GitHub. Never paste a key or password into chat, an issue or a file.
The full reference is `ops/STAGING-SETTINGS.md`; this is the shortest safe order. Nothing here touches the live site.

## A. hPanel (Hostinger), in your browser

1. **SSH key for GitHub Actions** — Websites → Manage (studymedicineuknigeria.com) → Advanced → **SSH Access** →
   SSH keys → Add SSH key: name `SMUKN-GitHub-Actions`, paste the **public** half (`smukn_deploy.pub`). Leave the
   existing `Claude–StudyMedicineUKNigeria` key exactly as it is. On the same page, note **IP/host**, **port** and
   **username** (you will type them into GitHub in B2).
2. **Staging subdomain** — Domains → **Subdomains** → create `staging` (→ `staging.studymedicineuknigeria.com`).
   Note the folder hPanel creates for it (usually `domains/staging.studymedicineuknigeria.com/public_html`).
3. **SSL for staging** — Security → **SSL** → install/enable for `staging.studymedicineuknigeria.com` (free SSL).
   It can take a few minutes to become active.
4. **Two new MySQL databases** — Databases → **Management** → create one for staging and one for production, each with
   its own user and a long generated password **without a single quote `'`**. Do not reuse or touch the current site's
   database. Note the three values of each (database name, user, password) for B3/B4.
5. **Mailbox** — Emails → confirm `info@studymedicineuknigeria.com` exists; note its password for B4 (reset it if
   unknown; this does not affect DNS, which is already correct).

## B. GitHub → bigbossua/studymedicineuknigeria → Settings

1. **Secrets and variables → Actions → Secrets** (repository): `HOSTINGER_SSH_KEY` = the **private** half
   (`smukn_deploy`, whole file including BEGIN/END lines); `BACKUP_PASSPHRASE` = a new long passphrase (also store it in
   your password manager); `STAGING_BASIC_PASSWORD` = any long password without `'`.
2. **Secrets and variables → Actions → Variables** (repository): `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`,
   `HOSTINGER_SSH_USER` (from A1); `STAGING_URL` = `https://staging.studymedicineuknigeria.com`;
   `STAGING_BASIC_USER` = any username.
3. **Environments → `staging` → Environment secrets**: `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`,
   `SMUKN_DB_PASSWORD` = the staging database from A4.
4. **Environments → `production`**: tick **Required reviewers** and add yourself (Save protection rules); then
   Environment secrets `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` = the production database from A4,
   and `SMUKN_MAIL_PASSWORD` = the mailbox password from A5.

## C. Then Claude runs, without further input until a production approval

Diagnose (values never shown) → read-only Inspect (which folder and database the current site uses; host key printed
for you to pin as `HOSTINGER_SSH_KNOWN_HOSTS`) → encrypted, restore-tested backup of the current site and its database →
staging bootstrap and deploy → smoke, SEO, security, accessibility and noindex review of staging → production bootstrap
(registration closed, payment closed) → **you approve** the production deploy in GitHub (Actions → the waiting run →
Review deployments) → cutover with automatic restore of the old site on any failure → you flush the CDN cache
(hPanel → Websites → CDN) → test email → registration opened → you register → admin role → full backup.
