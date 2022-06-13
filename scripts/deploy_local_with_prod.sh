#!/bin/bash
set -e

echo "Deployment started ..."

composer install
composer dump-autoload

php artisan optimize

# TODO: Uncomment when api.favorite issue will be resolved
# php artisan optimize

php artisan db:restore
php artisan migrate

echo "Deployment finished!"