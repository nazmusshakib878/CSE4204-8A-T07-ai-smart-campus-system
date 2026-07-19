FROM node:20-bookworm-slim AS frontend-build
WORKDIR /app/frontend

COPY frontend/package-lock.json frontend/package.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

FROM composer:2 AS backend-vendor
WORKDIR /app/backend

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

FROM php:8.2-cli-bookworm AS runtime
WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libonig-dev libsqlite3-dev libzip-dev libxml2-dev \
    && docker-php-ext-install intl mbstring pdo_sqlite zip xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=backend-vendor /app/backend/vendor ./backend/vendor
COPY backend ./backend
COPY --from=frontend-build /app/frontend/dist/ ./backend/public/
COPY router.php ./router.php

WORKDIR /app/backend

RUN set -eux; \
    rm -f .env; \
    printf '%s\n' \
        'APP_NAME="AI Smart Campus System"' \
        'APP_ENV=production' \
        'APP_KEY=' \
        'APP_DEBUG=false' \
        'APP_URL=https://ai-smart-campus-system.onrender.com' \
        'APP_LOCALE=en' \
        'APP_FALLBACK_LOCALE=en' \
        'BCRYPT_ROUNDS=12' \
        '' \
        'LOG_CHANNEL=stack' \
        'LOG_STACK=daily' \
        'LOG_LEVEL=warning' \
        'LOG_DAILY_DAYS=30' \
        '' \
        'DB_CONNECTION=sqlite' \
        'DB_DATABASE=/app/backend/database/database.sqlite' \
        '' \
        'SESSION_DRIVER=file' \
        'SESSION_LIFETIME=120' \
        'SESSION_ENCRYPT=true' \
        'SESSION_SECURE_COOKIE=true' \
        'SESSION_SAME_SITE=lax' \
        'CACHE_STORE=file' \
        'QUEUE_CONNECTION=sync' \
        'FILESYSTEM_DISK=public' \
        '' \
        'CORS_ALLOWED_ORIGINS=https://ai-smart-campus-system.onrender.com' \
        'CORS_MAX_AGE=3600' \
        'CORS_SUPPORTS_CREDENTIALS=false' \
        'API_RATE_LIMIT=60' \
        'AUTH_RATE_LIMIT=5' \
        '' \
        'MAIL_MAILER=log' \
        'MAIL_FROM_ADDRESS=noreply@example.com' \
        'MAIL_FROM_NAME="${APP_NAME}"' \
        '' \
        'NOTICE_EMAIL_ENABLED=false' \
        'NOTICE_SMS_ENABLED=false' \
        'NOTICE_SMS_URL=' \
        'NOTICE_SMS_TOKEN=' \
        'NOTICE_SMS_SENDER=NUBTK' \
        '' \
        'OPENAI_API_KEY=' \
        'OPENAI_MODEL=gpt-4.1-mini' \
        'OPENAI_TIMEOUT=30' \
        > .env; \
    mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache; \
    touch database/database.sqlite; \
    php artisan key:generate --force --ansi; \
    php artisan migrate --force --ansi; \
    php artisan storage:link --ansi || true

EXPOSE 10000

CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-10000} -t /app/backend/public /app/router.php"]
