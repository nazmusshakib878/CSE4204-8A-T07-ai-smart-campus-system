#!/bin/sh
set -eu

cd /app/backend

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
touch database/database.sqlite
chmod -R ug+rwX storage bootstrap/cache database

php artisan migrate --force --ansi
php artisan db:seed --force --ansi
php artisan storage:link --ansi || true

exec php -S "0.0.0.0:${PORT:-10000}" -t /app/backend/public /app/router.php
