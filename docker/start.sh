#!/bin/sh
set -e

# Ensure SQLite database file exists on the persistent disk
if [ ! -f "${DB_DATABASE}" ]; then
    echo "Creating SQLite database at ${DB_DATABASE}..."
    touch "${DB_DATABASE}"
fi

# php-fpm workers run as www-data, but this script runs as root, so the
# disk (and the db file/its WAL/journal siblings) must be writable by
# www-data or every request will fail with "unable to open database file".
chown -R www-data:www-data "$(dirname "${DB_DATABASE}")"

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
