# Restoring from an encrypted backup

Backups are produced by `ops/backup.sh` (run by `.github/workflows/backup-hostinger.yml`) and stored as
GitHub Actions artifacts named `smukn-<target>-<scope>-<run id>`. Each contains `<name>.tar.enc` and
`<name>.tar.enc.sha256`. Nothing in the artifact is readable without the `BACKUP_PASSPHRASE` secret.

## 1. Download and verify

```bash
# GitHub → Actions → "Backup Hostinger" → run → Artifacts, or:
gh run download <run id> -n smukn-production-full-<run id> -D restore/
cd restore && sha256sum -c *.sha256
```

## 2. Decrypt

```bash
read -rs BACKUP_PASSPHRASE; export BACKUP_PASSPHRASE
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in smukn-production-full-*.tar.enc | tar -xf -
cat smukn-production-full-*/MANIFEST.txt
```

Contents: `database.sql.gz` (or `database.sqlite`), and for `full` scope `shared.env` and `private-storage.tgz`.

## 3. Restore on the server

Restores are a production-destructive change: take a fresh backup first (`SCOPE=full`) and announce downtime.

```bash
APP=~/apps/smukn-production
php $APP/current/artisan down --retry=60
# database (credentials from shared/.env)
gunzip -c database.sql.gz | mysql --defaults-extra-file=<(printf '[client]\nhost=%s\nuser=%s\npassword=%s\n' "$DB_HOST" "$DB_USERNAME" "$DB_PASSWORD") "$DB_DATABASE"
# documents (only if restoring storage; keeps nothing that is not in the backup)
tar -C $APP/shared/storage/app -xzf private-storage.tgz
# .env only when rebuilding a server from nothing; APP_KEY must match the one the documents were encrypted with
# cp shared.env $APP/shared/.env
php $APP/current/artisan migrate --force && php $APP/current/artisan config:cache
php $APP/current/artisan up
bash ops/smoke.sh https://studymedicineuknigeria.com
```

## 4. Quarterly restore test

Decrypt the latest `full` artifact into a scratch directory, import the dump into a throwaway database
(`CREATE DATABASE smukn_restore_test`), run `php artisan migrate:status` against it, open one encrypted
document with `Crypt::decrypt` under the backed-up APP_KEY, then drop the database. Record the date,
artifact name and result in `ops/reports/restore-test-<date>.md`.

## Retention and quota

Daily `db` artifacts are kept 14 days, weekly `full` artifacts 28 days. Artifacts count towards the
repository's Actions storage quota; the workflow warns when a file exceeds 400 MB. Hostinger's own
backups remain enabled as the second line. The server keeps 7 days of `db` and the 2 latest `full`
encrypted copies in `~/backups/offsite` as a staging area only.

## 5. The site that was live before the launch

- **Put it back in place** (it was moved aside, not deleted): Actions → *Roll back Hostinger release* → `production`,
  `restore_previous_site=true`. The SMUKN release and its database stay untouched for a relaunch.
- **From the encrypted pre-launch backup** (scope `site`): decrypt as in step 2; `site-files.tgz` holds the document
  root exactly as it was (`tar -xzf site-files.tgz -C ~/domains/studymedicineuknigeria.com/` after moving the published folder
  away) and `database.sql.gz`, if present, the WordPress database (`gunzip -c database.sql.gz | mysql …` into the
  database named in its `wp-config.php`). The deploy also keeps `~/backups/docroot-production-<time>.tgz` on the server.
