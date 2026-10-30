#!/usr/bin/env bash
# Applies every committed decisions file (data/verification/decisions-*.csv) in the release directory it runs in, as
# ops/deploy.sh does on the server, and lists in .facts-imported the files this run actually applied (files already in
# the fact_imports ledger are skipped by smukn:facts-import). ops/revert-release-facts.sh undoes exactly those on a
# rollback, so the previous release sees the facts it was deployed with.
# Usage (from the release directory): bash ops/import-decisions.sh [php-binary]
set -euo pipefail
PHP=${1:-php}
: > .facts-imported
for f in data/verification/decisions-*.csv; do
  [ -e "$f" ] || continue
  out=$($PHP artisan smukn:facts-import "$f")
  echo "$f: $out"
  case "$out" in "Already applied"*) ;; *) echo "$f" >> .facts-imported;; esac
done
echo "decision files applied by this release: $(grep -c . .facts-imported || true)"
