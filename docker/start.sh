#!/bin/sh
set -e

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
