#!/bin/bash

BASE_URL="http://127.0.0.1:8000"

echo "=== Testing New Admin Login Locally ==="
echo ""

# Get login page
echo "1. Getting login page..."
LOGIN_HTML=$(curl -s -c /tmp/test_cookies.txt "$BASE_URL/admin/login")
echo "   ✓ Login page retrieved"
echo ""

# Extract CSRF token
CSRF_TOKEN=$(echo "$LOGIN_HTML" | grep -o 'name="_token" value="[^"]*"' | sed 's/.*value="\([^"]*\)".*/\1/')

if [ -z "$CSRF_TOKEN" ]; then
    echo "   ❌ Could not extract CSRF token"
    exit 1
fi

echo "2. CSRF Token: ${CSRF_TOKEN:0:20}..."
echo ""

# Attempt login
echo "3. Attempting login with testadmin@agrisiti.com..."
LOGIN_RESPONSE=$(curl -s -b /tmp/test_cookies.txt -c /tmp/test_cookies.txt -L \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Referer: $BASE_URL/admin/login" \
    -d "email=testadmin@agrisiti.com" \
    -d "password=test123" \
    -d "_token=$CSRF_TOKEN" \
    -w "\nHTTP_CODE:%{http_code}\n" 2>&1)

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Login HTTP code: $HTTP_CODE"
echo ""

# Try to access dashboard
echo "4. Testing dashboard access..."
DASHBOARD_RESPONSE=$(curl -s -b /tmp/test_cookies.txt -L \
    -w "\nHTTP_CODE:%{http_code}\n" \
    "$BASE_URL/admin" 2>&1)

DASHBOARD_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Dashboard HTTP code: $DASHBOARD_CODE"
echo ""

if [ "$DASHBOARD_CODE" = "403" ]; then
    echo "❌ 403 FORBIDDEN detected!"
    echo ""
    echo "Response body (first 500 chars):"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | head -c 500
    echo ""
elif [ "$DASHBOARD_CODE" = "200" ]; then
    echo "✓ SUCCESS: Dashboard accessible!"
    echo "   Response length: $(echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | wc -c) bytes"
elif [ "$DASHBOARD_CODE" = "302" ]; then
    LOCATION=$(echo "$DASHBOARD_RESPONSE" | grep -i "location:" | head -1)
    echo "⚠️  REDIRECT: $LOCATION"
    if echo "$LOCATION" | grep -q "login"; then
        echo "   → Being redirected back to login (authentication failed)"
    fi
else
    echo "⚠️  HTTP Code: $DASHBOARD_CODE"
    echo "   Response preview:"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | head -30
fi

echo ""
echo "=== Test Complete ==="

rm -f /tmp/test_cookies.txt





