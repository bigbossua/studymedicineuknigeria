#!/usr/bin/env bash
# Read-only diagnosis of why the web server refuses a document root that links into ~/apps (launch 2026-10-04: every
# path answered 403 after the cutover). Prints permissions along the path and recent error-log lines that mention
# it, then (mode "links") adds two unlisted probe links inside the live document root, one into ~/apps and one into
# the domain's own folder; mode "cleanup" removes them. The live site itself is not changed.
# Usage on the server: MODE=links|cleanup TS=<id> bash -s < ops/docroot-probe.sh
set -u
D="$HOME/domains/studymedicineuknigeria.com"; APP="$HOME/apps/smukn-production"; TS=${TS:?}; MODE=${MODE:-links}
if [ "$MODE" = cleanup ]; then
  rm -f "$D/public_html/smukn-probe-a-$TS" "$D/public_html/smukn-probe-b-$TS"; rm -rf "$HOME/apps/smukn-probe-$TS" "$D/smukn-probe-$TS"
  echo "probe links removed"; exit 0
fi
echo "== permissions along the paths =="
ls -ld "$HOME" "$HOME/apps" "$APP" "$APP/releases" "$APP/current" "$D" "$D/public_html" 2>&1
readlink -f "$APP/current" 2>&1
command -v namei >/dev/null && namei -mo "$APP/current/public/index.php" 2>&1
echo "== the domain folder =="; ls -la "$D" 2>&1 | head -20
echo "== error log lines mentioning the new paths (last 15) =="
for f in "$D"/logs/* "$HOME"/logs/* "$HOME"/.logs/*; do [ -f "$f" ] && grep -hE 'smukn|/apps/|Symbolic|symlink|denied' "$f" 2>/dev/null | tail -15; done | tail -15
echo "== probes =="
mkdir -p "$HOME/apps/smukn-probe-$TS" "$D/smukn-probe-$TS"
echo apps-ok > "$HOME/apps/smukn-probe-$TS/probe.txt"; echo domain-ok > "$D/smukn-probe-$TS/probe.txt"
chmod 755 "$HOME/apps/smukn-probe-$TS" "$D/smukn-probe-$TS"; chmod 644 "$HOME/apps/smukn-probe-$TS/probe.txt" "$D/smukn-probe-$TS/probe.txt"
ln -s "$HOME/apps/smukn-probe-$TS" "$D/public_html/smukn-probe-a-$TS"
ln -s "$D/smukn-probe-$TS" "$D/public_html/smukn-probe-b-$TS"
echo "probe links ready"
