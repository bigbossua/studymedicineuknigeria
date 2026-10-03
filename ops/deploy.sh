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
rsync -az --delete -e "$SSH" --exclude .env --exclude storage --exclude node_modules --exclude tests --exclude .git --exclude .github ./ "$HOST:$REL/"

echo "== linking shared, backing up DB, migrating, caching =="
$SSH "$HOST" bash -s <<REMOTE
set -euo pipefail
cd $REL
rm -rf storage && ln -s $APP/shared/storage storage && ln -s $APP/shared/.env .env
PHP=\$(command -v php83 || command -v php8.3 || command -v php)
DB=\$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'); DU=\$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'); DP=\$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'); DH=\$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"')
if [ -n "\$DB" ] && command -v mysqldump >/dev/null; then mysqldump -h"\${DH:-127.0.0.1}" -u"\$DU" -p"\$DP" "\$DB" 2>/dev/null | gzip > ~/backups/$TARGET-db-$TS.sql.gz && echo "db backup: ~/backups/$TARGET-db-$TS.sql.gz"; fi
\$PHP artisan migrate --force
\$PHP artisan config:cache && \$PHP artisan route:cache && \$PHP artisan view:cache && \$PHP artisan event:cache
PREV=\$(readlink $APP/current 2>/dev/null || true)
ln -sfn $REL $APP/current
echo "previous=\$PREV" > $APP/releases/.last_switch
ls -1dt $APP/releases/*/ | tail -n +6 | xargs -r rm -rf
echo "switched $APP/current -> $REL"
REMOTE

if [ -n "$SITE_URL" ]; then
  echo "== smoke $SITE_URL =="
  if ! ops/smoke.sh "$SITE_URL"; then
    echo "SMOKE FAILED — rolling back"
    $SSH "$HOST" "PREV=\$(sed 's/previous=//' $APP/releases/.last_switch); if [ -n \"\$PREV\" ]; then ln -sfn \$PREV $APP/current && cd $APP/current && php artisan config:cache && php artisan route:cache && php artisan view:cache && echo rolled back to \$PREV; fi"
    exit 1
  fi
fi
echo "deployed $TARGET $TS"
