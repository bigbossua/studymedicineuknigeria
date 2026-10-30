#!/usr/bin/env bash
# serve CODE in production mode on DB, write sitemap URLs to OUT, print robots/canonical for given slugs
CODE=$1; DB=$2; OUT=$3; shift 3
cd "$CODE"
export APP_ENV=production APP_DEBUG=false APP_URL=https://studymedicineuknigeria.com DB_CONNECTION=sqlite DB_DATABASE=$DB SESSION_DRIVER=database CACHE_STORE=database QUEUE_CONNECTION=sync MAIL_MAILER=array SITE_PUBLISH_UNVERIFIED=false SITE_REGISTRATION_OPEN=false SITE_BANK_TRANSFER=false STRIPE_SECRET= STRIPE_KEY=
php artisan config:clear -q 2>/dev/null
( cd public && nohup php -S 127.0.0.1:8092 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php >/dev/null 2>&1 & )
for i in $(seq 1 40); do curl -s --noproxy 127.0.0.1 -o /dev/null http://127.0.0.1:8092/up && break; sleep 0.5; done
C="curl -s --noproxy 127.0.0.1 -H Host:studymedicineuknigeria.com -H X-Forwarded-Proto:https"
$C http://127.0.0.1:8092/sitemap.xml | grep -o '<loc>[^<]*' | sed 's#<loc>##' | sort > "$OUT"
for slug in "$@"; do
  h=$($C http://127.0.0.1:8092/medical-schools/$slug)
  echo "$slug | $(grep -o '<meta name="robots" content="[^"]*"' <<<"$h" | sed 's/.*content=//') | $(grep -o '<link rel="canonical" href="[^"]*"' <<<"$h" | sed 's/.*href=//')"
done
fuser -k 8092/tcp >/dev/null 2>&1; true
