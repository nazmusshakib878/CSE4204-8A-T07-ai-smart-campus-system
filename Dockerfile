FROM node:20-bookworm-slim AS frontend-build
WORKDIR /app/frontend

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

FROM php:8.2-apache-bookworm AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libmariadb-dev libonig-dev libsqlite3-dev libzip-dev libxml2-dev \
    && docker-php-ext-install intl mbstring pdo_mysql pdo_sqlite zip xml \
    && php -r 'if (! extension_loaded("pdo_mysql")) { fwrite(STDERR, "pdo_mysql was not enabled\\n"); exit(1); }' \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app/backend
COPY backend/ ./

RUN set -eux; \
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache; \
    composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts; \
    php artisan package:discover --ansi; \
    php artisan storage:link --ansi || true; \
    chmod -R ug+rwX storage bootstrap/cache

COPY --from=frontend-build /app/frontend/dist/ ./public/
COPY Spring_2026_A_Sections_Official_Routine.csv /app/Spring_2026_A_Sections_Official_Routine.csv
COPY router.php /app/router.php
COPY start-render.sh /app/start-render.sh

RUN chmod +x /app/start-render.sh

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

EXPOSE 10000

CMD ["/app/start-render.sh"]
