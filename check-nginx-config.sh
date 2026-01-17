#!/bin/bash

echo "=== Checking Nginx Configuration ==="
echo ""

# Check for any rules blocking /admin
echo "1. Checking for /admin rules in Nginx config..."
if [ -d "/etc/nginx/sites-enabled" ]; then
    grep -r "admin" /etc/nginx/sites-enabled/ 2>/dev/null | grep -v "^#" | head -10
else
    echo "   /etc/nginx/sites-enabled not found"
fi

echo ""

# Check Nginx error logs for 403 errors
echo "2. Checking Nginx error logs for recent 403 errors..."
sudo tail -50 /var/log/nginx/error.log | grep -i "403\|forbidden" | tail -10

echo ""

# Check if there are any deny rules
echo "3. Checking for deny/allow rules..."
if [ -d "/etc/nginx/sites-enabled" ]; then
    grep -r "deny\|allow" /etc/nginx/sites-enabled/ 2>/dev/null | grep -v "^#" | head -10
fi

echo ""
echo "=== Check Complete ==="

