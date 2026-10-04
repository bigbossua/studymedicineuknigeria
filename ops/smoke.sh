#!/usr/bin/env bash
# Smoke tests for a deployed environment. Usage: ops/smoke.sh https://studymedicineuknigeria.com [staging|production|local]
# SMOKE_AUTH=user:password for a basic-auth staging site. Exit 1 on any FAIL; deploy.sh rolls back on that.
set -u; B=${1:?base url}; T=${2:-}; fail=0; AUTH=(); [ -n "${SMOKE_AUTH:-}" ] && AUTH=(-u "$SMOKE_AUTH")
[ -n "$T" ] || { [ "$B" = https://studymedicineuknigeria.com ] && T=production || T=staging; }
ok(){ echo "ok   $*"; }; bad(){ echo "FAIL $*"; fail=1; }
chk(){ local want=$1 path=$2; local got; got=$(curl -s "${AUTH[@]}" -o /dev/null -w "%{http_code}" "$B$path"); [ "$got" = "$want" ] && ok "$got $path" || bad "$got (want $want) $path"; }
# never served: a document root pointing at the release instead of public/ would expose these
deny(){ local got; got=$(curl -s "${AUTH[@]}" -o /dev/null -w "%{http_code}" "$B$1"); [ "$got" != 200 ] && ok "$got $1 not served" || bad "$1 is publicly readable (document root must be current/public)"; }

chk 200 /; chk 200 /up; chk 200 /robots.txt; chk 200 /sitemap.xml; chk 200 /favicon.svg; chk 200 /site.webmanifest
chk 301 /Fees/; chk 301 /index.php/fees; chk 404 /no-such-page; chk 200 /login
chk 301 /Study-medicine-in-university-of-oxford-england; chk 301 /privacy-policy   # the previous site's URLs (data/seo/legacy-redirects.csv)
chk 200 /fees; chk 200 /apply-online/eligibility; chk 200 /medical-schools
for p in /.env /.env.example /composer.json /artisan /storage/logs/laravel.log /.git/HEAD /vendor/autoload.php /database/database.sqlite; do deny "$p"; done

# the application itself rendered, not a placeholder, an error page or a debug screen
home=$(curl -s "${AUTH[@]}" "$B/")
echo "$home" | grep -q 'Study Medicine UK Nigeria' && ok "home renders the site" || bad "home does not contain the site name (placeholder or wrong document root?)"
nf=$(curl -s "${AUTH[@]}" "$B/no-such-page")
if printf '%s\n%s' "$home" "$nf" | grep -qiE 'Whoops|Stack trace|SQLSTATE|vendor/laravel/framework|ignition'; then bad "debug output or exception text in a response (APP_DEBUG must be false)"; else ok "no debug output"; fi

# reference data reached this environment (smukn:reference-sync): the directory lists schools
n=$(curl -s "${AUTH[@]}" "$B/medical-schools" | grep -o 'href="[^"]*/medical-schools/[a-z0-9-]*"' | sort -u | wc -l)
[ "$n" -ge 20 ] && ok "directory lists $n schools" || bad "directory lists $n schools (reference data missing?)"

h=$(curl -sI "${AUTH[@]}" "$B/login")
echo "$h" | grep -qi "x-robots-tag: noindex" && ok "noindex header on /login" || bad "noindex header missing on /login"
echo "$h" | grep -qi "content-security-policy:" && ok "CSP header" || bad "Content-Security-Policy header missing"
if curl -sI "${AUTH[@]}" "$B/" | grep -qi "strict-transport-security"; then ok "HSTS"; elif [ "$T" = production ]; then bad "HSTS header missing on production"; else echo "warn HSTS header missing"; fi

robots=$(curl -s "${AUTH[@]}" "$B/robots.txt")
case "$T" in
  staging)
    # staging must never be public or indexable
    got=$(curl -s -o /dev/null -w "%{http_code}" "$B/")
    [ "$got" = 401 ] && ok "staging asks for credentials without them" || bad "staging answered $got without credentials (StagingGate inactive: set STAGING_BASIC_USER and STAGING_BASIC_PASSWORD)"
    echo "$robots" | grep -qx 'Disallow: /' && ok "staging robots.txt disallows all" || bad "staging robots.txt does not disallow crawling"
    curl -sI "${AUTH[@]}" "$B/" | grep -qi "x-robots-tag: noindex" && ok "staging pages carry noindex" || bad "staging home has no X-Robots-Tag noindex";;
  production)
    echo "$robots" | grep -qx 'Disallow: /' && bad "production robots.txt disallows everything" || ok "production robots.txt allows crawling"
    echo "$home" | grep -qi '<meta name="robots" content="noindex' && bad "production home is noindex" || ok "production home indexable"
    curl -sI "${AUTH[@]}" "$B/fees" | grep -qi "x-robots-tag: noindex" && bad "production /fees sends X-Robots-Tag noindex" || ok "production pages carry no noindex header"
    echo "$robots" | grep -qi "sitemap: https://studymedicineuknigeria.com/sitemap.xml" && ok "robots.txt names the production sitemap" || bad "robots.txt does not name https://studymedicineuknigeria.com/sitemap.xml"
    got=$(curl -s -o /dev/null -w "%{http_code}" "$B/"); [ "$got" = 200 ] && ok "production answers without a password" || bad "production answered $got without credentials (staging gate left on?)";;
esac

# canonical host and scheme (only meaningful against the public https URL)
case "$B" in https://studymedicineuknigeria.com)
  code=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "http://studymedicineuknigeria.com/fees"); echo "$code" | grep -q "301 https://studymedicineuknigeria.com/fees" && ok "http redirects to https" || bad "http→https redirect ($code)"
  code=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "https://www.studymedicineuknigeria.com/fees"); echo "$code" | grep -q "301 https://studymedicineuknigeria.com/fees" && ok "www redirects to apex" || bad "www does not answer 301 to the apex ($code)";;
esac
[ "$fail" = 0 ] && echo "smoke: all checks passed ($T)" || echo "smoke: FAILED ($T)"
exit $fail
