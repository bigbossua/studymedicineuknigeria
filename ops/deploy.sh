#!/usr/bin/env bash
# Release-directory deployment for Hostinger (docs/architecture/21). Run FROM a machine that can reach the server.
# Usage: ops/deploy.sh user@host port [branch]
# Prereqs on server: PHP 8.3 CLI, composer, mysql, ~/apps/smukn/shared/.env filled, cron line installed (21.3 step 6).
set -euo pipefail
HOST=${1:?user@host}; PORT=${2:?ssh port}; BRANCH=${3:-main}
APP=~/apps/smukn; TS=$(date -u +%Y-%m-%dT%H-%M-%S); REL="$APP/releases/$TS"
echo "== building artefact locally ($BRANCH) =="
TMP=$(mktemp -d); git archive --format=tar "$BRANCH" | tar -x -C "$TMP"
( cd "$TMP" && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist -q && npm ci --no-audit --no-fund >/dev/null && npm run build >/dev/null )
rm -rf "$TMP/node_modules" "$TMP/tests" "$TMP/.github"
echo "== uploading to $REL =="
ssh -p "$PORT" "$HOST" "mkdir -p $REL $APP/shared/storage/app/private $APP/shared/storage/framework/{cache,sessions,views} $APP/shared/storage/logs ~/backups"
rsync -az --delete -e "ssh -p $PORT" --exclude .env --exclude storage "$TMP/" "$HOST:$REL/"
echo "== linking shared, migrating, caching =="
ssh -p "$PORT" "$HOST" bash -s <<REMOTE
set -euo pipefail
cd $REL
rm -rf storage && ln -s $APP/shared/storage storage && ln -s $APP/shared/.env .env
test -f .env || { echo 'shared/.env missing'; exit 1; }
# backup DB before migrating
DB=\$(grep ^DB_DATABASE= .env | cut -d= -f2); DU=\$(grep ^DB_USERNAME= .env | cut -d= -f2); DP=\$(grep ^DB_PASSWORD= .env | cut -d= -f2)
[ -n "\$DB" ] && mysqldump -u"\$DU" -p"\$DP" "\$DB" | gzip > ~/backups/db-$TS.sql.gz || true
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan storage:unlink >/dev/null 2>&1 || true
PREV=\$(readlink $APP/current || true)
ln -sfn $REL $APP/current
echo "previous=\$PREV" > $APP/releases/.last_switch
# keep last 5 releases
ls -1dt $APP/releases/*/ | tail -n +6 | xargs -r rm -rf
REMOTE
rm -rf "$TMP"
echo "== smoke =="
ops/smoke.sh "https://studymedicineuknigeria.com" || { echo "SMOKE FAILED — rolling back"; ssh -p "$PORT" "$HOST" "PREV=\$(sed 's/previous=//' $APP/releases/.last_switch); [ -n \"\$PREV\" ] && ln -sfn \$PREV $APP/current && cd $APP/current && php artisan config:cache route:cache view:cache"; exit 1; }
echo "deployed $TS"
