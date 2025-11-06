# Quick Start Guide - Testing APIs Locally

## 🚀 Quick Setup

### 1. Start the Laravel Server

```bash
cd /Users/segun/Documents/projects/agrisiti/agrisiti-main-academy
php artisan serve
```

The server will start on `http://localhost:8000`

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Access the API Tester

Open your browser and go to:

```
http://localhost:8000/api-test.html
```

This is an interactive HTML page that lets you test all API endpoints easily!

## 📋 Testing Steps

### Step 1: Register/Login

1. Open the API Tester page
2. Enter an email and password
3. Click "Register" or "Login"
4. Your token will be automatically saved

### Step 2: Test Public Endpoints

Click on any of the quick test cards:

-   Get Categories
-   Categories with Courses
-   Get Courses
-   Get Course (single)

### Step 3: Test Protected Endpoints

After logging in, you can test:

-   My Enrollments
-   My Courses
-   Create Notes
-   Submit Assignments
-   Send Messages

### Step 4: Custom Requests

Use the "Custom API Request" section to test any endpoint with custom data.

## 🔗 API Base URL

```
http://localhost:8000/api
```

## 📝 Example cURL Commands

### Register

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Get Categories (Public)

```bash
curl -X GET http://localhost:8000/api/categories \
  -H "Accept: application/json"
```

### Get Courses (Public)

```bash
curl -X GET http://localhost:8000/api/courses \
  -H "Accept: application/json"
```

### Get Current User (Protected)

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 🧪 Using Postman/Insomnia

1. Import the `API_TEST_COLLECTION.json` file
2. Set the `base_url` variable to `http://localhost:8000/api`
3. Run the "Login" request to get a token
4. All protected endpoints will automatically use the token

## 🐛 Troubleshooting

### "Route not found"

-   Make sure the server is running: `php artisan serve`
-   Check routes: `php artisan route:list --path=api`

### "Unauthenticated"

-   Make sure you've logged in and have a token
-   Check that the token is included in the Authorization header
-   Try logging in again to get a new token

### "Database Error"

-   Run migrations: `php artisan migrate`
-   Check your `.env` file for database configuration

## 📚 All Available Endpoints

### Public Endpoints

-   `POST /api/register` - Register user
-   `POST /api/login` - Login user
-   `GET /api/categories` - Get all categories
-   `GET /api/categories/{id}` - Get category
-   `GET /api/categories-with-courses` - Get categories with courses
-   `GET /api/courses` - Get all courses
-   `GET /api/courses/{id}` - Get single course

### Protected Endpoints (Require Authentication)

-   `GET /api/user` - Get current user
-   `POST /api/logout` - Logout
-   `POST /api/enroll` - Enroll in course
-   `GET /api/my-enrollments` - Get my enrollments
-   `GET /api/my-courses` - Get my courses
-   `GET /api/enrollments/{id}` - Get enrollment
-   `GET /api/courses/{id}/progress` - Get progress
-   `POST /api/topics/{id}/complete` - Complete topic
-   `PUT /api/progress/{id}` - Update progress
-   `GET /api/courses/{id}/notes` - Get notes
-   `POST /api/notes` - Create note
-   `PUT /api/notes/{id}` - Update note
-   `DELETE /api/notes/{id}` - Delete note
-   `GET /api/courses/{id}/assignments` - Get assignments
-   `GET /api/assignments/{id}` - Get assignment
-   `POST /api/assignments/{id}/submit` - Submit assignment
-   `GET /api/my-submissions` - Get submissions
-   `GET /api/courses/{id}/messages` - Get messages
-   `POST /api/messages` - Send message
-   `GET /api/messages/{id}` - Get message
-   `PUT /api/messages/{id}/read` - Mark as read

## 🎯 Next Steps

1. Test all endpoints using the HTML tester
2. Create test data using the admin panel (`/admin`)
3. Test with different user roles (admin, tutor, student)
4. Review the full API documentation in `API_DOCUMENTATION.md`

Happy Testing! 🎉
