#!/usr/bin/env bash
# Read-only verification of the live site after a launch, from outside (a GitHub runner). Complements ops/smoke.sh and
# the page review: every old-site redirect, no service fee or debug text on any public page, canonical and JSON-LD on
# every sitemap page, registration and sign-in pages, portal protection, foreign hosts, security headers.
# Usage: ops/live-verify.sh [https://studymedicineuknigeria.com]   Exit 1 on any failure.
set -u
B=${1:-https://studymedicineuknigeria.com}; fail=0
ok(){ echo "ok   $*"; }; bad(){ echo "FAIL $*"; fail=1; }

n=0; wrong=0
while IFS=, read -r from to _; do
  [ -z "$from" ] && continue; n=$((n+1))
  got=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B$from")
  [ "$got" = "301 $B$to" ] || { wrong=$((wrong+1)); echo "     $from -> $got (want 301 $B$to)"; }
done < <(tail -n +2 data/seo/legacy-redirects.csv)
[ "$wrong" = 0 ] && ok "all $n old-site URLs answer 301 to their page" || bad "$wrong of $n old-site URLs do not redirect as recorded"
while read -r gone; do [ -z "$gone" ] && continue; case "$gone" in \#*) continue;; esac
  c=$(curl -s -o /dev/null -w '%{http_code}' "$B$gone"); [ "$c" = 404 ] || bad "$gone answered $c (want 404)"
done < data/seo/legacy-gone.txt

pages=$(curl -s "$B/sitemap.xml" | grep -o '<loc>[^<]*' | sed 's/<loc>//')
total=$(echo "$pages" | grep -c .); leaks=0; nocanon=0; nold=0
for u in $pages "$B/apply-online" "$B/apply-online/services" "$B/register" "$B/login"; do
  body=$(curl -s "$u")
  grep -qE '£125|£695|£1,295|12500|69500|129500|priceCurrency' <<<"$body" && { bad "service fee visible on $u"; leaks=1; }
  grep -qiE 'Whoops|Stack trace|SQLSTATE|vendor/laravel' <<<"$body" && bad "debug text on $u"
  # nothing unfinished, internal or personal: placeholder text, test or development addresses, keys, personal mailboxes
  hit=$( { grep -oE '\b(TBC|TBD|CONFIRM|NOT PUBLISHED|NOT_PUBLISHED|VERIFY-ON-PAGE|PLACEHOLDER|DUMMY)\b' <<<"$body"; grep -oiE 'lorem ipsum|\bTODO\b|FIXME|example\.test|localhost|127\.0\.0\.1|staging\.studymedicine|(sk|rk)_(live|test)_|pk_test_|whsec_|@(gmail|yahoo|hotmail|outlook)\.com|test mode' <<<"$body"; } | sort -u | tr '\n' ' ')
  [ -n "$hit" ] && bad "unfinished, internal or personal text on $u: $hit"
  case "$u" in "$B/register"|"$B/login"|"$B/apply-online"|"$B/apply-online/services") continue;; esac
  grep -qF "<link rel=\"canonical\" href=\"$u\"" <<<"$body" || { nocanon=$((nocanon+1)); echo "     no self-canonical: $u"; }
  grep -q 'application/ld+json' <<<"$body" || nold=$((nold+1))
done
[ "$leaks" = 0 ] && ok "no service fee on $total sitemap pages + apply, services, register, login"
[ "$nocanon" = 0 ] && ok "every sitemap page carries its own canonical" || bad "$nocanon sitemap pages without their own canonical"
[ "$nold" = 0 ] && ok "every sitemap page carries JSON-LD" || echo "info $nold sitemap pages without JSON-LD"

home=$(curl -s "$B/")
grep -q 'name="ga4-id"' <<<"$home" && echo "info GA4 is configured (consent banner first; nothing loads before consent)" || echo "info GA4 is not configured yet (no Measurement ID on the server)"
grep -q 'name="google-site-verification"' <<<"$home" && echo "info Search Console HTML tag present" || true
grep -q 'wa.me/447842292527' <<<"$home" && ok "WhatsApp contact (+44 7842 292527) on the home page" || bad "WhatsApp contact missing from the home page"

reg=$(curl -s "$B/register")
if printf '%s' "$reg" | grep -q 'Registration opens shortly'; then echo "info registration is closed"; else
  printf '%s' "$reg" | grep -q 'name="password_confirmation"' && ok "registration form is open" || bad "registration page shows neither the form nor the closed notice"; fi
curl -s "$B/login" | grep -q 'name="password"' && ok "sign-in form renders" || bad "sign-in form missing"
c=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B/portal"); echo "$c" | grep -q '^302 .*/login' && ok "portal requires sign-in" || bad "portal: $c"
# documents: a signed-out request for a (guessed) document URL is sent to sign-in; storage paths are not served
c=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B/portal/SMUKN-2028-000001/documents/1/v/1"); echo "$c" | grep -q '^302 .*/login' && ok "a guessed document URL requires sign-in" || bad "guessed document URL: $c"
for p in /storage/app/private /storage /private /../storage/app/private /.env /vendor/composer/installed.json; do
  c=$(curl -s -o /dev/null -w '%{http_code}' "$B$p"); case "$c" in 404|403|301|302) ;; *) bad "$p answered $c";; esac
done; ok "storage, .env and vendor paths are not served"
c=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$B/admin"); echo "$c" | grep -q '^302 .*/login' && ok "admin requires sign-in" || bad "admin: $c"

# 000: the CDN closes the connection for a host it does not serve (the application's own 400 is proven by the rehearsal)
for h in evil.example studymedicineuknigeria.com.evil.example; do
  c=$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $h" "$B/"); case "$c" in 000|400|403|421) ok "foreign host $h refused ($c)";; *) bad "foreign host $h answered $c";; esac
done
hd=$(curl -sI "$B/")
for x in strict-transport-security content-security-policy x-content-type-options referrer-policy x-frame-options permissions-policy; do
  echo "$hd" | grep -qi "^$x:" && ok "header $x" || echo "info header $x not sent"
done
echo "$hd" | grep -qi '^x-powered-by:' && bad "X-Powered-By is exposed" || ok "no X-Powered-By"
[ "$fail" = 0 ] && echo "live verification: all checks passed" || echo "live verification: FAILED"
exit $fail
