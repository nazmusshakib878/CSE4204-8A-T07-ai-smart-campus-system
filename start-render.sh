#!/bin/sh
set -eu

cd /app/backend

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache database

if [ "${APP_ENV:-production}" = "production" ] && [ -z "${DB_CONNECTION:-}" ]; then
  echo "DB_CONNECTION must be supplied as a Render environment variable for production." >&2
  exit 1
fi

if [ "${APP_ENV:-production}" = "production" ] && [ "${DB_CONNECTION:-}" = "sqlite" ]; then
  echo "Production deployments must use a persistent managed database, not SQLite." >&2
  exit 1
fi

if [ "${APP_ENV:-production}" = "production" ] && ! php -r 'exit(extension_loaded("pdo_mysql") ? 0 : 1);'; then
  echo "pdo_mysql is not enabled in this image. Deploy an image built from the current Dockerfile before running migrations." >&2
  exit 1
fi

if [ -n "${MYSQL_ATTR_SSL_CA:-}" ] && [ ! -r "${MYSQL_ATTR_SSL_CA}" ]; then
  echo "MYSQL_ATTR_SSL_CA must be the readable path of a mounted CA certificate file." >&2
  exit 1
fi

# Production migrations are intentionally run as an approved one-off operation,
# never as an implicit side effect of a web-service restart or deploy.
php artisan storage:link --ansi || true

exec php -S "0.0.0.0:${PORT:-10000}" -t /app/backend/public /app/router.php
