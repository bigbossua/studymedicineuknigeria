#!/usr/bin/env bash
# Encrypted backup of one SMUKN target, run ON THE SERVER (fed over SSH by
# .github/workflows/backup-hostinger.yml, or by hand: TARGET=production bash ops/backup.sh).
#
#   SCOPE=db    -> database dump only (small; daily)
#   SCOPE=full  -> database dump + shared/.env + storage/app/private (student documents; weekly)
#
# Output: ~/backups/offsite/smukn-<target>-<scope>-<timestamp>.tar.enc (+ .sha256)
# Encryption: AES-256-CBC, PBKDF2 200k iterations, passphrase from $BACKUP_PASSPHRASE (never written to disk).
# The archive contains shared/.env because APP_KEY is required to read encrypted documents on restore.
set -euo pipefail

TARGET="${TARGET:-production}"
SCOPE="${SCOPE:-db}"
APP="$HOME/apps/smukn-$TARGET"
OUT="$HOME/backups/offsite"
TS="$(date -u +%Y%m%dT%H%M%SZ)"
NAME="smukn-$TARGET-$SCOPE-$TS"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/smukn-backup.XXXXXX")"
trap 'rm -rf "$WORK"' EXIT

[ -n "${BACKUP_PASSPHRASE:-}" ] || { echo "BACKUP_PASSPHRASE is not set" >&2; exit 2; }
[ -f "$APP/shared/.env" ] || { echo "no $APP/shared/.env – target '$TARGET' is not bootstrapped" >&2; exit 2; }
case "$SCOPE" in db|full) ;; *) echo "SCOPE must be db or full" >&2; exit 2;; esac
mkdir -p "$OUT" "$WORK/$NAME"

envval() { (grep -E "^$1=" "$APP/shared/.env" || true) | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB_CONNECTION="$(envval DB_CONNECTION)"; DB_HOST="$(envval DB_HOST)"; DB_PORT="$(envval DB_PORT)"
DB_DATABASE="$(envval DB_DATABASE)"; DB_USERNAME="$(envval DB_USERNAME)"; DB_PASSWORD="$(envval DB_PASSWORD)"

echo "target=$TARGET scope=$SCOPE release=$(readlink -f "$APP/current" 2>/dev/null | xargs -r basename)"

# 1. Database
if [ "${DB_CONNECTION:-mysql}" = "sqlite" ]; then
  cp "$APP/shared/storage/database.sqlite" "$WORK/$NAME/database.sqlite" 2>/dev/null || cp "$APP/current/database/database.sqlite" "$WORK/$NAME/database.sqlite"
else
  command -v mysqldump >/dev/null || { echo "mysqldump not found on this server" >&2; exit 3; }
  # credentials via a private defaults file so they never appear in `ps`
  CNF="$WORK/my.cnf"; umask 077
  printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}" "$DB_USERNAME" "$DB_PASSWORD" > "$CNF"
  mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --no-tablespaces "$DB_DATABASE" | gzip -6 > "$WORK/$NAME/database.sql.gz"
  rm -f "$CNF"
fi

# 2. Secrets and documents (full scope only)
if [ "$SCOPE" = "full" ]; then
  cp "$APP/shared/.env" "$WORK/$NAME/shared.env"
  if [ -d "$APP/shared/storage/app/private" ]; then
    tar -C "$APP/shared/storage/app" -czf "$WORK/$NAME/private-storage.tgz" private
  fi
fi

# 3. Manifest, archive, encrypt, checksum
{
  echo "name=$NAME"; echo "created_utc=$TS"; echo "target=$TARGET"; echo "scope=$SCOPE"; echo "host=$(hostname)"
  echo "release=$(readlink -f "$APP/current" 2>/dev/null | xargs -r basename)"
  echo "db_connection=${DB_CONNECTION:-mysql}"; echo "db_database=${DB_DATABASE:-}"
  echo "cipher=aes-256-cbc pbkdf2 iter=200000 salted"
  (cd "$WORK/$NAME" && ls -l --time-style=+%Y-%m-%dT%H:%M:%SZ)
} > "$WORK/$NAME/MANIFEST.txt"

tar -C "$WORK" -cf - "$NAME" | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$OUT/$NAME.tar.enc"
( cd "$OUT" && sha256sum "$NAME.tar.enc" > "$NAME.tar.enc.sha256" )
chmod 600 "$OUT/$NAME.tar.enc" "$OUT/$NAME.tar.enc.sha256"

# 4. Keep the local off-site staging area small: 7 days of db, 2 full copies
find "$OUT" -name "smukn-$TARGET-db-*.tar.enc*" -mtime +7 -delete 2>/dev/null || true
ls -1t "$OUT"/smukn-"$TARGET"-full-*.tar.enc 2>/dev/null | tail -n +3 | while read -r f; do rm -f "$f" "$f.sha256"; done

echo "backup=$OUT/$NAME.tar.enc"
echo "size=$(stat -c %s "$OUT/$NAME.tar.enc")"
cat "$OUT/$NAME.tar.enc.sha256"
