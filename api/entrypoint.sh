#!/bin/sh

# Adjust permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Start PHP-FPM
php-fpm