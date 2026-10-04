#!/usr/bin/env bash
# Production rehearsal (owner decision 2026-10-04: no staging site). Serves THIS checkout in production mode on the
# machine running it (a GitHub runner before a production deploy) under the real host name, with a throwaway SQLite
# database, and runs the production smoke test plus the launch checks a staging review would have made:
# trusted hosts, www → apex, old-site redirects, no debug output, unverified facts gated (production mode), no
# service fee on any public page, registration closed, payment closed. Needs vendor/ and public/build/ (built).
# Exit 1 on any failure. Usage: ops/production-rehearsal.sh [port]
set -u
PORT=${1:-8090}; ROOT=$(pwd); W=$(mktemp -d); fail=0
ok(){ echo "ok   $*"; }; bad(){ echo "FAIL $*"; fail=1; }
[ -d vendor ] && [ -d public/build ] || { echo "vendor/ or public/build/ missing: build first"; exit 1; }
DB="$W/rehearsal.sqlite"; touch "$DB"
export APP_ENV=production APP_DEBUG=false APP_URL=https://studymedicineuknigeria.com APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" \
  DB_CONNECTION=sqlite DB_DATABASE="$DB" SESSION_DRIVER=database CACHE_STORE=database QUEUE_CONNECTION=sync MAIL_MAILER=array \
  SITE_PUBLISH_UNVERIFIED=false SITE_REGISTRATION_OPEN=false SITE_BANK_TRANSFER=false STRIPE_SECRET= STRIPE_KEY= LOG_CHANNEL=stderr
php artisan migrate --force >/dev/null && php artisan smukn:reference-sync >/dev/null || { echo "FAIL migrations or reference sync"; exit 1; }
( cd public && nohup php -S 127.0.0.1:$PORT "$ROOT/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php" >"$W/server.log" 2>&1 & )
for i in $(seq 1 30); do curl -s -o /dev/null -H 'Host: studymedicineuknigeria.com' "http://127.0.0.1:$PORT/up" && break; sleep 0.5; done
# every request reaches the app as https://studymedicineuknigeria.com, as Hostinger's front end delivers it
mkdir -p "$W/bin"; printf '#!/usr/bin/env bash\nexec %s -H "Host: ${REHEARSAL_HOST:-studymedicineuknigeria.com}" -H "X-Forwarded-Proto: https" "$@"\n' "$(command -v curl)" > "$W/bin/curl"; chmod +x "$W/bin/curl"
B="http://127.0.0.1:$PORT"; C="$W/bin/curl"

echo "== production smoke test (rehearsal) =="
PATH="$W/bin:$PATH" ops/smoke.sh "$B" production || fail=1

echo "== launch checks =="
for h in evil.example staging.studymedicineuknigeria.com studymedicineuknigeria.com.evil.example; do
  c=$(REHEARSAL_HOST=$h "$C" -s -o /dev/null -w '%{http_code}' "$B/fees"); [ "$c" = 400 ] && ok "foreign host $h refused ($c)" || bad "foreign host $h answered $c (trusted hosts)"
done
r=$(REHEARSAL_HOST=www.studymedicineuknigeria.com "$C" -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B/fees?x=1")
[ "$r" = "301 https://studymedicineuknigeria.com/fees?x=1" ] && ok "www answers 301 to the apex" || bad "www: $r"
n=0; bad_r=0
while IFS=, read -r from to _; do
  [ "$from" = from_path ] && continue; n=$((n+1))
  r=$("$C" -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B$from"); [ "$r" = "301 https://studymedicineuknigeria.com$to" ] || { bad "old URL $from → $r (want $to)"; bad_r=1; }
done < data/seo/legacy-redirects.csv
[ "$bad_r" = 0 ] && ok "all $n old-site URLs answer 301 to their page"
leaks=0; pages=0
for p in $("$C" -s "$B/sitemap.xml" | grep -oE '<loc>[^<]+</loc>' | sed -E 's#</?loc>##g; s#^https?://[^/]+##') /apply-online/services /apply-online /register /login; do
  pages=$((pages+1)); body=$("$C" -s "$B${p:-/}")
  printf '%s' "$body" | grep -qE '£125|£695|£1,295|12500|69500|129500|priceCurrency' && { bad "service fee visible on ${p:-/}"; leaks=1; }
  printf '%s' "$body" | grep -qiE 'Whoops|Stack trace|SQLSTATE|vendor/laravel' && bad "debug output on ${p:-/}"
done
[ "$leaks" = 0 ] && ok "no service fee on $pages public pages (sitemap + apply + auth)"
"$C" -s "$B/register" | grep -q 'Registration opens shortly' && ok "registration closed until the test email" || bad "registration page is open"
c=$("$C" -s -o /dev/null -w '%{http_code}' -X POST "$B/register"); [ "$c" = 419 ] || [ "$c" = 503 ] && ok "registration POST refused ($c)" || bad "registration POST answered $c"
c=$("$C" -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B/portal"); echo "$c" | grep -q '^302 .*/login' && ok "portal requires sign-in" || bad "portal: $c"
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(app(App\Services\Payments\StripeService::class)->paymentsOpen() ? 1 : 0);' && ok "payment closed (no Stripe key, bank transfer off)" || bad "payment would be open"
pkill -f "php -S 127.0.0.1:$PORT" 2>/dev/null; rm -rf "$W"
[ "$fail" = 0 ] && echo "rehearsal: all production checks passed" || echo "rehearsal: FAILED"
exit $fail
