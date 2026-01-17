#!/bin/bash

echo "=== Checking Session Configuration ==="
echo ""

# Check session driver
echo "1. Session driver in .env:"
grep "SESSION_DRIVER" /var/www/laravel/.env || echo "   Not found in .env"

echo ""

# Check session files directory
echo "2. Session files directory:"
if [ -d "/var/www/laravel/storage/framework/sessions" ]; then
    echo "   ✓ Directory exists"
    ls -la /var/www/laravel/storage/framework/sessions | head -5
    echo ""
    echo "   Permissions:"
    ls -ld /var/www/laravel/storage/framework/sessions
else
    echo "   ❌ Directory does not exist!"
fi

echo ""

# Check cookie domain
echo "3. Cookie/Session domain settings:"
grep -E "SESSION_DOMAIN|SANCTUM_STATEFUL_DOMAINS" /var/www/laravel/.env || echo "   Not found"

echo ""

# Check if sessions are being created
echo "4. Recent session files:"
find /var/www/laravel/storage/framework/sessions -type f -mmin -10 2>/dev/null | wc -l | xargs echo "   Session files created in last 10 minutes:"

echo ""
echo "=== Check Complete ==="

