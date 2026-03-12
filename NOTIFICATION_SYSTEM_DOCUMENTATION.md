# Notification System Documentation

## Overview

A comprehensive notification system has been implemented that automatically creates notifications for users when specific events occur in the system. This works similar to Django signals - notifications are automatically triggered by model events.

## Database Structure

### Notifications Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `type` - Type of notification (message_sent, course_added, enrollment_confirmed, etc.)
- `title` - Notification title
- `message` - Notification message
- `action_type` - Type of related resource (course, message, assignment, etc.)
- `action_id` - ID of the related resource
- `data` - Additional JSON data
- `is_read` - Read status
- `read_at` - Timestamp when read
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

## API Endpoints

All endpoints require authentication (`auth:sanctum` middleware).

### Get Notifications
```
GET /api/notifications
```
**Query Parameters:**
- `unread_only` (boolean) - Filter to show only unread notifications
- `type` (string) - Filter by notification type
- `per_page` (integer) - Items per page (default: 20)
- `page` (integer) - Page number

**Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {...},
  "message": "Notifications retrieved successfully"
}
```

### Get Unread Count
```
GET /api/notifications/unread-count
```

**Response:**
```json
{
  "success": true,
  "data": {
    "unread_count": 5
  },
  "message": "Unread count retrieved successfully"
}
```

### Get Single Notification
```
GET /api/notifications/{notification}
```
Automatically marks the notification as read when viewed.

### Mark Notification as Read
```
PUT /api/notifications/{notification}/read
```

### Mark All Notifications as Read
```
PUT /api/notifications/read-all
```

### Delete Notification
```
DELETE /api/notifications/{notification}
```

### Delete All Read Notifications
```
DELETE /api/notifications/read/all
```

## Automatic Notifications (Event-Driven)

Notifications are automatically created when these events occur:

### 1. Message Sent
**Trigger:** When a new `Message` is created
**Type:** `message_sent`
**Recipient:** Message recipient
**Data:** Includes course info, sender info, and message subject

### 2. Course Added/Published
**Trigger:** When a new `Course` is created with `is_published=true` OR when a course's `is_published` status changes to `true`
**Type:** `course_added` or `course_published`
**Recipient:** All active students
**Data:** Includes course details (id, title, slug, category)

### 3. Enrollment Confirmed
**Trigger:** When a new `Enrollment` is created
**Type:** `enrollment_confirmed`
**Recipient:** The enrolled user
**Data:** Includes course and enrollment details

### 4. Course Completed
**Trigger:** When an `Enrollment` status changes to `completed`
**Type:** `course_completed`
**Recipient:** The user who completed the course
**Data:** Includes course details and certificate availability

### 5. Module Added
**Trigger:** When a new `Module` is created with `is_active=true` OR when a module's `is_active` status changes to `true`
**Type:** `module_added`
**Recipient:** All students enrolled in the course
**Data:** Includes course and module details

### 6. Assignment Graded
**Trigger:** When an `AssignmentSubmission` status changes to `graded` or `returned`
**Type:** `assignment_graded`
**Recipient:** The student who submitted the assignment
**Data:** Includes assignment details, score, feedback, and course info

## Notification Service

The `NotificationService` class provides helper methods for creating notifications:

### Create Single Notification
```php
NotificationService::create(
    $user,
    'custom_type',
    'Title',
    'Message',
    'action_type',
    $actionId,
    ['custom' => 'data']
);
```

### Create for Multiple Users
```php
NotificationService::createForUsers(
    $users, // Collection or array of User objects
    'type',
    'Title',
    'Message',
    'action_type',
    $actionId,
    $data
);
```

### Create for Role
```php
NotificationService::createForRole(
    'student', // role
    'type',
    'Title',
    'Message',
    'action_type',
    $actionId,
    $data
);
```

### Create for Course Enrollments
```php
NotificationService::createForCourseEnrollments(
    $courseId,
    'type',
    'Title',
    'Message',
    'action_type',
    $actionId,
    $data
);
```

## Notification Types

Current notification types:
- `message_sent` - New message received
- `course_added` - New course available
- `course_published` - Course published
- `enrollment_confirmed` - Enrollment successful
- `course_completed` - Course completed
- `module_added` - New module added to course
- `assignment_graded` - Assignment has been graded

## User Model Relationship

The `User` model now has a `notifications()` relationship:

```php
$user->notifications()->get();
$user->notifications()->unread()->get();
$user->notifications()->ofType('message_sent')->get();
```

## Implementation Details

### Model Events (Similar to Django Signals)

Notifications are created using Laravel's model events in the `boot()` method of each model:

- `Message::created` - Creates notification when message is sent
- `Course::created` - Creates notification when course is published
- `Course::updated` - Creates notification when course publishing status changes
- `Enrollment::created` - Creates notification when user enrolls
- `Enrollment::updated` - Creates notification when course is completed
- `Module::created` / `Module::updated` - Creates notification when module is added/activated
- `AssignmentSubmission::updated` - Creates notification when assignment is graded

### Performance Considerations

- Notifications are created synchronously but can be optimized to use queues if needed
- Bulk notifications use `insert()` for better performance
- Indexes are added on frequently queried columns (user_id, is_read, type, etc.)

## Future Enhancements

Potential additions:
- Push notifications (FCM/APNS)
- Email notifications integration
- Notification preferences per user
- Notification templates
- Scheduled notifications
- Notification channels (in-app, email, push)
