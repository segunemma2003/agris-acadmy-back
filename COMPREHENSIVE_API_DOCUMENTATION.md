# Comprehensive API Documentation - Agrisiti Academy

## Base URL

```
Production: https://academy-backends.agrisiti.com/api
Development: http://localhost:8000/api
```

## Authentication

All protected endpoints require authentication using Bearer tokens:

```
Authorization: Bearer {your_token}
```

## Image Dimensions (Universal for Mobile/Desktop/Web)

### Course Images

-   **Dimensions:** 1920×1080px (16:9 aspect ratio)
-   **Format:** JPEG or PNG
-   **Max Size:** 2MB
-   **Storage:** S3 (configurable via `FILESYSTEM_DISK` env variable)

### Category Images

-   **Dimensions:** 800×800px (1:1 square)
-   **Format:** PNG with transparency or JPEG
-   **Max Size:** 500KB

### User Avatars

-   **Dimensions:** 400×400px (1:1 square)
-   **Format:** JPEG or PNG
-   **Max Size:** 200KB

**Note:** All images are automatically resized to these dimensions when uploaded through the admin panel. Images work seamlessly across mobile apps, desktop, and web.

---

## Caching Strategy

-   **Cache Duration:** 5 minutes for user-specific data, 10 minutes for public data
-   **Cache Keys:** `user_{user_id}_*` for user-specific, `*_courses` for public
-   **Cache Tags:** Used for efficient cache invalidation
-   **Performance Target:** All APIs respond in < 3 seconds

---

## Table of Contents

1. [Course APIs](#1-course-apis)
2. [User Profile APIs](#2-user-profile-apis)
3. [Enrollment APIs](#3-enrollment-apis)
4. [Notes APIs](#4-notes-apis)
5. [Comments APIs](#5-comments-apis)
6. [Saved Courses APIs](#6-saved-courses-apis)
7. [Category APIs](#7-category-apis)
8. [Progress APIs](#8-progress-apis)
9. [Test/Quiz APIs](#9-testquiz-apis)

---

## 1. Course APIs

### 1.0 Search Courses

**Endpoint:** `GET /api/courses`

**Description:** Search and filter courses with comprehensive options

**Authentication:** Optional (enrollment status shown if authenticated)

**Query Parameters:**

-   `search` - Search in title, description, short_description, tags
-   `category_id` - Filter by category
-   `level` - Filter by level (beginner, intermediate, advanced)
-   `min_rating` - Minimum rating filter
-   `min_duration` - Minimum duration in minutes
-   `max_duration` - Maximum duration in minutes
-   `per_page` - Results per page (default: 20)

**Example:**

```
GET /api/courses?search=agriculture&category_id=1&level=beginner&min_rating=4.0&per_page=15
```

**Success Response (200):**

```json
{
  "success": true,
  "data": [...],
  "pagination": {...},
  "message": "Courses retrieved successfully"
}
```

---

### 1.1 Daily Recommended Courses

**Endpoint:** `GET /api/daily-recommended-courses`

**Description:** Get personalized daily recommended courses for authenticated user

**Authentication:** Required

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Course Title",
      "slug": "course-slug",
      "short_description": "Brief description",
      "image_url": "https://s3.amazonaws.com/bucket/courses/image.jpg",
      "preview_video_url": "https://youtube.com/watch?v=...",
      "rating": 4.5,
      "rating_count": 100,
      "enrollment_count": 500,
      "duration_minutes": 120,
      "level": "beginner",
      "lessons_count": 15,
      "certificate_included": true,
      "category": {
        "id": 1,
        "name": "Agriculture",
        "slug": "agriculture"
      },
      "main_instructor": {
        "id": 2,
        "name": "Instructor Name",
        "bio": "Instructor bio",
        "avatar": "https://s3.amazonaws.com/bucket/avatars/avatar.jpg"
      },
      "instructors": [...],
      "is_enrolled": false
    }
  ],
  "message": "Daily recommended courses retrieved successfully"
}
```

---

### 1.2 Latest Courses

**Endpoint:** `GET /api/latest-courses`

**Description:** Get latest 10 courses

**Authentication:** Required

**Success Response (200):**

```json
{
  "success": true,
  "data": [...],
  "message": "Latest courses retrieved successfully"
}
```

---

### 1.3 Featured Courses

**Endpoint:** `GET /api/featured-courses`

**Description:** Get all featured courses with enrollment status

**Authentication:** Required

**Success Response (200):**

```json
{
  "success": true,
  "data": [...],
  "message": "Featured courses retrieved successfully"
}
```

---

### 1.4 Course Details

**Endpoint:** `GET /api/courses/{course_id}`

**Description:** Get full course details including all information

**Authentication:** Optional (enrollment status only shown if authenticated)

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Course Title",
    "slug": "course-slug",
    "description": "Full description",
    "short_description": "Brief description",
    "about": "About this course...",
    "requirements": "Course requirements...",
    "what_to_expect": "What to expect...",
    "image_url": "https://s3.amazonaws.com/bucket/courses/image.jpg",
    "preview_video_url": "https://youtube.com/watch?v=...",
    "rating": 4.5,
    "rating_count": 100,
    "enrollment_count": 500,
    "lessons_count": 15,
    "certificate_included": true,
    "duration_minutes": 120,
    "level": "beginner",
    "language": "English",
    "category": {...},
    "main_instructor": {
      "id": 2,
      "name": "Instructor Name",
      "bio": "Instructor biography",
      "avatar": "https://s3.amazonaws.com/bucket/avatars/avatar.jpg"
    },
    "instructors": [...],
    "modules": [...],
    "reviews": [...],
    "is_enrolled": false,
    "completion_percentage": 0
  },
  "message": "Course details retrieved successfully"
}
```

---

### 1.5 Course Curriculum

**Endpoint:** `GET /api/courses/{course_id}/curriculum`

**Description:** Get full course curriculum with modules, lessons, assignments, VR, DIY, tests

**Authentication:** Required (must be enrolled)

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "course_id": 1,
    "course_title": "Course Title",
    "modules": [
      {
        "id": 1,
        "title": "Module 1",
        "description": "Module description",
        "duration_minutes": 60,
        "lessons_count": 5,
        "sort_order": 1,
        "topics": [
          {
            "id": 1,
            "title": "Lesson 1",
            "description": "Lesson description",
            "video_url": "https://youtube.com/watch?v=...",
            "duration_minutes": 12,
            "transcript_english": "English transcript...",
            "transcript_hausa": "Hausa transcript...",
            "sort_order": 1
          }
        ],
        "test": {
          "id": 1,
          "title": "Module 1 Test",
          "questions": [...]
        },
        "assignments": [
          {
            "id": 1,
            "title": "Assignment 1",
            "description": "..."
          }
        ],
        "vr_experience": {
          "id": 1,
          "title": "VR Experience",
          "instructions": "..."
        },
        "diy_instructions": [
          {
            "id": 1,
            "title": "DIY Project",
            "content": "..."
          }
        ]
      }
    ]
  },
  "message": "Course curriculum retrieved successfully"
}
```

---

### 1.6 Course Completion Percentage

**Endpoint:** `GET /api/courses/{course_id}/completion`

**Description:** Get course completion percentage for authenticated user

**Authentication:** Required (must be enrolled)

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "course_id": 1,
        "completion_percentage": 65.5
    },
    "message": "Course completion retrieved successfully"
}
```

---

### 1.7 Course Reviews

**Endpoint:** `GET /api/courses/{course_id}/reviews`

**Description:** Get course reviews with pagination

**Authentication:** Not required

**Query Parameters:**

-   `per_page` (optional): Items per page (default: 10)

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "rating": 5,
            "comment": "Great course!",
            "user": {
                "id": 5,
                "name": "Student Name",
                "avatar": "https://..."
            },
            "created_at": "2025-01-15T10:00:00.000000Z"
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

---

## 2. User Profile APIs

### 2.1 Get User Profile with Stats

**Endpoint:** `GET /api/user`

**Description:** Get authenticated user profile with course statistics

**Authentication:** Required

**Success Response (200):**

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "student",
    "avatar": "https://s3.amazonaws.com/bucket/avatars/avatar.jpg",
    "bio": "Student bio",
    "is_active": true,
    "stats": {
        "total_courses": 10,
        "ongoing_courses": 5,
        "completed_courses": 3,
        "total_hours_spent": 45.5,
        "certificates_acquired": 2
    },
    "created_at": "2025-01-01T00:00:00.000000Z"
}
```

---

### 2.2 Update Profile

**Endpoint:** `PUT /api/user/profile`

**Description:** Update user profile

**Authentication:** Required

**Request Body:**

```json
{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "phone": "+9876543210",
    "bio": "Updated bio",
    "avatar": "https://s3.amazonaws.com/bucket/avatars/new_avatar.jpg"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {...}
  }
}
```

---

### 2.3 Change Password

**Endpoint:** `PUT /api/user/password`

**Description:** Change user password

**Authentication:** Required

**Request Body:**

```json
{
    "current_password": "oldpassword123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

---

### 2.4 Delete Account

**Endpoint:** `DELETE /api/user/account`

**Description:** Delete user account (requires password confirmation)

**Authentication:** Required

**Request Body:**

```json
{
    "password": "userpassword123"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Account deleted successfully"
}
```

---

### 2.5 Get User Certificates

**Endpoint:** `GET /api/user/certificates`

**Description:** Get all certificates acquired by user

**Authentication:** Required

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "course": {
                "id": 1,
                "title": "Course Title",
                "image": "https://...",
                "slug": "course-slug"
            },
            "issued_at": "2025-01-15T10:00:00.000000Z"
        }
    ],
    "message": "Certificates retrieved successfully"
}
```

---

## 3. Enrollment APIs

### 3.1 My Ongoing Courses

**Endpoint:** `GET /api/my-ongoing-courses`

**Description:** Get user's ongoing courses with progress

**Authentication:** Required

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "status": "active",
            "progress_percentage": 45.5,
            "course": {
                "id": 1,
                "title": "Course Title",
                "image_url": "https://...",
                "enrollment_count": 500,
                "rating": 4.5
            }
        }
    ],
    "message": "Ongoing courses retrieved successfully"
}
```

---

### 3.2 Saved Courses

**Endpoint:** `GET /api/saved-courses-list`

**Description:** Get user's saved courses

**Authentication:** Required

**Success Response (200):**

```json
{
  "success": true,
  "data": [...],
  "message": "Saved courses retrieved successfully"
}
```

---

### 3.3 Certified Courses

**Endpoint:** `GET /api/certified-courses`

**Description:** Get courses user has certificates for

**Authentication:** Required

**Success Response (200):**

```json
{
  "success": true,
  "data": [...],
  "message": "Certified courses retrieved successfully"
}
```

---

## 4. Notes APIs

### 4.1 Get Course Notes

**Endpoint:** `GET /api/courses/{course_id}/notes`

**Description:** Get all notes for a course

**Authentication:** Required (must be enrolled)

---

### 4.2 Get Module Notes

**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/notes`

**Description:** Get all notes for a module

**Authentication:** Required (must be enrolled)

---

### 4.3 Get Lesson Notes

**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/topics/{topic_id}/notes`

**Description:** Get all notes for a specific lesson/topic

**Authentication:** Required (must be enrolled)

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "notes": "My note text",
            "timestamp_seconds": 120,
            "created_at": "2025-01-15T10:00:00.000000Z",
            "topic": {
                "id": 1,
                "title": "Lesson Title"
            }
        }
    ],
    "message": "Topic notes retrieved successfully"
}
```

---

## 5. Comments APIs

### 5.1 Get Lesson Comments

**Endpoint:** `GET /api/courses/{course_id}/topics/{topic_id}/comments`

**Description:** Get all comments for a lesson

**Authentication:** Required

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "comment": "Great lesson!",
      "user": {
        "id": 5,
        "name": "Student Name",
        "avatar": "https://..."
      },
      "replies": [...],
      "created_at": "2025-01-15T10:00:00.000000Z"
    }
  ],
  "message": "Lesson comments retrieved successfully"
}
```

---

### 5.2 Add Lesson Comment

**Endpoint:** `POST /api/courses/{course_id}/topics/{topic_id}/comments`

**Description:** Add a comment to a lesson

**Authentication:** Required

**Request Body:**

```json
{
    "comment": "This is a great lesson!",
    "parent_id": null
}
```

**Note:** `parent_id` is optional. Use it to reply to another comment.

---

### 5.3 Get Course Comments

**Endpoint:** `GET /api/courses/{course_id}/comments`

**Description:** Get all comments for a course

**Authentication:** Required

---

### 5.4 Add Course Comment

**Endpoint:** `POST /api/courses/{course_id}/comments`

**Description:** Add a comment to a course

**Authentication:** Required

---

## 6. Saved Courses APIs

### 6.1 Get Saved Courses

**Endpoint:** `GET /api/saved-courses`

**Description:** Get user's saved courses

**Authentication:** Required

---

### 6.2 Save Course

**Endpoint:** `POST /api/courses/{course_id}/save`

**Description:** Save a course to user's list

**Authentication:** Required

---

### 6.3 Unsave Course

**Endpoint:** `DELETE /api/courses/{course_id}/unsave`

**Description:** Remove course from saved list

**Authentication:** Required

---

## 7. Category APIs

### 7.1 Get Categories

**Endpoint:** `GET /api/categories`

**Description:** Get all active categories

**Authentication:** Not required

---

### 7.2 Get Category Details

**Endpoint:** `GET /api/categories/{category_id}`

**Description:** Get category with courses

**Authentication:** Not required

---

### 7.3 Get Courses by Category

**Endpoint:** `GET /api/categories/{category_id}/courses`

**Description:** Get courses in a specific category

**Authentication:** Required (for enrollment status)

**Query Parameters:**

-   `per_page` (optional): Items per page (default: 20)

---

## 8. Progress APIs

### 8.1 Mark Lesson Complete

**Endpoint:** `POST /api/topics/{topic_id}/complete`

**Description:** Mark a lesson/topic as completed

**Authentication:** Required

**Success Response (200):**

```json
{
    "success": true,
    "message": "Topic marked as completed",
    "data": {
        "progress": {
            "id": 1,
            "is_completed": true,
            "completion_percentage": 100
        }
    }
}
```

---

## 9. Test/Quiz APIs

### 9.1 Get Module Test

**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/test`

**Description:** Get module test with questions

**Authentication:** Required (must be enrolled)

---

### 9.2 Get Lesson Test

**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/topics/{topic_id}/test`

**Description:** Get lesson/topic test with questions

**Authentication:** Required (must be enrolled)

---

### 9.3 Submit Test

**Endpoint:** `POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/submit`

**Description:** Submit test answers

**Authentication:** Required

**Request Body:**

```json
{
    "answers": {
        "1": "B",
        "2": "A",
        "3": "true"
    }
}
```

---

## Video Transcription

Videos are automatically transcribed in Hausa and English via a background cron job that runs hourly. The transcription updates the `transcript_english` and `transcript_hausa` fields in the topics table.

**Cron Command:** `php artisan videos:transcribe`

**Note:** You need to integrate a transcription service (AWS Transcribe, Google Speech-to-Text, etc.) in the `TranscribeVideos` command.

---

## Performance Notes

-   All APIs use caching to ensure < 3 second response times
-   User-specific data is cached per user
-   Public data is cached globally
-   Cache is automatically cleared on data updates

---

## Course Completion Email

When a user completes a course (reaches 100% progress), they automatically receive a congratulatory email.

**Implementation:**

-   **Trigger:** When enrollment status changes to `completed` in `ProgressController::updateEnrollmentProgress()`
-   **Email Class:** `App\Mail\CourseCompletionMail`
-   **Template:** `resources/views/emails/course-completion.blade.php`
-   **Delivery:** Queued (non-blocking) via `ShouldQueue`
-   **Retry Logic:** 3 attempts with exponential backoff

**Email Includes:**

-   Congratulations message
-   Course title and description
-   Completion statistics (100% completion, lessons count, certificate status)
-   Next steps and recommendations
-   Brand colors (#3E6866 and #50C1AE)

**Note:** Email sending failures are logged but don't affect course completion status.

---

**Last Updated:** January 2025
