#!/bin/bash

# Test admin login with curl
BASE_URL="https://academy-backends.agrisiti.com"
# For local testing, use: BASE_URL="http://127.0.0.1:8000"

echo "=== Testing Admin Login with cURL ==="
echo ""

# Step 1: Get login page and extract CSRF token
echo "Step 1: Getting login page..."
LOGIN_PAGE=$(curl -s -c /tmp/cookies.txt "$BASE_URL/admin/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP 'name="_token" value="\K[^"]+' || echo "")

if [ -z "$CSRF_TOKEN" ]; then
    echo "❌ Could not extract CSRF token"
    echo "Response:"
    echo "$LOGIN_PAGE" | head -50
    exit 1
fi

echo "✓ CSRF Token: $CSRF_TOKEN"
echo ""

# Step 2: Attempt login
echo "Step 2: Attempting login..."
LOGIN_RESPONSE=$(curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Referer: $BASE_URL/admin/login" \
    -d "email=admin@agrisiti.com" \
    -d "password=admin123" \
    -d "_token=$CSRF_TOKEN" \
    -w "\nHTTP_CODE:%{http_code}\nREDIRECT_URL:%{redirect_url}\n")

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
REDIRECT_URL=$(echo "$LOGIN_RESPONSE" | grep "REDIRECT_URL:" | cut -d: -f2-)

echo "Login HTTP Code: $HTTP_CODE"
echo "Redirect URL: $REDIRECT_URL"
echo ""

# Step 3: Try to access dashboard
echo "Step 3: Attempting to access dashboard..."
DASHBOARD_RESPONSE=$(curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt \
    -w "\nHTTP_CODE:%{http_code}\n" \
    "$BASE_URL/admin")

DASHBOARD_HTTP_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
DASHBOARD_BODY=$(echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d')

echo "Dashboard HTTP Code: $DASHBOARD_HTTP_CODE"
echo ""

if [ "$DASHBOARD_HTTP_CODE" = "200" ]; then
    echo "✓ SUCCESS: Dashboard accessible!"
    echo "Response preview:"
    echo "$DASHBOARD_BODY" | head -20
elif [ "$DASHBOARD_HTTP_CODE" = "403" ]; then
    echo "❌ FORBIDDEN (403): Access denied"
    echo "Response body:"
    echo "$DASHBOARD_BODY"
    echo ""
    echo "Checking for error messages..."
    echo "$DASHBOARD_BODY" | grep -i "forbidden\|unauthorized\|access\|denied" | head -5
elif [ "$DASHBOARD_HTTP_CODE" = "302" ] || [ "$DASHBOARD_HTTP_CODE" = "301" ]; then
    echo "⚠️  REDIRECT: Being redirected (likely to login)"
    echo "Response:"
    echo "$DASHBOARD_BODY" | head -20
else
    echo "⚠️  Unexpected HTTP Code: $DASHBOARD_HTTP_CODE"
    echo "Response preview:"
    echo "$DASHBOARD_BODY" | head -30
fi

echo ""
echo "=== Test Complete ==="

# Cleanup
rm -f /tmp/cookies.txt

