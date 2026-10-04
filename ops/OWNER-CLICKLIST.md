# Owner click-list: production launch without staging (one sitting, about 15 minutes)

Owner decision 2026-10-04: no staging site; launch straight to https://studymedicineuknigeria.com. Everything here is
typed by you into hPanel or GitHub. Never paste a key or password into chat, an issue or a file. Nothing in A or B
touches the live site. The full reference for each setting is `ops/STAGING-SETTINGS.md` (its staging rows are no
longer needed).

## A. hPanel (Hostinger), in your browser

1. **A key pair for GitHub Actions**, made on your own computer (not the Claude key):
   `ssh-keygen -t ed25519 -N "" -C SMUKN-GitHub-Actions -f smukn_deploy` creates `smukn_deploy` (private) and
   `smukn_deploy.pub` (public).
2. **Authorise it**: Websites → Manage (studymedicineuknigeria.com) → Advanced → **SSH Access** → SSH keys → Add SSH
   key: name `SMUKN-GitHub-Actions`, paste the contents of `smukn_deploy.pub`. Leave the existing
   `Claude–StudyMedicineUKNigeria` key exactly as it is. On the same page, note **IP/host**, **port** and **username**.
3. **PHP 8.3 for the website**: Websites → Manage → Advanced → **PHP Configuration** → PHP version **8.3** → Save.
   The domain runs PHP 8.2.33 today and the new site needs 8.3. The launch checks this first and stops before changing
   anything if the domain is still on 8.2.
4. **One new MySQL database**: Databases → **Management** → create a database and user for the new site, with a long
   generated password **without a single quote `'`**. Do not reuse or touch the current site's database. Note the
   database name, user and password.
5. **Mailbox**: Emails → `info@studymedicineuknigeria.com` → note its password (email already works; nothing to set up).

## B. GitHub → bigbossua/studymedicineuknigeria → Settings

1. **Secrets and variables → Actions → Secrets** (repository):
   - `HOSTINGER_SSH_KEY` = the whole contents of `smukn_deploy`, including the BEGIN/END lines;
   - `BACKUP_PASSPHRASE` = a new long passphrase (keep a copy in your password manager: backups cannot be opened
     without it).
2. **Secrets and variables → Actions → Variables** (repository): `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`,
   `HOSTINGER_SSH_USER` (from A2).
3. **Environments → New environment `production`**: tick **Required reviewers** and add yourself (Save protection
   rules). Then add these Environment secrets:
   - `SMUKN_DB_DATABASE`, `SMUKN_DB_USERNAME`, `SMUKN_DB_PASSWORD` (from A4);
   - `SMUKN_MAIL_PASSWORD` (from A5).

## C. Then Claude runs **Launch production (no staging)** (Actions, confirm = `LAUNCH`)

1. **Rehearse.** The full test suite runs, then this exact commit is served in production mode on the runner under the
   real domain name. It runs:
   - the smoke test;
   - foreign hosts refused;
   - www → apex 301;
   - all 65 old-site URLs redirected;
   - no debug output;
   - no fee on any public page;
   - registration and payment closed.

   The domain's PHP version and public DNS/HTTPS are also checked.
2. **Back up.** A read-only inspection finds the folder the domain serves. Then an encrypted backup of the current site
   and its database is made, downloaded, checksum-verified, decrypted and restored into a throwaway database. The
   artifact is kept for 90 days.
3. **You approve.** Actions → the waiting run → **Review deployments** → production → Approve. This is the only click
   during the launch.
4. **Launch.**
   - The server is prepared, with registration and payment closed and no Stripe key.
   - Deploy runs the preflight: debug off, live URL, real email, unverified facts hidden.
   - The current site folder is archived and moved aside, never deleted, and the new site takes its place.
   - The production smoke test runs. **Any failure puts the old site back automatically.**
5. **Test email and registration.** A test email goes out from info@. If it is accepted and email DNS is ready,
   student registration opens.
6. **Check.**
   - Every page is reviewed on desktop and mobile in production mode: status, CSP, accessibility, canonical,
     indexable.
   - Public DNS and HTTPS are checked again.
   - Claude then checks the live site page by page and reports.
7. **You flush the CDN cache once:** hPanel → Websites → CDN → Flush cache.

Payment stays closed until you provide live Stripe keys after launch. Approved students see their three fees with
"Payment is not open yet." and no payment button or bank-transfer option.
