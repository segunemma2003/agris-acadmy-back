# Enrollment Flow Documentation

## Overview

The Agrisiti Academy enrollment system uses a **code-based enrollment** approach. Students must have a valid enrollment code to enroll in any course. This ensures controlled access and allows administrators/tutors to manage course access effectively.

## Enrollment Flow Diagram

```
┌─────────────────┐
│ Admin/Tutor     │
│ Creates Code    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Code Generated  │
│ & Sent via Email│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Student Receives│
│ Enrollment Code │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Student Uses    │
│ Code to Enroll  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Enrollment      │
│ Created & Code   │
│ Marked as Used  │
└─────────────────┘
```

## Step-by-Step Process

### Step 1: Code Generation (Admin/Tutor)

**Location:** Filament Admin Panel → Enrollment Codes

**How to Create Enrollment Codes:**

1. Navigate to **Enrollment Codes** in the admin panel
2. Click **New Enrollment Code**
3. Fill in the form:
   - **Course**: Select the course
   - **Tutor**: Select the tutor (optional)
   - **Student**: Select a specific student (optional)
   - **Email**: Enter student email (if not selecting a student)
   - **Expiration Date**: Set when the code expires (optional)
4. Click **Create**
5. The code is automatically generated and sent via email

**Bulk Code Generation:**

1. Select multiple enrollment codes (or use bulk actions)
2. Choose **Bulk Create & Send (Student List)** or **Bulk Create & Send (Email List)**
3. Provide:
   - Course selection
   - List of students or emails
   - Expiration date (optional)
4. System will:
   - Generate unique codes for each student
   - Send enrollment code emails
   - Skip if code already exists for student

### Step 2: Code Distribution

**Automatic Email Delivery:**

When an enrollment code is created, the system automatically:
- Generates a unique 12-character code (e.g., `ABC123XYZ456`)
- Sends an email to the student with:
  - The enrollment code
  - Course information
  - Expiration date (if set)
  - Instructions on how to use the code

**Email Template:** `resources/views/emails/enrollment-code.blade.php`

### Step 3: Student Enrollment (API)

**Endpoint:** `POST /api/enroll`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "course_id": 1,
  "enrollment_code": "ABC123XYZ456"
}
```

**Request Validation:**
- `course_id`: Required, must exist in courses table
- `enrollment_code`: Required, must be a valid string

**Enrollment Validation Process:**

The system performs the following checks:

1. **Course Availability**
   - Course must be published (`is_published = true`)
   - Returns error if course is not available

2. **Duplicate Enrollment Check**
   - Checks if user is already enrolled
   - Returns existing enrollment if already enrolled

3. **Code Validation**
   - Code must exist in the database
   - Code must match the course
   - Code must not be used (`is_used = false`)
   - Code must not be expired (if `expires_at` is set)

4. **Email Validation** (if code has email)
   - If enrollment code has an `email` field, it must match the authenticated user's email
   - Prevents code sharing between users

5. **User ID Validation** (if code has user_id)
   - If enrollment code has a `user_id` field, it must match the authenticated user's ID
   - Ensures code is used by the intended recipient

**Success Response (201):**
```json
{
  "success": true,
  "message": "Successfully enrolled in course",
  "data": {
    "enrollment": {
      "id": 1,
      "user_id": 5,
      "course_id": 1,
      "enrollment_code": "ABC123XYZ456",
      "status": "active",
      "enrolled_at": "2025-01-18T12:00:00.000000Z",
      "progress_percentage": "0.00",
      "course": {
        "id": 1,
        "title": "Introduction to Agriculture",
        "image": "courses/intro-agriculture.jpg",
        "slug": "introduction-to-agriculture",
        "short_description": "Learn the basics of modern agriculture",
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

**400 - Invalid Code:**
```json
{
  "success": false,
  "message": "Invalid or already used enrollment code"
}
```

**400 - Code Expired:**
```json
{
  "success": false,
  "message": "Enrollment code has expired"
}
```

**400 - Already Enrolled:**
```json
{
  "success": false,
  "message": "You are already enrolled in this course",
  "data": {
    "enrollment": { ... }
  }
}
```

**403 - Email Mismatch:**
```json
{
  "success": false,
  "message": "This enrollment code is not valid for your account. Please use the code sent to your email address."
}
```

**400 - Course Not Available:**
```json
{
  "success": false,
  "message": "This course is not available for enrollment"
}
```

### Step 4: Post-Enrollment

After successful enrollment:

1. **Enrollment Record Created**
   - Status: `active`
   - Progress: `0.00%`
   - Enrollment date: Current timestamp

2. **Code Marked as Used**
   - `is_used`: Set to `true`
   - `user_id`: Set to enrolled user's ID
   - `used_at`: Set to current timestamp

3. **Course Statistics Updated**
   - `enrollment_count`: Incremented by 1

4. **Student Access Granted**
   - Student can now access course content
   - Student can view modules, topics, tests, assignments
   - Progress tracking begins

## Enrollment Code Model

**Table:** `enrollment_codes`

**Fields:**
- `id`: Primary key
- `course_id`: Foreign key to courses
- `tutor_id`: Foreign key to users (tutor who created the code)
- `user_id`: Foreign key to users (intended recipient, nullable)
- `email`: Email address of intended recipient (nullable)
- `code`: Unique 12-character code (e.g., `ABC123XYZ456`)
- `is_used`: Boolean flag indicating if code has been used
- `expires_at`: Optional expiration timestamp
- `used_at`: Timestamp when code was used
- `created_at`: Code creation timestamp
- `updated_at`: Last update timestamp

**Code Generation:**
- Codes are automatically generated using `EnrollmentCode::generateCode()`
- Format: 12 uppercase alphanumeric characters
- Uniqueness: System ensures no duplicate codes exist

## Enrollment Model

**Table:** `enrollments`

**Fields:**
- `id`: Primary key
- `user_id`: Foreign key to users
- `course_id`: Foreign key to courses
- `enrollment_code`: The code used for enrollment
- `status`: Enum (`active`, `completed`, `cancelled`)
- `enrolled_at`: Enrollment timestamp
- `completed_at`: Completion timestamp (nullable)
- `progress_percentage`: Decimal (0.00 to 100.00)
- `amount_paid`: Decimal (for future payment integration)
- `payment_method`: String (nullable)
- `transaction_id`: String (nullable)
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

**Unique Constraint:** `(user_id, course_id)` - Prevents duplicate enrollments

## API Endpoints

### Enroll in Course
```
POST /api/enroll
Authorization: Bearer {token}
Content-Type: application/json

{
  "course_id": 1,
  "enrollment_code": "ABC123XYZ456"
}
```

### Get My Enrollments
```
GET /api/my-enrollments
Authorization: Bearer {token}
```

### Get My Courses
```
GET /api/my-courses?status=all|active|completed|cancelled
Authorization: Bearer {token}
```

### Get Ongoing Courses
```
GET /api/my-ongoing-courses
Authorization: Bearer {token}
```

### Get Completed Courses
```
GET /api/completed-courses
Authorization: Bearer {token}
```

### Get Enrollment Details
```
GET /api/enrollments/{enrollment_id}
Authorization: Bearer {token}
```

## Admin Panel Usage

### Creating Enrollment Codes

1. **Single Code Creation:**
   - Go to **Enrollment Codes** → **New Enrollment Code**
   - Select course, tutor, and student/email
   - Set expiration (optional)
   - Save - code is generated and email is sent

2. **Bulk Code Creation:**
   - Select existing codes or use bulk actions
   - Choose **Bulk Create & Send**
   - Provide course and student list
   - System generates codes and sends emails

### Managing Enrollment Codes

- **View All Codes**: See all generated codes with status
- **Filter**: Filter by course, tutor, used/unused, expired
- **Resend Email**: Resend enrollment code email
- **Delete**: Remove unused codes

### Viewing Enrollments

- **All Enrollments**: View all student enrollments
- **Filter by Status**: Active, completed, cancelled
- **Filter by Course**: See enrollments for specific course
- **Filter by Student**: See all enrollments for a student
- **Progress Tracking**: View student progress percentages

## Security Features

1. **Code Uniqueness**: Each code is unique and can only be used once
2. **Email Validation**: Codes can be tied to specific email addresses
3. **User Validation**: Codes can be tied to specific user IDs
4. **Expiration**: Codes can have expiration dates
5. **Course Matching**: Codes are validated against the course they're used for
6. **Authentication Required**: Enrollment requires user authentication

## Email Notifications

### Enrollment Code Email

**Subject:** `Your Enrollment Code - {Course Title}`

**Content:**
- Personalized greeting
- Course information (title, description, image)
- Enrollment code (prominently displayed)
- Expiration date (if applicable)
- Instructions on how to use the code
- Link to the application

**Template:** `resources/views/emails/enrollment-code.blade.php`

## Best Practices

1. **Code Expiration**: Set expiration dates for time-sensitive enrollments
2. **Email-Specific Codes**: Use email-specific codes to prevent sharing
3. **Bulk Generation**: Use bulk actions for multiple students
4. **Code Management**: Regularly clean up expired or unused codes
5. **Student Communication**: Ensure students understand they need a code to enroll
6. **Code Distribution**: Always send codes via the system (not manually) to ensure proper tracking

## Troubleshooting

### Student Cannot Enroll

**Issue:** "Invalid or already used enrollment code"

**Solutions:**
- Verify the code exists and hasn't been used
- Check if code matches the course
- Verify code hasn't expired
- Check if code email matches student's email

**Issue:** "This enrollment code is not valid for your account"

**Solutions:**
- Verify student is using the code sent to their email
- Check if code has email/user_id restrictions
- Ensure student is logged in with the correct account

**Issue:** "Enrollment code has expired"

**Solutions:**
- Generate a new enrollment code
- Extend expiration date if needed
- Contact admin for a new code

### Code Not Sent via Email

**Issue:** Student didn't receive enrollment code email

**Solutions:**
- Check email configuration (SMTP settings)
- Verify email address is correct
- Check spam/junk folder
- Resend code from admin panel
- Check queue status (emails are queued)

### Duplicate Enrollment

**Issue:** Student tries to enroll but is already enrolled

**Solution:**
- System automatically detects and returns existing enrollment
- Student can continue with their existing enrollment

## Future Enhancements

Potential improvements to the enrollment system:

1. **Payment Integration**: Link enrollment codes with payment processing
2. **Code Types**: Different code types (free, paid, discount)
3. **Code Analytics**: Track code usage and effectiveness
4. **Auto-Expiration**: Automatic cleanup of expired codes
5. **Code Sharing**: Allow limited code sharing with restrictions
6. **Batch Operations**: Enhanced bulk operations for admins

## Related Documentation

- [API Documentation](./COMPLETE_API_DOCUMENTATION.md)
- [Email Configuration](./.env.example)
- [Admin Panel Guide](./README.md)

