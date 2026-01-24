#!/bin/bash

echo "=== Checking Nginx Configuration for 403 Issues ==="
echo ""

# Check Nginx config files
echo "1. Checking Nginx site configuration..."
if [ -f "/etc/nginx/sites-enabled/default" ]; then
    echo "   Found: /etc/nginx/sites-enabled/default"
    echo "   Checking for admin-related rules:"
    grep -n "admin\|403\|deny\|allow" /etc/nginx/sites-enabled/default | grep -v "^#" | head -20
fi

if [ -f "/etc/nginx/sites-enabled/laravel" ]; then
    echo ""
    echo "   Found: /etc/nginx/sites-enabled/laravel"
    echo "   Checking for admin-related rules:"
    grep -n "admin\|403\|deny\|allow" /etc/nginx/sites-enabled/laravel | grep -v "^#" | head -20
fi

# Check for any location blocks that might block /admin
echo ""
echo "2. Checking for location blocks that might affect /admin..."
if [ -f "/etc/nginx/sites-enabled/default" ]; then
    grep -A 10 "location.*admin" /etc/nginx/sites-enabled/default | head -20
fi

# Check Nginx error logs
echo ""
echo "3. Recent Nginx error log entries (403/forbidden):"
sudo tail -100 /var/log/nginx/error.log | grep -i "403\|forbidden" | tail -10

# Check access logs for 403s
echo ""
echo "4. Recent 403 responses in access log:"
sudo tail -100 /var/log/nginx/access.log | grep " 403 " | tail -10

# Check if there are any security modules blocking
echo ""
echo "5. Checking for security modules..."
nginx -V 2>&1 | grep -i "security\|modsecurity" || echo "   No security modules found in nginx -V"

echo ""
echo "=== Check Complete ==="
echo ""
echo "To fix potential Nginx issues, check:"
echo "  1. No 'deny' rules for /admin path"
echo "  2. No security modules blocking requests"
echo "  3. Proper PHP-FPM configuration"
echo "  4. File permissions on Laravel files"





