#!/usr/bin/env bash
# StudyMedicineUKNigeria.com — read-only inspection of the Hostinger account.
# Usage (from a machine that can reach the host):
#   ssh -p <PORT> <USER>@<HOST> 'bash -s' < ops/inspect-hostinger.sh > ops/reports/inspect-$(date +%F).txt
# This script only READS. It never modifies, deletes or restarts anything.
set -u
hr(){ printf '\n==== %s ====\n' "$1"; }

hr "1. SERVER ENVIRONMENT"; uname -a; cat /etc/os-release 2>/dev/null | head -3; echo "user=$(whoami) home=$HOME"; echo "date=$(date -u)"; (df -h "$HOME" | tail -1); echo "shell=$SHELL"
hr "3. PHP"; which php php83 php8.3 php8.2 2>/dev/null; php -v 2>/dev/null | head -1; php -m 2>/dev/null | tr '\n' ' ' | fold -w 160; echo; php -r 'echo "memory_limit=".ini_get("memory_limit")." upload_max=".ini_get("upload_max_filesize")." post_max=".ini_get("post_max_size")." max_exec=".ini_get("max_execution_time")."\n";' 2>/dev/null
which composer node npm git mysql rsync 2>/dev/null; composer --version 2>/dev/null | head -1; node -v 2>/dev/null; git --version 2>/dev/null
hr "4. DOCUMENT ROOT(S)"; ls -la "$HOME"; for d in "$HOME"/domains/*/public_html "$HOME"/public_html; do [ -d "$d" ] && { echo "--- $d"; ls -la "$d" | head -60; }; done
hr "5. EXISTING FILES (tree, 3 levels, excluding vendor/node_modules)"; for d in "$HOME"/domains/*/ "$HOME"/public_html; do [ -d "$d" ] && find "$d" -maxdepth 3 -not -path '*/vendor/*' -not -path '*/node_modules/*' -not -path '*/.git/*' 2>/dev/null | head -400; done
echo "--- sizes"; du -sh "$HOME"/domains/* "$HOME"/public_html 2>/dev/null
hr "2. CMS / FRAMEWORK DETECTION"; for d in "$HOME"/domains/*/public_html "$HOME"/public_html; do [ -d "$d" ] || continue; echo "--- $d"; [ -f "$d/wp-config.php" ] && { echo "WordPress detected"; grep -E "^\s*\\\$table_prefix|DB_NAME|DB_HOST" "$d/wp-config.php" | sed -E "s/(define\('DB_(PASSWORD|USER)'.*)/[redacted]/"; grep -R "wp_version =" "$d/wp-includes/version.php" 2>/dev/null; ls "$d/wp-content/themes" "$d/wp-content/plugins" 2>/dev/null; }; [ -f "$d/../artisan" ] || [ -f "$d/artisan" ] && echo "Laravel detected"; [ -f "$d/index.html" ] && { echo "Static index.html:"; head -c 1500 "$d/index.html"; echo; }; [ -f "$d/index.php" ] && { echo "index.php head:"; head -c 800 "$d/index.php"; echo; }; [ -f "$d/.htaccess" ] && { echo ".htaccess:"; cat "$d/.htaccess"; }; [ -f "$d/composer.json" ] && head -c 800 "$d/composer.json"; done
# .env lines are limited to non-secret keys (never APP_KEY or passwords)
hr "6. DATABASE CONFIGURATION (credentials redacted)"; grep -RlE "DB_NAME|DB_DATABASE|mysqli_connect|PDO\(" "$HOME"/domains/*/public_html "$HOME"/public_html 2>/dev/null | head -20; for f in $(find "$HOME" -maxdepth 4 -name ".env" 2>/dev/null); do echo "--- $f"; grep -E "^(APP_(NAME|ENV|DEBUG|URL)|DB_(CONNECTION|HOST|PORT|DATABASE))=" "$f"; done; mysql --version 2>/dev/null; mysqldump --version 2>/dev/null
hr "7/8. SITE STRUCTURE AND HOMEPAGE (local request)"; curl -sS -o /dev/null -w "local https: %{http_code} %{redirect_url}\n" --max-time 15 https://studymedicineuknigeria.com/ 2>/dev/null; curl -sS --max-time 15 https://studymedicineuknigeria.com/ 2>/dev/null | head -c 3000; echo
hr "9. SEO CONFIG"; for d in "$HOME"/domains/*/public_html "$HOME"/public_html; do [ -d "$d" ] || continue; for f in robots.txt sitemap.xml sitemap_index.xml; do [ -f "$d/$f" ] && { echo "--- $d/$f"; head -40 "$d/$f"; }; done; done; curl -sS --max-time 15 https://studymedicineuknigeria.com/robots.txt 2>/dev/null | head -20
hr "10. ANALYTICS / TAGS"; grep -RhoE "(G-[A-Z0-9]{6,}|UA-[0-9]+-[0-9]+|GTM-[A-Z0-9]+|googletagmanager|gtag\(|clarity\.ms|fbq\()" "$HOME"/domains/*/public_html "$HOME"/public_html 2>/dev/null | sort | uniq -c | head
hr "11. SSL"; echo | timeout 10 openssl s_client -servername studymedicineuknigeria.com -connect studymedicineuknigeria.com:443 2>/dev/null | openssl x509 -noout -issuer -subject -dates 2>/dev/null
hr "12. DEPLOYMENT METHOD HINTS"; ls -la "$HOME"/.ssh 2>/dev/null | head; find "$HOME" -maxdepth 3 -name ".git" -type d 2>/dev/null | head; crontab -l 2>/dev/null || echo "no crontab"; ls "$HOME"/.hostinger* "$HOME"/hpanel* 2>/dev/null   # shell history is not read: commands can carry passwords
hr "15. BACKUP CANDIDATES (sizes)"; du -sh "$HOME"/domains/*/public_html "$HOME"/public_html "$HOME"/domains/*/private_html 2>/dev/null; ls -la "$HOME"/backups 2>/dev/null
hr "DONE (read-only)"
