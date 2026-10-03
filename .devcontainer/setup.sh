#!/usr/bin/env bash
# One-shot preview environment (GitHub Codespaces or any devcontainer): installs, migrates and seeds the demo database,
# builds the front end. Demo accounts: student@example.test / Testpass12345, admin@example.test / Adminpass12345.
# Nothing here touches production; the database is local SQLite.
set -euo pipefail
sudo apt-get update -qq && sudo apt-get install -y -qq libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev libzip-dev >/dev/null
sudo docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp >/dev/null 2>&1 || true
sudo docker-php-ext-install -j"$(nproc)" gd zip bcmath >/dev/null 2>&1 || true
composer install --no-interaction --prefer-dist --no-progress
npm ci --no-audit --no-fund
[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
touch database/database.sqlite
php artisan migrate --seed --force
npm run build
echo "Preview ready: open the forwarded port 8000 (Ports tab) in your browser."
