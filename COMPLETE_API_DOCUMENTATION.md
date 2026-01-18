# Agrisiti Academy - Complete API Documentation

## Base URL
```
Production: https://academy-backends.agrisiti.com/api
Development: http://localhost:8000/api
```

## Authentication
All protected endpoints require authentication using Bearer tokens. Include the token in the `Authorization` header:
```
Authorization: Bearer {your_token}
```

---

## Table of Contents

1. [Authentication Endpoints](#1-authentication-endpoints)
2. [Category Endpoints](#2-category-endpoints)
3. [Course Endpoints](#3-course-endpoints)
4. [Enrollment Endpoints](#4-enrollment-endpoints)
5. [Module Endpoints](#5-module-endpoints)
6. [Progress Endpoints](#6-progress-endpoints)
7. [Notes Endpoints](#7-notes-endpoints)
8. [Tests/Quizzes Endpoints](#8-testsquizzes-endpoints)
9. [Assignment Endpoints](#9-assignment-endpoints)
10. [Message Endpoints](#10-message-endpoints)

---

## 1. Authentication Endpoints

### 1.1 Register Student
**Endpoint:** `POST /api/register`

**Description:** Register a new student account. A welcome email is automatically sent via queue (non-blocking).

**Authentication:** Not required

**Request Body:**
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

---

### 1.2 Login
**Endpoint:** `POST /api/login`

**Description:** Authenticate user and receive access token

**Authentication:** Not required

**Request Body:**
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
      "avatar": null,
      "bio": null
    },
    "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "The provided credentials are incorrect."
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "Your account is inactive. Please contact support."
}
```

---

### 1.3 Get Current User
**Endpoint:** `GET /api/user`

**Description:** Get authenticated user profile

**Authentication:** Required

**Success Response (200):**
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

---

### 1.4 Update Profile
**Endpoint:** `PUT /api/user/profile`

**Description:** Update authenticated user's profile

**Authentication:** Required

**Request Body:**
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

**Note:** All fields are optional. Only include fields you want to update.

**Success Response (200):**
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

---

### 1.5 Logout
**Endpoint:** `POST /api/logout`

**Description:** Logout and revoke current access token

**Authentication:** Required

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### 1.6 Forgot Password
**Endpoint:** `POST /api/forgot-password`

**Description:** Request password reset link

**Authentication:** Not required

**Request Body:**
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

---

### 1.7 Reset Password
**Endpoint:** `POST /api/reset-password`

**Description:** Reset password using token from email

**Authentication:** Not required

**Request Body:**
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

---

## 2. Category Endpoints

### 2.1 Get All Categories
**Endpoint:** `GET /api/categories`

**Description:** Get all active categories

**Authentication:** Not required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Agriculture",
      "slug": "agriculture",
      "description": "Agricultural courses",
      "image": "https://example.com/image.jpg",
      "is_active": true,
      "sort_order": 1,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "message": "Categories retrieved successfully"
}
```

---

### 2.2 Get Category Details
**Endpoint:** `GET /api/categories/{category_id}`

**Description:** Get category with its courses

**Authentication:** Not required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Agriculture",
    "slug": "agriculture",
    "description": "Agricultural courses",
    "image": "https://example.com/image.jpg",
    "is_active": true,
    "sort_order": 1,
    "courses": [
      {
        "id": 1,
        "title": "Introduction to Farming",
        "slug": "introduction-to-farming",
        "image": "https://example.com/course.jpg",
        "tutor": {
          "id": 2,
          "name": "Primary Tutor",
          "avatar": "https://example.com/tutor.jpg"
        },
        "tutors": [
          {
            "id": 2,
            "name": "Primary Tutor",
            "avatar": "https://example.com/tutor.jpg"
          },
          {
            "id": 3,
            "name": "Additional Tutor",
            "avatar": "https://example.com/tutor2.jpg"
          }
        ]
      }
    ]
  },
  "message": "Category details retrieved successfully"
}
```

**Note:** Courses can have multiple tutors. The `tutor` field contains the primary tutor, while `tutors` array contains all tutors.

---

### 2.3 Get Categories with Courses
**Endpoint:** `GET /api/categories-with-courses`

**Description:** Get all categories with their courses

**Authentication:** Not required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Agriculture",
      "slug": "agriculture",
      "courses": [
        {
          "id": 1,
          "title": "Introduction to Farming",
          "tutor": {...},
          "tutors": [...]
        }
      ]
    }
  ],
  "message": "Categories with courses retrieved successfully"
}
```

---

### 2.4 Get Featured Courses
**Endpoint:** `GET /api/featured-courses`

**Description:** Get featured courses organized by category

**Authentication:** Not required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Agriculture",
      "courses": [
        {
          "id": 1,
          "title": "Featured Course",
          "is_featured": true,
          "tutor": {...},
          "tutors": [...]
        }
      ]
    }
  ],
  "message": "Featured courses per category retrieved successfully"
}
```

---

## 3. Course Endpoints

### 3.1 Get All Courses
**Endpoint:** `GET /api/courses`

**Description:** Get all published courses with filtering and pagination

**Authentication:** Not required

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `category_id` | integer | Filter by category ID | `?category_id=1` |
| `level` | string | Filter by level (beginner, intermediate, advanced) | `?level=beginner` |
| `min_rating` | float | Minimum rating | `?min_rating=4.0` |
| `min_duration` | integer | Minimum duration in minutes | `?min_duration=60` |
| `max_duration` | integer | Maximum duration in minutes | `?max_duration=300` |
| `search` | string | Search in title, description, short_description, tags | `?search=farming` |
| `per_page` | integer | Items per page (default: 20) | `?per_page=10` |

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Introduction to Farming",
      "slug": "introduction-to-farming",
      "short_description": "Learn the basics of farming",
      "description": "Full course description...",
      "image": "https://example.com/course.jpg",
      "level": "beginner",
      "duration_minutes": 120,
      "rating": 4.5,
      "rating_count": 100,
      "enrollment_count": 500,
      "category": {
        "id": 1,
        "name": "Agriculture",
        "slug": "agriculture"
      },
      "tutor": {
        "id": 2,
        "name": "Primary Tutor",
        "avatar": "https://example.com/tutor.jpg"
      },
      "tutors": [
        {
          "id": 2,
          "name": "Primary Tutor",
          "avatar": "https://example.com/tutor.jpg"
        },
        {
          "id": 3,
          "name": "Additional Tutor",
          "avatar": "https://example.com/tutor2.jpg"
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  },
  "message": "Courses retrieved successfully"
}
```

**Example Requests:**
```bash
# Get all courses
GET /api/courses

# Filter by category
GET /api/courses?category_id=1

# Search courses
GET /api/courses?search=farming

# Filter by level and rating
GET /api/courses?level=beginner&min_rating=4.0

# Filter by duration
GET /api/courses?min_duration=60&max_duration=300
```

---

### 3.2 Get Course Details
**Endpoint:** `GET /api/courses/{course_id}`

**Description:** Get full course details including modules, topics, reviews, resources, etc.

**Authentication:** Not required

**Success Response (200):**
```json
{
  "id": 1,
  "title": "Introduction to Farming",
  "slug": "introduction-to-farming",
  "description": "Full course description",
  "short_description": "Brief description",
  "image": "https://example.com/course.jpg",
  "level": "beginner",
  "duration_minutes": 120,
  "rating": 4.5,
  "rating_count": 100,
  "enrollment_count": 500,
  "category": {
    "id": 1,
    "name": "Agriculture",
    "slug": "agriculture"
  },
  "tutor": {
    "id": 2,
    "name": "Primary Tutor",
    "bio": "Tutor biography",
    "avatar": "https://example.com/tutor.jpg"
  },
  "tutors": [
    {
      "id": 2,
      "name": "Primary Tutor",
      "bio": "Tutor biography",
      "avatar": "https://example.com/tutor.jpg"
    }
  ],
  "modules": [
    {
      "id": 1,
      "title": "Module 1: Basics",
      "description": "Module description",
      "topics": [
        {
          "id": 1,
          "title": "Topic 1",
          "content": "Topic content"
        }
      ]
    }
  ],
  "reviews": [
    {
      "id": 1,
      "rating": 5,
      "comment": "Great course!",
      "user": {
        "id": 5,
        "name": "Student Name"
      }
    }
  ],
  "resources": [...],
  "vr_content": [...],
  "diy_content": [...],
  "recommended_courses": [
    {
      "id": 2,
      "title": "Advanced Farming",
      "image": "https://example.com/course2.jpg",
      "slug": "advanced-farming",
      "short_description": "Advanced course description"
    }
  ]
}
```

---

### 3.3 Get Recommended Courses
**Endpoint:** `GET /api/recommended-courses`

**Description:** Get personalized course recommendations for authenticated user

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Recommended Course",
      "slug": "recommended-course",
      "image": "https://example.com/course.jpg",
      "short_description": "Course description",
      "category": {
        "id": 1,
        "name": "Agriculture"
      },
      "tutor": {
        "id": 2,
        "name": "Tutor Name",
        "avatar": "https://example.com/tutor.jpg"
      },
      "tutors": [...],
      "rating": 4.5,
      "enrollment_count": 500
    }
  ],
  "message": "Recommended courses retrieved successfully"
}
```

---

### 3.4 Get Course Modules (Curriculum)
**Endpoint:** `GET /api/courses/{course_id}/modules`

**Description:** Get all modules for a course (curriculum)

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "course_id": 1,
    "course_title": "Introduction to Farming",
    "modules": [
      {
        "id": 1,
        "title": "Module 1: Basics",
        "description": "Module description",
        "sort_order": 1,
        "is_active": true,
        "topics": [
          {
            "id": 1,
            "title": "Topic 1",
            "content": "Topic content",
            "video_url": "https://example.com/video.mp4",
            "sort_order": 1
          }
        ]
      }
    ]
  },
  "message": "Course modules retrieved successfully"
}
```

---

### 3.5 Get Course Information
**Endpoint:** `GET /api/courses/{course_id}/information`

**Description:** Get detailed course information (about, what you will learn, course details)

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Introduction to Farming",
    "description": "Full course description",
    "short_description": "Brief description",
    "what_you_will_learn": "Learning outcomes and objectives",
    "what_you_will_get": "What you'll receive from this course",
    "course_information": "Additional course information",
    "level": "beginner",
    "duration_minutes": 120,
    "language": "English",
    "rating": 4.5,
    "rating_count": 100,
    "enrollment_count": 500,
    "category": {
      "id": 1,
      "name": "Agriculture"
    },
    "tutor": {
      "id": 2,
      "name": "Primary Tutor",
      "bio": "Tutor biography",
      "avatar": "https://example.com/tutor.jpg"
    },
    "tutors": [
      {
        "id": 2,
        "name": "Primary Tutor",
        "bio": "Tutor biography",
        "avatar": "https://example.com/tutor.jpg"
      }
    ]
  },
  "message": "Course information retrieved successfully"
}
```

---

### 3.6 Get Course DIY Content
**Endpoint:** `GET /api/courses/{course_id}/diy-content`

**Description:** Get DIY (Do It Yourself) content for enrolled course

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "DIY Project 1",
      "content": "Project instructions and details",
      "image": "https://example.com/diy.jpg",
      "sort_order": 1
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

---

### 3.7 Get Course Resources
**Endpoint:** `GET /api/courses/{course_id}/resources`

**Description:** Get downloadable resources for enrolled course

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Resource 1",
      "description": "Resource description",
      "file_url": "https://example.com/resource.pdf",
      "file_type": "pdf",
      "file_size": 1024000,
      "sort_order": 1
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

---

## 4. Enrollment Endpoints

### 4.1 Enroll in Course
**Endpoint:** `POST /api/enroll`

**Description:** Enroll in a course using enrollment code

**Authentication:** Required

**Request Body:**
```json
{
  "course_id": 1,
  "enrollment_code": "ENROLL123"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Successfully enrolled in course",
  "data": {
    "enrollment": {
      "id": 1,
      "user_id": 1,
      "course_id": 1,
      "enrollment_code": "ENROLL123",
      "status": "active",
      "enrolled_at": "2025-01-15T10:30:00.000000Z",
      "course": {
        "id": 1,
        "title": "Introduction to Farming",
        "image": "https://example.com/course.jpg",
        "slug": "introduction-to-farming",
        "short_description": "Brief description",
        "category_id": 1,
        "category": {
          "id": 1,
          "name": "Agriculture",
          "slug": "agriculture"
        }
      }
    }
  }
}
```

**Error Responses:**

**400 - Already Enrolled:**
```json
{
  "success": false,
  "message": "You are already enrolled in this course",
  "data": {
    "enrollment": {...}
  }
}
```

**400 - Invalid Code:**
```json
{
  "success": false,
  "message": "Invalid or already used enrollment code"
}
```

**400 - Expired Code:**
```json
{
  "success": false,
  "message": "Enrollment code has expired"
}
```

**400 - Course Not Published:**
```json
{
  "success": false,
  "message": "This course is not available for enrollment"
}
```

---

### 4.2 Get My Enrollments
**Endpoint:** `GET /api/my-enrollments`

**Description:** Get all user enrollments

**Authentication:** Required

**Success Response (200):**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "enrollment_code": "ENROLL123",
    "status": "active",
    "enrolled_at": "2025-01-15T10:30:00.000000Z",
    "course": {
      "id": 1,
      "title": "Introduction to Farming",
      "image": "https://example.com/course.jpg",
      "slug": "introduction-to-farming"
    }
  }
]
```

---

### 4.3 Get My Courses
**Endpoint:** `GET /api/my-courses`

**Description:** Get enrolled courses with progress

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `status` | string | Filter by status (all, active, completed) | `all` |

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "course_id": 1,
      "status": "active",
      "progress_percentage": 45.5,
      "enrolled_at": "2025-01-15T10:30:00.000000Z",
      "course": {
        "id": 1,
        "title": "Introduction to Farming",
        "category": {
          "id": 1,
          "name": "Agriculture"
        },
        "tutor": {
          "id": 2,
          "name": "Primary Tutor"
        },
        "tutors": [
          {
            "id": 2,
            "name": "Primary Tutor",
            "avatar": "https://example.com/tutor.jpg"
          }
        ],
        "modules": [
          {
            "id": 1,
            "title": "Module 1",
            "topics": [...]
          }
        ]
      }
    }
  ],
  "message": "Courses retrieved successfully"
}
```

---

### 4.4 Get Ongoing Courses
**Endpoint:** `GET /api/ongoing-courses`

**Description:** Get active enrollments with progress percentage and course details

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
      "enrolled_at": "2025-01-15T10:30:00.000000Z",
      "course": {
        "id": 1,
        "title": "Introduction to Farming",
        "image": "https://example.com/course.jpg",
        "slug": "introduction-to-farming",
        "short_description": "Brief description",
        "enrollment_count": 500,
        "total_students": 500,
        "rating": 4.5,
        "rating_count": 100,
        "duration_minutes": 120,
        "level": "beginner",
        "category_id": 1,
        "category": {
          "id": 1,
          "name": "Agriculture",
          "slug": "agriculture"
        },
        "tutor": {
          "id": 2,
          "name": "Primary Tutor",
          "avatar": "https://example.com/tutor.jpg"
        },
        "tutors": [
          {
            "id": 2,
            "name": "Primary Tutor",
            "avatar": "https://example.com/tutor.jpg"
          }
        ]
      }
    }
  ],
  "message": "Ongoing courses retrieved successfully"
}
```

---

### 4.5 Get Completed Courses
**Endpoint:** `GET /api/completed-courses`

**Description:** Get completed enrollments

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "completed",
      "completed_at": "2025-01-20T10:30:00.000000Z",
      "course": {
        "id": 1,
        "title": "Introduction to Farming",
        "image": "https://example.com/course.jpg",
        "slug": "introduction-to-farming",
        "short_description": "Brief description",
        "enrollment_count": 500,
        "rating": 4.5,
        "rating_count": 100,
        "duration_minutes": 120,
        "level": "beginner",
        "category": {
          "id": 1,
          "name": "Agriculture",
          "slug": "agriculture"
        },
        "tutor": {
          "id": 2,
          "name": "Primary Tutor",
          "avatar": "https://example.com/tutor.jpg"
        },
        "tutors": [...]
      }
    }
  ],
  "message": "Completed courses retrieved successfully"
}
```

---

### 4.6 Get Enrollment Details
**Endpoint:** `GET /api/enrollments/{enrollment_id}`

**Description:** Get specific enrollment details with course modules and tutors

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "status": "active",
    "enrolled_at": "2025-01-15T10:30:00.000000Z",
    "course": {
      "id": 1,
      "title": "Introduction to Farming",
      "modules": [
        {
          "id": 1,
          "title": "Module 1",
          "topics": [...]
        }
      ],
      "tutor": {
        "id": 2,
        "name": "Primary Tutor",
        "avatar": "https://example.com/tutor.jpg"
      },
      "tutors": [
        {
          "id": 2,
          "name": "Primary Tutor",
          "avatar": "https://example.com/tutor.jpg"
        }
      ]
    }
  },
  "message": "Enrollment details retrieved successfully"
}
```

**Error Response (403):**
```json
{
  "message": "Unauthorized"
}
```

---

## 5. Module Endpoints

### 5.1 Get Module Details
**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}`

**Description:** Get module with topics, test, and user progress

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "module": {
      "id": 1,
      "title": "Module 1: Basics",
      "description": "Module description",
      "sort_order": 1,
      "is_active": true,
      "topics": [
        {
          "id": 1,
          "title": "Topic 1",
          "content": "Topic content",
          "video_url": "https://example.com/video.mp4",
          "is_completed": true,
          "completion_percentage": 100,
          "sort_order": 1
        }
      ],
      "test": {
        "id": 1,
        "title": "Module 1 Test",
        "description": "Test description",
        "passing_score": 70,
        "time_limit": 30,
        "is_active": true,
        "questions": [
          {
            "id": 1,
            "question": "What is agriculture?",
            "question_type": "mcq",
            "options": {
              "A": "Option A",
              "B": "Option B",
              "C": "Option C",
              "D": "Option D"
            },
            "correct_answer": "B",
            "points": 1,
            "sort_order": 1
          }
        ]
      }
    },
    "course": {
      "id": 1,
      "title": "Introduction to Farming"
    }
  },
  "message": "Module details retrieved successfully"
}
```

**Error Responses:**

**403 - Not Enrolled:**
```json
{
  "success": false,
  "message": "You must be enrolled in this course to access module details"
}
```

**404 - Module Not Found:**
```json
{
  "success": false,
  "message": "Module not found in this course"
}
```

---

## 6. Progress Endpoints

### 6.1 Get Course Progress
**Endpoint:** `GET /api/courses/{course_id}/progress`

**Description:** Get overall progress for a course

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "course_id": 1,
    "overall_progress": 45.5,
    "total_topics": 20,
    "completed_topics": 9,
    "topics": [
      {
        "id": 1,
        "title": "Topic 1",
        "module_id": 1,
        "is_completed": true,
        "completion_percentage": 100,
        "last_accessed_at": "2025-01-10T00:00:00.000000Z"
      },
      {
        "id": 2,
        "title": "Topic 2",
        "module_id": 1,
        "is_completed": false,
        "completion_percentage": 0,
        "last_accessed_at": null
      }
    ]
  },
  "message": "Course progress retrieved successfully"
}
```

**Error Response (403):**
```json
{
  "message": "You are not enrolled in this course"
}
```

---

### 6.2 Mark Topic as Complete
**Endpoint:** `POST /api/topics/{topic_id}/complete`

**Description:** Mark a topic as completed

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "message": "Topic marked as completed",
  "data": {
    "progress": {
      "id": 1,
      "user_id": 1,
      "topic_id": 1,
      "course_id": 1,
      "is_completed": true,
      "completion_percentage": 100,
      "completed_at": "2025-01-10T00:00:00.000000Z",
      "last_accessed_at": "2025-01-10T00:00:00.000000Z"
    }
  }
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not enrolled in this course"
}
```

---

### 6.3 Update Progress
**Endpoint:** `PUT /api/progress/{progress_id}`

**Description:** Update progress for a topic (e.g., watch time, completion percentage)

**Authentication:** Required

**Request Body:**
```json
{
  "watch_time_seconds": 300,
  "completion_percentage": 75
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "topic_id": 1,
    "course_id": 1,
    "completion_percentage": 75,
    "watch_time_seconds": 300,
    "is_completed": false,
    "last_accessed_at": "2025-01-10T00:00:00.000000Z"
  },
  "message": "Progress updated successfully"
}
```

**Note:** If `completion_percentage` reaches 100, the topic is automatically marked as completed.

---

### 6.4 Mark Quiz as Complete
**Endpoint:** `POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/complete-quiz`

**Description:** Mark quiz completion in progress tracking

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "test_attempt": {
      "id": 1,
      "is_passed": true,
      "score": 8,
      "percentage": 80
    },
    "is_passed": true,
    "score": 8,
    "percentage": 80
  },
  "message": "Quiz marked as completed"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not enrolled in this course"
}
```

---

## 7. Notes Endpoints

### 7.1 Get Course Notes
**Endpoint:** `GET /api/courses/{course_id}/notes`

**Description:** Get all notes for a course

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "course_id": 1,
      "module_id": 1,
      "topic_id": 1,
      "notes": "My note text",
      "timestamp_seconds": 120,
      "is_public": false,
      "created_at": "2025-01-10T00:00:00.000000Z",
      "topic": {
        "id": 1,
        "title": "Topic 1",
        "module_id": 1,
        "module": {
          "id": 1,
          "title": "Module 1"
        }
      }
    }
  ],
  "message": "All course notes retrieved successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not enrolled in this course"
}
```

---

### 7.2 Get Module Notes
**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/notes`

**Description:** Get notes for a specific module

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "notes": "Module note",
      "topic_id": 1,
      "created_at": "2025-01-10T00:00:00.000000Z",
      "topic": {
        "id": 1,
        "title": "Topic 1",
        "module_id": 1,
        "module": {
          "id": 1,
          "title": "Module 1"
        }
      }
    }
  ],
  "message": "Module notes retrieved successfully"
}
```

---

### 7.3 Create Note
**Endpoint:** `POST /api/notes`

**Description:** Create a new note

**Authentication:** Required

**Request Body:**
```json
{
  "course_id": 1,
  "topic_id": 1,
  "notes": "My note text",
  "timestamp_seconds": 120,
  "is_public": false
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "topic_id": 1,
    "notes": "My note text",
    "timestamp_seconds": 120,
    "is_public": false,
    "created_at": "2025-01-10T00:00:00.000000Z",
    "topic": {
      "id": 1,
      "title": "Topic 1"
    }
  },
  "message": "Note created successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not enrolled in this course"
}
```

---

### 7.4 Update Note
**Endpoint:** `PUT /api/notes/{note_id}`

**Description:** Update an existing note

**Authentication:** Required

**Request Body:**
```json
{
  "notes": "Updated note text",
  "is_public": true
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "notes": "Updated note text",
    "is_public": true,
    "updated_at": "2025-01-10T00:00:00.000000Z",
    "topic": {
      "id": 1,
      "title": "Topic 1"
    }
  },
  "message": "Note updated successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

---

### 7.5 Delete Note
**Endpoint:** `DELETE /api/notes/{note_id}`

**Description:** Delete a note

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "message": "Note deleted successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

---

## 8. Tests/Quizzes Endpoints

### 8.1 Get Module Test
**Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/test`

**Description:** Get test/quiz for a module with questions and user attempts

**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "test": {
      "id": 1,
      "title": "Module 1 Test",
      "description": "Test description",
      "passing_score": 70,
      "time_limit": 30,
      "is_active": true,
      "questions": [
        {
          "id": 1,
          "question": "What is agriculture?",
          "question_type": "mcq",
          "options": {
            "A": "Option A",
            "B": "Option B",
            "C": "Option C",
            "D": "Option D"
          },
          "correct_answer": "B",
          "points": 1,
          "sort_order": 1
        }
      ]
    },
    "attempts": [
      {
        "id": 1,
        "score": 8,
        "total_questions": 10,
        "percentage": 80,
        "is_passed": true,
        "completed_at": "2025-01-10T00:00:00.000000Z"
      }
    ],
    "has_attempted": true,
    "best_score": 80,
    "is_passed": true
  },
  "message": "Module test retrieved successfully"
}
```

**Question Types:**
- `mcq`: Multiple Choice Question
- `true_false`: True or False
- `open`: Open-ended question

**Error Responses:**

**403 - Not Enrolled:**
```json
{
  "success": false,
  "message": "You must be enrolled in this course to access tests"
}
```

**404 - No Test:**
```json
{
  "success": false,
  "message": "No test available for this module"
}
```

---

### 8.2 Submit Test
**Endpoint:** `POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/submit`

**Description:** Submit test answers and get results

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

**Note:** Answers should be an object with question IDs as keys and answers as values.

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "attempt": {
      "id": 1,
      "module_test_id": 1,
      "user_id": 1,
      "answers": {
        "1": "B",
        "2": "A",
        "3": "true"
      },
      "score": 8,
      "total_questions": 10,
      "percentage": 80,
      "is_passed": true,
      "started_at": "2025-01-10T00:00:00.000000Z",
      "completed_at": "2025-01-10T00:00:00.000000Z"
    },
    "score": 8,
    "total_questions": 10,
    "percentage": 80,
    "is_passed": true,
    "passing_score": 70
  },
  "message": "Test passed successfully"
}
```

**Error Responses:**

**403 - Not Enrolled:**
```json
{
  "success": false,
  "message": "You must be enrolled in this course to take tests"
}
```

**404 - Test Not Found:**
```json
{
  "success": false,
  "message": "Test not found in this module"
}
```

---

## 9. Assignment Endpoints

### 9.1 Get Course Assignments
**Endpoint:** `GET /api/courses/{course_id}/assignments`

**Description:** Get all assignments for a course with user's submissions

**Authentication:** Required

**Success Response (200):**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "title": "Assignment 1",
    "description": "Assignment description",
    "instructions": "Assignment instructions",
    "due_date": "2025-02-01T00:00:00.000000Z",
    "points": 100,
    "is_active": true,
    "sort_order": 1,
    "submissions": [
      {
        "id": 1,
        "submission_content": "My assignment answer",
        "file_path": "assignments/submission.pdf",
        "file_name": "submission.pdf",
        "status": "submitted",
        "submitted_at": "2025-01-15T00:00:00.000000Z"
      }
    ]
  }
]
```

**Error Response (403):**
```json
{
  "message": "You are not enrolled in this course"
}
```

---

### 9.2 Get Assignment Details
**Endpoint:** `GET /api/assignments/{assignment_id}`

**Description:** Get assignment details with user's submissions

**Authentication:** Required

**Success Response (200):**
```json
{
  "id": 1,
  "course_id": 1,
  "title": "Assignment 1",
  "description": "Full description",
  "instructions": "Assignment instructions",
  "due_date": "2025-02-01T00:00:00.000000Z",
  "points": 100,
  "is_active": true,
  "submissions": [
    {
      "id": 1,
      "submission_content": "My assignment answer",
      "file_path": "assignments/submission.pdf",
      "file_name": "submission.pdf",
      "status": "submitted",
      "submitted_at": "2025-01-15T00:00:00.000000Z"
    }
  ]
}
```

---

### 9.3 Submit Assignment
**Endpoint:** `POST /api/assignments/{assignment_id}/submit`

**Description:** Submit or update assignment submission

**Authentication:** Required

**Request Body (multipart/form-data):**
```
submission_content: "My assignment answer"
file: [binary file] (optional, max 10MB)
```

**Success Response (201):**
```json
{
  "id": 1,
  "assignment_id": 1,
  "user_id": 1,
  "submission_content": "My assignment answer",
  "file_path": "assignments/submission.pdf",
  "file_name": "submission.pdf",
  "status": "submitted",
  "submitted_at": "2025-01-15T00:00:00.000000Z"
}
```

**Note:** If a submission already exists, it will be updated. Otherwise, a new submission is created.

---

### 9.4 Get My Submissions
**Endpoint:** `GET /api/my-submissions`

**Description:** Get all assignment submissions by user

**Authentication:** Required

**Success Response (200):**
```json
[
  {
    "id": 1,
    "assignment_id": 1,
    "user_id": 1,
    "submission_content": "My answer",
    "file_path": "assignments/submission.pdf",
    "file_name": "submission.pdf",
    "status": "submitted",
    "score": 85,
    "feedback": "Good work!",
    "submitted_at": "2025-01-15T00:00:00.000000Z",
    "assignment": {
      "id": 1,
      "title": "Assignment 1",
      "course": {
        "id": 1,
        "title": "Introduction to Farming"
      }
    }
  }
]
```

---

## 10. Message Endpoints

### 10.1 Get Course Messages
**Endpoint:** `GET /api/courses/{course_id}/messages`

**Description:** Get messages/announcements for a course (where user is sender or recipient)

**Authentication:** Required

**Success Response (200):**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "sender_id": 2,
    "recipient_id": 1,
    "subject": "Important Announcement",
    "message": "Message content",
    "is_read": false,
    "created_at": "2025-01-10T00:00:00.000000Z",
    "sender": {
      "id": 2,
      "name": "Tutor Name",
      "avatar": "https://example.com/avatar.jpg"
    },
    "recipient": {
      "id": 1,
      "name": "Student Name",
      "avatar": "https://example.com/avatar.jpg"
    }
  }
]
```

**Error Response (403):**
```json
{
  "message": "You are not enrolled in this course"
}
```

---

### 10.2 Send Message
**Endpoint:** `POST /api/messages`

**Description:** Send a message to a tutor or student in a course

**Authentication:** Required

**Request Body:**
```json
{
  "course_id": 1,
  "recipient_id": 2,
  "subject": "Question about course",
  "message": "My message content"
}
```

**Success Response (201):**
```json
{
  "id": 1,
  "sender_id": 1,
  "recipient_id": 2,
  "course_id": 1,
  "subject": "Question about course",
  "message": "My message content",
  "is_read": false,
  "created_at": "2025-01-10T00:00:00.000000Z",
  "sender": {
    "id": 1,
    "name": "Student Name",
    "avatar": "https://example.com/avatar.jpg"
  },
  "recipient": {
    "id": 2,
    "name": "Tutor Name",
    "avatar": "https://example.com/avatar.jpg"
  }
}
```

**Error Responses:**

**400 - Invalid Recipient:**
```json
{
  "message": "Invalid recipient"
}
```

**403 - Not Enrolled:**
```json
{
  "message": "You are not enrolled in this course"
}
```

---

### 10.3 Get Message Details
**Endpoint:** `GET /api/messages/{message_id}`

**Description:** Get message details (automatically marks as read if user is recipient)

**Authentication:** Required

**Success Response (200):**
```json
{
  "id": 1,
  "course_id": 1,
  "sender_id": 2,
  "recipient_id": 1,
  "subject": "Question about course",
  "message": "Message content",
  "is_read": true,
  "read_at": "2025-01-10T00:00:00.000000Z",
  "created_at": "2025-01-10T00:00:00.000000Z",
  "sender": {
    "id": 2,
    "name": "Tutor Name",
    "avatar": "https://example.com/avatar.jpg"
  },
  "recipient": {
    "id": 1,
    "name": "Student Name",
    "avatar": "https://example.com/avatar.jpg"
  },
  "course": {
    "id": 1,
    "title": "Introduction to Farming"
  }
}
```

**Error Response (403):**
```json
{
  "message": "Unauthorized"
}
```

---

### 10.4 Mark Message as Read
**Endpoint:** `PUT /api/messages/{message_id}/read`

**Description:** Mark a message as read

**Authentication:** Required

**Success Response (200):**
```json
{
  "id": 1,
  "is_read": true,
  "read_at": "2025-01-10T00:00:00.000000Z"
}
```

**Error Response (403):**
```json
{
  "message": "Unauthorized"
}
```

---

## Common Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "You must be enrolled in this course to access this resource"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "The field name is required.",
      "The field name must be a valid email."
    ]
  }
}
```

### 500 Internal Server Error
```json
{
  "message": "Server Error"
}
```

---

## Response Format

All API responses follow a consistent format:

**Success Response:**
```json
{
  "success": true,
  "data": {...},
  "message": "Operation successful"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message"
}
```

**Pagination Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  },
  "message": "Data retrieved successfully"
}
```

---

## Important Notes

### Multiple Tutors Support
- Courses can have multiple tutors
- The `tutor` field contains the primary tutor (for backward compatibility)
- The `tutors` array contains all tutors assigned to the course
- Both fields are included in all course-related responses

### Enrollment Codes
- Enrollment codes are required to enroll in courses
- Codes are unique per course
- Codes can expire (check `expires_at` field)
- Codes can only be used once

### Progress Tracking
- Progress is automatically calculated based on completed topics
- Progress percentage is updated when topics are marked as complete
- Course is automatically marked as completed when progress reaches 100%

### Welcome Email
- Welcome emails are sent automatically after registration
- Emails are sent via queue (asynchronous, non-blocking)
- Registration succeeds even if email sending fails

### Timestamps
- All timestamps are in ISO 8601 format (UTC)
- Example: `2025-01-15T10:30:00.000000Z`

### Image URLs
- All image URLs are full URLs (not relative paths)
- Example: `https://example.com/image.jpg`

---

## Testing Examples

### Complete Registration and Login Flow

```javascript
// 1. Register
const registerResponse = await fetch('http://localhost:8000/api/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john.doe@example.com',
    password: 'SecurePassword123!',
    password_confirmation: 'SecurePassword123!',
    phone: '+1234567890'
  })
});

const registerData = await registerResponse.json();
const token = registerData.data.token;

// 2. Login
const loginResponse = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'john.doe@example.com',
    password: 'SecurePassword123!'
  })
});

const loginData = await loginResponse.json();
const loginToken = loginData.data.token;

// 3. Get Categories
const categoriesResponse = await fetch('http://localhost:8000/api/categories', {
  headers: {
    'Accept': 'application/json'
  }
});

// 4. Get Courses
const coursesResponse = await fetch('http://localhost:8000/api/courses?category_id=1', {
  headers: {
    'Accept': 'application/json'
  }
});

// 5. Enroll in Course
const enrollResponse = await fetch('http://localhost:8000/api/enroll', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    course_id: 1,
    enrollment_code: 'ENROLL123'
  })
});

// 6. Get My Courses
const myCoursesResponse = await fetch('http://localhost:8000/api/my-courses', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

---

## Rate Limiting

Currently, there are no rate limits implemented. Consider implementing rate limiting for production use.

---

## Support

For issues or questions, please contact the Agrisiti Academy support team.

**Last Updated:** January 2025




