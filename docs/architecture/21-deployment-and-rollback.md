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
4. **Database**: create MySQL database and user in hPanel; `php artisan migrate --force`; `php artisan db:seed --class=ReferenceDataSeeder` (imports `data/*.json` as VERIFY-ON-PAGE records).
5. **Caches**: `php artisan config:cache route:cache view:cache event:cache`; `php artisan storage:link` is **not** used (no public document URLs).
6. **Cron** (hPanel → Cron Jobs): `* * * * * cd ~/apps/smukn/current && php artisan schedule:run >> /dev/null 2>&1`. The scheduler runs `queue:work --stop-when-empty` every minute on shared hosting.
7. **Switch**: `ln -sfn releases/<ts> current`. Verify `https://studymedicineuknigeria.com/up` returns 200.
8. **Smoke tests** (from `ops/smoke.sh`): home 200, `/robots.txt`, `/sitemap.xml`, a `/Fees/` → `/fees` 301, `/no-such-page` 404 page, favicon, `X-Robots-Tag` on `/login`, HTTPS redirect, HSTS header, no directory listing on `/storage`.
9. **Search Console**: submit sitemap only once `APP_ENV=production` (robots serves `Disallow: /` in any other environment).

## 21.4 Rollback

```
ln -sfn releases/<previous> current && cd current && php artisan config:cache route:cache view:cache
```
Database migrations are written to be backward-compatible for one release (add-only; destructive changes land one release after the code stops using the column).

## 21.5 Backups (ongoing)

- Implemented in `ops/backup.sh` + `.github/workflows/backup-hostinger.yml`: daily `mysqldump` (02:50 UTC) and a weekly full copy (Sundays 03:20 UTC: dump + `shared/.env` + `storage/app/private`). Everything is encrypted **on the server** with AES-256-CBC (PBKDF2, 200k iterations) using the `BACKUP_PASSPHRASE` Actions secret before it is copied to the runner, checksum-verified and kept as a GitHub Actions artifact (db 14 days, full 28 days). £0; artifact storage quota is the limit and the workflow warns above 400 MB.
- Hostinger's own backups remain enabled as a second line.
- Restore procedure and quarterly restore test: `ops/RESTORE.md`; results go in `ops/reports/restore-test-<date>.md`.

## 21.6 Environment separation

`staging.studymedicineuknigeria.com` (subdomain, `APP_ENV=staging`, HTTP basic auth, robots disallow) receives every release first. Production only after smoke tests pass on staging.

## 21.7 Secrets

Only in `shared/.env` on the server and in the deployer's password manager. Never in git, logs, screenshots, chat or `ops/reports/`.
