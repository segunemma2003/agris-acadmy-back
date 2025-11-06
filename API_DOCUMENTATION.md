# API Documentation

## Base URL
```
http://your-domain.com/api
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header for protected routes.

```
Authorization: Bearer {token}
```

---

## Public Endpoints

### 1. Register User

**POST** `/register`

Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "student"
  },
  "token": "1|xxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

---

### 2. Login

**POST** `/login`

Authenticate user and get access token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "student"
  },
  "token": "1|xxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

---

### 3. Get All Categories

**GET** `/categories`

Get list of all active categories.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Agriculture",
    "slug": "agriculture",
    "description": "Agricultural courses",
    "image": "path/to/image.jpg",
    "is_active": true,
    "sort_order": 1
  }
]
```

---

### 4. Get Category with Courses

**GET** `/categories/{id}`

Get a single category with its courses.

**Response:**
```json
{
  "id": 1,
  "name": "Agriculture",
  "slug": "agriculture",
  "courses": [...]
}
```

---

### 5. Get Categories with Courses

**GET** `/categories-with-courses`

Get all categories with their associated courses.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Agriculture",
    "courses": [
      {
        "id": 1,
        "title": "Introduction to Farming",
        "slug": "introduction-to-farming",
        ...
      }
    ]
  }
]
```

---

### 6. Get All Courses

**GET** `/courses`

Get list of all published courses.

**Query Parameters:**
- `category_id` (optional): Filter by category
- `level` (optional): Filter by level (beginner, intermediate, advanced)
- `search` (optional): Search in course title
- `page` (optional): Page number for pagination

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Introduction to Farming",
      "slug": "introduction-to-farming",
      "short_description": "Learn the basics of farming",
      "description": "Full description...",
      "image": "path/to/image.jpg",
      "level": "beginner",
      "language": "English",
      "duration_minutes": 120,
      "rating": 4.5,
      "rating_count": 100,
      "enrollment_count": 500,
      "price": 99.99,
      "is_free": false,
      "category": {
        "id": 1,
        "name": "Agriculture"
      },
      "tutor": {
        "id": 2,
        "name": "Jane Smith",
        "avatar": "path/to/avatar.jpg"
      }
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 100
}
```

---

### 7. Get Single Course

**GET** `/courses/{id}`

Get detailed information about a course.

**Response:**
```json
{
  "id": 1,
  "title": "Introduction to Farming",
  "slug": "introduction-to-farming",
  "short_description": "Learn the basics of farming",
  "description": "Full description...",
  "what_you_will_learn": ["Skill 1", "Skill 2"],
  "what_you_will_get": ["Material 1", "Material 2"],
  "image": "path/to/image.jpg",
  "level": "beginner",
  "language": "English",
  "duration_minutes": 120,
  "materials_count": 5,
  "rating": 4.5,
  "rating_count": 100,
  "enrollment_count": 500,
  "price": 99.99,
  "is_free": false,
  "category": {...},
  "tutor": {
    "id": 2,
    "name": "Jane Smith",
    "bio": "Expert farmer",
    "avatar": "path/to/avatar.jpg"
  },
  "modules": [
    {
      "id": 1,
      "title": "Module 1",
      "description": "Module description",
      "total_topics": 5,
      "topics": [...]
    }
  ],
  "resources": [...],
  "reviews": [...],
  "vr_content": [...],
  "diy_content": [...],
  "recommended_courses": [...]
}
```

---

## Protected Endpoints

All protected endpoints require authentication token in the header.

### 8. Logout

**POST** `/logout`

Logout the authenticated user.

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

### 9. Get Current User

**GET** `/user`

Get authenticated user information.

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "student",
  "phone": "+1234567890",
  "avatar": "path/to/avatar.jpg"
}
```

---

### 10. Enroll in Course

**POST** `/enroll`

Enroll in a course using enrollment code or direct enrollment.

**Request Body:**
```json
{
  "course_id": 1,
  "enrollment_code": "ABC123XYZ456" // optional
}
```

**Response:**
```json
{
  "message": "Successfully enrolled in course",
  "enrollment": {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "enrollment_code": "ABC123XYZ456",
    "status": "active",
    "enrolled_at": "2025-01-10T10:00:00.000000Z",
    "progress_percentage": 0.00
  }
}
```

---

### 11. Get My Enrollments

**GET** `/my-enrollments`

Get all courses the user is enrolled in.

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "enrollment_code": "ABC123XYZ456",
    "status": "active",
    "progress_percentage": 45.50,
    "enrolled_at": "2025-01-10T10:00:00.000000Z",
    "course": {
      "id": 1,
      "title": "Introduction to Farming",
      "image": "path/to/image.jpg",
      "slug": "introduction-to-farming"
    }
  }
]
```

---

### 12. Get My Courses

**GET** `/my-courses`

Get user's courses with status filter.

**Query Parameters:**
- `status` (optional): Filter by status (active, completed, cancelled, all)

**Response:**
```json
[
  {
    "id": 1,
    "status": "active",
    "progress_percentage": 45.50,
    "course": {
      "id": 1,
      "title": "Introduction to Farming",
      "category": {...},
      "tutor": {...}
    }
  }
]
```

---

### 13. Get Course Progress

**GET** `/courses/{id}/progress`

Get detailed progress for a specific course.

**Response:**
```json
{
  "course_id": 1,
  "overall_progress": 45.50,
  "total_topics": 20,
  "completed_topics": 9,
  "topics": [
    {
      "id": 1,
      "title": "Topic 1",
      "module_id": 1,
      "is_completed": true,
      "completion_percentage": 100,
      "last_accessed_at": "2025-01-10T10:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "Topic 2",
      "module_id": 1,
      "is_completed": false,
      "completion_percentage": 50,
      "last_accessed_at": null
    }
  ]
}
```

---

### 14. Mark Topic as Complete

**POST** `/topics/{id}/complete`

Mark a topic as completed.

**Response:**
```json
{
  "message": "Topic marked as completed",
  "progress": {
    "id": 1,
    "user_id": 1,
    "topic_id": 1,
    "is_completed": true,
    "completion_percentage": 100,
    "completed_at": "2025-01-10T10:00:00.000000Z"
  }
}
```

---

### 15. Update Progress

**PUT** `/progress/{id}`

Update progress for a topic (watch time, completion percentage).

**Request Body:**
```json
{
  "watch_time_seconds": 3600,
  "completion_percentage": 75
}
```

**Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "topic_id": 1,
  "watch_time_seconds": 3600,
  "completion_percentage": 75,
  "is_completed": false
}
```

---

### 16. Get Course Notes

**GET** `/courses/{id}/notes`

Get all notes for a course.

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "course_id": 1,
    "topic_id": 1,
    "notes": "Important point about...",
    "timestamp_seconds": 120,
    "is_public": false,
    "topic": {
      "id": 1,
      "title": "Topic 1",
      "module_id": 1
    }
  }
]
```

---

### 17. Create Note

**POST** `/notes`

Create a new note.

**Request Body:**
```json
{
  "course_id": 1,
  "topic_id": 1,
  "notes": "Important point about...",
  "timestamp_seconds": 120,
  "is_public": false
}
```

**Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "course_id": 1,
  "topic_id": 1,
  "notes": "Important point about...",
  "timestamp_seconds": 120,
  "is_public": false,
  "topic": {...}
}
```

---

### 18. Update Note

**PUT** `/notes/{id}`

Update an existing note.

**Request Body:**
```json
{
  "notes": "Updated notes...",
  "is_public": true
}
```

**Response:**
```json
{
  "id": 1,
  "notes": "Updated notes...",
  "is_public": true,
  ...
}
```

---

### 19. Delete Note

**DELETE** `/notes/{id}`

Delete a note.

**Response:**
```json
{
  "message": "Note deleted successfully"
}
```

---

### 20. Get Course Assignments

**GET** `/courses/{id}/assignments`

Get all assignments for a course.

**Response:**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "module_id": 1,
    "title": "Assignment 1",
    "description": "Complete this assignment",
    "instructions": "Follow these steps...",
    "max_score": 100,
    "due_date": "2025-01-20T23:59:59.000000Z",
    "submissions": [
      {
        "id": 1,
        "status": "submitted",
        "submitted_at": "2025-01-15T10:00:00.000000Z"
      }
    ]
  }
]
```

---

### 21. Get Assignment

**GET** `/assignments/{id}`

Get detailed information about an assignment.

**Response:**
```json
{
  "id": 1,
  "course_id": 1,
  "module_id": 1,
  "title": "Assignment 1",
  "description": "Complete this assignment",
  "instructions": "Follow these steps...",
  "max_score": 100,
  "due_date": "2025-01-20T23:59:59.000000Z",
  "submissions": [...]
}
```

---

### 22. Submit Assignment

**POST** `/assignments/{id}/submit`

Submit an assignment.

**Request Body (multipart/form-data):**
```
submission_content: "My assignment answer..."
file: (optional file upload)
```

**Response:**
```json
{
  "id": 1,
  "assignment_id": 1,
  "user_id": 1,
  "submission_content": "My assignment answer...",
  "file_path": "path/to/file.pdf",
  "file_name": "assignment.pdf",
  "status": "submitted",
  "submitted_at": "2025-01-15T10:00:00.000000Z"
}
```

---

### 23. Get My Submissions

**GET** `/my-submissions`

Get all assignment submissions by the user.

**Response:**
```json
[
  {
    "id": 1,
    "assignment_id": 1,
    "submission_content": "My answer...",
    "status": "graded",
    "score": 85,
    "feedback": "Good work!",
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

### 24. Get Course Messages

**GET** `/courses/{id}/messages`

Get all messages for a course.

**Response:**
```json
[
  {
    "id": 1,
    "course_id": 1,
    "sender_id": 1,
    "recipient_id": 2,
    "subject": "Question about Module 1",
    "message": "I have a question...",
    "is_read": false,
    "created_at": "2025-01-10T10:00:00.000000Z",
    "sender": {
      "id": 1,
      "name": "John Doe",
      "avatar": "path/to/avatar.jpg"
    },
    "recipient": {
      "id": 2,
      "name": "Jane Smith",
      "avatar": "path/to/avatar.jpg"
    }
  }
]
```

---

### 25. Send Message

**POST** `/messages`

Send a message to tutor or student.

**Request Body:**
```json
{
  "course_id": 1,
  "recipient_id": 2,
  "subject": "Question about Module 1",
  "message": "I have a question about..."
}
```

**Response:**
```json
{
  "id": 1,
  "course_id": 1,
  "sender_id": 1,
  "recipient_id": 2,
  "subject": "Question about Module 1",
  "message": "I have a question about...",
  "is_read": false,
  "sender": {...},
  "recipient": {...}
}
```

---

### 26. Get Message

**GET** `/messages/{id}`

Get a single message (automatically marks as read if user is recipient).

**Response:**
```json
{
  "id": 1,
  "course_id": 1,
  "sender_id": 1,
  "recipient_id": 2,
  "subject": "Question about Module 1",
  "message": "I have a question about...",
  "is_read": true,
  "read_at": "2025-01-10T10:05:00.000000Z",
  "sender": {...},
  "recipient": {...}
}
```

---

### 27. Mark Message as Read

**PUT** `/messages/{id}/read`

Mark a message as read.

**Response:**
```json
{
  "id": 1,
  "is_read": true,
  "read_at": "2025-01-10T10:05:00.000000Z",
  ...
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "message": "Error message",
  "errors": {
    "field": ["Error message for field"]
  }
}
```

### Common HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Rate Limiting

API requests are rate-limited to prevent abuse. Current limits:
- 60 requests per minute per IP for public endpoints
- 120 requests per minute per authenticated user

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

---

## Pagination

List endpoints support pagination. Response includes:
```json
{
  "data": [...],
  "current_page": 1,
  "per_page": 20,
  "total": 100,
  "last_page": 5
}
```

Query parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)



