#!/usr/bin/env bash
# Manual code rollback: repoint ~/apps/smukn-<target>/current to the release before it and rebuild caches.
# Usage: ops/rollback.sh user@host port staging|production [site_url]   (SSH_OPTS as for deploy.sh)
# The database is never restored automatically: migrations are add-only for one release (docs/architecture/21.4),
# and the script names the backup taken before the release being abandoned, for a deliberate restore (ops/RESTORE.md).
set -euo pipefail
HOST=${1:?user@host}; PORT=${2:?ssh port}; TARGET=${3:?staging|production}; SITE_URL=${4:-}
case "$TARGET" in staging|production) ;; *) echo "target must be staging or production"; exit 1;; esac
SSH="ssh -p $PORT ${SSH_OPTS:-}"; APP="~/apps/smukn-$TARGET"

# RESTORE_PREVIOUS_SITE=1: serve again the site that was live before the first launch (deploy.sh's cutover moved it
# aside, never deleted it). The SMUKN releases and database stay in place for a later relaunch.
if [ "${RESTORE_PREVIOUS_SITE:-0}" = 1 ]; then
  { printf 'TARGET=%q\n' "$TARGET"; cat <<'SITE'
set -euo pipefail
F="$HOME/apps/smukn-$TARGET/releases/.cutover"
[ -f "$F" ] || { echo "no recorded cutover: the document root was never switched by deploy.sh, nothing to restore"; exit 2; }
IFS="|" read -r D OLD < "$F"
[ -L "$D" ] || { echo "$D is not a link to a release: refusing to touch it"; exit 2; }
[ -e "$OLD" ] || { echo "previous site folder $OLD is missing: restore it from the docroot archive in ~/backups (ops/RESTORE.md)"; exit 2; }
rm "$D" && mv "$OLD" "$D" && mv "$F" "$F.reverted"
echo "previous site restored at $D (the SMUKN release is still in ~/apps; deploy again with cutover_docroot to relaunch)"
SITE
  } | $SSH "$HOST" bash -s
  exit 0
fi

$SSH "$HOST" bash -s <<REMOTE
set -euo pipefail
CUR=\$(readlink $APP/current 2>/dev/null || true); [ -n "\$CUR" ] || { echo "no current release on $TARGET"; exit 2; }
CUR=\${CUR%/}; PREV=""
# Only releases that went live are candidates (deploy.sh records them); a deploy that failed before the switch
# leaves a directory that was never served and must never become current.
H=$APP/releases/.history; [ -f "\$H" ] || { echo "no release history on $TARGET (deploy.sh writes releases/.history)"; exit 2; }
while read -r d; do d=\${d/#\~/\$HOME}; [ "\$d" = "\$CUR" ] && continue; [ -d "\$d" ] && PREV=\$d; done < <(grep -v '^$' "\$H" | awk -v c="\$CUR" '{ sub("^~", ENVIRON["HOME"]); if (\$0 == c) exit; print }')
[ -n "\$PREV" ] || { echo "no earlier live release of $TARGET to roll back to"; exit 2; }
PHP=""; for c in php83 php8.3 /opt/alt/php83/usr/bin/php php; do p=\$(command -v "\$c" 2>/dev/null) && "\$p" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && { PHP=\$p; break; }; done
[ -n "\$PHP" ] || { echo "no PHP 8.3+ command-line binary found (tried php83, php8.3, /opt/alt/php83/usr/bin/php, php)"; exit 3; }
ln -sfn "\$PREV" $APP/current
cd $APP/current && \$PHP artisan config:cache >/dev/null && \$PHP artisan route:cache >/dev/null && \$PHP artisan view:cache >/dev/null
echo "previous=\$CUR" > $APP/releases/.last_switch
echo "\$PREV" >> $APP/releases/.history
echo "rolled back $TARGET: \$(basename "\$CUR") -> \$(basename "\$PREV")"
B=\$(ls -1 ~/backups/$TARGET-db-\$(basename "\$CUR").* 2>/dev/null | head -1 || true)
[ -n "\$B" ] && echo "database backup taken before \$(basename "\$CUR") was deployed: \$B (restore only deliberately, see ops/RESTORE.md)" || echo "no database backup found for \$(basename "\$CUR")"
REMOTE

if [ -n "$SITE_URL" ]; then ops/smoke.sh "$SITE_URL" "$TARGET"; fi
