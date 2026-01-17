#!/bin/bash

echo "=== Checking 403 Response Body ==="
echo ""

# Get the actual 403 response
echo "1. Getting 403 response from /admin:"
RESPONSE=$(curl -s -k "https://academy-backends.agrisiti.com/admin" 2>&1)
HTTP_CODE=$(curl -s -k -o /dev/null -w "%{http_code}" "https://academy-backends.agrisiti.com/admin")

echo "HTTP Code: $HTTP_CODE"
echo ""
echo "Response body (first 500 chars):"
echo "$RESPONSE" | head -c 500
echo ""
echo ""

# Check for specific error messages
echo "2. Looking for error messages:"
echo "$RESPONSE" | grep -i "forbidden\|unauthorized\|access\|denied\|403" | head -5

echo ""
echo "3. Full response saved to /tmp/403_response.html"
echo "$RESPONSE" > /tmp/403_response.html
echo "   View with: cat /tmp/403_response.html"

