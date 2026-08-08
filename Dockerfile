FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# System dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    sqlite-dev \
    dcron \
    git \
    zip \
    unzip \
    curl

# PHP extensions needed for Laravel + SQLite (matches render.yaml's
# DB_CONNECTION=sqlite — see the persistent disk mounted at /var/data).
# bcmath is used throughout this app's money/quantity math (FEFO batch
# allocation, sale totals, stock levels, credit ledgers — 36 call sites)
# and was missing entirely, so every page touching a product or sale
# 500'd the moment real data existed instead of an empty table.
RUN docker-php-ext-install pdo pdo_sqlite opcache pcntl bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . .

# Install PHP dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install and build frontend assets
RUN npm ci && npm run build

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Nginx + startup config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Daily backup: Render's persistent disk is only mountable by this one web
# service, so a separate Cron Job service can't reach the SQLite file — the
# schedule has to run inside this same container instead. crond reads this
# crontab directly; nothing in Laravel needs to trigger it.
COPY docker/crontab /etc/crontabs/root

EXPOSE 10000

CMD ["/start.sh"]
