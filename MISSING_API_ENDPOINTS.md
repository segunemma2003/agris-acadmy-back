# Missing API Endpoints & Recommended Features

## Currently Missing Endpoints

### 1. Course Content Endpoints (Partially Documented)

#### ✅ Get Course Information
- **Endpoint:** `GET /api/courses/{id}/information`
- **Status:** Exists but needs better documentation
- **Description:** Returns detailed course information including what you'll learn, requirements, etc.

#### ❌ Get Course DIY Content
- **Endpoint:** `GET /api/courses/{id}/diy-content`
- **Status:** EXISTS but NOT documented
- **Description:** Returns DIY (Do It Yourself) content for enrolled students
- **Auth:** Required, must be enrolled

#### ❌ Get Course Resources
- **Endpoint:** `GET /api/courses/{id}/resources`
- **Status:** EXISTS but NOT documented
- **Description:** Returns downloadable resources for enrolled students
- **Auth:** Required, must be enrolled

---

### 2. Review Management

#### ❌ Add Course Review
- **Endpoint:** `POST /api/courses/{id}/reviews`
- **Status:** NOT IMPLEMENTED (documented but needs backend)
- **Description:** Allow students to submit reviews/ratings for courses
- **Request Body:**
  ```json
  {
    "rating": 5,
    "review": "Great course!"
  }
  ```

#### ❌ Update Course Review
- **Endpoint:** `PUT /api/courses/{id}/reviews/{review_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Allow students to edit their reviews

#### ❌ Delete Course Review
- **Endpoint:** `DELETE /api/courses/{id}/reviews/{review_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Allow students to delete their reviews

---

### 3. Comment Management

#### ❌ Update Comment
- **Endpoint:** `PUT /api/comments/{comment_id}` or `PUT /api/courses/{id}/comments/{comment_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Allow users to edit their comments

#### ❌ Delete Comment
- **Endpoint:** `DELETE /api/comments/{comment_id}` or `DELETE /api/courses/{id}/comments/{comment_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Allow users to delete their comments

---

### 4. Enrollment Management

#### ❌ Unenroll from Course
- **Endpoint:** `DELETE /api/enrollments/{enrollment_id}` or `POST /api/courses/{id}/unenroll`
- **Status:** NOT IMPLEMENTED
- **Description:** Allow students to unenroll from a course
- **Note:** Should handle cleanup of progress, submissions, etc.

---

### 5. Certificate Management

#### ❌ Download Certificate
- **Endpoint:** `GET /api/certificates/{certificate_id}/download`
- **Status:** NOT IMPLEMENTED
- **Description:** Download certificate PDF/file
- **Response:** File download or PDF URL

---

### 6. Profile Management

#### ❌ Upload Profile Picture
- **Endpoint:** `POST /api/user/avatar` or `POST /api/user/profile/avatar`
- **Status:** NOT IMPLEMENTED (currently only accepts URL string)
- **Description:** Upload profile picture file
- **Request:** `multipart/form-data` with image file
- **Response:** URL of uploaded avatar

#### ❌ Delete Profile Picture
- **Endpoint:** `DELETE /api/user/avatar`
- **Status:** NOT IMPLEMENTED
- **Description:** Remove profile picture

---

### 7. Email & Phone Verification

#### ❌ Verify Email
- **Endpoint:** `POST /api/verify-email` or `GET /api/verify-email/{token}`
- **Status:** NOT IMPLEMENTED
- **Description:** Verify user email address
- **Request Body:**
  ```json
  {
    "token": "verification_token_from_email"
  }
  ```

#### ❌ Resend Verification Email
- **Endpoint:** `POST /api/resend-verification-email`
- **Status:** NOT IMPLEMENTED
- **Description:** Resend email verification link

#### ❌ Verify Phone
- **Endpoint:** `POST /api/verify-phone`
- **Status:** NOT IMPLEMENTED
- **Description:** Verify phone number via SMS code
- **Request Body:**
  ```json
  {
    "phone": "+1234567890",
    "code": "123456"
  }
  ```

#### ❌ Send Phone Verification Code
- **Endpoint:** `POST /api/send-phone-verification`
- **Status:** NOT IMPLEMENTED
- **Description:** Send SMS verification code to phone number

---

### 8. Notifications

#### ❌ Get Notifications
- **Endpoint:** `GET /api/notifications`
- **Status:** NOT IMPLEMENTED
- **Description:** Get user notifications (assignments graded, new messages, course updates, etc.)
- **Query Parameters:**
  - `unread_only`: boolean (default: false)
  - `per_page`: integer
  - `page`: integer

#### ❌ Mark Notification as Read
- **Endpoint:** `PUT /api/notifications/{notification_id}/read`
- **Status:** NOT IMPLEMENTED
- **Description:** Mark notification as read

#### ❌ Mark All Notifications as Read
- **Endpoint:** `PUT /api/notifications/read-all`
- **Status:** NOT IMPLEMENTED
- **Description:** Mark all notifications as read

#### ❌ Delete Notification
- **Endpoint:** `DELETE /api/notifications/{notification_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Delete a notification

#### ❌ Get Unread Count
- **Endpoint:** `GET /api/notifications/unread-count`
- **Status:** NOT IMPLEMENTED
- **Description:** Get count of unread notifications

---

### 9. Push Notifications

#### ❌ Register Device Token
- **Endpoint:** `POST /api/device-tokens`
- **Status:** NOT IMPLEMENTED
- **Description:** Register device for push notifications
- **Request Body:**
  ```json
  {
    "device_token": "fcm_token_or_apns_token",
    "device_type": "ios" | "android",
    "device_id": "unique_device_id"
  }
  ```

#### ❌ Unregister Device Token
- **Endpoint:** `DELETE /api/device-tokens/{token_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Unregister device for push notifications

---

### 10. Assignment Management

#### ❌ Update Assignment Submission
- **Endpoint:** `PUT /api/assignments/{assignment_id}/submissions/{submission_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Update assignment submission (if allowed before grading)

#### ❌ Delete Assignment Submission
- **Endpoint:** `DELETE /api/assignments/{assignment_id}/submissions/{submission_id}`
- **Status:** NOT IMPLEMENTED
- **Description:** Delete assignment submission (if allowed)

---

### 11. Test/Quiz Management

#### ❌ Get Test Attempt History
- **Endpoint:** `GET /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/attempts`
- **Status:** NOT IMPLEMENTED
- **Description:** Get user's previous test attempts

#### ❌ Retake Test
- **Endpoint:** `POST /api/courses/{course_id}/modules/{module_id}/tests/{test_id}/retake`
- **Status:** NOT IMPLEMENTED
- **Description:** Request to retake a test (if allowed)

---

### 12. Content Download/Offline

#### ❌ Download Course Content
- **Endpoint:** `GET /api/courses/{id}/download` or `POST /api/courses/{id}/download`
- **Status:** NOT IMPLEMENTED
- **Description:** Download course content for offline viewing
- **Response:** ZIP file or list of downloadable resources

#### ❌ Get Downloadable Resources
- **Endpoint:** `GET /api/courses/{id}/downloadable-resources`
- **Status:** NOT IMPLEMENTED
- **Description:** Get list of downloadable resources (videos, PDFs, etc.)

---

### 13. App Configuration

#### ❌ Get App Version Info
- **Endpoint:** `GET /api/app/version`
- **Status:** NOT IMPLEMENTED
- **Description:** Check app version, force update, maintenance mode
- **Response:**
  ```json
  {
    "current_version": "1.0.0",
    "minimum_version": "1.0.0",
    "latest_version": "1.1.0",
    "force_update": false,
    "maintenance_mode": false,
    "maintenance_message": null
  }
  ```

#### ❌ Get App Configuration
- **Endpoint:** `GET /api/app/config`
- **Status:** NOT IMPLEMENTED
- **Description:** Get app-wide configuration (feature flags, settings, etc.)
- **Response:**
  ```json
  {
    "features": {
      "offline_mode": true,
      "push_notifications": true,
      "social_sharing": true
    },
    "settings": {
      "max_file_upload_size": 10485760,
      "supported_video_formats": ["mp4", "webm"]
    }
  }
  ```

---

### 14. Legal & Support

#### ❌ Get Terms of Service
- **Endpoint:** `GET /api/legal/terms`
- **Status:** NOT IMPLEMENTED
- **Description:** Get terms of service content

#### ❌ Get Privacy Policy
- **Endpoint:** `GET /api/legal/privacy`
- **Status:** NOT IMPLEMENTED
- **Description:** Get privacy policy content

#### ❌ Get FAQ
- **Endpoint:** `GET /api/faq`
- **Status:** NOT IMPLEMENTED
- **Description:** Get frequently asked questions
- **Query Parameters:**
  - `category`: string (optional)

#### ❌ Submit Support Request
- **Endpoint:** `POST /api/support`
- **Status:** NOT IMPLEMENTED
- **Description:** Submit support ticket/request
- **Request Body:**
  ```json
  {
    "subject": "Issue with course",
    "message": "Description of issue",
    "category": "technical" | "billing" | "general",
    "course_id": 1 (optional)
  }
  ```

#### ❌ Get Support Requests
- **Endpoint:** `GET /api/support`
- **Status:** NOT IMPLEMENTED
- **Description:** Get user's support requests

---

### 15. Social Features

#### ❌ Share Course
- **Endpoint:** `POST /api/courses/{id}/share`
- **Status:** NOT IMPLEMENTED
- **Description:** Track course shares (analytics)
- **Request Body:**
  ```json
  {
    "platform": "facebook" | "twitter" | "whatsapp" | "email",
    "shared_at": "2024-01-15T10:00:00Z"
  }
  ```

#### ❌ Get Course Share Link
- **Endpoint:** `GET /api/courses/{id}/share-link`
- **Status:** NOT IMPLEMENTED
- **Description:** Get shareable link with referral code

---

### 16. Analytics & Tracking

#### ❌ Track Video Playback
- **Endpoint:** `POST /api/topics/{topic_id}/video-progress`
- **Status:** NOT IMPLEMENTED
- **Description:** Track video playback position
- **Request Body:**
  ```json
  {
    "current_time": 120.5,
    "total_duration": 600.0,
    "percentage_watched": 20.0
  }
  ```

#### ❌ Track Screen Time
- **Endpoint:** `POST /api/analytics/screen-time`
- **Status:** NOT IMPLEMENTED
- **Description:** Track app usage time
- **Request Body:**
  ```json
  {
    "screen": "course_detail",
    "duration_seconds": 45,
    "timestamp": "2024-01-15T10:00:00Z"
  }
  ```

---

### 17. Search & Discovery

#### ❌ Advanced Search
- **Endpoint:** `GET /api/search`
- **Status:** NOT IMPLEMENTED (basic search exists in courses)
- **Description:** Global search across courses, categories, tutors
- **Query Parameters:**
  - `q`: search query
  - `type`: "courses" | "categories" | "tutors" | "all"
  - `filters`: JSON object with advanced filters

#### ❌ Search History
- **Endpoint:** `GET /api/search/history`
- **Status:** NOT IMPLEMENTED
- **Description:** Get user's search history

#### ❌ Clear Search History
- **Endpoint:** `DELETE /api/search/history`
- **Status:** NOT IMPLEMENTED
- **Description:** Clear user's search history

---

### 18. Course Recommendations

#### ❌ Get Personalized Recommendations
- **Endpoint:** `GET /api/recommendations`
- **Status:** Partially exists (`/recommended-courses`, `/daily-recommended-courses`)
- **Description:** Get AI/ML-based personalized course recommendations
- **Query Parameters:**
  - `limit`: number of recommendations
  - `based_on`: "enrollment" | "progress" | "ratings" | "all"

---

### 19. Progress Analytics

#### ❌ Get Learning Analytics
- **Endpoint:** `GET /api/analytics/learning`
- **Status:** NOT IMPLEMENTED
- **Description:** Get detailed learning analytics for user
- **Response:**
  ```json
  {
    "total_study_time_hours": 45.5,
    "courses_completed": 5,
    "average_score": 85.5,
    "streak_days": 7,
    "weekly_progress": [...],
    "monthly_progress": [...]
  }
  ```

#### ❌ Get Course Analytics
- **Endpoint:** `GET /api/courses/{id}/analytics`
- **Status:** NOT IMPLEMENTED
- **Description:** Get detailed analytics for specific course
- **Response:**
  ```json
  {
    "time_spent_hours": 12.5,
    "topics_completed": 15,
    "tests_passed": 3,
    "assignments_submitted": 5,
    "average_test_score": 88.0
  }
  ```

---

### 20. Content Interaction

#### ❌ Like/Unlike Course
- **Endpoint:** `POST /api/courses/{id}/like` or `DELETE /api/courses/{id}/like`
- **Status:** NOT IMPLEMENTED
- **Description:** Like/unlike a course

#### ❌ Bookmark Topic
- **Endpoint:** `POST /api/topics/{id}/bookmark` or `DELETE /api/topics/{id}/bookmark`
- **Status:** NOT IMPLEMENTED
- **Description:** Bookmark/unbookmark a topic for later

#### ❌ Get Bookmarks
- **Endpoint:** `GET /api/bookmarks`
- **Status:** NOT IMPLEMENTED
- **Description:** Get all bookmarked topics

---

## Priority Recommendations

### High Priority (Essential for MVP)
1. ✅ **Add Course Review** - Critical for course ratings
2. ✅ **Unenroll from Course** - Basic enrollment management
3. ✅ **Upload Profile Picture** - Essential user feature
4. ✅ **Get Notifications** - Important for user engagement
5. ✅ **Get Course DIY Content** - Document existing endpoint
6. ✅ **Get Course Resources** - Document existing endpoint

### Medium Priority (Important for UX)
7. ✅ **Edit/Delete Comments** - User content management
8. ✅ **Edit/Delete Reviews** - User content management
9. ✅ **Certificate Download** - Complete certificate feature
10. ✅ **Email Verification** - Security and trust
11. ✅ **Push Notifications** - User engagement
12. ✅ **App Version Check** - Force updates, maintenance mode

### Low Priority (Nice to Have)
13. ✅ **Phone Verification** - Additional security
14. ✅ **Offline Content Download** - Advanced feature
15. ✅ **Learning Analytics** - Advanced insights
16. ✅ **Social Sharing** - Marketing feature
17. ✅ **Support System** - Customer service
18. ✅ **FAQ & Legal Pages** - Compliance

---

## Implementation Notes

### For Existing Endpoints (Need Documentation)
- `/api/courses/{id}/diy-content` - Already implemented, needs documentation
- `/api/courses/{id}/resources` - Already implemented, needs documentation

### For New Endpoints
- Consider rate limiting for review/comment submissions
- Implement proper file upload handling for profile pictures
- Use queue system for email/SMS sending
- Implement caching for frequently accessed data (notifications, analytics)
- Consider pagination for all list endpoints
- Implement proper error handling and validation

---

**Last Updated:** January 2024

