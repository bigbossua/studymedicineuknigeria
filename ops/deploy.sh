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
PHP=""; for c in php83 php8.3 /opt/alt/php83/usr/bin/php php; do p=\$(command -v "\$c" 2>/dev/null) && "\$p" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && { PHP=\$p; break; }; done
[ -n "\$PHP" ] || { echo "no PHP 8.3+ command-line binary found (tried php83, php8.3, /opt/alt/php83/usr/bin/php, php)"; exit 3; }
# .env is read with phpdotenv (ops/env-shell.php), exactly as Laravel reads it.
ENVSH=\$(\$PHP ops/env-shell.php DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD) || { echo "shared/.env unreadable: refusing to migrate"; exit 3; }; eval "\$ENVSH"
# Preflight: the server's settings must fit the target before anything changes (values are never printed).
eval "\$(\$PHP ops/env-shell.php APP_ENV APP_DEBUG APP_URL MAIL_MAILER MAIL_PASSWORD STRIPE_KEY STRIPE_SECRET STRIPE_WEBHOOK_SECRET SITE_PUBLISH_UNVERIFIED)"
pf=""
[ "\$APP_ENV" = "$TARGET" ] || pf="\$pf APP_ENV must be $TARGET;"
case "\$APP_DEBUG" in false|0|"") ;; *) pf="\$pf APP_DEBUG must be false;";; esac
case "\$SITE_PUBLISH_UNVERIFIED" in true|1) pf="\$pf SITE_PUBLISH_UNVERIFIED must not be true (unverified facts stay hidden);";; esac
live=0; case "\$STRIPE_SECRET" in sk_live_*|rk_live_*) live=1;; esac
if [ "$TARGET" = production ]; then
  [ "\$APP_URL" = https://studymedicineuknigeria.com ] || pf="\$pf APP_URL must be https://studymedicineuknigeria.com;"
  case "\$MAIL_MAILER" in smtp|ses|postmark|resend|mailgun|sendmail) ;; *) pf="\$pf MAIL_MAILER must send real email (not '\$MAIL_MAILER');";; esac
  [ "\$MAIL_MAILER" != smtp ] || [ -n "\$MAIL_PASSWORD" ] || pf="\$pf MAIL_PASSWORD is empty;"
  if [ -n "\$STRIPE_SECRET" ]; then
    [ "\$live" = 1 ] || pf="\$pf STRIPE_SECRET is not a live key;"
    case "\$STRIPE_KEY" in pk_live_*) ;; *) pf="\$pf STRIPE_KEY is not a live publishable key;";; esac
    [ -n "\$STRIPE_WEBHOOK_SECRET" ] || pf="\$pf STRIPE_WEBHOOK_SECRET is empty;"
  else
    echo "warn: no Stripe keys yet: card payments stay switched off (bank transfer only) until Update server settings writes live keys"
  fi
else
  [ "\$live" = 0 ] || pf="\$pf STRIPE_SECRET is a live key on $TARGET;"
fi
[ -z "\$pf" ] || { echo "preflight failed for $TARGET:\$pf fix shared/.env (Update server settings) before deploying"; exit 3; }
echo "preflight passed for $TARGET"
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
# Stripe products and one-time prices for the service fees (idempotent; a failure here only delays it to the first checkout)
if [ -n "\$STRIPE_SECRET" ]; then \$PHP artisan smukn:stripe-sync || echo "warn: Stripe catalogue not synced now; the first checkout creates or finds it"; fi
for f in data/verification/decisions-*.csv; do [ -e "\$f" ] && \$PHP artisan smukn:facts-import "\$f"; done
\$PHP artisan config:cache && \$PHP artisan route:cache && \$PHP artisan view:cache && \$PHP artisan event:cache
PREV=\$(readlink $APP/current 2>/dev/null || true)
ln -sfn $REL $APP/current
echo "previous=\$PREV" > $APP/releases/.last_switch
echo "\$(cd $REL && pwd)" >> $APP/releases/.history   # releases that actually went live, oldest first (ops/rollback.sh)
ls -1dt $APP/releases/*/ | tail -n +6 | xargs -r rm -rf
echo "switched $APP/current -> $REL"
REMOTE

# Document root cutover (first production launch): only when CUTOVER_DOCROOT names the folder the domain serves.
# Hostinger refuses to serve a document root through a symbolic link, so the folder stays a real folder: the existing
# site is archived to ~/backups and moved aside (never deleted), a new empty folder takes its place and the release's
# public files are published into it (ops/publish-docroot.sh). A failed publish or smoke test puts the old site back.
CUTOVER=0
if [ -n "${CUTOVER_DOCROOT:-}" ]; then
  echo "== document root cutover: $CUTOVER_DOCROOT =="
  out=$({ printf 'D=%q; TARGET=%q; TS=%q\n' "$CUTOVER_DOCROOT" "$TARGET" "$TS"; cat <<'CUT'
set -euo pipefail
D="${D/#\~/$HOME}"; D="${D%/}"; APPD="$HOME/apps/smukn-$TARGET"
# "auto": the folder Hostinger serves for the domain, found rather than guessed; anything ambiguous stops here
if [ "$D" = auto ]; then
  D=""; for c in "$HOME/domains/studymedicineuknigeria.com/public_html"; do [ -e "$c" ] && { D="$c"; break; }; done   # only this domain's folder: the account hosts many websites
  [ -n "$D" ] || { echo "auto: no document root found (looked for ~/domains/studymedicineuknigeria.com/public_html; pass the folder from the inspection report instead)"; exit 3; }
  echo "auto: document root is $D"
fi
if [ -f "$D/.smukn-docroot" ]; then
  printf '%s\n' "$D" > "$APPD/releases/.docroot"; bash "$APPD/current/ops/publish-docroot.sh" "$D" "$APPD"
  echo "document root already serves the application"; exit 0
fi
[ -e "$D" ] || { echo "document root $D does not exist: check the path in the inspection report"; exit 3; }
[ ! -L "$D" ] || { echo "document root $D is a symbolic link, which this host does not serve: refusing"; exit 3; }
case "$D" in "$HOME"/*) ;; *) echo "document root $D is outside the home directory: refusing"; exit 3;; esac
tar czf "$HOME/backups/docroot-$TARGET-$TS.tgz" -C "$(dirname "$D")" "$(basename "$D")" && tar tzf "$HOME/backups/docroot-$TARGET-$TS.tgz" >/dev/null \
  || { echo "could not archive $D: refusing to switch"; exit 3; }
mv "$D" "$D.pre-smukn-$TS"
if ! { mkdir -m 755 "$D" && bash "$APPD/current/ops/publish-docroot.sh" "$D" "$APPD"; }; then
  [ -e "$D" ] && mv "$D" "$D.smukn-failed-$TS"; mv "$D.pre-smukn-$TS" "$D"
  echo "could not publish the release into $D: the previous site is in place again"; exit 3
fi
printf '%s|%s\n' "$D" "$D.pre-smukn-$TS" > "$APPD/releases/.cutover"
printf '%s\n' "$D" > "$APPD/releases/.docroot"
echo "document root $D now serves the release; previous site kept at $D.pre-smukn-$TS and ~/backups/docroot-$TARGET-$TS.tgz"
echo "cutover=done"
CUT
  } | $SSH "$HOST" bash -s) || { echo "$out"; echo "CUTOVER FAILED: the document root was not changed and the previous site is still served"; exit 1; }
  echo "$out"; if grep -q '^cutover=done$' <<<"$out"; then CUTOVER=1; fi
fi

# Every later release: the document root (a real folder since the cutover) gets this release's public files.
PUBLISHED=1
if [ "$CUTOVER" = 0 ]; then
  $SSH "$HOST" 'f=$HOME/apps/smukn-'"$TARGET"'/releases/.docroot; [ ! -f "$f" ] || bash "$HOME/apps/smukn-'"$TARGET"'/current/ops/publish-docroot.sh" "$(cat "$f")" "$HOME/apps/smukn-'"$TARGET"'"' || PUBLISHED=0
fi

if [ -n "$SITE_URL" ]; then
  echo "== smoke $SITE_URL =="
  if [ "$PUBLISHED" = 0 ] || ! ops/smoke.sh "$SITE_URL" "$TARGET"; then
    [ "$PUBLISHED" = 1 ] || echo "PUBLISH FAILED: the document root did not receive this release"
    echo "SMOKE FAILED — rolling back"
    if [ "$CUTOVER" = 1 ]; then
      # the site that was live before this launch is served again, exactly as it was; the new folder is moved aside
      # (kept for diagnosis, never deleted)
      $SSH "$HOST" 'R=$HOME/apps/smukn-'"$TARGET"'/releases; F=$R/.cutover; IFS="|" read -r D OLD < "$F"; [ -e "$OLD" ] && [ -f "$D/.smukn-docroot" ] && mv "$D" "$D.smukn-failed-'"$TS"'" && mv "$OLD" "$D" && mv "$F" "$F.reverted" && rm -f "$R/.docroot" && echo "previous site restored at $D"'
    fi
    $SSH "$HOST" "PREV=\$(sed 's/previous=//' $APP/releases/.last_switch); PHP=\"\"; for c in php83 php8.3 /opt/alt/php83/usr/bin/php php; do p=\$(command -v \"\$c\" 2>/dev/null) && \"\$p\" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && { PHP=\$p; break; }; done; [ -n \"\$PHP\" ] || { echo \"no PHP 8.3+ command-line binary found (tried php83, php8.3, /opt/alt/php83/usr/bin/php, php)\"; exit 3; }; if [ -n \"\$PREV\" ]; then ln -sfn \$PREV $APP/current && echo \$PREV >> $APP/releases/.history && cd $APP/current && \$PHP artisan config:cache && \$PHP artisan route:cache && \$PHP artisan view:cache && echo rolled back to \$PREV; f=$APP/releases/.docroot; [ ! -f \$f ] || bash $APP/current/ops/publish-docroot.sh \$(cat \$f) \$(cd $APP && pwd); else echo 'no previous release to roll back to'; fi"
    exit 1
  fi
fi
echo "deployed $TARGET $TS"
