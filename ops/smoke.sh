#!/usr/bin/env bash
# Smoke tests for a deployed environment. Usage: ops/smoke.sh https://studymedicineuknigeria.com
set -u; B=${1:?base url}; fail=0; AUTH=(); [ -n "${SMOKE_AUTH:-}" ] && AUTH=(-u "$SMOKE_AUTH")  # user:password for a basic-auth staging site
chk(){ local want=$1 path=$2; local got; got=$(curl -s "${AUTH[@]}" -o /dev/null -w "%{http_code}" "$B$path"); if [ "$got" = "$want" ]; then echo "ok   $got $path"; else echo "FAIL $got (want $want) $path"; fail=1; fi; }
chk 200 /; chk 200 /up; chk 200 /robots.txt; chk 200 /sitemap.xml; chk 200 /favicon.svg; chk 200 /site.webmanifest
chk 301 /Fees/; chk 404 /no-such-page; chk 200 /login
# reference data reached this environment (smukn:reference-sync): the directory lists schools and the fee guide renders
n=$(curl -s "${AUTH[@]}" "$B/medical-schools" | grep -o 'href="[^"]*/medical-schools/[a-z0-9-]*"' | sort -u | wc -l); [ "$n" -ge 20 ] && echo "ok   directory lists $n schools" || { echo "FAIL directory lists $n schools (reference data missing?)"; fail=1; }
chk 200 /fees; chk 200 /apply-online/eligibility
curl -sI "${AUTH[@]}" "$B/login" | grep -qi "x-robots-tag: noindex" && echo "ok   noindex header on /login" || { echo "FAIL noindex header missing on /login"; fail=1; }
curl -sI "${AUTH[@]}" "$B/" | grep -qi "strict-transport-security" && echo "ok   HSTS" || echo "warn HSTS header missing"
# canonical host and scheme (only meaningful against a public https URL)
case "$B" in https://studymedicineuknigeria.com) 
  code=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "http://studymedicineuknigeria.com/fees"); echo "http→https: $code" | grep -q "301 https://studymedicineuknigeria.com/fees" && echo "ok   http redirects to https" || { echo "FAIL http→https redirect ($code)"; fail=1; }
  code=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "https://www.studymedicineuknigeria.com/fees"); echo "$code" | grep -q "301 https://studymedicineuknigeria.com/fees" && echo "ok   www redirects to apex" || echo "warn www→apex redirect not confirmed ($code)";;
esac
exit $fail
