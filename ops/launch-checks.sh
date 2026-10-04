#!/usr/bin/env bash
# Public, read-only launch checks for studymedicineuknigeria.com, run from a GitHub runner (needs dig, curl, openssl).
# Nothing here logs in, writes or uses a secret. DNS values are reported as found, never invented: SPF, DMARC, MX,
# DKIM at common Hostinger selectors (DKIM proper is confirmed by a test message: "Show original" → DKIM: PASS).
# Exit status: 0 when the email DNS is ready for student registration (one SPF record, a DMARC record, MX present).
# Usage: ops/launch-checks.sh [domain] [staging-host]
set -u
D=${1:-studymedicineuknigeria.com}; S=${2:-staging.$D}; fail=0
ok(){ echo "ok    $*"; }; no(){ echo "MISS  $*"; fail=1; }; info(){ echo "info  $*"; }
hr(){ printf '\n== %s ==\n' "$1"; }

hr "DNS"
for h in "$D" "www.$D" "$S"; do
  a=$(dig +short A "$h" | tr '\n' ' '); aaaa=$(dig +short AAAA "$h" | tr '\n' ' ')
  [ -n "$a$aaaa" ] && info "$h → ${a}${aaaa}" || info "$h does not resolve"
done
mx=$(dig +short MX "$D" | sort -n | tr '\n' ';'); [ -n "$mx" ] && ok "MX: $mx" || no "no MX record for $D (mailbox cannot receive replies)"
spf=$(dig +short TXT "$D" | tr -d '"' | grep -i '^v=spf1' || true); n=$(printf '%s' "$spf" | grep -c 'v=spf1' || true)
if [ "$n" = 1 ]; then ok "SPF (one record): $spf"; echo "$spf" | grep -qiE 'hostinger' && ok "SPF authorises Hostinger mail" || info "SPF does not mention Hostinger; compare with hPanel → Emails"; elif [ "$n" -gt 1 ]; then no "$n SPF records (there must be exactly one): $spf"; else no "no SPF record at $D"; fi
dmarc=$(dig +short TXT "_dmarc.$D" | tr -d '"' | grep -i 'v=DMARC1' || true); [ -n "$dmarc" ] && ok "DMARC: $dmarc" || no "no DMARC record at _dmarc.$D"
found=""; for sel in hostingermail-a hostingermail-b hostingermail-c hostingermail1 hostingermail2 default dkim mail; do
  v=$(dig +short TXT "$sel._domainkey.$D" | tr -d '"' | head -c 60); [ -n "$v" ] && found="$found $sel"
done
[ -n "$found" ] && ok "DKIM key published at selector(s):$found" || info "no DKIM key at the common selectors; check hPanel → Emails → DKIM shows active, then confirm with a test message"

hr "HTTPS"
for h in "$D" "www.$D" "$S"; do
  dig +short A "$h" AAAA "$h" | grep -q . || { info "$h: skipped (does not resolve)"; continue; }
  c=$(echo | timeout 15 openssl s_client -servername "$h" -connect "$h:443" 2>/dev/null | openssl x509 -noout -subject -enddate -ext subjectAltName 2>/dev/null | tr '\n' ' ')
  code=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' --max-time 20 "https://$h/")
  if [ -n "$c" ] && [ "${code%% *}" != 000 ]; then ok "$h certificate valid for curl; HTTPS $code; $c"; else info "$h: no valid certificate or no HTTPS answer ($code)"; fi
done
code=$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' --max-time 20 "http://$D/"); info "http://$D/ → $code"

hr "WHAT $D SERVES TODAY (read-only)"
h=$(curl -sI --max-time 20 "https://$D/"); echo "$h" | grep -iE '^(server|x-powered-by|content-type|x-robots-tag|strict-transport-security|link):' | sed 's/^/info  /'
b=$(curl -s --max-time 20 "https://$D/")
info "title: $(printf '%s' "$b" | grep -oiE '<title>[^<]*' | head -1 | sed 's/<title>//I')"
info "generator: $(printf '%s' "$b" | grep -oiE '<meta name="generator" content="[^"]*' | head -1 | sed 's/.*content="//')"
printf '%s' "$b" | grep -q 'wp-content' && info "WordPress assets present (wp-content): back up its database too (Backup, scope site)" || info "no wp-content in the home page"
info "home page size: $(printf '%s' "$b" | wc -c) bytes"
for p in /robots.txt /sitemap.xml /sitemap_index.xml; do info "$p → $(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "https://$D$p")"; done
# every URL the current site lists, so the new site can answer or redirect each one (no indexed page may break)
urls=$(curl -s --max-time 20 "https://$D/sitemap.xml" | grep -oE '<loc>[^<]+</loc>' | sed -E 's#</?loc>##g')
for sm in $(printf '%s\n' "$urls" | grep -E '\.xml$'); do urls="$urls
$(curl -s --max-time 20 "$sm" | grep -oE '<loc>[^<]+</loc>' | sed -E 's#</?loc>##g')"; done
printf '%s\n' "$urls" | grep -vE '\.xml$' | grep . | sort -u > current-site-urls.txt
info "current sitemap lists $(wc -l < current-site-urls.txt) page URL(s) (current-site-urls.txt)"; sed 's/^/url   /' current-site-urls.txt | head -300

hr "RESULT"
[ "$fail" = 0 ] && echo "email DNS ready for student registration (confirm DKIM with a test message)" || echo "email DNS NOT ready: fix the MISS lines in hPanel → Domains → DNS / Emails (docs/ops/EMAIL-DELIVERABILITY.md)"
exit $fail
