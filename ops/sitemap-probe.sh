#!/usr/bin/env bash
# What an external crawler receives for /sitemap.xml and /robots.txt (run by .github/workflows/sitemap-probe.yml).
# Public URLs only. Exit 1 on any failure.  Usage: ops/sitemap-probe.sh [https://studymedicineuknigeria.com]
# Content, canonical and indexability are judged as a browser sees them. Requests that claim to be Googlebot are sent
# too, but they come from GitHub's addresses, not Google's: a CDN that refuses a fake Googlebot (403) while serving a
# browser (200) is protecting the site, not blocking Google, so that is a warning to confirm in Search Console (URL
# Inspection, live test), not a failure. Since 2026-10-27 the Hostinger CDN does exactly that.
set -u
B=${1:-https://studymedicineuknigeria.com}; H=${B#*://}; H=${H%%/*}; fail=0; spoof=0; served=0
ok(){ echo "ok    $*"; }; bad(){ echo "FAIL  $*"; fail=1; }; info(){ echo "info  $*"; }; warn(){ echo "WARN  $*"; }
BROWSER='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36'
GOOGLEBOT='Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
SMARTPHONE='Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
T=$(mktemp -d)

echo "== DNS =="
command -v dig >/dev/null && info "A: $(dig +short A "$H" | tr '\n' ' ')  AAAA: $(dig +short AAAA "$H" | tr '\n' ' ')"

echo "== sitemap.xml =="
# Browser and curl first: their copy is the one judged below.
for name in browser curl Googlebot Googlebot-smartphone; do
  case $name in Googlebot) ua=$GOOGLEBOT;; Googlebot-smartphone) ua=$SMARTPHONE;; browser) ua=$BROWSER;; *) ua=curl/8;; esac
  for enc in identity gzip; do
    out="$T/s-$name.xml"
    r=$(curl -s -A "$ua" -H "Accept-Encoding: $enc" --compressed -o "$out" -D "$T/h-$name" -w '%{http_code}|%{content_type}|%{num_redirects}' "$B/sitemap.xml")
    IFS="|" read -r code type redirects <<<"$r"
    if [ "$code" = 200 ] && [ "$redirects" = 0 ] && case "$type" in application/xml*|text/xml*) true;; *) false;; esac; then
      ok "GET ($name, $enc): $code $type"; [ "$name" = browser ] && served=1
    elif [ "$code" = 403 ] && [ "${name#Googlebot}" != "$name" ] && [ "$served" = 1 ]; then
      warn "GET ($name, $enc): 403 to a Googlebot user agent from a non-Google address, while a browser gets the sitemap"; spoof=1
    else
      bad "GET ($name, $enc): $code $type, $redirects redirect(s)"
    fi
  done
done
cp "$T/s-browser.xml" "$T/s.xml" 2>/dev/null; cp "$T/h-browser" "$T/h" 2>/dev/null
for fam in -4 -6; do
  [ "$fam" = -6 ] && [ -z "$(dig +short AAAA "$H" 2>/dev/null)" ] && continue
  # GitHub's hosted runners have no IPv6 route: only judge IPv6 when this machine can reach another IPv6 site
  [ "$fam" = -6 ] && ! curl -s -6 -o /dev/null --max-time 8 https://www.google.com/ && { info "IPv6 not testable from this machine (no IPv6 route); AAAA records are published"; continue; }
  c=$(curl -s $fam -A "$BROWSER" -o /dev/null -w '%{http_code}' --max-time 20 "$B/sitemap.xml"); [ "$c" = 200 ] && ok "IPv${fam#-}: $c" || bad "IPv${fam#-}: $c"
done
c=$(curl -sI -A "$BROWSER" -o /dev/null -w '%{http_code}' "$B/sitemap.xml"); [ "$c" = 200 ] && ok "HEAD (browser): $c" || bad "HEAD (browser): $c"
c=$(curl -sI -A "$GOOGLEBOT" -o /dev/null -w '%{http_code}' "$B/sitemap.xml")
if [ "$c" = 200 ]; then ok "HEAD (Googlebot): $c"; elif [ "$c" = 403 ] && [ "$served" = 1 ]; then warn "HEAD (Googlebot): 403 from a non-Google address"; spoof=1; else bad "HEAD (Googlebot): $c"; fi

info "headers: $(grep -iE '^(content-type|content-encoding|cache-control|x-robots-tag|server|x-hcdn[a-z-]*|age):' "$T/h" | tr -d '\r' | tr '\n' ';')"
grep -qi '^x-robots-tag:.*noindex' "$T/h" && bad "the sitemap carries X-Robots-Tag noindex" || true
first=$(head -c 5 "$T/s.xml"); [ "$first" = '<?xml' ] && ok "body starts with <?xml (no BOM or whitespace before it)" || bad "body starts with: $(head -c 20 "$T/s.xml" | od -c | head -1)"
if command -v xmllint >/dev/null; then xmllint --noout "$T/s.xml" 2>"$T/err" && ok "well-formed XML (xmllint)" || bad "XML error: $(head -3 "$T/err")"; fi
grep -q 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' "$T/s.xml" && ok "sitemaps.org 0.9 namespace" || bad "wrong or missing urlset namespace"
urls=$(grep -o '<loc>[^<]*</loc>' "$T/s.xml" | sed -E 's#</?loc>##g'); n=$(echo "$urls" | grep -c .)
[ "$n" -gt 0 ] && ok "$n URLs listed" || bad "no URL listed"
off=$(echo "$urls" | grep . | grep -vc "^$B/\?[^?#]*$" || true); [ "$off" = 0 ] && ok "every URL is absolute https on $H, no query" || bad "$off URLs off-origin or with a query"

echo "== each listed URL (as a browser) =="
badurls=0
while read -r u; do
  [ -z "$u" ] && continue
  r=$(curl -s -A "$BROWSER" -o "$T/p.html" -D "$T/ph" -w '%{http_code}' "$u")
  if [ "$r" != 200 ]; then echo "     $u -> $r"; badurls=$((badurls+1)); continue; fi
  grep -qi '^x-robots-tag:.*noindex' "$T/ph" && { echo "     $u -> X-Robots-Tag noindex"; badurls=$((badurls+1)); continue; }
  grep -qiE '<meta name="robots" content="[^"]*noindex' "$T/p.html" && { echo "     $u -> meta noindex"; badurls=$((badurls+1)); continue; }
  grep -qF "<link rel=\"canonical\" href=\"$u\"" "$T/p.html" || { echo "     $u -> canonical differs"; badurls=$((badurls+1)); }
done <<<"$urls"
[ "$badurls" = 0 ] && ok "all $n URLs answer 200 directly, indexable, self-canonical" || bad "$badurls listed URLs are not 200/indexable/self-canonical"
echo "$urls" | grep -qE '/(portal|admin|login|register|password|email|two-factor|webhooks)(/|$)' && bad "a private URL is listed" || ok "no private URL listed"

echo "== robots.txt =="
r=$(curl -s -A "$BROWSER" -o "$T/r.txt" -w '%{http_code}|%{content_type}' "$B/robots.txt"); IFS='|' read -r code type <<<"$r"
[ "$code" = 200 ] && case "$type" in text/plain*) true;; *) false;; esac && ok "robots.txt $code $type" || bad "robots.txt $code $type"
grep -qx "Sitemap: $B/sitemap.xml" "$T/r.txt" && ok "robots.txt names $B/sitemap.xml" || bad "robots.txt does not name the sitemap"
grep -qiE '^Disallow: /sitemap|^Disallow: /$' "$T/r.txt" && bad "robots.txt blocks the sitemap or the whole site" || ok "robots.txt blocks neither the sitemap nor the site"

[ "$spoof" = 1 ] && warn "a CDN refuses user agents that claim to be Googlebot from this (non-Google) address; that is not evidence about real Googlebot. Confirm with Search Console -> URL Inspection -> Test live URL on $B/sitemap.xml"

rm -rf "$T"
[ "$fail" = 0 ] && echo "sitemap probe: all checks passed" || echo "sitemap probe: FAILURES above"
exit $fail
