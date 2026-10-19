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

if [ "${APP_ENV:-production}" = "production" ] && { [ "${DB_CONNECTION:-}" = "mysql" ] || [ "${DB_CONNECTION:-}" = "mariadb" ]; } && [ -z "${MYSQL_ATTR_SSL_CA:-}" ]; then
  echo "Production MySQL/MariaDB requires MYSQL_ATTR_SSL_CA to reference a mounted CA certificate file." >&2
  exit 1
fi

if [ -n "${MYSQL_ATTR_SSL_CA:-}" ] && [ ! -r "${MYSQL_ATTR_SSL_CA}" ]; then
  echo "MYSQL_ATTR_SSL_CA must be the readable path of a mounted CA certificate file." >&2
  exit 1
fi

if [ "${APP_ENV:-production}" = "production" ] && { [ "${DB_CONNECTION:-}" = "mysql" ] || [ "${DB_CONNECTION:-}" = "mariadb" ]; }; then
  php artisan database:startup-health --no-interaction || exit 1
fi

# Production migrations are intentionally run as an approved one-off operation,
# never as an implicit side effect of a web-service restart or deploy.
php artisan storage:link --ansi || true

# Apache provides concurrent request handling. The PHP built-in server is
# single-process and lets a slow upstream (AI or database) stall every page.
render_port="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${render_port}/" /etc/apache2/ports.conf
# The vhost must match the dynamic Render port too. Without this replacement,
# Apache falls back to its default /var/www/html document root and returns 403.
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${render_port}>/" /etc/apache2/sites-available/000-default.conf
apache2ctl configtest
exec apache2-foreground
