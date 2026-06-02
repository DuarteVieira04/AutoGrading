FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    git \
    unzip \
    curl \
    ca-certificates \
    gettext-base \
    python3 \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pdo_sqlite \
        zip \
        mbstring \
        xml \
        dom \
        bcmath \
        opcache \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/^;*clear_env = .*/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php-render.ini /usr/local/etc/php/conf.d/99-autograding.ini

WORKDIR /app

COPY . .

WORKDIR /app/submission-platform

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci \
    && npm run build \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

RUN chmod +x /app/docker/entrypoint-web.sh /app/docker/entrypoint-worker.sh

ENV AUTOGRADING_PROJECT_ROOT=/app \
    AUTOGRADING_PYTHON=python3 \
    AUTOGRADING_NODE_BINARY=/usr/bin/node \
    AUTOGRADING_NPM_BINARY=/usr/bin/npm \
    AUTOGRADING_ENABLED=true \
    AUTOGRADING_RUN_SYNC=false \
    AUTOGRADING_TIMEOUT=1900 \
    APP_ENV=production \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql \
    QUEUE_CONNECTION=database

EXPOSE 10000

CMD ["/app/docker/entrypoint-web.sh"]
