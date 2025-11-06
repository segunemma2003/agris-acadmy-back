# Stats, Charts & Bulk Enrollment Codes Guide

## 📊 Stats & Charts

### Admin Dashboard

#### 1. Stats Overview Widget

-   **Total Users** - All registered users with trend chart
-   **Total Courses** - All courses with published count
-   **Total Enrollments** - Active and completed enrollments
-   **Tutors & Students** - Breakdown by role
-   **Total Revenue** - Revenue from all enrollments

#### 2. Chart Stats Widget

-   **Enrollment Trends** - Line chart showing enrollments over last 7 days
-   Visual representation of enrollment growth
-   Helps identify trends and peak periods

#### 3. Table Stats Widget

-   **Top Courses by Enrollments** - Table showing:
    -   Course title
    -   Tutor name
    -   Enrollment count
    -   Rating
    -   Publication status
-   Sorted by enrollment count (highest first)
-   Limited to top 10 courses

### Tutor Dashboard

#### 1. Stats Overview Widget

-   **My Courses** - Courses created by tutor
-   **Total Students** - Students enrolled in tutor's courses
-   **Pending Assignments** - Assignments waiting for grading
-   **Unread Messages** - Messages from students

#### 2. Chart Stats Widget

-   **My Course Enrollments** - Line chart showing enrollments for tutor's courses over last 7 days
-   Visual representation of course performance

#### 3. Table Stats Widget

-   **My Courses Performance** - Table showing:
    -   Course title
    -   Enrollment count
    -   Rating
    -   Publication status
    -   Created date
-   Sorted by enrollment count

---

## 📧 Bulk Enrollment Code Generation

### Features

1. **Bulk Creation** - Create multiple enrollment codes at once
2. **Email Integration** - Automatically send codes to email addresses
3. **Flexible Input** - Support for comma, semicolon, or newline separated emails
4. **Multiple Codes per Email** - Option to send 1-10 codes per email
5. **Expiration Date** - Set expiration date for all codes
6. **Beautiful Email Template** - Professional HTML email template

### How to Use

#### For Admins

1. **Go to Enrollment Codes**

    - Navigate to: Course Management → Enrollment Codes

2. **Click "Bulk Create & Send"**

    - Located in the table header actions

3. **Fill in the Form**

    - **Course**: Select the course
    - **Tutor**: Select the tutor (auto-filled if you're a tutor)
    - **Email Addresses**: Enter emails separated by commas, new lines, or semicolons
        ```
        Example:
        student1@example.com, student2@example.com
        student3@example.com
        student4@example.com; student5@example.com
        ```
    - **Number of Codes per Email**: How many codes each email should receive (1-10)
    - **Expiration Date**: (Optional) When codes expire

4. **Submit**
    - System will:
        - Create unique enrollment codes for each email
        - Send beautiful HTML emails automatically
        - Show success notification with counts

#### For Tutors

Same process as above, but:

-   Can only generate codes for their own courses
-   Tutor ID is auto-filled

### Individual Code Email

1. **View Enrollment Codes**

    - Go to Enrollment Codes list

2. **Click "Send Email"**

    - Available for codes with email addresses that aren't used yet

3. **Email Sent**
    - Code is sent to the email address on file

---

## 👥 Admin Full Visibility

### New Admin Resources

#### 1. Enrollments Resource

-   **Location**: User Management → Enrollments
-   **Features**:
    -   View all enrollments across all courses
    -   See student progress
    -   Filter by course, student, status
    -   Edit enrollment details
    -   View enrollment codes

#### 2. Messages Resource

-   **Location**: Communication → Messages
-   **Features**:
    -   View all messages between students and tutors
    -   Filter by course, read status
    -   Create new messages
    -   View message details
    -   Manage communication

### Admin Can See

1. **All Courses** - All courses from all tutors
2. **All Enrollments** - Every student enrollment
3. **All Messages** - All communication
4. **All Enrollment Codes** - All codes generated
5. **All Users** - All users with their roles
6. **Complete Statistics** - System-wide stats and trends

---

## 📧 Email Template

The enrollment code email includes:

-   Professional design with gradient header
-   Course information
-   Large, easy-to-read enrollment code
-   Expiration date (if set)
-   Clear instructions
-   Branded footer

### Email Content

-   **Subject**: Your Enrollment Code - [Course Title]
-   **Body**:
    -   Greeting
    -   Course title and description
    -   Enrollment code (prominently displayed)
    -   Expiration date (if applicable)
    -   Instructions on how to use the code

---

## 🎯 Usage Examples

### Example 1: Send Codes to 10 Students

1. Click "Bulk Create & Send"
2. Select course
3. Enter 10 email addresses (one per line)
4. Set codes per email: 1
5. Submit
6. All 10 students receive their codes via email

### Example 2: Send Multiple Codes to One Student

1. Click "Bulk Create & Send"
2. Select course
3. Enter one email address
4. Set codes per email: 3
5. Submit
6. Student receives 3 enrollment codes

### Example 3: Send Codes with Expiration

1. Click "Bulk Create & Send"
2. Fill in course and emails
3. Set expiration date (e.g., 30 days from now)
4. Submit
5. All codes expire on the specified date

---

## 📊 Understanding Stats

### Enrollment Trends Chart

-   Shows daily enrollments for the last 7 days
-   Helps identify:
    -   Peak enrollment days
    -   Growth trends
    -   Seasonal patterns

### Top Courses Table

-   Shows which courses are most popular
-   Helps identify:
    -   Best performing courses
    -   Most engaging tutors
    -   Course categories that perform well

### Course Performance (Tutor)

-   Shows tutor's own courses
-   Helps tutors:
    -   Track their course performance
    -   Identify which courses need improvement
    -   Plan content updates

---

## 🔧 Configuration

### Email Settings

Make sure your `.env` file has email configuration:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="AgriSiti Academy"
```

### Testing Email

To test email sending:

1. Use Mailtrap or similar service for development
2. Check logs if emails fail: `storage/logs/laravel.log`
3. Verify SMTP settings in `.env`

---

## ✅ Features Summary

-   ✅ **Stats Widgets** - Visual statistics with charts
-   ✅ **Table Widgets** - Data tables with rankings
-   ✅ **Bulk Code Generation** - Create multiple codes at once
-   ✅ **Email Integration** - Automatic email sending
-   ✅ **Beautiful Email Template** - Professional HTML emails
-   ✅ **Admin Full Visibility** - See all system data
-   ✅ **Tutor Statistics** - Performance tracking
-   ✅ **Real-time Data** - Up-to-date statistics

---

**All features are ready to use! 🚀**
