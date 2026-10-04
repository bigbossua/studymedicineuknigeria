#!/usr/bin/env bash
# Hostinger's web server refuses to follow a symbolic link from a website's document root (first launch, 2026-10-04:
# every path answered 403; docroot-probe confirmed it for links into ~/apps and into the domain folder alike). So the
# document root is a real folder: the live release's public/ files are copied into it, and its index.php loads the
# application from that release's real path. Run after every switch of ~/apps/smukn-<target>/current.
# Usage on the server: bash publish-docroot.sh <document root> <app dir, e.g. $HOME/apps/smukn-production>
set -euo pipefail
D=${1:?document root}; APPD=${2:?app dir}
REL=$(readlink -f "$APPD/current") || { echo "no current release at $APPD"; exit 3; }
[ -f "$REL/public/index.php" ] || { echo "release $REL has no public/index.php"; exit 3; }
[ -d "$D" ] && [ ! -L "$D" ] || { echo "document root $D is not a folder"; exit 3; }
# only a folder this script published before, or a brand-new empty one, is ever written to
[ -f "$D/.smukn-docroot" ] || [ -z "$(ls -A "$D")" ] || { echo "$D holds another site: refusing to publish into it"; exit 3; }
printf '%s\n' "$APPD" > "$D/.smukn-docroot"
rsync -a --delete --exclude /.smukn-docroot --exclude /index.php "$REL/public/" "$D/"
# index.php: every __DIR__.'/..' (the release root) becomes the release's absolute path
sed "s#__DIR__\.'/\.\.#'$REL#g" "$REL/public/index.php" > "$D/.index.php.new"
if grep -q '__DIR__' "$D/.index.php.new" || ! grep -qF "'$REL/vendor/autoload.php'" "$D/.index.php.new"; then
  rm -f "$D/.index.php.new"; echo "could not rewrite index.php for $REL"; exit 3
fi
chmod 644 "$D/.index.php.new"; mv -f "$D/.index.php.new" "$D/index.php"; chmod 755 "$D"
echo "document root $D publishes $REL"
