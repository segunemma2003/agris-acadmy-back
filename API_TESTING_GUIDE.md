# API Testing Guide

This guide will help you test all API endpoints locally.

## Prerequisites

1. **Start Laravel Development Server:**

    ```bash
    php artisan serve
    ```

    The server will run on `http://localhost:8000`

2. **Run Migrations:**

    ```bash
    php artisan migrate
    ```

3. **Create Test Data (Optional):**
   You can create test data using the admin panel or tinker.

## Testing Methods

### Method 1: Using Postman/Insomnia

1. **Import the Collection:**

    - Open Postman or Insomnia
    - Import the `API_TEST_COLLECTION.json` file
    - The collection includes all endpoints with example requests

2. **Set Base URL:**

    - Update the `base_url` variable to `http://localhost:8000/api`

3. **Test Authentication:**
    - First, run the "Register User" or "Login" request
    - The token will be automatically saved to the `token` variable
    - All protected endpoints will use this token

### Method 2: Using cURL (Command Line)

#### 1. Register a New User

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### 2. Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Save the token from the response for authenticated requests.

#### 3. Get Current User (Authenticated)

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 4. Get All Categories (Public)

```bash
curl -X GET http://localhost:8000/api/categories \
  -H "Accept: application/json"
```

#### 5. Get All Courses (Public)

```bash
curl -X GET http://localhost:8000/api/courses \
  -H "Accept: application/json"
```

#### 6. Enroll in Course

```bash
curl -X POST http://localhost:8000/api/enroll \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "course_id": 1,
    "enrollment_code": "ABC123XYZ456"
  }'
```

### Method 3: Using the Test Script

1. **Make the script executable:**

    ```bash
    chmod +x test-api.sh
    ```

2. **Run the script:**

    ```bash
    ./test-api.sh
    ```

    The script will test all endpoints automatically.

**Note:** Make sure you have `jq` installed for JSON parsing:

```bash
# macOS
brew install jq

# Ubuntu/Debian
sudo apt-get install jq
```

### Method 4: Using PHP Artisan Tinker

You can also test the API using Laravel's tinker:

```bash
php artisan tinker
```

```php
// Create a test user
$user = \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password123'),
    'role' => 'student',
]);

// Create a category
$category = \App\Models\Category::create([
    'name' => 'Agriculture',
    'slug' => 'agriculture',
    'description' => 'Agricultural courses',
    'is_active' => true,
]);

// Create a tutor
$tutor = \App\Models\User::create([
    'name' => 'Tutor User',
    'email' => 'tutor@example.com',
    'password' => bcrypt('password123'),
    'role' => 'tutor',
]);

// Create a course
$course = \App\Models\Course::create([
    'category_id' => $category->id,
    'tutor_id' => $tutor->id,
    'title' => 'Introduction to Farming',
    'slug' => 'introduction-to-farming',
    'short_description' => 'Learn the basics of farming',
    'description' => 'Full course description here...',
    'level' => 'beginner',
    'language' => 'English',
    'is_published' => true,
]);
```

## Testing All Endpoints

### Public Endpoints (No Authentication Required)

1. ✅ `POST /api/register` - Register user
2. ✅ `POST /api/login` - Login user
3. ✅ `GET /api/categories` - Get all categories
4. ✅ `GET /api/categories/{id}` - Get category with courses
5. ✅ `GET /api/categories-with-courses` - Get all categories with courses
6. ✅ `GET /api/courses` - Get all courses
7. ✅ `GET /api/courses/{id}` - Get single course

### Protected Endpoints (Authentication Required)

#### Authentication

8. ✅ `GET /api/user` - Get current user
9. ✅ `POST /api/logout` - Logout user

#### Enrollments

10. ✅ `POST /api/enroll` - Enroll in course
11. ✅ `GET /api/my-enrollments` - Get my enrollments
12. ✅ `GET /api/my-courses` - Get my courses
13. ✅ `GET /api/enrollments/{id}` - Get enrollment details

#### Progress

14. ✅ `GET /api/courses/{id}/progress` - Get course progress
15. ✅ `POST /api/topics/{id}/complete` - Mark topic as complete
16. ✅ `PUT /api/progress/{id}` - Update progress

#### Notes

17. ✅ `GET /api/courses/{id}/notes` - Get course notes
18. ✅ `POST /api/notes` - Create note
19. ✅ `PUT /api/notes/{id}` - Update note
20. ✅ `DELETE /api/notes/{id}` - Delete note

#### Assignments

21. ✅ `GET /api/courses/{id}/assignments` - Get course assignments
22. ✅ `GET /api/assignments/{id}` - Get assignment details
23. ✅ `POST /api/assignments/{id}/submit` - Submit assignment
24. ✅ `GET /api/my-submissions` - Get my submissions

#### Messages

25. ✅ `GET /api/courses/{id}/messages` - Get course messages
26. ✅ `POST /api/messages` - Send message
27. ✅ `GET /api/messages/{id}` - Get message
28. ✅ `PUT /api/messages/{id}/read` - Mark message as read

## Expected Responses

### Success Response (200/201)

```json
{
  "data": {...},
  "message": "Success message"
}
```

### Error Response (400/401/403/404)

```json
{
    "message": "Error message",
    "errors": {
        "field": ["Error message for field"]
    }
}
```

## Common Issues

### 1. "Route not found" Error

-   Make sure the Laravel server is running: `php artisan serve`
-   Check that routes are registered: `php artisan route:list --path=api`

### 2. "Unauthenticated" Error

-   Make sure you're including the Bearer token in the Authorization header
-   Verify the token is valid and not expired
-   Try logging in again to get a new token

### 3. "Validation Error"

-   Check that all required fields are included in the request
-   Verify data types match the expected format
-   Check the API documentation for required fields

### 4. "Database Error"

-   Make sure migrations have been run: `php artisan migrate`
-   Check database connection in `.env` file
-   Verify database exists and is accessible

## Testing with Real Data

To test with real data, you can:

1. **Use the Admin Panel:**

    - Access `/admin`
    - Create categories, courses, users
    - Generate enrollment codes

2. **Use the Tutor Panel:**

    - Access `/tutor`
    - Create courses and content
    - Generate enrollment codes

3. **Use Database Seeders:**
   Create seeders to populate test data:
    ```bash
    php artisan make:seeder CategorySeeder
    php artisan make:seeder CourseSeeder
    php artisan db:seed
    ```

## Performance Testing

For performance testing with 100,000+ users:

1. **Use Load Testing Tools:**

    - Apache Bench (ab)
    - JMeter
    - Artillery
    - k6

2. **Example with Apache Bench:**

    ```bash
    ab -n 1000 -c 10 -H "Authorization: Bearer YOUR_TOKEN" \
       http://localhost:8000/api/courses
    ```

3. **Monitor Performance:**
    - Check database query times
    - Monitor memory usage
    - Check API response times
    - Review Laravel logs

## Next Steps

1. Test all endpoints using your preferred method
2. Verify responses match the API documentation
3. Test error cases (invalid data, unauthorized access, etc.)
4. Test with different user roles (admin, tutor, student)
5. Test edge cases and boundary conditions

For detailed API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
