#!/bin/sh
set -e

# Ensure SQLite database file exists on the persistent disk
if [ ! -f "${DB_DATABASE}" ]; then
    echo "Creating SQLite database at ${DB_DATABASE}..."
    touch "${DB_DATABASE}"
fi

echo "Running migrations..."
php artisan migrate --force

echo "Seeding users..."
php artisan db:seed --class=UserSeeder --force

echo "Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
exec nginx -g 'daemon off;'
