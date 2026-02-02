# Agrisiti Academy - Complete Mobile App API Documentation

## Table of Contents
1. [Overview](#overview)
2. [Base Configuration](#base-configuration)
3. [Authentication Flow](#authentication-flow)
4. [User Stories & Implementation Guide](#user-stories--implementation-guide)
5. [API Endpoints](#api-endpoints)
6. [Data Models](#data-models)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)

---

## Overview

This documentation provides complete API reference for building the Agrisiti Academy mobile application. The API follows RESTful principles and uses Laravel Sanctum for authentication.

### Key Features
- ✅ User registration and authentication
- ✅ Get user profile (with stats)
- ✅ Update user profile
- ✅ Course browsing and enrollment
- ✅ Progress tracking
- ✅ Assignments and submissions
- ✅ Tests and quizzes
- ✅ Notes (create, read, update, delete)
- ✅ Comments on courses and lessons (with reply/threading support)
- ✅ Course reviews (GET available, POST may need implementation)
- ✅ Messaging system (direct messages with threading for discussions)
- ✅ Certificates
- ✅ Saved courses
- ✅ Password management (change, reset, forgot)

### Feature Status
- **Fully Implemented:** Authentication, Profile, Courses, Enrollment, Progress, Assignments, Tests, Notes, Comments, Messages, Certificates
- **Partially Implemented:** Reviews (GET endpoint exists, POST endpoint may need to be added)
- **Group Discussions:** Supported via Message system with threading (`parent_id` for replies)

---

## Base Configuration

### Base URL
```
Production: https://academy-backends.agrisiti.com/api
Development: http://localhost:8000/api
```

### Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

### Content Type
All requests should include:
```
Content-Type: application/json
Accept: application/json
```

---

## Authentication Flow

### 1. Registration Flow

**Step 1: Register New User**
```
POST /api/register
```

**Request:**
```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "phone": "+1234567890"
}
```

**Success Response (201):**
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

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `email`: required, valid email, unique, max 255 characters
- `password`: required, string, minimum 8 characters, must match password_confirmation
- `phone`: optional, string, max 255 characters

**Implementation Notes:**
- Store the token securely (use secure storage)
- Welcome email is sent automatically via queue (non-blocking)
- User is automatically set as `is_active = true`
- Role is automatically set to `student`

---

### 2. Login Flow

**Step 1: Login**
```
POST /api/login
```

**Request:**
```json
{
  "email": "john.doe@example.com",
  "password": "SecurePassword123!"
}
```

**Success Response (200):**
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
      "avatar": "https://example.com/avatar.jpg",
      "bio": "Student bio"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Error Responses:**

**401 - Invalid Credentials:**
```json
{
  "success": false,
  "message": "The provided credentials are incorrect."
}
```

**403 - Account Inactive:**
```json
{
  "success": false,
  "message": "Your account is inactive. Please contact support."
}
```

**Implementation Notes:**
- Store token securely after successful login
- Update `last_login_at` timestamp automatically
- Handle token refresh if needed

---

### 3. Forgot Password Flow

**Step 1: Request Password Reset**
```
POST /api/forgot-password
```

**Request:**
```json
{
  "email": "john.doe@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password reset link has been sent to your email address."
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Unable to send password reset link. Please try again later."
}
```

**Step 2: Reset Password**
```
POST /api/reset-password
```

**Request:**
```json
{
  "token": "reset_token_from_email",
  "email": "john.doe@example.com",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password has been reset successfully. You can now login with your new password."
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Invalid or expired reset token. Please request a new password reset link."
}
```

**Implementation Notes:**
- User receives email with reset link
- Token expires after a set time (check Laravel config)
- After successful reset, user should be redirected to login

---

### 4. Logout Flow

**Logout**
```
POST /api/logout
Headers: Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

**Implementation Notes:**
- Delete token from local storage
- Clear user session data
- Redirect to login screen

---

## User Stories & Implementation Guide

### User Story 1: New User Registration

**As a** new user  
**I want to** create an account  
**So that** I can access courses

**Implementation Steps:**
1. Show registration screen with form fields:
   - Name (required)
   - Email (required, validated)
   - Password (required, min 8 chars)
   - Password Confirmation (required, must match)
   - Phone (optional)
2. Call `POST /api/register`
3. On success:
   - Store token securely
   - Store user data
   - Navigate to home/dashboard
4. On error:
   - Display validation errors
   - Allow user to correct and resubmit

**UI Flow:**
```
Registration Screen → API Call → Success → Home Screen
                              → Error → Show Errors → Stay on Registration
```

---

### User Story 2: User Login

**As a** registered user  
**I want to** login to my account  
**So that** I can access my courses and progress

**Implementation Steps:**
1. Show login screen with:
   - Email field
   - Password field
   - "Forgot Password?" link
   - "Register" link
2. Call `POST /api/login`
3. On success:
   - Store token securely
   - Store user data
   - Navigate to home/dashboard
4. On error:
   - Show error message
   - Allow retry

**UI Flow:**
```
Login Screen → API Call → Success → Home/Dashboard
                      → Error → Show Error → Stay on Login
```

---

### User Story 3: Browse Courses

**As a** logged-in user  
**I want to** browse available courses  
**So that** I can find courses to enroll in

**Implementation Steps:**
1. Call `GET /api/courses` (with optional filters)
2. Display course list with:
   - Course image
   - Course title
   - Category
   - Rating
   - Enrollment count
   - Price/Free badge
3. Implement filters:
   - Category
   - Level (beginner, intermediate, advanced)
   - Rating
   - Duration
   - Search by keyword
4. On course tap, navigate to course details

**API Endpoints:**
- `GET /api/courses` - List all courses
- `GET /api/categories` - Get categories for filter
- `GET /api/featured-courses` - Get featured courses

---

### User Story 4: View Course Details

**As a** logged-in user  
**I want to** view detailed information about a course  
**So that** I can decide whether to enroll

**Implementation Steps:**
1. Call `GET /api/courses/{id}`
2. Display:
   - Course image/video
   - Title and description
   - Category
   - Tutor information
   - What you'll learn
   - Course requirements
   - Curriculum overview
   - Reviews/ratings
   - Enrollment button (if not enrolled)
3. If enrolled, show "Continue Learning" button instead

**API Endpoints:**
- `GET /api/courses/{id}` - Course details
- `GET /api/courses/{id}/curriculum` - Course curriculum
- `GET /api/courses/{id}/reviews` - Course reviews

---

### User Story 5: Enroll in Course

**As a** logged-in user  
**I want to** enroll in a course  
**So that** I can start learning

**Implementation Steps:**
1. User taps "Enroll" button
2. Show enrollment code input dialog
3. Call `POST /api/enroll` with:
   - `course_id`
   - `enrollment_code`
4. On success:
   - Show success message
   - Navigate to course content
   - Update "My Courses" list
5. On error:
   - Show error message
   - Allow retry

**API Endpoint:**
```
POST /api/enroll
Headers: Authorization: Bearer {token}
Body: {
  "course_id": 1,
  "enrollment_code": "ENROLL123"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Enrolled successfully",
  "data": {
    "enrollment": {
      "id": 1,
      "user_id": 1,
      "course_id": 1,
      "status": "active",
      "enrolled_at": "2024-01-15T10:00:00.000000Z",
      "course": {
        "id": 1,
        "title": "Introduction to Agriculture",
        "image": "https://example.com/image.jpg",
        "slug": "introduction-to-agriculture"
      }
    }
  }
}
```

**Error Responses:**
- 400: Invalid enrollment code
- 400: Already enrolled
- 400: Course not published

---

### User Story 6: Track Learning Progress

**As a** enrolled student  
**I want to** track my progress through course content  
**So that** I know how much I've completed

**Implementation Steps:**
1. When user opens a topic/lesson:
   - Call `PUT /api/progress/{progress_id}` to update watch time
2. When user completes a topic:
   - Call `POST /api/topics/{topic_id}/complete`
3. Display progress:
   - Overall course progress percentage
   - Module completion status
   - Topic completion status
4. Call `GET /api/courses/{id}/progress` to get full progress

**API Endpoints:**
- `GET /api/courses/{id}/progress` - Get course progress
- `POST /api/topics/{topic_id}/complete` - Mark topic as complete
- `PUT /api/progress/{id}` - Update progress (watch time, percentage)

**Progress Update Example:**
```
PUT /api/progress/123
Headers: Authorization: Bearer {token}
Body: {
  "watch_time_seconds": 300,
  "completion_percentage": 75
}
```

---

### User Story 7: Take Tests/Quizzes

**As a** enrolled student  
**I want to** take module and topic tests  
**So that** I can assess my understanding

**Implementation Steps:**
1. Navigate to test screen
2. Call `GET /api/courses/{id}/modules/{module_id}/test` for module test
   OR
   `GET /api/courses/{id}/modules/{module_id}/topics/{topic_id}/test` for topic test
3. Display questions with options
4. User selects answers
5. Submit test:
   - Call `POST /api/courses/{id}/modules/{module_id}/tests/{test_id}/submit`
   - Show results (score, pass/fail)
   - Update progress if passed

**Module Test Endpoint:**
```
GET /api/courses/{course_id}/modules/{module_id}/test
Headers: Authorization: Bearer {token}
```

**Submit Test:**
```
POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/submit
Headers: Authorization: Bearer {token}
Body: {
  "answers": {
    "1": "option_a",
    "2": "option_b",
    "3": "option_c"
  }
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Test submitted successfully",
  "data": {
    "test_attempt": {
      "id": 1,
      "score": 8,
      "total_questions": 10,
      "percentage": 80,
      "is_passed": true,
      "submitted_at": "2024-01-15T10:00:00.000000Z"
    }
  }
}
```

---

### User Story 8: Submit Assignments

**As a** enrolled student  
**I want to** submit assignments  
**So that** I can complete course requirements

**Implementation Steps:**
1. Navigate to assignments list
2. Call `GET /api/courses/{id}/assignments`
3. Display assignments with:
   - Title
   - Description
   - Due date
   - Submission status
4. On assignment tap, show details
5. User uploads file/enters text
6. Submit:
   - Call `POST /api/assignments/{id}/submit`
   - Show success message
   - Update assignment status

**Get Assignments:**
```
GET /api/courses/{course_id}/assignments
Headers: Authorization: Bearer {token}
```

**Submit Assignment:**
```
POST /api/assignments/{assignment_id}/submit
Headers: Authorization: Bearer {token}
Content-Type: multipart/form-data
Body: {
  "submission_text": "Assignment answer text",
  "file": [file upload]
}
```

---

### User Story 9: View My Courses

**As a** logged-in user  
**I want to** see all my enrolled courses  
**So that** I can continue learning

**Implementation Steps:**
1. Call `GET /api/my-courses`
2. Display courses with:
   - Course image
   - Course title
   - Progress percentage
   - Last accessed
   - Continue button
3. Filter by:
   - All courses
   - Ongoing courses
   - Completed courses
   - Certified courses

**API Endpoints:**
- `GET /api/my-courses` - All enrolled courses
- `GET /api/my-ongoing-courses` - Active courses
- `GET /api/completed-courses` - Completed courses
- `GET /api/certified-courses` - Courses with certificates

---

### User Story 10: View Certificates

**As a** student  
**I want to** view my earned certificates  
**So that** I can share my achievements

**Implementation Steps:**
1. Call `GET /api/user/certificates`
2. Display certificates with:
   - Course name
   - Certificate number
   - Issue date
   - Download/view option

**API Endpoint:**
```
GET /api/user/certificates
Headers: Authorization: Bearer {token}
```

---

## API Endpoints

### Authentication Endpoints

#### 1. Register
```
POST /api/register
```
[See Registration Flow above](#1-registration-flow)

#### 2. Login
```
POST /api/login
```
[See Login Flow above](#2-login-flow)

#### 3. Forgot Password
```
POST /api/forgot-password
```
[See Forgot Password Flow above](#3-forgot-password-flow)

#### 4. Reset Password
```
POST /api/reset-password
```
[See Forgot Password Flow above](#3-forgot-password-flow)

#### 5. Logout
```
POST /api/logout
Headers: Authorization: Bearer {token}
```

#### 6. Get Current User
```
GET /api/user
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "role": "student",
  "avatar": "https://example.com/avatar.jpg",
  "bio": "Student bio",
  "location": "Lagos, Nigeria",
  "is_active": true,
  "stats": {
    "total_courses": 5,
    "ongoing_courses": 3,
    "completed_courses": 2,
    "total_hours_spent": 45.5,
    "certificates_acquired": 2
  }
}
```

#### 7. Get User Profile (with Stats)
```
GET /api/user
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "role": "student",
  "avatar": "https://example.com/avatar.jpg",
  "bio": "Student bio",
  "location": "Lagos, Nigeria",
  "is_active": true,
  "last_login_at": "2024-01-15T10:00:00.000000Z",
  "stats": {
    "total_courses": 5,
    "ongoing_courses": 3,
    "completed_courses": 2,
    "total_hours_spent": 45.5,
    "certificates_acquired": 2
  },
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-15T10:00:00.000000Z"
}
```

#### 8. Update Profile
```
PUT /api/user/profile
Headers: Authorization: Bearer {token}
Body: {
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "bio": "Updated bio",
  "avatar": "https://example.com/new-avatar.jpg"
}
```

**Request Validation:**
- `name`: optional, string, max 255 characters
- `email`: optional, valid email, unique (excluding current user)
- `phone`: optional, string, max 255 characters
- `bio`: optional, string, max 1000 characters
- `avatar`: optional, string, max 500 characters (URL)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "phone": "+1234567890",
      "bio": "Updated bio",
      "avatar": "https://example.com/new-avatar.jpg",
      "role": "student"
    }
  }
}
```

#### 9. Change Password
```
PUT /api/user/password
Headers: Authorization: Bearer {token}
Body: {
  "current_password": "OldPassword123!",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

#### 10. Delete Account
```
DELETE /api/user/account
Headers: Authorization: Bearer {token}
Body: {
  "password": "CurrentPassword123!"
}
```

#### 11. Get Certificates
```
GET /api/user/certificates
Headers: Authorization: Bearer {token}
```

---

### Category Endpoints

#### 1. List Categories
```
GET /api/categories
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Crop Production",
      "slug": "crop-production",
      "description": "Learn about crop production",
      "image": "https://example.com/image.jpg"
    }
  ]
}
```

#### 2. Get Category
```
GET /api/categories/{id}
```

#### 3. Get Category Courses
```
GET /api/categories/{id}/courses
```

#### 4. Categories with Courses
```
GET /api/categories-with-courses
```

#### 5. Featured Courses (Public)
```
GET /api/featured-courses-public
```

---

### Course Endpoints

#### 1. List Courses
```
GET /api/courses
Query Parameters:
  - category_id: Filter by category
  - level: Filter by level (beginner, intermediate, advanced)
  - min_rating: Minimum rating
  - min_duration: Minimum duration in minutes
  - max_duration: Maximum duration in minutes
  - search: Search term
  - page: Page number (pagination)
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Introduction to Agriculture",
      "slug": "introduction-to-agriculture",
      "short_description": "Learn the basics",
      "image": "https://example.com/image.jpg",
      "rating": 4.5,
      "rating_count": 120,
      "enrollment_count": 500,
      "price": 0,
      "is_free": true,
      "level": "beginner",
      "category": {
        "id": 1,
        "name": "Crop Production"
      },
      "tutor": {
        "id": 1,
        "name": "Dr. Smith"
      }
    }
  ],
  "current_page": 1,
  "total": 50,
  "per_page": 15
}
```

#### 2. Get Course Details
```
GET /api/courses/{id}
```

**Response:**
```json
{
  "id": 1,
  "title": "Introduction to Agriculture",
  "slug": "introduction-to-agriculture",
  "description": "Full course description",
  "short_description": "Brief description",
  "image": "https://example.com/image.jpg",
  "preview_video_url": "https://example.com/video.mp4",
  "rating": 4.5,
  "rating_count": 120,
  "enrollment_count": 500,
  "price": 0,
  "is_free": true,
  "is_published": true,
  "is_featured": false,
  "level": "beginner",
  "language": "English",
  "duration_minutes": 180,
  "certificate_included": true,
  "category": {
    "id": 1,
    "name": "Crop Production"
  },
  "tutor": {
    "id": 1,
    "name": "Dr. Smith",
    "avatar": "https://example.com/avatar.jpg",
    "bio": "Expert in agriculture"
  },
  "tutors": [
    {
      "id": 1,
      "name": "Dr. Smith"
    }
  ],
  "what_you_will_learn": [
    "Basic farming techniques",
    "Crop management"
  ],
  "requirements": [
    "Basic knowledge of agriculture"
  ],
  "is_enrolled": false
}
```

#### 3. Get Course Modules
```
GET /api/courses/{id}/modules
Headers: Authorization: Bearer {token}
```

#### 4. Get Course Curriculum
```
GET /api/courses/{id}/curriculum
Headers: Authorization: Bearer {token}
```

#### 5. Get Course Information
```
GET /api/courses/{id}/information
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Introduction to Agriculture",
    "description": "Full course description",
    "short_description": "Brief description",
    "about": "About the course",
    "requirements": "Course requirements",
    "what_to_expect": "What to expect",
    "what_you_will_learn": ["Skill 1", "Skill 2"],
    "what_you_will_get": ["Resource 1", "Resource 2"],
    "course_information": {...},
    "image_url": "https://example.com/image.jpg",
    "preview_video_url": "https://example.com/video.mp4",
    "level": "beginner",
    "duration_minutes": 180,
    "language": "English",
    "rating": 4.5,
    "rating_count": 120,
    "enrollment_count": 500,
    "lessons_count": 25,
    "certificate_included": true,
    "category": {...},
    "main_instructor": {
      "id": 1,
      "name": "Dr. Smith",
      "bio": "Expert in agriculture",
      "avatar": "https://example.com/avatar.jpg"
    },
    "instructors": [...],
    "is_enrolled": false
  }
}
```

#### 6. Get Course DIY Content
```
GET /api/courses/{id}/diy-content
Headers: Authorization: Bearer {token}
```

**Description:** Get DIY (Do It Yourself) content for enrolled students

**Auth:** Required, must be enrolled in course

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "module_id": 1,
      "title": "DIY Activity 1",
      "description": "Step-by-step instructions",
      "instructions": "Detailed instructions...",
      "materials_needed": ["Material 1", "Material 2"],
      "estimated_time": "30 minutes",
      "difficulty": "beginner",
      "images": ["https://example.com/image1.jpg"],
      "videos": ["https://example.com/video1.mp4"],
      "is_active": true,
      "sort_order": 1,
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "message": "Course DIY content retrieved successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You must be enrolled in this course to access DIY content"
}
```

#### 7. Get Course Resources
```
GET /api/courses/{id}/resources
Headers: Authorization: Bearer {token}
```

**Description:** Get downloadable resources for enrolled students

**Auth:** Required, must be enrolled in course

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "title": "Course PDF Guide",
      "description": "Complete guide to the course",
      "file_url": "https://example.com/resources/guide.pdf",
      "file_type": "pdf",
      "file_size": 2048576,
      "download_count": 150,
      "is_active": true,
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "message": "Course resources retrieved successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You must be enrolled in this course to access resources"
}
```

#### 8. Get Course Reviews
```
GET /api/courses/{id}/reviews
Headers: Authorization: Bearer {token}
Query Parameters:
  - per_page: Number of reviews per page (default: 10)
  - page: Page number
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "course_id": 1,
      "rating": 5,
      "review": "Excellent course! Very informative.",
      "is_verified_purchase": true,
      "is_approved": true,
      "user": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://example.com/avatar.jpg"
      },
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  },
  "message": "Course reviews retrieved successfully"
}
```

#### 7. Add Course Review
```
POST /api/courses/{id}/reviews
Headers: Authorization: Bearer {token}
Body: {
  "rating": 5,
  "review": "This course exceeded my expectations. The content is well-structured and easy to follow."
}
```

**Note:** This endpoint may need to be implemented. Currently, only GET endpoint exists.

**Request Validation:**
- `rating`: required, integer, between 1 and 5
- `review`: optional, string, max 2000 characters

**Success Response (201):**
```json
{
  "success": true,
  "message": "Review submitted successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "rating": 5,
    "review": "This course exceeded my expectations...",
    "is_verified_purchase": true,
    "is_approved": false,
    "created_at": "2024-01-15T10:00:00.000000Z"
  }
}
```

**Error Responses:**
- 400: Already reviewed (one review per user per course)
- 403: Not enrolled in course (if reviews require enrollment)

#### 9. Get Course Completion Status
```
GET /api/courses/{id}/completion
Headers: Authorization: Bearer {token}
```

#### 10. Recommended Courses
```
GET /api/recommended-courses
Headers: Authorization: Bearer {token}
```

#### 11. Daily Recommended Courses
```
GET /api/daily-recommended-courses
Headers: Authorization: Bearer {token}
```

#### 12. Latest Courses
```
GET /api/latest-courses
Headers: Authorization: Bearer {token}
```

---

### Enrollment Endpoints

#### 1. Enroll in Course
```
POST /api/enroll
Headers: Authorization: Bearer {token}
Body: {
  "course_id": 1,
  "enrollment_code": "ENROLL123"
}
```

#### 2. My Enrollments
```
GET /api/my-enrollments
Headers: Authorization: Bearer {token}
```

#### 3. My Courses
```
GET /api/my-courses
Headers: Authorization: Bearer {token}
```

#### 4. My Ongoing Courses
```
GET /api/my-ongoing-courses
Headers: Authorization: Bearer {token}
```

#### 5. Completed Courses
```
GET /api/completed-courses
Headers: Authorization: Bearer {token}
```

#### 6. Certified Courses
```
GET /api/certified-courses
Headers: Authorization: Bearer {token}
```

#### 7. Saved Courses
```
GET /api/saved-courses-list
Headers: Authorization: Bearer {token}
```

#### 8. Get Enrollment Details
```
GET /api/enrollments/{id}
Headers: Authorization: Bearer {token}
```

---

### Progress Endpoints

#### 1. Get Course Progress
```
GET /api/courses/{id}/progress
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "course_id": 1,
    "overall_progress": 65.5,
    "total_topics": 20,
    "completed_topics": 13,
    "topics": [
      {
        "id": 1,
        "title": "Introduction to Farming",
        "module_id": 1,
        "is_completed": true,
        "completion_percentage": 100,
        "last_accessed_at": "2024-01-15T10:00:00.000000Z"
      }
    ]
  }
}
```

#### 2. Mark Topic as Complete
```
POST /api/topics/{topic_id}/complete
Headers: Authorization: Bearer {token}
```

#### 3. Update Progress
```
PUT /api/progress/{progress_id}
Headers: Authorization: Bearer {token}
Body: {
  "watch_time_seconds": 300,
  "completion_percentage": 75
}
```

#### 4. Complete Quiz
```
POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/complete-quiz
Headers: Authorization: Bearer {token}
```

---

### Module Endpoints

#### 1. Get Module Details
```
GET /api/courses/{course_id}/modules/{module_id}
Headers: Authorization: Bearer {token}
```

---

### Test/Quiz Endpoints

#### 1. Get Module Test
```
GET /api/courses/{course_id}/modules/{module_id}/test
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "test": {
      "id": 1,
      "module_id": 1,
      "title": "Module 1 Test",
      "description": "Test your knowledge",
      "passing_score": 70,
      "time_limit_minutes": 30,
      "questions": [
        {
          "id": 1,
          "question": "What is agriculture?",
          "type": "multiple_choice",
          "options": {
            "option_a": "Farming",
            "option_b": "Fishing",
            "option_c": "Mining",
            "option_d": "Trading"
          },
          "correct_answer": "option_a",
          "points": 10,
          "sort_order": 1
        }
      ]
    }
  }
}
```

#### 2. Submit Module Test
```
POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/submit
Headers: Authorization: Bearer {token}
Body: {
  "answers": {
    "1": "option_a",
    "2": "option_b",
    "3": "option_c"
  }
}
```

#### 3. Get Topic Test
```
GET /api/courses/{course_id}/modules/{module_id}/topics/{topic_id}/test
Headers: Authorization: Bearer {token}
```

#### 4. Submit Topic Test
```
POST /api/courses/{course_id}/modules/{module_id}/topics/{topic_id}/tests/{test_id}/submit
Headers: Authorization: Bearer {token}
Body: {
  "answers": {
    "1": "option_a",
    "2": "option_b"
  }
}
```

---

### Assignment Endpoints

#### 1. Get Course Assignments
```
GET /api/courses/{course_id}/assignments
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "module_id": 1,
      "topic_id": 1,
      "title": "Assignment 1",
      "description": "Complete this assignment",
      "instructions": "Follow the guidelines",
      "max_score": 100,
      "due_date": "2024-02-01T23:59:59.000000Z",
      "is_active": true,
      "submissions": [
        {
          "id": 1,
          "user_id": 1,
          "status": "submitted",
          "score": null,
          "submitted_at": "2024-01-20T10:00:00.000000Z"
        }
      ]
    }
  ]
}
```

#### 2. Get Assignment Details
```
GET /api/assignments/{assignment_id}
Headers: Authorization: Bearer {token}
```

#### 3. Submit Assignment
```
POST /api/assignments/{assignment_id}/submit
Headers: Authorization: Bearer {token}
Content-Type: multipart/form-data
Body: {
  "submission_text": "My assignment answer",
  "file": [file upload]
}
```

#### 4. My Submissions
```
GET /api/my-submissions
Headers: Authorization: Bearer {token}
```

---

### Notes Endpoints

#### 1. Get Course Notes
```
GET /api/courses/{course_id}/notes
Headers: Authorization: Bearer {token}
```

#### 2. Get Module Notes
```
GET /api/courses/{course_id}/modules/{module_id}/notes
Headers: Authorization: Bearer {token}
```

#### 3. Get Topic Notes
```
GET /api/courses/{course_id}/modules/{module_id}/topics/{topic_id}/notes
Headers: Authorization: Bearer {token}
```

#### 4. Create Note
```
POST /api/notes
Headers: Authorization: Bearer {token}
Body: {
  "course_id": 1,
  "module_id": 1,
  "topic_id": 1,
  "content": "My note content"
}
```

#### 5. Update Note
```
PUT /api/notes/{note_id}
Headers: Authorization: Bearer {token}
Body: {
  "content": "Updated note content"
}
```

#### 6. Delete Note
```
DELETE /api/notes/{note_id}
Headers: Authorization: Bearer {token}
```

---

### Comment Endpoints

#### 1. Get Lesson Comments (with Replies/Threading)
```
GET /api/courses/{course_id}/topics/{topic_id}/comments
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "topic_id": 1,
      "course_id": 1,
      "comment": "This lesson is very helpful!",
      "parent_id": null,
      "user": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://example.com/avatar.jpg"
      },
      "replies": [
        {
          "id": 2,
          "user_id": 2,
          "comment": "I agree!",
          "parent_id": 1,
          "user": {
            "id": 2,
            "name": "Jane Smith",
            "avatar": "https://example.com/avatar2.jpg"
          },
          "created_at": "2024-01-15T11:00:00.000000Z"
        }
      ],
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "message": "Lesson comments retrieved successfully"
}
```

#### 2. Add Lesson Comment (with Reply Support)
```
POST /api/courses/{course_id}/topics/{topic_id}/comments
Headers: Authorization: Bearer {token}
Body: {
  "comment": "This lesson is very helpful!",
  "parent_id": null  // Optional: Set to comment ID to reply to a comment
}
```

**Request Validation:**
- `comment`: required, string, max 2000 characters
- `parent_id`: optional, integer, must exist in lesson_comments table

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "topic_id": 1,
    "course_id": 1,
    "comment": "This lesson is very helpful!",
    "parent_id": null,
    "user": {
      "id": 1,
      "name": "John Doe",
      "avatar": "https://example.com/avatar.jpg"
    },
    "created_at": "2024-01-15T10:00:00.000000Z"
  },
  "message": "Comment added successfully"
}
```

**Reply to Comment Example:**
```
POST /api/courses/{course_id}/topics/{topic_id}/comments
Body: {
  "comment": "I agree with your point!",
  "parent_id": 1  // Replying to comment ID 1
}
```

#### 3. Get Course Comments (with Replies/Threading)
```
GET /api/courses/{course_id}/comments
Headers: Authorization: Bearer {token}
```

**Response:** Same structure as lesson comments with nested replies

#### 4. Add Course Comment (with Reply Support)
```
POST /api/courses/{course_id}/comments
Headers: Authorization: Bearer {token}
Body: {
  "comment": "Great course! The content is well-structured.",
  "parent_id": null  // Optional: Set to comment ID to reply to a comment
}
```

**Request Validation:**
- `comment`: required, string, max 2000 characters
- `parent_id`: optional, integer, must exist in course_comments table

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "comment": "Great course!",
    "parent_id": null,
    "user": {
      "id": 1,
      "name": "John Doe",
      "avatar": "https://example.com/avatar.jpg"
    },
    "created_at": "2024-01-15T10:00:00.000000Z"
  },
  "message": "Comment added successfully"
}
```

---

### Saved Courses Endpoints

#### 1. Get Saved Courses
```
GET /api/saved-courses
Headers: Authorization: Bearer {token}
```

#### 2. Save Course
```
POST /api/courses/{course_id}/save
Headers: Authorization: Bearer {token}
```

#### 3. Unsave Course
```
DELETE /api/courses/{course_id}/unsave
Headers: Authorization: Bearer {token}
```

---

### Message Endpoints (Direct Messaging & Discussions)

**Note:** The messaging system supports threading (replies) via `parent_id`, making it suitable for course discussions between students and tutors.

#### 1. Get Course Messages (with Threading/Replies)
```
GET /api/courses/{course_id}/messages
Headers: Authorization: Bearer {token}
```

**Response:**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "sender_id": 1,
    "recipient_id": 2,
    "parent_id": null,
    "subject": "Question about Module 1",
    "message": "I have a question about the first module...",
    "is_read": false,
    "read_at": null,
    "sender": {
      "id": 1,
      "name": "John Doe",
      "avatar": "https://example.com/avatar.jpg"
    },
    "recipient": {
      "id": 2,
      "name": "Dr. Smith",
      "avatar": "https://example.com/tutor.jpg"
    },
    "replies": [
      {
        "id": 2,
        "parent_id": 1,
        "message": "Great question! Let me explain...",
        "sender": {
          "id": 2,
          "name": "Dr. Smith"
        },
        "created_at": "2024-01-15T11:00:00.000000Z"
      }
    ],
    "created_at": "2024-01-15T10:00:00.000000Z"
  }
]
```

#### 2. Send Message (Direct Message or Reply)
```
POST /api/messages
Headers: Authorization: Bearer {token}
Body: {
  "course_id": 1,
  "recipient_id": 2,
  "subject": "Question about lesson",
  "message": "I have a question...",
  "parent_id": null  // Optional: Set to message ID to reply to a message
}
```

**Request Validation:**
- `course_id`: required, integer, must exist
- `recipient_id`: required, integer, must exist (typically tutor)
- `subject`: optional, string, max 255 characters
- `message`: required, string
- `parent_id`: optional, integer, must exist in messages table (for replies)

**Success Response (201):**
```json
{
  "id": 1,
  "course_id": 1,
  "sender_id": 1,
  "recipient_id": 2,
  "parent_id": null,
  "subject": "Question about lesson",
  "message": "I have a question...",
  "is_read": false,
  "sender": {
    "id": 1,
    "name": "John Doe",
    "avatar": "https://example.com/avatar.jpg"
  },
  "recipient": {
    "id": 2,
    "name": "Dr. Smith",
    "avatar": "https://example.com/tutor.jpg"
  },
  "created_at": "2024-01-15T10:00:00.000000Z"
}
```

**Reply to Message Example:**
```
POST /api/messages
Body: {
  "course_id": 1,
  "recipient_id": 1,
  "subject": "Re: Question about lesson",
  "message": "Here's the answer to your question...",
  "parent_id": 1  // Replying to message ID 1
}
```

#### 3. Get Message Details (with Thread)
```
GET /api/messages/{message_id}
Headers: Authorization: Bearer {token}
```

**Response:** Returns message with all replies in thread

#### 4. Mark Message as Read
```
PUT /api/messages/{message_id}/read
Headers: Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "id": 1,
  "is_read": true,
  "read_at": "2024-01-15T10:30:00.000000Z"
}
```

**Implementation Notes for Discussions:**
- Use `parent_id` to create threaded discussions
- Messages can be between student and tutor
- All messages in a course are visible to enrolled users
- Use `subject` field to organize discussion topics

---

## Data Models

### User Model
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "role": "student",
  "avatar": "https://example.com/avatar.jpg",
  "bio": "Student bio",
  "location": "Lagos, Nigeria",
  "is_active": true,
  "last_login_at": "2024-01-15T10:00:00.000000Z",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-15T10:00:00.000000Z"
}
```

### Course Model
```json
{
  "id": 1,
  "title": "Introduction to Agriculture",
  "slug": "introduction-to-agriculture",
  "description": "Full description",
  "short_description": "Brief description",
  "image": "https://example.com/image.jpg",
  "rating": 4.5,
  "rating_count": 120,
  "enrollment_count": 500,
  "price": 0,
  "is_free": true,
  "is_published": true,
  "level": "beginner",
  "duration_minutes": 180,
  "certificate_included": true
}
```

### Enrollment Model
```json
{
  "id": 1,
  "user_id": 1,
  "course_id": 1,
  "enrollment_code": "ENROLL123",
  "status": "active",
  "progress_percentage": 65.5,
  "enrolled_at": "2024-01-15T10:00:00.000000Z",
  "completed_at": null
}
```

### Progress Model
```json
{
  "id": 1,
  "user_id": 1,
  "course_id": 1,
  "topic_id": 1,
  "is_completed": true,
  "completion_percentage": 100,
  "watch_time_seconds": 1800,
  "last_accessed_at": "2024-01-15T10:00:00.000000Z",
  "completed_at": "2024-01-15T10:30:00.000000Z"
}
```

---

## Error Handling

### Standard Error Response Format
```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Error detail 1", "Error detail 2"]
  }
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

### Common Error Scenarios

**Invalid Token:**
```json
{
  "message": "Unauthenticated."
}
```
**Action:** Redirect to login

**Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```
**Action:** Display field-specific errors

**Not Enrolled:**
```json
{
  "message": "You are not enrolled in this course"
}
```
**Action:** Show enrollment prompt

---

## Best Practices

### 1. Token Management
- Store tokens securely (use secure storage)
- Implement token refresh if needed
- Handle token expiration gracefully
- Clear tokens on logout

### 2. Error Handling
- Always check response status codes
- Display user-friendly error messages
- Log errors for debugging
- Handle network errors gracefully

### 3. Loading States
- Show loading indicators during API calls
- Implement pull-to-refresh where appropriate
- Cache data to reduce API calls

### 4. Offline Support
- Cache important data locally
- Queue actions when offline
- Sync when connection is restored

### 5. Performance
- Implement pagination for lists
- Lazy load images
- Use appropriate image sizes
- Minimize API calls

### 6. Security
- Never store passwords in plain text
- Use HTTPS for all API calls
- Validate all user inputs
- Implement proper authentication checks

### 7. User Experience
- Provide clear feedback for all actions
- Show progress indicators
- Implement proper navigation flows
- Handle edge cases gracefully

---

## Implementation Checklist

### Phase 1: Authentication
- [ ] Registration screen
- [ ] Login screen
- [ ] Forgot password flow
- [ ] Reset password screen
- [ ] Token storage
- [ ] Auto-login on app start

### Phase 2: Course Browsing
- [ ] Course list screen
- [ ] Course details screen
- [ ] Category filtering
- [ ] Search functionality
- [ ] Featured courses

### Phase 3: Enrollment
- [ ] Enrollment flow
- [ ] Enrollment code input
- [ ] My courses screen
- [ ] Course filtering (ongoing, completed)

### Phase 4: Learning
- [ ] Course content viewer
- [ ] Progress tracking
- [ ] Module navigation
- [ ] Topic completion
- [ ] Video player integration

### Phase 5: Assessments
- [ ] Test/Quiz screens
- [ ] Assignment submission
- [ ] Results display
- [ ] Progress updates

### Phase 6: Additional Features
- [ ] Notes functionality
- [ ] Comments system
- [ ] Messaging
- [ ] Certificates
- [ ] Profile management

---

## Support & Contact

For API support or questions:
- Email: support@agrisiti.com
- Documentation: https://academy-backends.agrisiti.com/docs

---

**Last Updated:** January 2024  
**API Version:** 1.0

