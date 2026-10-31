#!/usr/bin/env bash
# Runs on the server (piped over SSH by .github/workflows/lead-drafts.yml). Generates one reply draft per lead still
# marked "new" (ops/lead-drafts.php), encrypts them with the backup passphrase exactly as ops/backup.sh encrypts
# backups, and deletes the plain text. Prints only the count and the encrypted file. Nothing is sent; no record changes.
# Inputs (environment): BACKUP_PASSPHRASE, CLASS_B64 (app/Support/LeadReplyDraft.php), GEN_B64 (ops/lead-drafts.php).
set -euo pipefail
umask 077
[ -n "${BACKUP_PASSPHRASE:-}" ] || { echo "BACKUP_PASSPHRASE is not set" >&2; exit 2; }
APP=~/apps/smukn-production/current
[ -d "$APP" ] || { echo "production is not bootstrapped" >&2; exit 2; }
PHP=""; for c in php83 php8.3 /opt/alt/php83/usr/bin/php php; do p=$(command -v "$c" 2>/dev/null) && "$p" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && { PHP=$p; break; }; done
[ -n "$PHP" ] || { echo "no PHP 8.3+ command-line binary found" >&2; exit 3; }
W=$(mktemp -d ~/lead-drafts.XXXXXX); trap 'rm -rf "$W"' EXIT
mkdir "$W/drafts"
printf '%s' "$CLASS_B64" | base64 -d > "$W/LeadReplyDraft.php"
printf '%s' "$GEN_B64" | base64 -d > "$W/gen.php"
# only the "drafts=N" line may reach the log: a PHP error message could quote data
( cd "$APP" && "$PHP" "$W/gen.php" "$W/LeadReplyDraft.php" "$W/drafts" 2>/dev/null ) | grep -E '^drafts=[0-9]+$' || { echo "draft generation failed (details withheld from the log)"; exit 4; }
OUT=~/lead-drafts-out; mkdir -p "$OUT"; chmod 700 "$OUT"
NAME=lead-drafts-$(date -u +%Y%m%dT%H%M%SZ)
tar -C "$W" -cf - drafts | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$OUT/$NAME.tar.enc"
( cd "$OUT" && sha256sum "$NAME.tar.enc" > "$NAME.tar.enc.sha256" )
echo "file=$OUT/$NAME.tar.enc"
