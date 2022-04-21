#!/bin/bash
set -e

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

# Pull the latest version of the app
git reset --hard origin/develop


# Install composer dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan nova:install
# Clear the old cache
php artisan clear-compiled
php artisan config:cache
php artisan cache:clear

# Recreate cache
php artisan optimize

# Compile npm assets
# npm run prod

# Run database migrations
php artisan migrate --force

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
