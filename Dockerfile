FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# System dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    sqlite-dev \
    git \
    zip \
    unzip \
    curl

# PHP extensions needed for Laravel + SQLite (matches render.yaml's
# DB_CONNECTION=sqlite — see the persistent disk mounted at /var/data)
RUN docker-php-ext-install pdo pdo_sqlite opcache pcntl

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

EXPOSE 10000

CMD ["/start.sh"]
