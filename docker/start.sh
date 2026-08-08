#!/bin/sh
set -e

# Ensure SQLite database file exists on the persistent disk
if [ ! -f "${DB_DATABASE}" ]; then
    echo "Creating SQLite database at ${DB_DATABASE}..."
    touch "${DB_DATABASE}"
fi

# storage/logs lives in the container's ephemeral filesystem by default, so
# every error would be silently lost on the next restart/redeploy. Point it
# at the persistent disk instead by symlinking it there.
DATA_DIR="$(dirname "${DB_DATABASE}")"
mkdir -p "${DATA_DIR}/logs"
rm -rf storage/logs
ln -s "${DATA_DIR}/logs" storage/logs

# php-fpm workers run as www-data, but this script runs as root, so the
# disk (db file, its WAL/journal siblings, and now logs) must be writable
# by www-data or every request will fail with "unable to open database file".
chown -R www-data:www-data "${DATA_DIR}"

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
