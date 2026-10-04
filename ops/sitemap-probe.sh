#!/usr/bin/env bash
# What an external crawler receives for /sitemap.xml and /robots.txt (run by .github/workflows/sitemap-probe.yml).
# Public URLs only. Exit 1 on any failure.  Usage: ops/sitemap-probe.sh [https://studymedicineuknigeria.com]
set -u
B=${1:-https://studymedicineuknigeria.com}; H=${B#https://}; fail=0
ok(){ echo "ok    $*"; }; bad(){ echo "FAIL  $*"; fail=1; }; info(){ echo "info  $*"; }
GOOGLEBOT='Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
SMARTPHONE='Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
T=$(mktemp -d)

echo "== DNS =="
command -v dig >/dev/null && info "A: $(dig +short A "$H" | tr '\n' ' ')  AAAA: $(dig +short AAAA "$H" | tr '\n' ' ')"

echo "== sitemap.xml =="
for name in Googlebot Googlebot-smartphone curl; do
  case $name in Googlebot) ua=$GOOGLEBOT;; Googlebot-smartphone) ua=$SMARTPHONE;; *) ua=curl/8;; esac
  for enc in identity gzip; do
    r=$(curl -s -A "$ua" -H "Accept-Encoding: $enc" --compressed -o "$T/s.xml" -D "$T/h" -w '%{http_code}|%{content_type}|%{num_redirects}' "$B/sitemap.xml")
    IFS="|" read -r code type redirects <<<"$r"
    [ "$code" = 200 ] && [ "$redirects" = 0 ] && case "$type" in application/xml*|text/xml*) true;; *) false;; esac \
      && ok "GET ($name, $enc): $code $type" || bad "GET ($name, $enc): $code $type, $redirects redirect(s)"
  done
done
for fam in -4 -6; do
  [ "$fam" = -6 ] && [ -z "$(dig +short AAAA "$H" 2>/dev/null)" ] && continue
  c=$(curl -s $fam -A "$GOOGLEBOT" -o /dev/null -w '%{http_code}' --max-time 20 "$B/sitemap.xml"); [ "$c" = 200 ] && ok "IPv${fam#-}: $c" || bad "IPv${fam#-}: $c"
done
c=$(curl -sI -A "$GOOGLEBOT" -o /dev/null -w '%{http_code}' "$B/sitemap.xml"); [ "$c" = 200 ] && ok "HEAD: $c" || bad "HEAD: $c"
info "headers: $(grep -iE '^(content-type|content-encoding|cache-control|x-robots-tag|server|x-hcdn[a-z-]*|age):' "$T/h" | tr -d '\r' | tr '\n' ';')"
grep -qi '^x-robots-tag:.*noindex' "$T/h" && bad "the sitemap carries X-Robots-Tag noindex" || true
first=$(head -c 5 "$T/s.xml"); [ "$first" = '<?xml' ] && ok "body starts with <?xml (no BOM or whitespace before it)" || bad "body starts with: $(head -c 20 "$T/s.xml" | od -c | head -1)"
if command -v xmllint >/dev/null; then xmllint --noout "$T/s.xml" 2>"$T/err" && ok "well-formed XML (xmllint)" || bad "XML error: $(head -3 "$T/err")"; fi
grep -q 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' "$T/s.xml" && ok "sitemaps.org 0.9 namespace" || bad "wrong or missing urlset namespace"
urls=$(grep -o '<loc>[^<]*</loc>' "$T/s.xml" | sed -E 's#</?loc>##g'); n=$(echo "$urls" | grep -c .)
ok "$n URLs listed"
off=$(echo "$urls" | grep -vc "^$B/\?[^?#]*$" || true); [ "$off" = 0 ] && ok "every URL is absolute https on $H, no query" || bad "$off URLs off-origin or with a query"

echo "== each listed URL (as Googlebot) =="
badurls=0
while read -r u; do
  [ -z "$u" ] && continue
  r=$(curl -s -A "$GOOGLEBOT" -o "$T/p.html" -D "$T/ph" -w '%{http_code}' "$u")
  if [ "$r" != 200 ]; then echo "     $u -> $r"; badurls=$((badurls+1)); continue; fi
  grep -qi '^x-robots-tag:.*noindex' "$T/ph" && { echo "     $u -> X-Robots-Tag noindex"; badurls=$((badurls+1)); continue; }
  grep -qiE '<meta name="robots" content="[^"]*noindex' "$T/p.html" && { echo "     $u -> meta noindex"; badurls=$((badurls+1)); continue; }
  grep -qF "<link rel=\"canonical\" href=\"$u\"" "$T/p.html" || { echo "     $u -> canonical differs"; badurls=$((badurls+1)); }
done <<<"$urls"
[ "$badurls" = 0 ] && ok "all $n URLs answer 200 directly, indexable, self-canonical" || bad "$badurls listed URLs are not 200/indexable/self-canonical"
echo "$urls" | grep -qE '/(portal|admin|login|register|password|email|two-factor|webhooks)(/|$)' && bad "a private URL is listed" || ok "no private URL listed"

echo "== robots.txt =="
r=$(curl -s -A "$GOOGLEBOT" -o "$T/r.txt" -w '%{http_code}|%{content_type}' "$B/robots.txt"); IFS='|' read -r code type <<<"$r"
[ "$code" = 200 ] && case "$type" in text/plain*) true;; *) false;; esac && ok "robots.txt $code $type" || bad "robots.txt $code $type"
grep -qx "Sitemap: $B/sitemap.xml" "$T/r.txt" && ok "robots.txt names $B/sitemap.xml" || bad "robots.txt does not name the sitemap"
grep -qiE '^Disallow: /sitemap|^Disallow: /$' "$T/r.txt" && bad "robots.txt blocks the sitemap or the whole site" || ok "robots.txt blocks neither the sitemap nor the site"

rm -rf "$T"
[ "$fail" = 0 ] && echo "sitemap probe: all checks passed" || echo "sitemap probe: FAILURES above"
exit $fail
