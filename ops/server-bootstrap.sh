#!/usr/bin/env bash
# One-time, idempotent preparation of a target (staging|production) on Hostinger. Run over SSH by the
# bootstrap workflow AFTER the read-only inspection has been reviewed. It never touches public_html or any
# existing site unless LINK_DOCROOT=1 is passed explicitly and the current docroot is backed up first.
#
# Inputs (environment): TARGET, APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_HOST (default 127.0.0.1),
#                       MAIL_PASSWORD (optional), STRIPE_* (optional), SITE_* (optional), LINK_DOCROOT (0|1), DOCROOT (path)
set -euo pipefail
: "${TARGET:?staging|production}"; : "${APP_URL:?}"; : "${DB_DATABASE:?}"; : "${DB_USERNAME:?}"; : "${DB_PASSWORD:?}"
# Values are written to .env single-quoted, which phpdotenv reads literally ($, ", \, ` and # are safe);
# a single quote or a line break cannot be represented, so refuse them before anything is written.
for v in DB_DATABASE DB_USERNAME DB_PASSWORD DB_HOST MAIL_PASSWORD STRIPE_KEY STRIPE_SECRET STRIPE_WEBHOOK_SECRET SITE_WHATSAPP SITE_LEGAL_NAME STAGING_BASIC_USER STAGING_BASIC_PASSWORD APP_URL; do
  case "${!v:-}" in *"'"*|*$'\n'*|*$'\r'*) echo "$v contains a single quote or a line break, which .env cannot hold safely: choose a value without them" >&2; exit 4;; esac
done
q(){ printf "'%s'" "${1:-}"; }
# staging must sit behind StagingGate's basic auth so nobody (and no crawler) sees pre-release content
if [ "$TARGET" = staging ] && { [ -z "${STAGING_BASIC_USER:-}" ] || [ -z "${STAGING_BASIC_PASSWORD:-}" ]; }; then
  echo "staging needs STAGING_BASIC_USER (variable) and STAGING_BASIC_PASSWORD (secret) so the site is not public" >&2; exit 4
fi
DOCROOT="${DOCROOT:-}"; DOCROOT="${DOCROOT/#\~/$HOME}"   # a quoted ~ from the workflow input is not expanded by the shell
APP=~/apps/smukn-$TARGET; SHARED=$APP/shared
PHP=$(command -v php83 || command -v php8.3 || command -v php)
echo "== bootstrap $TARGET at $APP (php: $($PHP -r 'echo PHP_VERSION;')) =="
mkdir -p $APP/releases $SHARED/storage/app/private $SHARED/storage/framework/{cache,sessions,views} $SHARED/storage/logs ~/backups
chmod 700 $SHARED/storage/app/private

if [ -f $SHARED/.env ]; then
  echo "shared/.env already exists — leaving it untouched"
else
  KEY=$($PHP -r 'echo "base64:".base64_encode(random_bytes(32));')
  umask 077
  cat > $SHARED/.env <<ENV
APP_NAME="Study Medicine UK Nigeria"
APP_ENV=$([ "$TARGET" = production ] && echo production || echo staging)
APP_KEY=$KEY
APP_DEBUG=false
APP_URL=$(q "$APP_URL")
APP_LOCALE=en
APP_TIMEZONE=UTC
LOG_CHANNEL=daily
LOG_LEVEL=warning
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=$(q "${DB_HOST:-127.0.0.1}")
DB_PORT=3306
DB_DATABASE=$(q "$DB_DATABASE")
DB_USERNAME=$(q "$DB_USERNAME")
DB_PASSWORD=$(q "$DB_PASSWORD")
SESSION_DRIVER=database
SESSION_LIFETIME=60
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=private
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=info@studymedicineuknigeria.com
MAIL_PASSWORD=$(q "${MAIL_PASSWORD:-}")
MAIL_FROM_ADDRESS="info@studymedicineuknigeria.com"
MAIL_FROM_NAME="Study Medicine UK Nigeria"
STRIPE_KEY=$(q "${STRIPE_KEY:-}")
STRIPE_SECRET=$(q "${STRIPE_SECRET:-}")
STRIPE_WEBHOOK_SECRET=$(q "${STRIPE_WEBHOOK_SECRET:-}")
SITE_WHATSAPP=$(q "${SITE_WHATSAPP:-}")
SITE_LEGAL_NAME=$(q "${SITE_LEGAL_NAME:-}")
SITE_PUBLISH_UNVERIFIED=false
$([ "$TARGET" = production ] || printf 'STAGING_BASIC_USER=%s\nSTAGING_BASIC_PASSWORD=%s\n' "$(q "${STAGING_BASIC_USER:-}")" "$(q "${STAGING_BASIC_PASSWORD:-}")")
ENV
  echo "shared/.env created (mode 600)"
fi

# Database reachability check (no schema changes here; migrations run in deploy.sh)
if [ "${DB_CONNECTION:-mysql}" = mysql ] && command -v mysql >/dev/null; then
  MYSQL_PWD="$DB_PASSWORD" mysql -h"${DB_HOST:-127.0.0.1}" -u"$DB_USERNAME" -e "SELECT 1" "$DB_DATABASE" >/dev/null 2>&1 && echo "database reachable" || echo "WARNING: database not reachable with the supplied credentials"
fi

# Cron: scheduler every minute (adds once)
CRON_LINE="* * * * * cd $APP/current && $PHP artisan schedule:run >> /dev/null 2>&1"
if command -v crontab >/dev/null; then
  ( crontab -l 2>/dev/null | grep -vF "$APP/current && " ; echo "$CRON_LINE" ) | crontab - && echo "cron installed for $TARGET"
else
  echo "WARNING: crontab not available; add this line in hPanel → Cron Jobs: $CRON_LINE"
fi

# Document root link — explicit opt-in only, with backup of whatever is there
if [ "${LINK_DOCROOT:-0}" = "1" ]; then
  : "${DOCROOT:?DOCROOT path required when LINK_DOCROOT=1}"
  if [ -e "$DOCROOT" ] && [ ! -L "$DOCROOT" ]; then
    TS=$(date -u +%Y%m%dT%H%M%S); tar czf ~/backups/docroot-$TARGET-$TS.tgz -C "$(dirname "$DOCROOT")" "$(basename "$DOCROOT")" && echo "backed up existing docroot to ~/backups/docroot-$TARGET-$TS.tgz"
    mv "$DOCROOT" "$DOCROOT.pre-smukn-$TS"
  fi
  ln -sfn $APP/current/public "$DOCROOT" && echo "docroot $DOCROOT -> $APP/current/public"
else
  echo "docroot untouched (LINK_DOCROOT not set)"
fi
echo "bootstrap complete"
