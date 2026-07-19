FROM node:20-bookworm-slim AS frontend-build
WORKDIR /app/frontend

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

FROM php:8.2-cli-bookworm AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libonig-dev libsqlite3-dev libzip-dev libxml2-dev \
    && docker-php-ext-install intl mbstring pdo_sqlite zip xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app/backend
COPY backend/ ./

RUN set -eux; \
    mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache; \
    touch database/database.sqlite; \
    printf '%s\n' \
        'APP_NAME="AI Smart Campus System"' \
        'APP_ENV=production' \
        'APP_KEY=' \
        'APP_DEBUG=false' \
        'APP_URL=https://ai-smart-campus-system.onrender.com' \
        'APP_LOCALE=en' \
        'APP_FALLBACK_LOCALE=en' \
        'BCRYPT_ROUNDS=12' \
        'LOG_CHANNEL=stderr' \
        'LOG_LEVEL=warning' \
        'DB_CONNECTION=sqlite' \
        'DB_DATABASE=/app/backend/database/database.sqlite' \
        'SESSION_DRIVER=file' \
        'SESSION_LIFETIME=120' \
        'SESSION_ENCRYPT=true' \
        'SESSION_SECURE_COOKIE=true' \
        'SESSION_SAME_SITE=lax' \
        'CACHE_STORE=file' \
        'QUEUE_CONNECTION=sync' \
        'FILESYSTEM_DISK=public' \
        'CORS_ALLOWED_ORIGINS=https://ai-smart-campus-system.onrender.com' \
        'CORS_MAX_AGE=3600' \
        'CORS_SUPPORTS_CREDENTIALS=false' \
        'API_RATE_LIMIT=60' \
        'AUTH_RATE_LIMIT=5' \
        'MAIL_MAILER=log' \
        'MAIL_FROM_ADDRESS=noreply@example.com' \
        'MAIL_FROM_NAME="${APP_NAME}"' \
        'NOTICE_EMAIL_ENABLED=false' \
        'NOTICE_SMS_ENABLED=false' \
        'NOTICE_SMS_URL=' \
        'NOTICE_SMS_TOKEN=' \
        'NOTICE_SMS_SENDER=NUBTK' \
        'OPENAI_API_KEY=' \
        'OPENAI_MODEL=gpt-4.1-mini' \
        'OPENAI_TIMEOUT=30' \
        > .env; \
    composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts; \
    php artisan key:generate --force --ansi; \
    php artisan package:discover --ansi; \
    php artisan migrate --force --ansi; \
    php artisan storage:link --ansi || true; \
    chmod -R ug+rwX storage bootstrap/cache database

COPY --from=frontend-build /app/frontend/dist/ ./public/
COPY router.php /app/router.php
COPY start-render.sh /app/start-render.sh

RUN chmod +x /app/start-render.sh

EXPOSE 10000

CMD ["/app/start-render.sh"]
