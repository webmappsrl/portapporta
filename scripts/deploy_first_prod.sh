#!/bin/bash
set -e

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

# backup database

# Install composer dependencies
composer install  --no-interaction --prefer-dist --optimize-autoloader
php artisan nova:install

# Clear caches
php artisan config:clear
php artisan optimize

# Migration
php artisan migrate:fresh --force
php artisan db:seed UserSeeder
php artisan db:seed CompanySeeder
php artisan pap:esasync 4 http://apiesa.netseven.it

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
