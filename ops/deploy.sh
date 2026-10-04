#!/usr/bin/env bash
# Release-directory deployment for Hostinger (docs/architecture/21).
# Usage: ops/deploy.sh user@host port staging|production [site_url]
# Env: SSH_OPTS (extra ssh options, e.g. identity file). Run from a checkout that has vendor/ and public/build/ already built
# (the GitHub Actions workflow builds and tests first), or build locally before calling.
# Server layout: ~/apps/smukn-<target>/{releases,shared,current}. shared/.env must exist (created once from .env.production.example).
set -euo pipefail
HOST=${1:?user@host}; PORT=${2:?ssh port}; TARGET=${3:?staging|production}; SITE_URL=${4:-}
case "$TARGET" in staging|production) ;; *) echo "target must be staging or production"; exit 1;; esac
SSH="ssh -p $PORT ${SSH_OPTS:-}"
APP="~/apps/smukn-$TARGET"; TS=$(date -u +%Y-%m-%dT%H-%M-%S); REL="$APP/releases/$TS"
[ -d vendor ] && [ -d public/build ] || { echo "vendor/ or public/build/ missing: build first"; exit 1; }

echo "== $TARGET: creating $REL =="
$SSH "$HOST" "mkdir -p $REL $APP/shared/storage/app/private $APP/shared/storage/framework/{cache,sessions,views} $APP/shared/storage/logs ~/backups && test -f $APP/shared/.env || { echo 'shared/.env missing on server: create it from .env.production.example first'; exit 2; }"
# Never ship local state: the development SQLite database (demo accounts), a Vite dev-server marker (public/hot
# would point every asset at localhost), environment files, tests and editor/CI configuration.
rsync -az --delete -e "$SSH" --exclude .env --exclude '.env.*' --exclude storage --exclude node_modules --exclude tests --exclude .git --exclude .github \
  --exclude .devcontainer --exclude 'database/*.sqlite*' --exclude public/hot --exclude 'ops/qa/*.json' ./ "$HOST:$REL/"

echo "== linking shared, backing up DB, migrating, caching =="
$SSH "$HOST" bash -s <<REMOTE
set -euo pipefail
cd $REL
rm -rf storage && ln -s $APP/shared/storage storage && ln -s $APP/shared/.env .env
PHP=\$(command -v php83 || command -v php8.3 || command -v php)
# .env is read with phpdotenv (ops/env-shell.php), exactly as Laravel reads it.
ENVSH=\$(\$PHP ops/env-shell.php DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD) || { echo "shared/.env unreadable: refusing to migrate"; exit 3; }; eval "\$ENVSH"
# Never migrate without a verified backup: no database name, no mysqldump, a failed dump or an empty file stops here.
BK=~/backups/$TARGET-db-$TS.sql.gz
if [ "\${DB_CONNECTION:-mysql}" = sqlite ]; then
  F="\${DB_DATABASE:-database/database.sqlite}"; [ -f "\$F" ] || touch "\$F"
  BK=~/backups/$TARGET-db-$TS.sqlite.gz; gzip -c "\$F" > "\$BK" || { echo "database backup failed: refusing to migrate"; exit 3; }
  gzip -t "\$BK" || { echo "database backup is corrupt: refusing to migrate"; exit 3; }
else
  [ -n "\$DB_DATABASE" ] || { echo "DB_DATABASE missing in shared/.env: refusing to migrate without a backup"; exit 3; }
  command -v mysqldump >/dev/null || { echo "mysqldump not found: refusing to migrate without a backup"; exit 3; }
  # --no-tablespaces: shared hosting accounts lack the PROCESS privilege that tablespace dumps need
  MYSQL_PWD="\$DB_PASSWORD" mysqldump --single-transaction --no-tablespaces -h"\${DB_HOST:-127.0.0.1}" -P"\${DB_PORT:-3306}" -u"\$DB_USERNAME" "\$DB_DATABASE" | gzip > "\$BK" || { echo "database backup failed: refusing to migrate"; exit 3; }
  gzip -t "\$BK" && gzip -dc "\$BK" | tail -1 | grep -q 'Dump completed' || { echo "database backup is incomplete or corrupt: refusing to migrate"; exit 3; }
fi
echo "db backup verified: \$BK (\$(du -h "\$BK" | cut -f1))"
\$PHP artisan migrate --force
\$PHP artisan smukn:reference-sync   # repository reference data; never touches reviewed facts or owner prices
for f in data/verification/decisions-*.csv; do [ -e "\$f" ] && \$PHP artisan smukn:facts-import "\$f"; done
\$PHP artisan config:cache && \$PHP artisan route:cache && \$PHP artisan view:cache && \$PHP artisan event:cache
PREV=\$(readlink $APP/current 2>/dev/null || true)
ln -sfn $REL $APP/current
echo "previous=\$PREV" > $APP/releases/.last_switch
echo "\$(cd $REL && pwd)" >> $APP/releases/.history   # releases that actually went live, oldest first (ops/rollback.sh)
ls -1dt $APP/releases/*/ | tail -n +6 | xargs -r rm -rf
echo "switched $APP/current -> $REL"
REMOTE

if [ -n "$SITE_URL" ]; then
  echo "== smoke $SITE_URL =="
  if ! ops/smoke.sh "$SITE_URL" "$TARGET"; then
    echo "SMOKE FAILED — rolling back"
    $SSH "$HOST" "PREV=\$(sed 's/previous=//' $APP/releases/.last_switch); PHP=\$(command -v php83 || command -v php8.3 || command -v php); if [ -n \"\$PREV\" ]; then ln -sfn \$PREV $APP/current && echo \$PREV >> $APP/releases/.history && cd $APP/current && \$PHP artisan config:cache && \$PHP artisan route:cache && \$PHP artisan view:cache && echo rolled back to \$PREV; else echo 'no previous release to roll back to'; fi"
    exit 1
  fi
fi
echo "deployed $TARGET $TS"
