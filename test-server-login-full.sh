#!/bin/bash

BASE_URL="https://academy-backends.agrisiti.com"

echo "=== Full Admin Login Test on Live Server ==="
echo ""

# Get login page and extract CSRF token
echo "1. Getting CSRF token..."
LOGIN_HTML=$(curl -s -k -c /tmp/cookies.txt "$BASE_URL/admin/login")
CSRF_TOKEN=$(echo "$LOGIN_HTML" | grep -o 'name="_token" value="[^"]*"' | sed 's/.*value="\([^"]*\)".*/\1/')

if [ -z "$CSRF_TOKEN" ]; then
    echo "   ❌ Could not extract CSRF token"
    echo "   Trying alternative method..."
    CSRF_TOKEN=$(echo "$LOGIN_HTML" | grep '_token' | head -1 | sed 's/.*value="\([^"]*\)".*/\1/')
fi

if [ -z "$CSRF_TOKEN" ]; then
    echo "   ❌ Still no CSRF token found"
    exit 1
fi

echo "   ✓ CSRF Token: ${CSRF_TOKEN:0:20}..."
echo ""

# Attempt login with CSRF token
echo "2. Attempting login with credentials..."
LOGIN_RESPONSE=$(curl -s -k -b /tmp/cookies.txt -c /tmp/cookies.txt -L \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Referer: $BASE_URL/admin/login" \
    -d "email=admin@agrisiti.com" \
    -d "password=admin123" \
    -d "_token=$CSRF_TOKEN" \
    -w "\nHTTP_CODE:%{http_code}\n" 2>&1)

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Login HTTP code: $HTTP_CODE"
echo ""

# Check if we got redirected to dashboard
if echo "$LOGIN_RESPONSE" | grep -q "admin" && [ "$HTTP_CODE" = "200" ]; then
    echo "   ✓ Login appears successful (200 OK)"
elif [ "$HTTP_CODE" = "302" ]; then
    REDIRECT=$(echo "$LOGIN_RESPONSE" | grep -i "location:" | head -1)
    echo "   Redirect: $REDIRECT"
fi

echo ""

# Try to access dashboard
echo "3. Testing dashboard access..."
DASHBOARD_RESPONSE=$(curl -s -k -b /tmp/cookies.txt -L \
    -w "\nHTTP_CODE:%{http_code}\n" \
    "$BASE_URL/admin" 2>&1)

DASHBOARD_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Dashboard HTTP code: $DASHBOARD_CODE"
echo ""

if [ "$DASHBOARD_CODE" = "403" ]; then
    echo "❌ 403 FORBIDDEN!"
    echo ""
    echo "Response body (first 500 chars):"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | head -c 500
    echo ""
    echo ""
    echo "Full response saved to /tmp/dashboard_response.html"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' > /tmp/dashboard_response.html
elif [ "$DASHBOARD_CODE" = "200" ]; then
    echo "✓ SUCCESS: Dashboard accessible!"
    echo "   Response length: $(echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | wc -c) bytes"
elif [ "$DASHBOARD_CODE" = "302" ]; then
    LOCATION=$(echo "$DASHBOARD_RESPONSE" | grep -i "location:" | head -1)
    echo "⚠️  REDIRECT: $LOCATION"
else
    echo "⚠️  HTTP Code: $DASHBOARD_CODE"
fi

echo ""
echo "=== Test Complete ==="

rm -f /tmp/cookies.txt





