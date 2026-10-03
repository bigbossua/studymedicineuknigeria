#!/usr/bin/env bash
# Static snapshot of every public page of the local build (sitemap URLs, sample university records, the gated pages and
# the directory/fee filter views) with the real stylesheet and fonts, for visual review when nothing is deployed.
# Usage: ops/qa/snapshot.sh [OUT_DIR] [BASE_URL]   (defaults: ./snapshot, http://127.0.0.1:8000; php artisan serve must be running)
set -euo pipefail
OUT="${1:-snapshot}"; BASE="${2:-http://127.0.0.1:8000}"
rm -rf "$OUT"; mkdir -p "$OUT"; cd "$OUT"
curl -s "$BASE/sitemap.xml" | grep -o "<loc>[^<]*" | sed 's/<loc>//' > urls.txt
for s in aston chester lancashire manchester edinburgh buckingham; do echo "$BASE/medical-schools/$s" >> urls.txt; done
printf '%s\n' "$BASE/fees/cost-of-studying-medicine-in-the-uk" "$BASE/working-in-the-uk" "$BASE/medical-schools?international=accepts" "$BASE/medical-schools?waec=published" "$BASE/fees?sort=fee" >> urls.txt
wget -q -E -k -p -nH --restrict-file-names=windows -e robots=off -i urls.txt || true
rm -f urls.txt
mv index.html home.html
grep -rl 'index.html' --include=*.html . | xargs -r sed -i 's#href="index\.html#href="home.html#g'
mv "medical-schools@international=accepts.html" medical-schools-international.html 2>/dev/null || true
mv "medical-schools@waec=published.html" medical-schools-waec.html 2>/dev/null || true
mv "fees@sort=fee.html" fees-by-fee.html 2>/dev/null || true
grep -rl '@' --include=*.html . | xargs -r sed -i 's#medical-schools@international=accepts\.html#medical-schools-international.html#g; s#medical-schools@waec=published\.html#medical-schools-waec.html#g; s#fees@sort=fee\.html#fees-by-fee.html#g'
echo "$(find . -name '*.html' | wc -l) pages in $OUT ($(du -sh . | cut -f1)); open home.html"
