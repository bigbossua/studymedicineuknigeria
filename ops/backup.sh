#!/usr/bin/env bash
# Encrypted backup of one SMUKN target, run ON THE SERVER (fed over SSH by
# .github/workflows/backup-hostinger.yml, or by hand: TARGET=production bash ops/backup.sh).
#
#   SCOPE=db    -> database dump only (small; daily)
#   SCOPE=full  -> database dump + shared/.env + storage/app/private (student documents; weekly)
#   SCOPE=site  -> the site a domain serves today, before the first launch replaces it: every file under
#                  SITE_DOCROOT, plus its WordPress database when a wp-config.php is found (read, never printed)
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
case "$SCOPE" in db|full|site) ;; *) echo "SCOPE must be db, full or site" >&2; exit 2;; esac
mkdir -p "$OUT" "$WORK/$NAME"

if [ "$SCOPE" = site ]; then
  D="${SITE_DOCROOT:-}"; D="${D/#\~/$HOME}"; D="${D%/}"
  # "auto": the folder Hostinger serves for the domain, found rather than guessed; anything ambiguous stops here
  if [ "$D" = auto ]; then
    D=""; for c in "$HOME/domains/studymedicineuknigeria.com/public_html"; do [ -e "$c" ] && { D="$c"; break; }; done   # only this domain's folder: the account hosts many websites
    [ -n "$D" ] || { echo "auto: no document root found (looked for ~/domains/studymedicineuknigeria.com/public_html; pass the folder from the inspection report instead)"; exit 2; }
    echo "auto: document root is $D"
  fi

  [ -n "$D" ] || { echo "SITE_DOCROOT is required for SCOPE=site (the folder the domain serves, from the inspection report)" >&2; exit 2; }
  [ -e "$D" ] || { echo "$D does not exist" >&2; exit 2; }
  if [ -L "$D" ] && [ "$(readlink "$D")" = "$APP/current/public" ]; then echo "$D already serves the SMUKN release: use SCOPE=full" >&2; exit 2; fi
  tar -C "$(dirname "$D")" -czf "$WORK/$NAME/site-files.tgz" "$(basename "$D")"
  FILES=$(tar -tzf "$WORK/$NAME/site-files.tgz" | wc -l)
  WP=no
  if [ -f "$D/wp-config.php" ]; then
    WP=yes
    command -v mysqldump >/dev/null || { echo "mysqldump not found on this server" >&2; exit 3; }
    CNF="$WORK/my.cnf"; umask 077
    # wp-config.php is parsed as text (never executed); the credentials go straight into a private defaults file
    php -r '
      $c = file_get_contents($argv[1]); $v = [];
      foreach (["NAME", "USER", "PASSWORD", "HOST"] as $k) {
        if (preg_match("/define\\(\\s*[\x27\"]DB_".$k."[\x27\"]\\s*,\\s*([\x27\"])(.*?)\\1\\s*\\)/s", $c, $m)) { $v[$k] = stripslashes($m[2]); }
      }
      if (! isset($v["NAME"], $v["USER"])) { fwrite(STDERR, "wp-config.php: DB_NAME/DB_USER not found\n"); exit(1); }
      [$host, $port] = array_pad(explode(":", $v["HOST"] ?? "localhost", 2), 2, "3306");
      $esc = fn ($s) => "\"".addcslashes($s, "\\\"")."\"";
      file_put_contents($argv[2], "[client]\nhost=".$esc($host)."\nport=".(int) $port."\nuser=".$esc($v["USER"])."\npassword=".$esc($v["PASSWORD"] ?? "")."\n");
      file_put_contents($argv[3], $v["NAME"]);
    ' "$D/wp-config.php" "$CNF" "$WORK/dbname"
    mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --no-tablespaces "$(cat "$WORK/dbname")" | gzip -6 > "$WORK/$NAME/database.sql.gz"
    rm -f "$CNF"
    gzip -dc "$WORK/$NAME/database.sql.gz" | tail -1 | grep -q 'Dump completed' || { echo "WordPress database dump is incomplete" >&2; exit 3; }
  fi
  # an application site (e.g. Laravel) keeps its settings in a .env in or above the folder it serves: dump the database
  # it names and archive that application folder too (without vendor/ and node_modules/, which its installer recreates)
  if [ "$WP" = no ]; then
    for dir in "$D" "$(dirname "$D")" "$(dirname "$(dirname "$D")")"; do
      [ -f "$dir/.env" ] && grep -q '^DB_DATABASE=' "$dir/.env" || continue
      case "$dir" in "$HOME"|"$HOME/domains") continue;; esac
      WP="env:$dir"
      command -v mysqldump >/dev/null || { echo "mysqldump not found on this server" >&2; exit 3; }
      CNF="$WORK/my.cnf"; umask 077
      php -r '
        $v = [];
        foreach (file($argv[1], FILE_IGNORE_NEW_LINES) as $l) {
          if (preg_match("/^(DB_[A-Z]+)=(.*)$/", trim($l), $m)) { $v[$m[1]] = trim(trim($m[2]), "\x27\""); }
        }
        if (($v["DB_CONNECTION"] ?? "mysql") !== "mysql" && ($v["DB_CONNECTION"] ?? "") !== "mariadb") { fwrite(STDERR, "not a MySQL database\n"); exit(2); }
        $esc = fn ($s) => "\"".addcslashes($s, "\\\"")."\"";
        file_put_contents($argv[2], "[client]\nhost=".$esc($v["DB_HOST"] ?? "127.0.0.1")."\nport=".(int) ($v["DB_PORT"] ?? 3306)."\nuser=".$esc($v["DB_USERNAME"] ?? "")."\npassword=".$esc($v["DB_PASSWORD"] ?? "")."\n");
        file_put_contents($argv[3], $v["DB_DATABASE"] ?? "");
      ' "$dir/.env" "$CNF" "$WORK/dbname" || { WP="env:$dir (not MySQL; files only)"; break; }
      mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --no-tablespaces "$(cat "$WORK/dbname")" | gzip -6 > "$WORK/$NAME/database.sql.gz"
      rm -f "$CNF"
      gzip -dc "$WORK/$NAME/database.sql.gz" | tail -1 | grep -q 'Dump completed' || { echo "application database dump is incomplete" >&2; exit 3; }
      [ "$dir" != "$D" ] && tar -C "$(dirname "$dir")" --exclude="$(basename "$dir")/vendor" --exclude="$(basename "$dir")/node_modules" -czf "$WORK/$NAME/app-files.tgz" "$(basename "$dir")"
      break
    done
  fi
  {
    echo "name=$NAME"; echo "created_utc=$TS"; echo "target=$TARGET"; echo "scope=site"; echo "host=$(hostname)"
    echo "docroot=$D"; echo "files=$FILES"; echo "database=$WP"; echo "cipher=aes-256-cbc pbkdf2 iter=200000 salted"
    (cd "$WORK/$NAME" && ls -l --time-style=+%Y-%m-%dT%H:%M:%SZ)
  } > "$WORK/$NAME/MANIFEST.txt"
  tar -C "$WORK" -cf - "$NAME" | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$OUT/$NAME.tar.enc"
  ( cd "$OUT" && sha256sum "$NAME.tar.enc" > "$NAME.tar.enc.sha256" )
  chmod 600 "$OUT/$NAME.tar.enc" "$OUT/$NAME.tar.enc.sha256"
  echo "site backup: $FILES entries from $D, database: $WP"   # pre-launch copies are never pruned here
  echo "backup=$OUT/$NAME.tar.enc"; echo "size=$(stat -c %s "$OUT/$NAME.tar.enc")"; cat "$OUT/$NAME.tar.enc.sha256"
  exit 0
fi
[ -f "$APP/shared/.env" ] || { echo "no $APP/shared/.env – target '$TARGET' is not bootstrapped" >&2; exit 2; }

envval() { (grep -E "^$1=" "$APP/shared/.env" || true) | head -1 | cut -d= -f2- | sed -E -e "s/^'(.*)'\$/\\1/" -e 's/^"(.*)"$/\1/'; }
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
(ls -1t "$OUT"/smukn-"$TARGET"-full-*.tar.enc 2>/dev/null || true) | tail -n +3 | while read -r f; do rm -f "$f" "$f.sha256"; done

echo "backup=$OUT/$NAME.tar.enc"
echo "size=$(stat -c %s "$OUT/$NAME.tar.enc")"
cat "$OUT/$NAME.tar.enc.sha256"
