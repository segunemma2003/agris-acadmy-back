#!/bin/bash

BASE_URL="http://127.0.0.1:8000"

echo "=== Testing Admin Login ==="
echo ""

# Test 1: Check if login page is accessible
echo "1. Testing login page access..."
LOGIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/admin/login")
echo "   Login page HTTP code: $LOGIN_STATUS"
echo ""

# Test 2: Try to access dashboard without login (should redirect or 403)
echo "2. Testing dashboard access without login..."
DASHBOARD_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/admin")
echo "   Dashboard HTTP code (no auth): $DASHBOARD_STATUS"
echo ""

# Test 3: Get login page and try to extract CSRF
echo "3. Getting login page HTML..."
curl -s -c /tmp/cookies.txt "$BASE_URL/admin/login" > /tmp/login.html
echo "   Login page retrieved"
echo ""

# Test 4: Try to login (we'll need to get CSRF token properly)
echo "4. Attempting login..."
# For now, let's just see what happens when we try
LOGIN_RESPONSE=$(curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt \
    -X POST "$BASE_URL/admin/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "email=admin@agrisiti.com" \
    -d "password=admin123" \
    -w "\nHTTP_CODE:%{http_code}\n" 2>&1)

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Login POST HTTP code: $HTTP_CODE"
echo ""

# Test 5: Try to access dashboard after login attempt
echo "5. Testing dashboard access after login attempt..."
DASHBOARD_RESPONSE=$(curl -s -b /tmp/cookies.txt \
    -w "\nHTTP_CODE:%{http_code}\n" \
    "$BASE_URL/admin" 2>&1)

DASHBOARD_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE:" | tail -1 | cut -d: -f2)
echo "   Dashboard HTTP code (after login): $DASHBOARD_CODE"

if [ "$DASHBOARD_CODE" = "403" ]; then
    echo ""
    echo "❌ 403 FORBIDDEN detected!"
    echo "   Response body:"
    echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | head -30
fi

echo ""
echo "=== Test Complete ==="

rm -f /tmp/cookies.txt /tmp/login.html



