#!/usr/bin/env bash
# Undoes the fact decisions a release applied on deploy (listed in <release>/.facts-imported by ops/import-decisions.sh),
# newest file first, using that release's own code (smukn:facts-revert). Run by ops/rollback.sh and by deploy.sh's
# automatic rollback before the previous release is switched back in, so the earlier code is not served facts it never
# had (for instance school pages its indexing rule would index). A release without the list has nothing to undo.
# Usage: bash ops/revert-release-facts.sh <release-dir> [php-binary]
set -euo pipefail
REL=${1:?release directory}; PHP=${2:-php}
L="$REL/.facts-imported"
if [ ! -s "$L" ]; then echo "no fact decisions recorded for $(basename "$REL"): nothing to undo"; exit 0; fi
cd "$REL"
tac "$L" | while read -r f; do
  [ -n "$f" ] || continue
  echo "$f: $($PHP artisan smukn:facts-revert "$f")"
done
mv "$L" "$L.reverted-$(date -u +%Y-%m-%dT%H-%M-%S)"
echo "fact decisions of $(basename "$REL") undone"
