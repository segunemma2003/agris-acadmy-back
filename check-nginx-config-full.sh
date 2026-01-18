#!/bin/bash

echo "=== Full Nginx Configuration Check ==="
echo ""

if [ -f "/etc/nginx/sites-enabled/laravel" ]; then
    echo "Full Nginx config for laravel site:"
    echo "-----------------------------------"
    cat /etc/nginx/sites-enabled/laravel
    echo ""
    echo "-----------------------------------"
    echo ""
    echo "Line 39 context (the 'deny all' rule):"
    sed -n '30,50p' /etc/nginx/sites-enabled/laravel
else
    echo "Laravel config file not found"
fi



