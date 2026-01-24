#!/bin/bash

echo "=== Fixing Production Configuration ==="
echo ""

cd /var/www/laravel

echo "1. Clearing all caches..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan optimize:clear

echo ""
echo "2. Checking .env settings..."
echo "   APP_ENV: $(grep '^APP_ENV=' .env | cut -d= -f2)"
echo "   APP_DEBUG: $(grep '^APP_DEBUG=' .env | cut -d= -f2)"
echo "   SESSION_DRIVER: $(grep '^SESSION_DRIVER=' .env | cut -d= -f2)"
echo "   SESSION_SECURE_COOKIE: $(grep '^SESSION_SECURE_COOKIE=' .env | cut -d= -f2 || echo 'not set')"
echo "   SESSION_DOMAIN: $(grep '^SESSION_DOMAIN=' .env | cut -d= -f2 || echo 'not set')"

echo ""
echo "3. For production, you should have:"
echo "   APP_ENV=production"
echo "   APP_DEBUG=false"
echo "   SESSION_SECURE_COOKIE=true (for HTTPS)"
echo "   SESSION_DOMAIN should match your domain or be empty"

echo ""
echo "4. After fixing, rebuild caches:"
echo "   sudo -u www-data php artisan config:cache"
echo "   sudo -u www-data php artisan route:cache"
echo "   sudo -u www-data php artisan view:cache"





