# 21. Deployment and rollback (Hostinger)

Status: PROCEDURE. Written before server access existed; §21.1 must be completed from the inspection report (`ops/inspect-hostinger.sh`) before the first deployment.

## 21.1 Facts to confirm on the server (blocking)

| Item | Why it matters | Where recorded |
|---|---|---|
| Hostinger plan type (shared Business / Cloud / VPS) and whether SSH, cron and MySQL are available | decides queue worker, scanner, backup method | `ops/reports/inspect-<date>.txt` |
| PHP CLI and web versions (need ≥ 8.3 for Laravel 13) | hard requirement | report §3 |
| Document root path (`~/domains/studymedicineuknigeria.com/public_html` typical) | Laravel must serve from `public/` only | report §4 |
| Existing site contents, CMS, database | backup scope and redirect map | report §2, §5, §6 |
| Existing SSL (Hostinger free Let's Encrypt) and forced HTTPS | security headers | report §11 |

## 21.2 Layout on the server (release directories + symlink)

```
~/apps/smukn/
  releases/2026-10-03T10-00-00/     ← full checkout + vendor + built assets
  releases/…
  shared/.env                       ← never in git, never in a release dir
  shared/storage/                   ← logs, cache, private documents (encrypted at rest where supported)
  shared/database/database.sqlite   ← only if MySQL is unavailable (not recommended for production)
  current -> releases/2026-10-03T10-00-00
~/domains/studymedicineuknigeria.com/public_html -> ~/apps/smukn/current/public   (symlink; if Hostinger forbids a symlinked docroot, public_html contains only a stub index.php + .htaccess that requires ../apps/smukn/current/public/index.php)
```

Rollback = repoint `current` to the previous release and clear caches. Takes seconds and keeps the old release intact.

## 21.3 First deployment, step by step

1. **Backup existing site**: `tar czf ~/backups/public_html-<date>.tgz ~/domains/*/public_html` and `mysqldump` of any existing database to `~/backups/`. Download a copy off-server. Record checksums in `ops/reports/`.
2. **Build artefact** (CI or local): `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`. Upload with `rsync -az --delete --exclude .env --exclude storage` into a new `releases/<timestamp>/`.
3. **Shared links**: symlink `releases/<ts>/storage` → `shared/storage`; copy `.env.production.example` → `shared/.env` and fill secrets (APP_KEY, DB, MAIL, STRIPE, SITE_*); symlink `.env`.
4. **Database**: create MySQL database and user in hPanel; `php artisan migrate --force`; `php artisan smukn:reference-sync` (medical schools, service tiers and checklist rules, topic facts, the healthcare taxonomy and allied course facts, all as VERIFY-ON-PAGE; `ops/deploy.sh` runs both on every deploy, and the sync never touches a reviewed fact or an owner-set price).
5. **Caches**: `php artisan config:cache route:cache view:cache event:cache`; `php artisan storage:link` is **not** used (no public document URLs).
6. **Cron** (hPanel → Cron Jobs): `* * * * * cd ~/apps/smukn/current && php artisan schedule:run >> /dev/null 2>&1`. The scheduler runs `queue:work --stop-when-empty` every minute on shared hosting.
7. **Switch**: `ln -sfn releases/<ts> current`. Verify `https://studymedicineuknigeria.com/up` returns 200.
8. **Smoke tests** (from `ops/smoke.sh`): home 200, `/robots.txt`, `/sitemap.xml`, a `/Fees/` → `/fees` 301, `/no-such-page` 404 page, favicon, `X-Robots-Tag` on `/login`, HTTPS redirect, HSTS header, no directory listing on `/storage`.
9. **Search Console**: submit sitemap only once `APP_ENV=production` (robots serves `Disallow: /` in any other environment).

## 21.4 Rollback

- **Automatic:** `ops/deploy.sh` repoints `current` to the previous release whenever the smoke test fails.
- **Manual:** Actions → *Roll back Hostinger release* → target (`ops/rollback.sh`). It moves `current` to the last
  release that actually went live (`releases/.history`; a deploy that failed before its switch is never a candidate),
  rebuilds caches, smoke-tests, and names the database backup taken before the abandoned release. Production runs in
  the `production` environment, so the required reviewer approves a rollback too.
- **Database:** never restored automatically. Restore deliberately from `~/backups/<target>-db-<release>.sql.gz` or an
  off-site artifact (`ops/RESTORE.md`).
Database migrations are written to be backward-compatible for one release (add-only; destructive changes land one release after the code stops using the column).

## 21.5 Backups (ongoing)

- Implemented in `ops/backup.sh` + `.github/workflows/backup-hostinger.yml`: daily `mysqldump` (02:50 UTC) and a weekly full copy (Sundays 03:20 UTC: dump + `shared/.env` + `storage/app/private`). Everything is encrypted **on the server** with AES-256-CBC (PBKDF2, 200k iterations) using the `BACKUP_PASSPHRASE` Actions secret before it is copied to the runner, checksum-verified and kept as a GitHub Actions artifact (db 14 days, full 28 days). £0; artifact storage quota is the limit and the workflow warns above 400 MB.
- Hostinger's own backups remain enabled as a second line.
- Restore procedure and quarterly restore test: `ops/RESTORE.md`; results go in `ops/reports/restore-test-<date>.md`.

## 21.6 Environment separation

`staging.studymedicineuknigeria.com` (subdomain, `APP_ENV=staging`, HTTP basic auth, robots disallow) receives every release first. Production only after smoke tests pass on staging.

## 21.7 Secrets

Only in `shared/.env` on the server and in the deployer's password manager. Never in git, logs, screenshots, chat or `ops/reports/`.

## 21.8 Deployment safeguards (reviewed 2026-10-04)

- **Production only after staging, for the same commit.** `deploy-hostinger.yml` refuses a production dispatch unless GitHub records a successful `staging` deployment of the exact commit being deployed. Recommended owner setting on top: Settings → Environments → `production` → Required reviewers (yourself), so every production run waits for an explicit approval click.
- **No migration without a verified backup.** `ops/deploy.sh` stops before `migrate` if `DB_DATABASE` is missing, `mysqldump` is unavailable, the dump fails, or the gzip file is corrupt or empty.
- **Reference data on every deploy.** `smukn:reference-sync` runs after migrations (never demo accounts; never touches reviewed facts or owner prices).
- **Smoke test covers content.** Besides status codes and headers it requires the directory to list at least 20 schools and the fee guide and eligibility check to render; any failure rolls the symlink back to the previous release.
- **Production needs the owner's approval rule.** A production deploy stops unless the `production` environment has a required reviewer, so it can never start unapproved.
- **Backups do not wait for approval.** The backup workflow uses no deployment environment; its key and passphrase are repository secrets.
- **Secrets never on a command line.** Bootstrap sends values on stdin; `.env` values are single-quoted (literal); a value with `'` or a line break is refused. Scripts read `.env` with phpdotenv (`ops/env-shell.php`), and MySQL tools get the password through `MYSQL_PWD`.
- **Releases carry no local state.** No development SQLite database, no `public/hot`, no dev dependencies (`composer install --no-dev`).
- **Smoke test fails on exposure or debug output.** `/.env`, `/composer.json`, `/artisan`, logs, `.git` and `vendor` must not be served (a document root on the release instead of `public/`); exception text fails it; staging must answer 401 without credentials and disallow crawling; production must be crawlable.
- **Strict host keys once pinned.** With `HOSTINGER_SSH_KNOWN_HOSTS` set, every workflow uses `StrictHostKeyChecking=yes`; the inspection prints the scanned fingerprints to compare first.
- **MySQL in CI.** The suite also runs on MySQL 8, and CI proves the smoke test fails on an empty application.
- **First admin on a new server.** Register on the site, then the *Grant account role* workflow (`smukn:grant-role`) sets the role; staff and admins must enrol an authenticator at their next sign-in. Demoting the last admin is refused.
- **Mail never breaks sign-up.** Verification and reset emails go through the queue (retried); without a mail password the bootstrap writes `MAIL_MAILER=log`.
- **Every staging response is noindex** (header), on top of the password gate and `robots.txt`.
- **After each staging deploy** *Review staging* checks every page on desktop and mobile from a GitHub runner (status, CSP, axe, H1, noindex); report kept as an artifact.
- **Backups are restore-tested.** Each backup run decrypts the copy on the runner and restores it into a throwaway MariaDB, checking the key tables.
- Rehearsed end to end on MariaDB: `ops/reports/deployment-rehearsal-2026-10-04.md`.
- **No unsmoked staging deploy.** A staging dispatch fails at its first step unless `STAGING_URL` is set (with SSH host, user and key), because `ops/deploy.sh` skips the smoke test when it has no URL, and a staging deploy that was never smoke-tested must not count as the success that unlocks production.
- **Pinned host key (optional, recommended).** Set the repository variable `HOSTINGER_SSH_KNOWN_HOSTS` to the server's line(s) from `ssh-keyscan -p 65002 <host>` (checked against hPanel's SSH fingerprint) and every workflow uses it instead of trusting the key on first connection.
