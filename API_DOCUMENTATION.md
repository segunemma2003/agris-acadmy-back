````

---

## Table of Contents

1. [Authentication APIs](#authentication-apis)
2. [Category APIs](#category-apis)
3. [Course APIs](#course-apis)
4. [Enrollment APIs](#enrollment-apis)
5. [Module APIs](#module-apis)
6. [Progress APIs](#progress-apis)
7. [Notes APIs](#notes-apis)
8. [Test/Quiz APIs](#testquiz-apis)
9. [Assignment APIs](#assignment-apis)
10. [Message APIs](#message-apis)

---

## Authentication APIs

### 1. Register Student

**Endpoint:** `POST /api/register`
**Authentication:** Not Required
**Description:** Register a new student account

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+1234567890" // optional
}
````

**Response (201):**

```json
{
    "success": true,
    "message": "Student registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1234567890",
            "role": "student",
            "avatar": null
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "token_type": "Bearer"
    }
}
```

---

### 2. Login Student

**Endpoint:** `POST /api/login`  
**Authentication:** Not Required  
**Description:** Login and get authentication token

**Request Body:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1234567890",
            "role": "student",
            "avatar": null,
            "bio": null
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
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

### 3. Forgot Password

**Endpoint:** `POST /api/forgot-password`  
**Authentication:** Not Required  
**Description:** Request password reset link

**Request Body:**

```json
{
    "email": "john@example.com"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Password reset link has been sent to your email address."
}
```

---

### 4. Reset Password

**Endpoint:** `POST /api/reset-password`  
**Authentication:** Not Required  
**Description:** Reset password using token from email

**Request Body:**

```json
{
    "email": "john@example.com",
    "token": "reset-token-from-email",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Password has been reset successfully. You can now login with your new password."
}
```

---

### 5. Logout

**Endpoint:** `POST /api/logout`  
**Authentication:** Required  
**Description:** Logout and invalidate current token

**Response (200):**

```json
{
    "message": "Logged out successfully"
}
```

---

### 6. Get Current User

**Endpoint:** `GET /api/user`  
**Authentication:** Required  
**Description:** Get authenticated user details

**Response (200):**

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "student",
  ...
}
```

---

## Category APIs

### 7. Get All Categories

**Endpoint:** `GET /api/categories`  
**Authentication:** Not Required  
**Description:** Get all active categories

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Agriculture",
            "slug": "agriculture",
            "description": "Category description",
            "image": "category-image.jpg",
            "is_active": true,
            "sort_order": 1
        }
    ],
    "message": "Categories retrieved successfully"
}
```

---

### 8. Get Category Details

**Endpoint:** `GET /api/categories/{category}`  
**Authentication:** Not Required  
**Description:** Get category details with courses

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Agriculture",
        "slug": "agriculture",
        "description": "Category description",
        "image": "category-image.jpg",
        "courses": [
            {
                "id": 1,
                "title": "Course Title",
                "tutor": {
                    "id": 1,
                    "name": "Tutor Name",
                    "avatar": "avatar.jpg"
                }
            }
        ]
    },
    "message": "Category details retrieved successfully"
}
```

---

### 9. Get Categories with Courses

**Endpoint:** `GET /api/categories-with-courses`  
**Authentication:** Not Required  
**Description:** Get all categories with their published courses

**Response (200):**

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
                    "title": "Course Title",
                    "tutor": {
                        "id": 1,
                        "name": "Tutor Name",
                        "avatar": "avatar.jpg"
                    }
                }
            ]
        }
    ],
    "message": "Categories with courses retrieved successfully"
}
```

---

### 10. Get Featured Courses

**Endpoint:** `GET /api/featured-courses`  
**Authentication:** Not Required  
**Description:** Get featured courses grouped by category

**Response (200):**

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
                    "tutor": {
                        "id": 1,
                        "name": "Tutor Name",
                        "avatar": "avatar.jpg"
                    }
                }
            ]
        }
    ],
    "message": "Featured courses per category retrieved successfully"
}
```

---

## Course APIs

### 11. Get All Courses

**Endpoint:** `GET /api/courses`  
**Authentication:** Not Required  
**Description:** Get all published courses with filters

**Query Parameters:**

-   `category_id` (optional) - Filter by category
-   `level` (optional) - Filter by level (beginner, intermediate, advanced)
-   `min_rating` (optional) - Minimum rating filter
-   `min_duration` (optional) - Minimum duration in minutes
-   `max_duration` (optional) - Maximum duration in minutes
-   `search` (optional) - Search in title, description, tags
-   `per_page` (optional) - Results per page (default: 20)

**Example:**

```
GET /api/courses?level=beginner&min_rating=4.0&search=farming&per_page=10
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Course Title",
            "slug": "course-slug",
            "short_description": "Course description",
            "image": "course-image.jpg",
            "rating": 4.5,
            "rating_count": 120,
            "enrollment_count": 500,
            "price": 99.99,
            "is_free": false,
            "duration_minutes": 180,
            "level": "intermediate",
            "category": {
                "id": 1,
                "name": "Agriculture"
            },
            "tutor": {
                "id": 1,
                "name": "Tutor Name",
                "avatar": "avatar.jpg"
            }
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

### 12. Get Course Details

**Endpoint:** `GET /api/courses/{course}`  
**Authentication:** Not Required  
**Description:** Get detailed course information

**Response (200):**

```json
{
  "id": 1,
  "title": "Course Title",
  "slug": "course-slug",
  "description": "Full course description",
  "category": {...},
  "tutor": {...},
  "modules": [...],
  "resources": [...],
  "reviews": [...],
  "vrContent": [...],
  "diyContent": [...],
  "recommended_courses": [...]
}
```

---

### 13. Get Course Modules

**Endpoint:** `GET /api/courses/{course}/modules`  
**Authentication:** Optional  
**Description:** Get all modules for a course

**Response (200):**

```json
{
    "success": true,
    "data": {
        "course_id": 1,
        "course_title": "Course Title",
        "modules": [
            {
                "id": 1,
                "title": "Module Title",
                "description": "Module description",
                "topics": [
                    {
                        "id": 1,
                        "title": "Topic Title",
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

### 14. Get Course Information

**Endpoint:** `GET /api/courses/{course}/information`  
**Authentication:** Not Required  
**Description:** Get course information (about, what you'll learn, etc.)

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Course Title",
    "description": "Full description",
    "short_description": "Short description",
    "what_you_will_learn": ["Item 1", "Item 2"],
    "what_you_will_get": ["Item 1", "Item 2"],
    "course_information": [
      {"key": "Duration", "value": "3 months"},
      {"key": "Format", "value": "Online"}
    ],
    "level": "intermediate",
    "duration_minutes": 180,
    "language": "English",
    "rating": 4.5,
    "rating_count": 120,
    "enrollment_count": 500,
    "category": {...},
    "tutor": {...}
  },
  "message": "Course information retrieved successfully"
}
```

---

### 15. Get Course DIY Content

**Endpoint:** `GET /api/courses/{course}/diy-content`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get DIY content for a course

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "DIY Project Title",
            "description": "Project description",
            "instructions": "Step by step instructions",
            "materials_needed": ["Material 1", "Material 2"],
            "video_url": "video-url",
            "image": "project-image.jpg",
            "estimated_time_minutes": 60,
            "difficulty_level": "beginner"
        }
    ],
    "message": "Course DIY content retrieved successfully"
}
```

**Error (403):**

```json
{
    "success": false,
    "message": "You must be enrolled in this course to access DIY content"
}
```

---

### 16. Get Course Resources

**Endpoint:** `GET /api/courses/{course}/resources`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get course resources

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Resource Title",
            "description": "Resource description",
            "file_path": "path/to/file.pdf",
            "file_name": "document.pdf",
            "file_type": "pdf",
            "resource_type": "download",
            "external_url": null,
            "is_free": false
        }
    ],
    "message": "Course resources retrieved successfully"
}
```

---

### 17. Get Recommended Courses

**Endpoint:** `GET /api/recommended-courses`  
**Authentication:** Required  
**Description:** Get recommended courses based on user's enrollments

**Response (200):**

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

## Enrollment APIs

### 18. Enroll in Course

**Endpoint:** `POST /api/enroll`  
**Authentication:** Required  
**Description:** Enroll in a course using enrollment code

**Request Body:**

```json
{
    "course_id": 1,
    "enrollment_code": "ABC123XYZ456"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Successfully enrolled in course",
    "data": {
        "enrollment": {
            "id": 1,
            "user_id": 1,
            "course_id": 1,
            "enrollment_code": "ABC123XYZ456",
            "status": "active",
            "enrolled_at": "2024-01-01T00:00:00.000000Z",
            "progress_percentage": "0.00",
            "course": {
                "id": 1,
                "title": "Course Title",
                "image": "course-image.jpg",
                "slug": "course-slug",
                "short_description": "...",
                "category": {
                    "id": 1,
                    "name": "Category Name",
                    "slug": "category-slug"
                }
            }
        }
    }
}
```

**Error Responses:**

-   **400** - Invalid/used/expired enrollment code
-   **400** - Already enrolled
-   **400** - Course not published

---

### 19. Get My Enrollments

**Endpoint:** `GET /api/my-enrollments`  
**Authentication:** Required  
**Description:** Get all enrollments for authenticated user

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "course": {
                "id": 1,
                "title": "Course Title",
                "image": "course-image.jpg",
                "slug": "course-slug"
            },
            "status": "active",
            "enrolled_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "message": "Enrollments retrieved successfully"
}
```

---

### 20. Get My Courses

**Endpoint:** `GET /api/my-courses`  
**Authentication:** Required  
**Description:** Get all courses for authenticated user with progress

**Query Parameters:**

-   `status` (optional) - Filter by status (all, active, completed, cancelled)

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course": {
        "id": 1,
        "title": "Course Title",
        "category": {...},
        "tutor": {...},
        "modules": [...]
      },
      "status": "active",
      "progress_percentage": "45.50"
    }
  ],
  "message": "Courses retrieved successfully"
}
```

---

### 21. Get Ongoing Courses

**Endpoint:** `GET /api/ongoing-courses`  
**Authentication:** Required  
**Description:** Get active courses with progress percentage

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course": {
        "id": 1,
        "title": "Course Title",
        "image": "course-image.jpg",
        "slug": "course-slug",
        "short_description": "...",
        "enrollment_count": 500,
        "rating": 4.5,
        "rating_count": 120,
        "duration_minutes": 180,
        "level": "intermediate",
        "category": {...},
        "tutor": {...},
        "total_students": 500
      },
      "progress_percentage": "45.50",
      "status": "active"
    }
  ],
  "message": "Ongoing courses retrieved successfully"
}
```

---

### 22. Get Completed Courses

**Endpoint:** `GET /api/completed-courses`  
**Authentication:** Required  
**Description:** Get completed courses

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course": {
        "id": 1,
        "title": "Course Title",
        "enrollment_count": 500,
        ...
      },
      "status": "completed",
      "completed_at": "2024-01-15T00:00:00.000000Z"
    }
  ],
  "message": "Completed courses retrieved successfully"
}
```

---

### 23. Get Enrollment Details

**Endpoint:** `GET /api/enrollments/{enrollment}`  
**Authentication:** Required  
**Description:** Get specific enrollment details

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "course": {
      "id": 1,
      "title": "Course Title",
      "modules": [...],
      "tutor": {...}
    },
    "status": "active",
    "progress_percentage": "45.50"
  },
  "message": "Enrollment details retrieved successfully"
}
```

---

## Module APIs

### 24. Get Module Details

**Endpoint:** `GET /api/courses/{course}/modules/{module}`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get module details with topics and test information

**Response (200):**

```json
{
  "success": true,
  "data": {
    "module": {
      "id": 1,
      "title": "Module Title",
      "description": "Module description",
      "topics": [
        {
          "id": 1,
          "title": "Topic Title",
          "is_completed": false,
          "completion_percentage": 0
        }
      ],
      "test": {
        "id": 1,
        "title": "Module Test",
        "questions": [...]
      }
    },
    "course": {
      "id": 1,
      "title": "Course Title"
    }
  },
  "message": "Module details retrieved successfully"
}
```

---

## Progress APIs

### 25. Get Course Progress

**Endpoint:** `GET /api/courses/{course}/progress`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get overall course progress

**Response (200):**

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
                "title": "Topic Title",
                "module_id": 1,
                "is_completed": true,
                "completion_percentage": 100,
                "last_accessed_at": "2024-01-01T00:00:00.000000Z"
            }
        ]
    },
    "message": "Course progress retrieved successfully"
}
```

---

### 26. Mark Topic as Completed

**Endpoint:** `POST /api/topics/{topic}/complete`  
**Authentication:** Required (Enrollment Required)  
**Description:** Mark a topic as completed

**Response (200):**

```json
{
    "success": true,
    "message": "Topic marked as completed",
    "data": {
        "progress": {
            "id": 1,
            "topic_id": 1,
            "is_completed": true,
            "completion_percentage": 100,
            "completed_at": "2024-01-01T00:00:00.000000Z"
        }
    }
}
```

---

### 27. Update Progress

**Endpoint:** `PUT /api/progress/{progress}`  
**Authentication:** Required  
**Description:** Update topic progress (watch time, completion percentage)

**Request Body:**

```json
{
    "watch_time_seconds": 3600,
    "completion_percentage": 75
}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "watch_time_seconds": 3600,
        "completion_percentage": 75,
        "is_completed": false
    },
    "message": "Progress updated successfully"
}
```

---

### 28. Complete Quiz

**Endpoint:** `POST /api/courses/{course}/modules/{module}/tests/{test}/complete-quiz`  
**Authentication:** Required (Enrollment Required)  
**Description:** Mark quiz as completed

**Response (200):**

```json
{
    "success": true,
    "data": {
        "test_attempt": {
            "id": 1,
            "is_passed": true,
            "score": 8,
            "percentage": 80.0
        },
        "is_passed": true,
        "score": 8,
        "percentage": 80.0
    },
    "message": "Quiz marked as completed"
}
```

---

## Notes APIs

### 29. Get All Course Notes

**Endpoint:** `GET /api/courses/{course}/notes`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get all notes for a course across all modules

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "notes": "My note text",
            "timestamp_seconds": 120,
            "is_public": false,
            "topic": {
                "id": 1,
                "title": "Topic Title",
                "module_id": 1,
                "module": {
                    "id": 1,
                    "title": "Module Title"
                }
            },
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "message": "All course notes retrieved successfully"
}
```

---

### 30. Get Module Notes

**Endpoint:** `GET /api/courses/{course}/modules/{module}/notes`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get notes for a specific module

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "notes": "My note text",
            "topic": {
                "id": 1,
                "title": "Topic Title",
                "module_id": 1
            }
        }
    ],
    "message": "Module notes retrieved successfully"
}
```

---

### 31. Create Note

**Endpoint:** `POST /api/notes`  
**Authentication:** Required (Enrollment Required)  
**Description:** Create a new note

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

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "notes": "My note text",
        "topic": {
            "id": 1,
            "title": "Topic Title"
        }
    },
    "message": "Note created successfully"
}
```

---

### 32. Update Note

**Endpoint:** `PUT /api/notes/{note}`  
**Authentication:** Required (Owner Only)  
**Description:** Update an existing note

**Request Body:**

```json
{
    "notes": "Updated note text",
    "is_public": true
}
```

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "notes": "Updated note text",
    "is_public": true,
    "topic": {...}
  },
  "message": "Note updated successfully"
}
```

---

### 33. Delete Note

**Endpoint:** `DELETE /api/notes/{note}`  
**Authentication:** Required (Owner Only)  
**Description:** Delete a note

**Response (200):**

```json
{
    "success": true,
    "message": "Note deleted successfully"
}
```

---

## Test/Quiz APIs

### 34. Get Module Test

**Endpoint:** `GET /api/courses/{course}/modules/{module}/test`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get test/quiz for a module

**Response (200):**

```json
{
    "success": true,
    "data": {
        "test": {
            "id": 1,
            "title": "Module Test",
            "description": "Test description",
            "passing_score": 70,
            "time_limit_minutes": 30,
            "total_questions": 10,
            "questions": [
                {
                    "id": 1,
                    "question": "What is...?",
                    "question_type": "multiple_choice",
                    "options": ["Option A", "Option B", "Option C", "Option D"],
                    "points": 10
                }
            ]
        },
        "attempts": [
            {
                "id": 1,
                "score": 8,
                "percentage": 80.0,
                "is_passed": true,
                "completed_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "has_attempted": true,
        "best_score": 80.0,
        "is_passed": true
    },
    "message": "Module test retrieved successfully"
}
```

---

### 35. Submit Quiz

**Endpoint:** `POST /api/courses/{course}/modules/{module}/tests/{test}/submit`  
**Authentication:** Required (Enrollment Required)  
**Description:** Submit quiz answers

**Request Body:**

```json
{
    "answers": {
        "1": "Option A",
        "2": "Option B",
        "3": "Option C"
    }
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "attempt": {
            "id": 1,
            "score": 8,
            "total_questions": 10,
            "percentage": 80.0,
            "is_passed": true
        },
        "score": 8,
        "total_questions": 10,
        "percentage": 80.0,
        "is_passed": true,
        "passing_score": 70
    },
    "message": "Test passed successfully"
}
```

---

## Assignment APIs

### 36. Get Course Assignments

**Endpoint:** `GET /api/courses/{course}/assignments`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get all assignments for a course

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Assignment Title",
            "description": "Assignment description",
            "due_date": "2024-01-15T00:00:00.000000Z",
            "max_score": 100
        }
    ],
    "message": "Assignments retrieved successfully"
}
```

---

### 37. Get Assignment Details

**Endpoint:** `GET /api/assignments/{assignment}`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get assignment details

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Assignment Title",
        "description": "Full assignment description",
        "instructions": "Assignment instructions",
        "due_date": "2024-01-15T00:00:00.000000Z",
        "max_score": 100
    },
    "message": "Assignment retrieved successfully"
}
```

---

### 38. Submit Assignment

**Endpoint:** `POST /api/assignments/{assignment}/submit`  
**Authentication:** Required (Enrollment Required)  
**Description:** Submit assignment

**Request Body:**

```json
{
    "submission_text": "My assignment answer",
    "file_path": "path/to/file.pdf" // optional
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "submission_text": "My assignment answer",
        "submitted_at": "2024-01-01T00:00:00.000000Z"
    },
    "message": "Assignment submitted successfully"
}
```

---

### 39. Get My Submissions

**Endpoint:** `GET /api/my-submissions`  
**Authentication:** Required  
**Description:** Get all assignment submissions by authenticated user

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "assignment": {
                "id": 1,
                "title": "Assignment Title"
            },
            "submission_text": "My answer",
            "score": 85,
            "submitted_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "message": "Submissions retrieved successfully"
}
```

---

## Message APIs

### 40. Get Course Messages

**Endpoint:** `GET /api/courses/{course}/messages`  
**Authentication:** Required (Enrollment Required)  
**Description:** Get messages for a course

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "subject": "Message Subject",
            "message": "Message content",
            "sender": {
                "id": 1,
                "name": "Sender Name"
            },
            "is_read": false,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "message": "Messages retrieved successfully"
}
```

---

### 41. Send Message

**Endpoint:** `POST /api/messages`  
**Authentication:** Required (Enrollment Required)  
**Description:** Send a message

**Request Body:**

```json
{
    "course_id": 1,
    "recipient_id": 2,
    "subject": "Message Subject",
    "message": "Message content"
}
```

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "subject": "Message Subject",
        "message": "Message content",
        "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "message": "Message sent successfully"
}
```

---

### 42. Get Message Details

**Endpoint:** `GET /api/messages/{message}`  
**Authentication:** Required  
**Description:** Get message details

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "subject": "Message Subject",
    "message": "Full message content",
    "sender": {...},
    "recipient": {...},
    "is_read": false
  },
  "message": "Message retrieved successfully"
}
```

---

### 43. Mark Message as Read

**Endpoint:** `PUT /api/messages/{message}/read`  
**Authentication:** Required  
**Description:** Mark message as read

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "is_read": true
    },
    "message": "Message marked as read"
}
```

---

## Error Responses

All endpoints return consistent error responses:

**400 Bad Request:**

```json
{
    "success": false,
    "message": "Error message describing what went wrong"
}
```

**401 Unauthorized:**

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**403 Forbidden:**

```json
{
    "success": false,
    "message": "You must be enrolled in this course to access this resource"
}
```

**404 Not Found:**

```json
{
    "success": false,
    "message": "Resource not found"
}
```

**422 Validation Error:**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

**500 Server Error:**

```json
{
    "success": false,
    "message": "Internal server error"
}
```

---

## Rate Limiting

API requests are rate-limited. If you exceed the limit, you'll receive:

```json
{
    "message": "Too Many Attempts."
}
```

---

## Notes

1. All timestamps are in ISO 8601 format (UTC)
2. All monetary values are in the base currency (e.g., USD)
3. File paths are relative to the storage directory
4. Pagination is available on list endpoints (default: 20 items per page)
5. All protected endpoints require valid Bearer token
6. Enrollment is required for course content access (modules, topics, resources, etc.)

---

## Support

For API support, please contact the development team.

**Last Updated:** November 2024
