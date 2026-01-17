#!/bin/bash

echo "=== Checking File Permissions and Structure ==="
echo ""

# Check if /admin directory exists (it shouldn't - should be handled by Laravel routing)
echo "1. Checking if /admin directory exists in public:"
if [ -d "/var/www/laravel/public/admin" ]; then
    echo "   ❌ PROBLEM: /admin directory exists in public folder!"
    echo "   This will cause Nginx to try to serve it as a directory, which may be blocked"
    ls -la /var/www/laravel/public/admin
else
    echo "   ✓ No /admin directory (correct - should be handled by Laravel)"
fi

echo ""

# Check public/index.php permissions
echo "2. Checking public/index.php permissions:"
ls -la /var/www/laravel/public/index.php
if [ ! -r "/var/www/laravel/public/index.php" ]; then
    echo "   ❌ index.php is not readable!"
else
    echo "   ✓ index.php is readable"
fi

echo ""

# Check if there's a directory index issue
echo "3. Checking Nginx directory index setting:"
grep -i "index" /etc/nginx/sites-enabled/laravel | grep -v "^#"

echo ""

# Check PHP-FPM socket permissions
echo "4. Checking PHP-FPM socket:"
ls -la /var/run/php/php8.3-fpm.sock 2>/dev/null || echo "   Socket not found or not accessible"

echo ""

# Test if we can access index.php directly
echo "5. Testing direct access to index.php:"
curl -s -o /dev/null -w "HTTP Code: %{http_code}\n" http://localhost/index.php

echo ""
echo "=== Check Complete ==="

