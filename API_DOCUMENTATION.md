# Agrisiti LMS Mobile App API Documentation

## Base URL
```
http://your-domain.com/api
```

## Authentication
All protected endpoints require authentication using Bearer tokens. Include the token in the `Authorization` header:
```
Authorization: Bearer {your_token}
```

---

## 1. Authentication Endpoints

### 1.1 Login
**Endpoint:** `POST /login`

**Description:** Authenticate user and receive access token

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
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
      "email": "user@example.com",
      "phone": "+1234567890",
      "role": "student",
      "avatar": "https://example.com/avatar.jpg",
      "bio": "Student bio"
    },
    "token": "1|xxxxxxxxxxxxx",
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

---

### 1.2 Register
**Endpoint:** `POST /register`

**Description:** Register a new student account

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
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
      "email": "user@example.com",
      "phone": "+1234567890",
      "role": "student",
      "avatar": null
    },
    "token": "1|xxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

---

### 1.3 Get Current User
**Endpoint:** `GET /user`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get authenticated user profile

**Success Response (200):**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "user@example.com",
  "phone": "+1234567890",
  "role": "student",
  "avatar": "https://example.com/avatar.jpg",
  "bio": "Student bio",
  "is_active": true,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

---

### 1.4 Update Profile
**Endpoint:** `PUT /user/profile`

**Headers:** `Authorization: Bearer {token}`

**Description:** Update user profile information

**Request Body:**
```json
{
  "name": "John Updated",
  "email": "newemail@example.com",
  "phone": "+1234567890",
  "bio": "Updated bio",
  "avatar": "https://example.com/new-avatar.jpg",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
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
      "email": "newemail@example.com",
      "phone": "+1234567890",
      "bio": "Updated bio",
      "avatar": "https://example.com/new-avatar.jpg",
      "role": "student"
    }
  }
}
```

---

### 1.5 Logout
**Endpoint:** `POST /logout`

**Headers:** `Authorization: Bearer {token}`

**Description:** Logout and revoke current access token

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### 1.6 Forgot Password
**Endpoint:** `POST /forgot-password`

**Description:** Request password reset link

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password reset link has been sent to your email address."
}
```

---

### 1.7 Reset Password
**Endpoint:** `POST /reset-password`

**Description:** Reset password using token from email

**Request Body:**
```json
{
  "token": "reset_token_from_email",
  "email": "user@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password has been reset successfully. You can now login with your new password."
}
```

---

## 2. Categories Endpoints

### 2.1 Get All Categories
**Endpoint:** `GET /categories`

**Description:** Get all active categories

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
      "sort_order": 1
    }
  ],
  "message": "Categories retrieved successfully"
}
```

---

### 2.2 Get Category Details
**Endpoint:** `GET /categories/{category_id}`

**Description:** Get category with its courses

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Agriculture",
    "slug": "agriculture",
    "description": "Agricultural courses",
    "courses": [
      {
        "id": 1,
        "title": "Introduction to Farming",
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

---

### 2.3 Get Categories with Courses
**Endpoint:** `GET /categories-with-courses`

**Description:** Get all categories with their courses

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Agriculture",
      "courses": [...]
    }
  ],
  "message": "Categories with courses retrieved successfully"
}
```

---

### 2.4 Get Featured Courses
**Endpoint:** `GET /featured-courses`

**Description:** Get featured courses organized by category

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
          "is_featured": true
        }
      ]
    }
  ],
  "message": "Featured courses per category retrieved successfully"
}
```

---

## 3. Courses Endpoints

**Note:** Courses can have multiple tutors. The `tutor` field contains the primary tutor (for backward compatibility), while the `tutors` array contains all tutors assigned to the course.

### 3.1 Get All Courses
**Endpoint:** `GET /courses`

**Query Parameters:**
- `category_id` (optional): Filter by category
- `level` (optional): Filter by level (beginner, intermediate, advanced)
- `min_rating` (optional): Minimum rating
- `min_duration` (optional): Minimum duration in minutes
- `max_duration` (optional): Maximum duration in minutes
- `search` (optional): Search in title, description, tags
- `per_page` (optional): Items per page (default: 20)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Introduction to Farming",
      "slug": "introduction-to-farming",
      "short_description": "Learn the basics",
      "description": "Full course description",
      "image": "https://example.com/course.jpg",
      "level": "beginner",
      "duration_minutes": 120,
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

---

### 3.2 Get Course Details
**Endpoint:** `GET /courses/{course_id}`

**Description:** Get full course details with modules, topics, reviews, etc.

**Success Response (200):**
```json
{
  "id": 1,
  "title": "Introduction to Farming",
  "slug": "introduction-to-farming",
  "description": "Full description",
  "short_description": "Brief description",
  "image": "https://example.com/course.jpg",
  "level": "beginner",
  "duration_minutes": 120,
  "rating": 4.5,
  "rating_count": 100,
  "enrollment_count": 500,
  "category": {...},
  "tutor": {...},
  "modules": [
    {
      "id": 1,
      "title": "Module 1",
      "description": "Module description",
      "topics": [...]
    }
  ],
  "reviews": [...],
  "resources": [...],
  "recommended_courses": [...]
}
```

---

### 3.3 Get Recommended Courses
**Endpoint:** `GET /recommended-courses`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get personalized course recommendations

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Recommended Course",
      "category": {...},
      "tutor": {...}
    }
  ],
  "message": "Recommended courses retrieved successfully"
}
```

---

### 3.4 Get Course Modules (Curriculum)
**Endpoint:** `GET /courses/{course_id}/modules`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get all modules for a course (curriculum)

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
**Endpoint:** `GET /courses/{course_id}/information`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get detailed course information

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Introduction to Farming",
    "description": "Full description",
    "short_description": "Brief description",
    "what_you_will_learn": "Learning outcomes",
    "what_you_will_get": "What you'll receive",
    "course_information": "Additional information",
    "level": "beginner",
    "duration_minutes": 120,
    "language": "English",
    "rating": 4.5,
    "rating_count": 100,
    "enrollment_count": 500,
    "category": {...},
    "tutor": {...}
  },
  "message": "Course information retrieved successfully"
}
```

---

### 3.6 Get Course DIY Content
**Endpoint:** `GET /courses/{course_id}/diy-content`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get DIY (Do It Yourself) content for enrolled course

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "DIY Project 1",
      "content": "Project instructions",
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
**Endpoint:** `GET /courses/{course_id}/resources`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get downloadable resources for enrolled course

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Resource 1",
      "file_url": "https://example.com/resource.pdf",
      "file_type": "pdf",
      "sort_order": 1
    }
  ],
  "message": "Course resources retrieved successfully"
}
```

---

## 4. Enrollment Endpoints

### 4.1 Enroll in Course
**Endpoint:** `POST /enroll`

**Headers:** `Authorization: Bearer {token}`

**Description:** Enroll in a course using enrollment code

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
      "status": "active",
      "enrolled_at": "2024-01-01T00:00:00.000000Z",
      "course": {
        "id": 1,
        "title": "Introduction to Farming",
        "image": "https://example.com/course.jpg",
        "slug": "introduction-to-farming",
        "short_description": "Brief description"
      }
    }
  }
}
```

**Error Responses:**
- **400:** Already enrolled, invalid code, or expired code
- **400:** Course not published

---

### 4.2 Get My Enrollments
**Endpoint:** `GET /my-enrollments`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get all user enrollments

**Success Response (200):**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "status": "active",
    "enrolled_at": "2024-01-01T00:00:00.000000Z",
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
**Endpoint:** `GET /my-courses`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status (all, active, completed) - default: "all"

**Description:** Get enrolled courses with progress

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
      "course": {
        "id": 1,
        "title": "Introduction to Farming",
        "category": {...},
        "tutor": {...},
        "modules": [...]
      }
    }
  ],
  "message": "Courses retrieved successfully"
}
```

---

### 4.4 Get Ongoing Courses
**Endpoint:** `GET /ongoing-courses`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get active enrollments with progress

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
        "title": "Introduction to Farming",
        "image": "https://example.com/course.jpg",
        "enrollment_count": 500,
        "rating": 4.5,
        "category": {...},
        "tutor": {...}
      }
    }
  ],
  "message": "Ongoing courses retrieved successfully"
}
```

---

### 4.5 Get Completed Courses
**Endpoint:** `GET /completed-courses`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get completed enrollments

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "completed",
      "completed_at": "2024-01-15T00:00:00.000000Z",
      "course": {...}
    }
  ],
  "message": "Completed courses retrieved successfully"
}
```

---

### 4.6 Get Enrollment Details
**Endpoint:** `GET /enrollments/{enrollment_id}`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get specific enrollment details

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "status": "active",
    "course": {
      "modules": [...],
      "tutor": {...}
    }
  },
  "message": "Enrollment details retrieved successfully"
}
```

---

## 5. Modules Endpoints

### 5.1 Get Module Details
**Endpoint:** `GET /courses/{course_id}/modules/{module_id}`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get module with topics, test, and user progress

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
        "questions": [...]
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
- **403:** Not enrolled in course
- **404:** Module not found

---

## 6. Progress Endpoints

### 6.1 Get Course Progress
**Endpoint:** `GET /courses/{course_id}/progress`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get overall progress for a course

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
        "last_accessed_at": "2024-01-10T00:00:00.000000Z"
      }
    ]
  },
  "message": "Course progress retrieved successfully"
}
```

---

### 6.2 Mark Topic as Complete
**Endpoint:** `POST /topics/{topic_id}/complete`

**Headers:** `Authorization: Bearer {token}`

**Description:** Mark a topic as completed

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
      "completed_at": "2024-01-10T00:00:00.000000Z"
    }
  }
}
```

---

### 6.3 Update Progress
**Endpoint:** `PUT /progress/{progress_id}`

**Headers:** `Authorization: Bearer {token}`

**Description:** Update progress for a topic (e.g., watch time, completion percentage)

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
    "completion_percentage": 75,
    "watch_time_seconds": 300,
    "last_accessed_at": "2024-01-10T00:00:00.000000Z"
  },
  "message": "Progress updated successfully"
}
```

---

## 7. Tests/Quizzes Endpoints

### 7.1 Get Module Test
**Endpoint:** `GET /courses/{course_id}/modules/{module_id}/test`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get test/quiz for a module with questions

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
        "completed_at": "2024-01-10T00:00:00.000000Z"
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

---

### 7.2 Submit Test
**Endpoint:** `POST /courses/{course_id}/modules/{module_id}/tests/{test_id}/submit`

**Headers:** `Authorization: Bearer {token}`

**Description:** Submit test answers and get results

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
      "score": 8,
      "total_questions": 10,
      "percentage": 80,
      "is_passed": true,
      "answers": {
        "1": "B",
        "2": "A"
      }
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

---

### 7.3 Mark Quiz as Complete (Progress)
**Endpoint:** `POST /courses/{course_id}/modules/{module_id}/tests/{test_id}/complete-quiz`

**Headers:** `Authorization: Bearer {token}`

**Description:** Mark quiz completion in progress tracking

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

---

## 8. Notes Endpoints

### 8.1 Get Course Notes
**Endpoint:** `GET /courses/{course_id}/notes`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get all notes for a course

**Success Response (200):**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "module_id": 1,
    "topic_id": 1,
    "note": "My note text",
    "created_at": "2024-01-10T00:00:00.000000Z"
  }
]
```

---

### 8.2 Get Module Notes
**Endpoint:** `GET /courses/{course_id}/modules/{module_id}/notes`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get notes for a specific module

**Success Response (200):**
```json
[
  {
    "id": 1,
    "note": "Module note",
    "topic_id": 1,
    "created_at": "2024-01-10T00:00:00.000000Z"
  }
]
```

---

### 8.3 Create Note
**Endpoint:** `POST /notes`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "course_id": 1,
  "module_id": 1,
  "topic_id": 1,
  "note": "My note text"
}
```

**Success Response (201):**
```json
{
  "id": 1,
  "user_id": 1,
  "course_id": 1,
  "module_id": 1,
  "topic_id": 1,
  "note": "My note text",
  "created_at": "2024-01-10T00:00:00.000000Z"
}
```

---

### 8.4 Update Note
**Endpoint:** `PUT /notes/{note_id}`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "note": "Updated note text"
}
```

**Success Response (200):**
```json
{
  "id": 1,
  "note": "Updated note text",
  "updated_at": "2024-01-10T00:00:00.000000Z"
}
```

---

### 8.5 Delete Note
**Endpoint:** `DELETE /notes/{note_id}`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "message": "Note deleted successfully"
}
```

---

## 9. Assignments Endpoints

### 9.1 Get Course Assignments
**Endpoint:** `GET /courses/{course_id}/assignments`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get all assignments for a course

**Success Response (200):**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "title": "Assignment 1",
    "description": "Assignment description",
    "due_date": "2024-02-01T00:00:00.000000Z",
    "points": 100
  }
]
```

---

### 9.2 Get Assignment Details
**Endpoint:** `GET /assignments/{assignment_id}`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "id": 1,
  "course_id": 1,
  "title": "Assignment 1",
  "description": "Full description",
  "due_date": "2024-02-01T00:00:00.000000Z",
  "points": 100,
  "instructions": "Assignment instructions"
}
```

---

### 9.3 Submit Assignment
**Endpoint:** `POST /assignments/{assignment_id}/submit`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "submission_text": "My assignment answer",
  "file_url": "https://example.com/file.pdf"
}
```

**Success Response (201):**
```json
{
  "id": 1,
  "assignment_id": 1,
  "user_id": 1,
  "submission_text": "My assignment answer",
  "file_url": "https://example.com/file.pdf",
  "submitted_at": "2024-01-15T00:00:00.000000Z"
}
```

---

### 9.4 Get My Submissions
**Endpoint:** `GET /my-submissions`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get all assignment submissions by user

**Success Response (200):**
```json
[
  {
    "id": 1,
    "assignment_id": 1,
    "submission_text": "My answer",
    "score": 85,
    "feedback": "Good work!",
    "submitted_at": "2024-01-15T00:00:00.000000Z"
  }
]
```

---

## 10. Messages Endpoints

### 10.1 Get Course Messages
**Endpoint:** `GET /courses/{course_id}/messages`

**Headers:** `Authorization: Bearer {token}`

**Description:** Get messages/announcements for a course

**Success Response (200):**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "sender_id": 2,
    "subject": "Important Announcement",
    "message": "Message content",
    "is_read": false,
    "created_at": "2024-01-10T00:00:00.000000Z",
    "sender": {
      "id": 2,
      "name": "Tutor Name",
      "avatar": "https://example.com/avatar.jpg"
    }
  }
]
```

---

### 10.2 Send Message
**Endpoint:** `POST /messages`

**Headers:** `Authorization: Bearer {token}`

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
  "created_at": "2024-01-10T00:00:00.000000Z"
}
```

---

### 10.3 Get Message Details
**Endpoint:** `GET /messages/{message_id}`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "id": 1,
  "subject": "Question about course",
  "message": "Message content",
  "sender": {...},
  "recipient": {...},
  "course": {...},
  "created_at": "2024-01-10T00:00:00.000000Z"
}
```

---

### 10.4 Mark Message as Read
**Endpoint:** `PUT /messages/{message_id}/read`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "message": "Message marked as read",
  "is_read": true
}
```

---

## Error Responses

All endpoints may return these error responses:

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
    "email": [
      "The email field is required."
    ]
  }
}
```

---

## Mobile App Flow

### Typical User Journey:

1. **Login/Register** → Get access token
2. **Browse Courses** → Get categories and courses
3. **View Course Details** → Get full course information
4. **Enroll** → Use enrollment code to enroll
5. **View Curriculum** → Get course modules and topics
6. **Study Topics** → Mark topics as complete, track progress
7. **Take Tests** → Get test, submit answers, view results
8. **Track Progress** → View overall course progress
9. **Notes** → Create, read, update notes
10. **Messages** → Communicate with tutors

---

## Notes

- All timestamps are in ISO 8601 format
- Image URLs are full URLs (not relative paths)
- The `Authorization: Bearer {token}` header is required for all protected endpoints
- Enrollment codes are required and unique per course
- Progress is automatically calculated based on completed topics
- Tests can be retaken (multiple attempts allowed)

---

## Base URL Configuration

Update the base URL in your mobile app configuration:
```
Production: https://your-domain.com/api
Development: http://localhost:8000/api
```

---

**Last Updated:** January 2024

