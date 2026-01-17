#!/bin/bash

BASE_URL="https://academy-backends.agrisiti.com"

echo "=== Testing Admin Login on Live Server ==="
echo "Server: $BASE_URL"
echo ""

# Test 1: Check if login page is accessible
echo "1. Testing login page access..."
LOGIN_STATUS=$(curl -s -k -o /dev/null -w "%{http_code}" "$BASE_URL/admin/login")
echo "   Login page HTTP code: $LOGIN_STATUS"
echo ""

# Test 2: Try to access dashboard without login
echo "2. Testing dashboard access without login..."
DASHBOARD_STATUS=$(curl -s -k -o /dev/null -w "%{http_code}" "$BASE_URL/admin")
echo "   Dashboard HTTP code (no auth): $DASHBOARD_STATUS"
echo ""

# Test 3: Get login page
echo "3. Getting login page..."
curl -s -k -c /tmp/server_cookies.txt "$BASE_URL/admin/login" > /tmp/server_login.html
echo "   Login page retrieved ($(wc -l < /tmp/server_login.html) lines)"
echo ""

# Test 4: Try to login (without CSRF for now to see what happens)
echo "4. Attempting login (POST request)..."
LOGIN_RESPONSE=$(curl -s -k -b /tmp/server_cookies.txt -c /tmp/server_cookies.txt \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Referer: $BASE_URL/admin/login" \
    -d "email=admin@agrisiti.com" \
    -d "password=admin123" \
    -w "\nHTTP_CODE:%{http_code}\n" 2>&1)

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Login POST HTTP code: $HTTP_CODE"
echo ""

# Test 5: Try to access dashboard after login attempt
echo "5. Testing dashboard access after login attempt..."
DASHBOARD_RESPONSE=$(curl -s -k -b /tmp/server_cookies.txt \
    -w "\nHTTP_CODE:%{http_code}\n" \
    "$BASE_URL/admin" 2>&1)

DASHBOARD_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Dashboard HTTP code (after login): $DASHBOARD_CODE"
echo ""

if [ "$DASHBOARD_CODE" = "403" ]; then
    echo "❌ 403 FORBIDDEN detected!"
    echo ""
    echo "Response body:"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | head -50
    echo ""
    echo "Checking for specific error messages..."
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | grep -i "forbidden\|unauthorized\|access\|denied\|403" | head -10
elif [ "$DASHBOARD_CODE" = "302" ] || [ "$DASHBOARD_CODE" = "301" ]; then
    echo "⚠️  REDIRECT ($DASHBOARD_CODE): Being redirected"
    LOCATION=$(echo "$DASHBOARD_RESPONSE" | grep -i "location:" | head -1)
    echo "   $LOCATION"
elif [ "$DASHBOARD_CODE" = "200" ]; then
    echo "✓ SUCCESS: Dashboard accessible!"
else
    echo "⚠️  Unexpected HTTP Code: $DASHBOARD_CODE"
    echo "   Response preview:"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | head -30
fi

echo ""
echo "=== Test Complete ==="

# Cleanup
rm -f /tmp/server_cookies.txt /tmp/server_login.html

