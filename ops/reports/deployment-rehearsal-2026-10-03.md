# Deployment lifecycle rehearsal — 2026-10-03 (local SSH server, no Hostinger access)

Purpose: prove `ops/*.sh` and the workflows' SSH patterns work before they touch the real server.
Method: `openssh-server` started on 127.0.0.1:2222 inside the development container, the Claude
deployment key authorised, HOME=/root standing in for the Hostinger account; SQLite in place of
MySQL via the new `DB_CONNECTION` override. Everything created was removed afterwards.

| Step | Command | Result |
|---|---|---|
| Inspect (read-only) | `ssh … 'bash -s' < ops/inspect-hostinger.sh` | exit 0, 13 sections, 88 lines |
| Bootstrap staging | `TARGET=staging APP_URL=… DB_CONNECTION=sqlite … bash -s < ops/server-bootstrap.sh` | `shared/.env` created (mode 600, APP_KEY generated on the server, `APP_ENV=staging`, `STAGING_BASIC_*` written); cron unavailable in the container → clear warning with the exact line to add in hPanel |
| Deploy 1 (no smoke URL) | `ops/deploy.sh root@127.0.0.1 2222 staging` | release dir created, rsync, shared links, migrate, config/route/view/event cache, `current` switched |
| Serve | `php -S 127.0.0.1:8081` on `current/public` | `/` 401 without credentials, 200 with; `/up` 200 open; robots `Disallow: /` (non-production) |
| Deploy 2 (smoke) | `SMOKE_AUTH=preview:… ops/deploy.sh … http://127.0.0.1:8081` | all 9 smoke checks ok, deployed |
| Deploy 3 (forced failure) | smoke URL on a closed port | `SMOKE FAILED — rolling back` → `current` restored to deploy 2, site 200 again; 3 releases retained |
| Backup | passphrase-on-stdin pipe → `ops/backup.sh` (`SCOPE=full`) | 320 KB `.tar.enc` + `.sha256`; fetched with scp, checksum OK, decrypts to database + shared.env + private storage + manifest |

Defects found and fixed during the rehearsal:
1. `server-bootstrap.sh` aborted when `crontab` was absent (`set -e` on the pipeline) → guarded, prints the cron line to add manually.
2. `server-bootstrap.sh` had no way to run against anything but MySQL → `DB_CONNECTION` override (default `mysql`).
3. `deploy.sh` rollback used a bare `php` instead of the detected PHP 8.3 binary and was silent when no previous release existed → fixed.

Not exercised here: MySQL dump (no `mysqldump` in the container; the code path is skipped with a message), Hostinger's PHP binary names (`php83`), LiteSpeed `.htaccess` behaviour, real DNS/TLS. The first real run therefore still starts with the read-only inspection workflow.
