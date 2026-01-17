# Comprehensive API Implementation Plan

This document outlines the implementation plan for all requested APIs with caching, S3 support, and performance optimization.

## Image Dimensions (Universal for Mobile/Desktop/Web)

**Recommended Dimensions:**
- **Course Images:** 1920×1080px (16:9 aspect ratio)
  - Works well for all platforms
  - Can be scaled down for mobile
  - Maintains quality on desktop
  - Format: JPEG or PNG
  - Max file size: 2MB

- **Category Images:** 800×800px (1:1 square)
  - Perfect for icons and thumbnails
  - Works on all platforms
  - Format: PNG with transparency or JPEG
  - Max file size: 500KB

- **User Avatars:** 400×400px (1:1 square)
  - Optimal for profile pictures
  - Format: JPEG or PNG
  - Max file size: 200KB

## S3 Configuration

Add to `.env`:
```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=your_region
AWS_BUCKET=your_bucket
AWS_URL=https://your_bucket.s3.region.amazonaws.com
```

## Caching Strategy

- Cache keys: `user_{user_id}_courses`, `user_{user_id}_recommendations`
- Cache duration: 5 minutes for user-specific data
- Cache tags: `courses`, `user_{user_id}`
- Clear cache on: course update, enrollment, progress update

## API Endpoints to Implement

1. **Daily Recommended Courses** - `GET /api/daily-recommended-courses`
2. **User Ongoing Courses** - `GET /api/my-ongoing-courses`
3. **Latest 10 Courses** - `GET /api/latest-courses`
4. **Featured Courses** - `GET /api/featured-courses`
5. **Course Details with Enrollment Status** - `GET /api/courses/{id}`
6. **Course Reviews** - `GET /api/courses/{id}/reviews`
7. **Course Completion Percentage** - `GET /api/courses/{id}/completion`
8. **Course Curriculum** - `GET /api/courses/{id}/curriculum`
9. **Lesson Notes** - Multiple endpoints
10. **Comments** - Lesson and Course comments
11. **Saved Courses** - CRUD operations
12. **User Profile with Stats** - `GET /api/user/profile`
13. **Change Password** - `PUT /api/user/password`
14. **Delete Account** - `DELETE /api/user/account`
15. **Certificates** - `GET /api/user/certificates`
16. **Categories** - `GET /api/categories`
17. **Courses by Category** - `GET /api/categories/{id}/courses`

## Video Transcription

- Cron job runs every hour
- Processes videos with `transcription_completed = false`
- Supports Hausa and English
- Updates `transcript_english` and `transcript_hausa` fields


