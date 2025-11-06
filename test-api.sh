#!/bin/bash

# API Testing Script for AgriSiti LMS
# This script tests all API endpoints locally

BASE_URL="http://localhost:8000/api"
TOKEN=""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== AgriSiti LMS API Testing ===${NC}\n"

# Function to make API request
api_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local auth=$4

    if [ "$auth" = "true" ]; then
        if [ -z "$TOKEN" ]; then
            echo -e "${RED}Error: No token available. Please login first.${NC}"
            return 1
        fi
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -H "Authorization: Bearer $TOKEN" \
            -d "$data" \
            "$BASE_URL$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "$data" \
            "$BASE_URL$endpoint")
    fi

    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')

    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}✓${NC} $method $endpoint (HTTP $http_code)"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    else
        echo -e "${RED}✗${NC} $method $endpoint (HTTP $http_code)"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    fi
    echo ""
}

# Test 1: Register User
echo -e "${YELLOW}1. Testing User Registration...${NC}"
register_data='{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}'
result=$(api_request "POST" "/register" "$register_data" "false")
TOKEN=$(echo "$result" | jq -r '.token' 2>/dev/null)
if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo -e "${GREEN}Token saved: ${TOKEN:0:20}...${NC}\n"
fi

# Test 2: Login
echo -e "${YELLOW}2. Testing User Login...${NC}"
login_data='{
  "email": "test@example.com",
  "password": "password123"
}'
result=$(api_request "POST" "/login" "$login_data" "false")
TOKEN=$(echo "$result" | jq -r '.token' 2>/dev/null)
if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo -e "${GREEN}Token saved: ${TOKEN:0:20}...${NC}\n"
fi

# Test 3: Get Current User
echo -e "${YELLOW}3. Testing Get Current User...${NC}"
api_request "GET" "/user" "" "true"

# Test 4: Get All Categories
echo -e "${YELLOW}4. Testing Get All Categories...${NC}"
api_request "GET" "/categories" "" "false"

# Test 5: Get Categories with Courses
echo -e "${YELLOW}5. Testing Get Categories with Courses...${NC}"
api_request "GET" "/categories-with-courses" "" "false"

# Test 6: Get All Courses
echo -e "${YELLOW}6. Testing Get All Courses...${NC}"
api_request "GET" "/courses" "" "false"

# Test 7: Get Single Course (if exists)
echo -e "${YELLOW}7. Testing Get Single Course...${NC}"
api_request "GET" "/courses/1" "" "false"

# Test 8: Enroll in Course
echo -e "${YELLOW}8. Testing Enroll in Course...${NC}"
enroll_data='{
  "course_id": 1
}'
api_request "POST" "/enroll" "$enroll_data" "true"

# Test 9: Get My Enrollments
echo -e "${YELLOW}9. Testing Get My Enrollments...${NC}"
api_request "GET" "/my-enrollments" "" "true"

# Test 10: Get My Courses
echo -e "${YELLOW}10. Testing Get My Courses...${NC}"
api_request "GET" "/my-courses" "" "true"

# Test 11: Get Course Progress
echo -e "${YELLOW}11. Testing Get Course Progress...${NC}"
api_request "GET" "/courses/1/progress" "" "true"

# Test 12: Mark Topic as Complete
echo -e "${YELLOW}12. Testing Mark Topic as Complete...${NC}"
api_request "POST" "/topics/1/complete" "" "true"

# Test 13: Get Course Notes
echo -e "${YELLOW}13. Testing Get Course Notes...${NC}"
api_request "GET" "/courses/1/notes" "" "true"

# Test 14: Create Note
echo -e "${YELLOW}14. Testing Create Note...${NC}"
note_data='{
  "course_id": 1,
  "topic_id": 1,
  "notes": "This is a test note",
  "timestamp_seconds": 120,
  "is_public": false
}'
api_request "POST" "/notes" "$note_data" "true"

# Test 15: Get Course Assignments
echo -e "${YELLOW}15. Testing Get Course Assignments...${NC}"
api_request "GET" "/courses/1/assignments" "" "true"

# Test 16: Get My Submissions
echo -e "${YELLOW}16. Testing Get My Submissions...${NC}"
api_request "GET" "/my-submissions" "" "true"

# Test 17: Get Course Messages
echo -e "${YELLOW}17. Testing Get Course Messages...${NC}"
api_request "GET" "/courses/1/messages" "" "true"

# Test 18: Send Message
echo -e "${YELLOW}18. Testing Send Message...${NC}"
message_data='{
  "course_id": 1,
  "recipient_id": 2,
  "subject": "Test Message",
  "message": "This is a test message"
}'
api_request "POST" "/messages" "$message_data" "true"

# Test 19: Logout
echo -e "${YELLOW}19. Testing Logout...${NC}"
api_request "POST" "/logout" "" "true"

echo -e "${GREEN}=== API Testing Complete ===${NC}"

