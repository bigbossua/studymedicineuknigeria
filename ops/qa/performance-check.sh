#!/usr/bin/env bash
# Lighthouse on the key landing pages (mobile, default throttling), printed as a Markdown table. Fails when a page
# scores below 80 for performance or 95 for accessibility/SEO, or exceeds LCP 4 s / CLS 0.1 / TBT 600 ms.
#   ops/qa/performance-check.sh https://studymedicineuknigeria.com
set -uo pipefail
BASE="${1:?base url}"
PAGES=(/ /study-medicine-in-the-uk/from-nigeria /study-medicine-in-the-uk /medical-schools /requirements /requirements/waec /fees /admissions/ucat /apply-online /apply-online/eligibility /medical-schools/aberdeen /working-in-the-uk)
dir=$(mktemp -d); fail=0
echo '| Page | Perf | A11y | Best practice | SEO | LCP | CLS | TBT |'
echo '|---|---:|---:|---:|---:|---:|---:|---:|'
for p in "${PAGES[@]}"; do
  out="$dir/r.json"
  lighthouse "$BASE$p" --quiet --output=json --output-path="$out" --chrome-flags="--headless=new --no-sandbox" \
    --only-categories=performance,accessibility,best-practices,seo >/dev/null 2>&1 || { echo "| $p | error | | | | | | |"; fail=1; continue; }
  row=$(node -e '
    const r = require(process.argv[1]); const c = r.categories, a = r.audits;
    const s = (k) => Math.round((c[k]?.score ?? 0) * 100);
    const lcp = a["largest-contentful-paint"].numericValue / 1000, cls = a["cumulative-layout-shift"].numericValue, tbt = a["total-blocking-time"].numericValue;
    const bad = s("performance") < 80 || s("accessibility") < 95 || s("seo") < 95 || lcp > 4 || cls > 0.1 || tbt > 600;
    const shift = cls > 0.05 ? ((a["layout-shifts"]?.details?.items || [])[0]?.node?.selector || "") : "";
    console.log(`${s("performance")} | ${s("accessibility")} | ${s("best-practices")} | ${s("seo")} | ${lcp.toFixed(1)} s | ${cls.toFixed(3)} | ${Math.round(tbt)} ms${shift ? " (shift: " + shift + ")" : ""} |${bad ? "FAIL" : ""}`);
  ' "$out")
  [[ "$row" == *FAIL ]] && fail=1
  echo "| $p | ${row%FAIL}"
done
echo
[ $fail -eq 0 ] && echo 'All pages within budget.' || echo 'At least one page is outside the budget (perf ≥ 80, a11y/SEO ≥ 95, LCP ≤ 4 s, CLS ≤ 0.1, TBT ≤ 600 ms).'
exit $fail
