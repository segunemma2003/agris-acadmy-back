# Authentication API Documentation

## Base URL

```
http://your-domain.com/api
```

## Overview

The Authentication API provides endpoints for user registration, login, logout, and profile management. All endpoints return JSON responses.

---

## 1. Student Registration

### Endpoint

```
POST /api/register
```

### Description

Register a new student account. Upon successful registration, a welcome email is sent to the student's email address via queue (non-blocking).

### Authentication

Not required (Public endpoint)

### Request Body

```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "phone": "+1234567890"
}
```

### Request Parameters

| Parameter               | Type   | Required | Description                                              |
| ----------------------- | ------ | -------- | -------------------------------------------------------- |
| `name`                  | string | Yes      | Full name of the student (max 255 characters)            |
| `email`                 | string | Yes      | Valid email address (must be unique, max 255 characters) |
| `password`              | string | Yes      | Password (minimum 8 characters)                          |
| `password_confirmation` | string | Yes      | Password confirmation (must match password)              |
| `phone`                 | string | No       | Phone number (max 255 characters)                        |

### Success Response (201 Created)

```json
{
    "success": true,
    "message": "Student registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john.doe@example.com",
            "phone": "+1234567890",
            "role": "student",
            "avatar": null
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "token_type": "Bearer"
    }
}
```

### Error Responses

#### 422 Unprocessable Entity - Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."]
    }
}
```

#### Common Validation Errors

-   `name`: "The name field is required."
-   `email`: "The email field is required." / "The email has already been taken." / "The email must be a valid email address."
-   `password`: "The password field is required." / "The password must be at least 8 characters." / "The password confirmation does not match."

### Example cURL Request

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "phone": "+1234567890"
  }'
```

### Example JavaScript/Fetch

```javascript
const response = await fetch("http://localhost:8000/api/register", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    body: JSON.stringify({
        name: "John Doe",
        email: "john.doe@example.com",
        password: "SecurePassword123!",
        password_confirmation: "SecurePassword123!",
        phone: "+1234567890",
    }),
});

const data = await response.json();
console.log(data);
```

### Notes

-   Upon successful registration, a welcome email is automatically sent to the student's email address
-   The email is sent via queue (asynchronous), so registration is not blocked if email sending fails
-   The user account is automatically set to `is_active: true`
-   The user role is automatically set to `student`
-   The token returned can be used immediately for authenticated requests

---

## 2. Student Login

### Endpoint

```
POST /api/login
```

### Description

Authenticate a student and receive an access token for subsequent API requests.

### Authentication

Not required (Public endpoint)

### Request Body

```json
{
    "email": "john.doe@example.com",
    "password": "SecurePassword123!"
}
```

### Request Parameters

| Parameter  | Type   | Required | Description              |
| ---------- | ------ | -------- | ------------------------ |
| `email`    | string | Yes      | Registered email address |
| `password` | string | Yes      | User's password          |

### Success Response (200 OK)

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john.doe@example.com",
            "phone": "+1234567890",
            "role": "student",
            "avatar": null,
            "bio": null
        },
        "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "token_type": "Bearer"
    }
}
```

### Error Responses

#### 401 Unauthorized - Invalid Credentials

```json
{
    "success": false,
    "message": "The provided credentials are incorrect."
}
```

#### 403 Forbidden - Account Inactive

```json
{
    "success": false,
    "message": "Your account is inactive. Please contact support."
}
```

#### 422 Unprocessable Entity - Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required.",
            "The email must be a valid email address."
        ],
        "password": ["The password field is required."]
    }
}
```

### Example cURL Request

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePassword123!"
  }'
```

### Example JavaScript/Fetch

```javascript
const response = await fetch("http://localhost:8000/api/login", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    body: JSON.stringify({
        email: "john.doe@example.com",
        password: "SecurePassword123!",
    }),
});

const data = await response.json();

if (data.success) {
    // Store token for authenticated requests
    localStorage.setItem("token", data.data.token);
    console.log("Login successful:", data.data.user);
} else {
    console.error("Login failed:", data.message);
}
```

### Notes

-   Upon successful login, the `last_login_at` timestamp is updated
-   The token is a Laravel Sanctum token that should be included in subsequent requests
-   Tokens do not expire by default (can be configured)
-   The same user can have multiple active tokens

---

## 3. Get Current User

### Endpoint

```
GET /api/user
```

### Description

Get the authenticated user's profile information.

### Authentication

Required (Bearer token)

### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

### Success Response (200 OK)

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "role": "student",
    "avatar": null,
    "bio": null,
    "is_active": true,
    "last_login_at": "2025-01-15T10:30:00.000000Z",
    "created_at": "2025-01-10T08:00:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
}
```

### Error Responses

#### 401 Unauthorized - Invalid/Missing Token

```json
{
    "message": "Unauthenticated."
}
```

### Example cURL Request

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer 2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Accept: application/json"
```

### Example JavaScript/Fetch

```javascript
const token = localStorage.getItem("token");

const response = await fetch("http://localhost:8000/api/user", {
    method: "GET",
    headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
    },
});

const user = await response.json();
console.log("Current user:", user);
```

---

## 4. Logout

### Endpoint

```
POST /api/logout
```

### Description

Invalidate the current access token (logout the user).

### Authentication

Required (Bearer token)

### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

### Success Response (200 OK)

```json
{
    "message": "Logged out successfully"
}
```

### Error Responses

#### 401 Unauthorized - Invalid/Missing Token

```json
{
    "message": "Unauthenticated."
}
```

### Example cURL Request

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Accept: application/json"
```

### Example JavaScript/Fetch

```javascript
const token = localStorage.getItem("token");

const response = await fetch("http://localhost:8000/api/logout", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
    },
});

const data = await response.json();
console.log(data.message); // "Logged out successfully"

// Remove token from storage
localStorage.removeItem("token");
```

### Notes

-   Only the current token is invalidated
-   Other tokens for the same user remain active
-   To logout from all devices, you would need to delete all tokens

---

## 5. Update Profile

### Endpoint

```
PUT /api/user/profile
```

### Description

Update the authenticated user's profile information.

### Authentication

Required (Bearer token)

### Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "phone": "+9876543210",
    "bio": "I am a passionate student learning agriculture.",
    "avatar": "https://example.com/avatar.jpg",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
}
```

### Request Parameters

| Parameter               | Type   | Required | Description                                                         |
| ----------------------- | ------ | -------- | ------------------------------------------------------------------- |
| `name`                  | string | No       | Full name (max 255 characters)                                      |
| `email`                 | string | No       | Email address (must be unique if changed, max 255 characters)       |
| `phone`                 | string | No       | Phone number (max 255 characters)                                   |
| `bio`                   | string | No       | User biography (max 1000 characters)                                |
| `avatar`                | string | No       | Avatar URL (max 500 characters)                                     |
| `password`              | string | No       | New password (minimum 8 characters, requires password_confirmation) |
| `password_confirmation` | string | No       | Password confirmation (required if password is provided)            |

### Success Response (200 OK)

```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Updated",
            "email": "john.updated@example.com",
            "phone": "+9876543210",
            "bio": "I am a passionate student learning agriculture.",
            "avatar": "https://example.com/avatar.jpg",
            "role": "student"
        }
    }
}
```

### Error Responses

#### 422 Unprocessable Entity - Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."]
    }
}
```

### Example cURL Request

```bash
curl -X PUT http://localhost:8000/api/user/profile \
  -H "Authorization: Bearer 2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Updated",
    "bio": "I am a passionate student learning agriculture."
  }'
```

### Example JavaScript/Fetch

```javascript
const token = localStorage.getItem("token");

const response = await fetch("http://localhost:8000/api/user/profile", {
    method: "PUT",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    body: JSON.stringify({
        name: "John Updated",
        bio: "I am a passionate student learning agriculture.",
    }),
});

const data = await response.json();
console.log("Profile updated:", data);
```

---

## Authentication Flow

### Complete Registration and Login Flow

```javascript
// 1. Register a new user
const registerResponse = await fetch("http://localhost:8000/api/register", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    body: JSON.stringify({
        name: "John Doe",
        email: "john.doe@example.com",
        password: "SecurePassword123!",
        password_confirmation: "SecurePassword123!",
        phone: "+1234567890",
    }),
});

const registerData = await registerResponse.json();

if (registerData.success) {
    // Store token
    const token = registerData.data.token;
    localStorage.setItem("token", token);

    // Welcome email is automatically sent via queue

    // 2. Use token for authenticated requests
    const userResponse = await fetch("http://localhost:8000/api/user", {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
        },
    });

    const user = await userResponse.json();
    console.log("User profile:", user);
}
```

---

## Error Handling

### Common HTTP Status Codes

| Status Code | Description                                         |
| ----------- | --------------------------------------------------- |
| 200         | Success                                             |
| 201         | Created (Registration successful)                   |
| 401         | Unauthorized (Invalid credentials or missing token) |
| 403         | Forbidden (Account inactive)                        |
| 422         | Validation Error (Invalid input data)               |
| 500         | Internal Server Error                               |

### Error Response Format

```json
{
    "success": false,
    "message": "Error message here"
}
```

Or for validation errors:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

---

## Security Considerations

1. **Password Requirements**

    - Minimum 8 characters
    - Should include uppercase, lowercase, numbers, and special characters for better security

2. **Token Storage**

    - Store tokens securely (e.g., in localStorage, sessionStorage, or secure HTTP-only cookies)
    - Never expose tokens in URLs or logs
    - Implement token refresh mechanism if needed

3. **HTTPS**

    - Always use HTTPS in production
    - Never send credentials over unencrypted connections

4. **Rate Limiting**

    - Consider implementing rate limiting for login/registration endpoints
    - Prevent brute force attacks

5. **Email Verification** (Optional)
    - Consider adding email verification step before account activation
    - Send verification email after registration

---

## Testing

### Test Registration

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "TestPassword123!",
    "password_confirmation": "TestPassword123!",
    "phone": "+1234567890"
  }'
```

### Test Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPassword123!"
  }'
```

### Test Get Current User

```bash
# Replace {token} with actual token from login response
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Notes

-   All timestamps are in ISO 8601 format (UTC)
-   All endpoints return JSON responses
-   Content-Type header should be `application/json` for POST/PUT requests
-   Accept header should be `application/json` for all requests
-   Welcome emails are sent asynchronously via queue and do not block registration
-   User accounts are automatically set to active upon registration
-   All users registered through this endpoint have the role `student`

---

## Support

For issues or questions, please contact the Agrisiti Academy support team.
