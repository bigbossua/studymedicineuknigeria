#!/usr/bin/env bash
# Set settings that arrive after bootstrap (Stripe keys, mail password) in ~/apps/smukn-<target>/shared/.env, then
# rebuild the config cache. Run over SSH by the "Update server settings" workflow; values come on stdin as exports,
# never on a command line. Only non-empty inputs are written; every other line of .env is left as it is.
# Inputs (environment): TARGET, STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET, MAIL_PASSWORD
set -euo pipefail
: "${TARGET:?staging|production}"
APP=~/apps/smukn-$TARGET; ENV_FILE=$APP/shared/.env
[ -f "$ENV_FILE" ] || { echo "no $ENV_FILE: bootstrap $TARGET first"; exit 2; }
# Stripe keys must match the target's mode: staging takes test keys only, production live keys only (a test key
# there would let a test charge pose as a real payment).
for v in STRIPE_KEY STRIPE_SECRET; do
  [ -n "${!v:-}" ] || continue
  case "${!v}" in pk_live_*|sk_live_*|rk_live_*) live=1;; *) live=0;; esac
  if [ "$TARGET" = production ] && [ "$live" = 0 ]; then echo "$v is not a live Stripe key; production takes live keys only (pk_live_/sk_live_)" >&2; exit 4; fi
  if [ "$TARGET" != production ] && [ "$live" = 1 ]; then echo "$v is a live Stripe key; $TARGET takes test keys only (pk_test_/sk_test_)" >&2; exit 4; fi
done
set_key(){ # set_key NAME VALUE : replace the NAME= line or append it, value single-quoted (read literally by phpdotenv)
  local name=$1 value=$2 tmp
  case "$value" in *"'"*|*$'\n'*|*$'\r'*) echo "$name contains a single quote or a line break, which .env cannot hold safely" >&2; exit 4;; esac
  tmp=$(mktemp "$APP/shared/.env.XXXXXX"); chmod 600 "$tmp"
  # values reach awk through the environment: awk -v would interpret backslashes in them
  N="$name" L="$name='$value'" awk 'BEGIN{done=0} index($0, ENVIRON["N"]"=")==1 {print ENVIRON["L"]; done=1; next} {print} END{if(!done) print ENVIRON["L"]}' "$ENV_FILE" > "$tmp"
  mv "$tmp" "$ENV_FILE"; echo "set $name"
}
changed=0
for v in STRIPE_KEY STRIPE_SECRET STRIPE_WEBHOOK_SECRET MAIL_PASSWORD; do
  [ -n "${!v:-}" ] && { set_key "$v" "${!v}"; changed=1; }
done
[ -n "${MAIL_PASSWORD:-}" ] && set_key MAIL_MAILER smtp
[ "$changed" = 1 ] || { echo "nothing to set (all inputs empty)"; exit 0; }
chmod 600 "$ENV_FILE"
if [ -d "$APP/current" ]; then
  PHP=""; for c in php83 php8.3 /opt/alt/php83/usr/bin/php php; do p=$(command -v "$c" 2>/dev/null) && "$p" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && { PHP=$p; break; }; done
  [ -n "$PHP" ] || { echo "no PHP 8.3+ command-line binary found"; exit 3; }
  (cd "$APP/current" && $PHP artisan config:cache >/dev/null && echo "config cache rebuilt")
fi
