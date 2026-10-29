#!/usr/bin/env bash
# Checks how ops/sitemap-probe.sh judges three CDN behaviours (run in CI; no network beyond 127.0.0.1):
#   open  -> passes with no warning
#   block -> a 403 only to fake Googlebot user agents passes with a warning (the probe's own false failure of 2026-10-27)
#   all   -> a 403 to every client fails, without the Googlebot warning
set -u
cd "$(dirname "$0")/../.." || exit 1
export NO_PROXY=127.0.0.1 no_proxy=127.0.0.1
port=8765; rc=0
expect(){ # mode, expected exit, expected number of WARN lines
  python3 ops/qa/fixtures/fake-cdn.py "$1" $port & pid=$!
  for _ in $(seq 50); do curl -s -o /dev/null "http://127.0.0.1:$port/robots.txt" && break; sleep 0.1; done
  out=$(bash ops/sitemap-probe.sh "http://127.0.0.1:$port"); code=$?
  kill $pid; wait $pid 2>/dev/null
  warns=$(grep -c '^WARN' <<<"$out")
  if [ "$code" = "$2" ] && [ "$warns" = "$3" ]; then
    echo "ok    $1: exit $code, $warns warning(s)"
  else
    echo "FAIL  $1: exit $code (wanted $2), $warns warning(s) (wanted $3)"; echo "$out"; rc=1
  fi
  port=$((port+1))
}
expect open 0 0
expect block 0 6
expect all 1 0   # no warning: a browser is refused too, so this is a real outage
exit $rc
