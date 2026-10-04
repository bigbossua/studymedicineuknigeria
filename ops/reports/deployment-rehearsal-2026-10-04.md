# Deployment lifecycle rehearsal on MySQL — 2026-10-04 (local SSH server, no Hostinger access)

Purpose: run the exact staging path the owner will unlock (bootstrap → backup → deploy → smoke → rollback →
off-site backup → restore) against **MariaDB 10.11**, which the 2026-10-03 rehearsal could not (SQLite only,
no `mysqldump`). Method: `sshd` on 127.0.0.1:2222 with a throwaway key (removed afterwards), HOME=/root standing in
for the Hostinger account, a PHP router standing in for LiteSpeed with the document root on `current/public`.
Secrets were deliberately hostile: database password `Pa$$w0rd"\x`#!`, staging password `s"t$a\g#e`.
Everything created was removed afterwards (key, ~/apps, ~/backups, databases).

| Step | Result |
|---|---|
| Bootstrap, staging without basic-auth credentials | refused (exit 4): staging must never be public |
| Bootstrap, password containing `'` | refused (exit 4) before anything was written |
| Bootstrap, real values over stdin (the workflow's new pattern) | `shared/.env` mode 600, values single-quoted and read literally by phpdotenv; database reachable via `MYSQL_PWD`; no secret in the server's process list |
| Deploy 1 (MySQL) | **failed in reference sync** — see defect 1; nothing switched, site untouched |
| Full test suite on MariaDB | **15 failures** — see defects 1–4; all fixed, 132/132 then pass on SQLite and MariaDB |
| Deploy 2 (no smoke URL) | verified dump (`Dump completed` trailer), migrate, reference sync, caches, switch |
| Deploy 3 (smoke URL, staging gate) | all checks pass: 401 without credentials, 200 with the special-character password, 53 schools, CSP, noindex, robots `Disallow: /`, no exposed files |
| Deploy 4 (smoke against a document root on the release folder, not `public/`) | smoke FAILED (exposed `/.env`, `/composer.json`, `/artisan`, `/database/database.sqlite` …) → automatic rollback to deploy 3 |
| Deploy 5 (`composer install --no-dev`, as CI now builds) | passes; release contains no `vendor/phpunit`, no `database/*.sqlite`, no `public/hot` |
| Deploy 6 (smoke on a closed port) | automatic rollback to deploy 5; history records the restore |
| Manual rollback (`ops/rollback.sh`) ×2 | 5 → 3 with the matching pre-deploy backup named, smoke passes; second refuses: "no earlier live release" |
| Off-site backup (`SCOPE=full`, passphrase on stdin) | checksum OK, decrypts; dump restored into an empty database: 55 universities, 701 facts |
| Host-key pinning (`StrictHostKeyChecking=yes`) | correct key connects; a different key is refused |

## Defects found and fixed

1. **MySQL column limits** (SQLite ignores them): a dataset UCAS code with notes (`A100 (also A104 …)`) overflowed
   `courses.ucas_code`; a fee year `UNCLEAR (likely 2025/26 or 2026/27)` overflowed `reference_facts.academic_year`;
   healthcare research prose overflowed `professions.*` varchar columns; the demo seeder wrote `Nigeria` into the
   two-letter `users.country`. The first real deploy would have stopped in `smukn:reference-sync`. Fixed: bare code
   on the course row (full text stays in the `ucas_code` fact), unclear years kept word for word in the fact's notes
   with no year guessed, profession columns widened (migration `2026_10_04_000040`), `NG` in the seeder.
2. **Decimal values as strings on MySQL** (`45000.00`): Course schema `price` differed by database. Fixed with a
   `float` cast on `value_number`.
3. **Bootstrap secrets on the remote command line**: visible in the server's process list and broken by any quote.
   Now sent on stdin as bash-quoted exports; `.env` values are single-quoted (literal in phpdotenv) and a value with
   `'` or a line break is refused. The old double-quoted `.env` would have made any password containing `"` or `\`
   crash the whole application (phpdotenv parse error) and silently altered one containing `$`.
4. **Pre-migrate dump without `--no-tablespaces`**: shared-hosting MySQL users lack the PROCESS privilege, so the
   mandatory backup would have failed and blocked every deploy. Added; the dump is now verified by its
   `Dump completed` trailer, and `.env` is read with phpdotenv (`ops/env-shell.php`) instead of grep.
5. **Local state shipped in every release**: rsync copied `database/database.sqlite` (the development database with
   demo accounts) and would copy `public/hot`; the deploy workflow shipped dev dependencies. Excluded; CI deploys
   build with `composer install --no-dev`.
6. **Rollback could target a release that never went live** (a deploy that failed before the switch leaves its
   directory). `releases/.history` now records only switched releases, including automatic restores.
7. **Scheduled backups would wait for production approval** once the owner adds a required reviewer (the job ran in
   the `production` environment). Backups no longer use a deployment environment.
8. **A staging deploy could run without the gate or a smoke URL** and still unlock production. The workflow now
   requires `STAGING_URL`, `STAGING_BASIC_USER` and `STAGING_BASIC_PASSWORD` for staging, and the smoke test fails
   unless staging answers 401 to anonymous requests.

Regression tests: `tests/Feature/DeploymentSafetyTest.php`; CI now also runs the suite on MySQL 8 and proves the
smoke test fails on an empty application and passes on a synced one.

Not exercised here: Hostinger's real PHP binary names, LiteSpeed `.htaccess`, DNS/TLS, the hPanel cron, and MySQL 8
on Hostinger (CI covers MySQL 8 for the application). The first real run still starts with the read-only inspection.
