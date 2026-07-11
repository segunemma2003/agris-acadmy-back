<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseV2Controller;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\SavedCourseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\TranscriptController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\CertificateVerificationController;
use App\Http\Controllers\Api\UssdController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrganisationController;
use App\Http\Controllers\Api\ApprenticeshipSlotController;
use App\Http\Controllers\Api\ApprenticeshipController;
use App\Http\Controllers\Api\ApprenticeshipLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Chatbot routes (public, throttled)
Route::middleware('throttle:20,1')->prefix('chatbot')->group(function () {
    Route::post('/session', [ChatbotController::class, 'session']);
    Route::post('/message', [ChatbotController::class, 'message']);
    Route::post('/message/stream', [ChatbotController::class, 'messageStream']); // SSE streaming
    Route::post('/message/ws',     [ChatbotController::class, 'messageWebSocket']); // Reverb WebSocket
    Route::post('/dismiss', [ChatbotController::class, 'dismiss']);
    Route::post('/answers', [ChatbotController::class, 'saveAnswers']);
    Route::post('/recommendations', [ChatbotController::class, 'recommendations']);
    Route::post('/link-answers', [ChatbotController::class, 'linkAnswers']);
});

// USSD webhook (Africa's Talking; rate not throttled — AT controls session frequency)
Route::post('/ussd', [UssdController::class, 'handle']);

// Nigeria location data (public, cached forever)
Route::prefix('locations')->group(function () {
    Route::get('/states', [LocationController::class, 'states']);
    Route::get('/lgas/{state}', [LocationController::class, 'lgas']);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/organisations/register', [OrganisationController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Categories (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/courses', [CategoryController::class, 'courses']);
Route::get('/categories-with-courses', [CategoryController::class, 'withCourses']);
Route::get('/featured-courses-public', [CategoryController::class, 'featuredCourses']);

// Courses (public)
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show']);
Route::get('/featured-courses', [CourseV2Controller::class, 'featured']); // Public featured courses endpoint

// Certificate verification (public, throttled to prevent abuse)
Route::middleware('throttle:20,1')->prefix('certificates')->group(function () {
    Route::post('/verify', [CertificateVerificationController::class, 'verifyUpload']);
    Route::get('/verify/{code}', [CertificateVerificationController::class, 'verifyCode']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);
    Route::delete('/user/account', [AuthController::class, 'deleteAccount']);
    Route::get('/user/certificates', [AuthController::class, 'certificates']);
    Route::post('/user/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::delete('/user/profile/avatar', [AuthController::class, 'deleteAvatar']);

    // Enrollments
    Route::post('/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/my-enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('/my-courses', [EnrollmentController::class, 'myCourses']);
    Route::get('/my-ongoing-courses', [EnrollmentController::class, 'myOngoingCourses']);
    Route::get('/ongoing-courses', [EnrollmentController::class, 'ongoingCourses']);
    Route::get('/completed-courses', [EnrollmentController::class, 'completedCourses']);
    Route::get('/saved-courses-list', [EnrollmentController::class, 'savedCourses']);
    Route::get('/certified-courses', [EnrollmentController::class, 'certifiedCourses']);
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);

    // Courses
    Route::get('/recommended-courses', [CourseController::class, 'recommendedCourses']);
    Route::get('/daily-recommended-courses', [CourseV2Controller::class, 'dailyRecommended']);
    Route::get('/latest-courses', [CourseV2Controller::class, 'latest']);
    Route::get('/courses/{course}/modules', [CourseController::class, 'modules']);
    Route::get('/courses/{course}/information', [CourseController::class, 'courseInformation']);
    Route::get('/courses/{course}/diy-content', [CourseController::class, 'diyContent']);
    Route::get('/courses/{course}/vr-content', [CourseController::class, 'vrContent']);
    Route::get('/courses/{course}/resources', [CourseController::class, 'resources']);
    Route::post('/topics/{topic}/resources/{resource}/download', [CourseController::class, 'logResourceDownload']);
    Route::get('/courses/{course}/curriculum', [CourseV2Controller::class, 'curriculum']);
    Route::get('/courses/{course}/completion', [CourseV2Controller::class, 'completion']);
    Route::get('/courses/{course}/reviews', [CourseV2Controller::class, 'reviews']);
    Route::post('/courses/{course}/reviews', [CourseV2Controller::class, 'addReview']);
    Route::put('/courses/{course}/reviews/{review}', [CourseV2Controller::class, 'updateReview']);
    Route::delete('/courses/{course}/reviews/{review}', [CourseV2Controller::class, 'deleteReview']);

    // Modules
    Route::get('/courses/{course}/modules/{module}', [ModuleController::class, 'show']);

    // Progress
    Route::get('/courses/{course}/progress', [ProgressController::class, 'show']);
    Route::post('/topics/{topic}/complete', [ProgressController::class, 'complete']);
    Route::put('/progress/{studentProgress}', [ProgressController::class, 'update']);
    Route::post('/progress/sync', [ProgressController::class, 'sync']);
    Route::post('/courses/{course}/modules/{module}/tests/{test}/complete-quiz', [ProgressController::class, 'completeQuiz']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/course-breakdown', [DashboardController::class, 'courseBreakdown']);
    Route::get('/dashboard/quiz-history', [DashboardController::class, 'quizHistory']);

    // Notes
    Route::get('/my-notes', [NoteController::class, 'myNotes']);
    Route::get('/courses/{course}/notes', [NoteController::class, 'index']);
    Route::get('/courses/{course}/modules/{module}/notes', [NoteController::class, 'moduleNotes']);
    Route::get('/courses/{course}/modules/{module}/topics/{topic}/notes', [NoteController::class, 'topicNotes']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::put('/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

    // Comments
    Route::get('/courses/{course}/topics/{topic}/comments', [CommentController::class, 'lessonComments']);
    Route::post('/courses/{course}/topics/{topic}/comments', [CommentController::class, 'addLessonComment']);
    Route::put('/courses/{course}/topics/{topic}/comments/{comment}', [CommentController::class, 'updateLessonComment']);
    Route::delete('/courses/{course}/topics/{topic}/comments/{comment}', [CommentController::class, 'deleteLessonComment']);
    Route::get('/courses/{course}/comments', [CommentController::class, 'courseComments']);
    Route::post('/courses/{course}/comments', [CommentController::class, 'addCourseComment']);
    Route::put('/courses/{course}/comments/{comment}', [CommentController::class, 'updateCourseComment']);
    Route::delete('/courses/{course}/comments/{comment}', [CommentController::class, 'deleteCourseComment']);
    Route::post('/courses/{course}/comments/{comment}/pin', [CommentController::class, 'pinCourseComment']);
    Route::post('/courses/{course}/comments/{comment}/accept', [CommentController::class, 'acceptCourseReply']);

    // Saved Courses
    Route::get('/saved-courses', [SavedCourseController::class, 'index']);
    Route::post('/courses/{course}/save', [SavedCourseController::class, 'store']);
    Route::delete('/courses/{course}/unsave', [SavedCourseController::class, 'destroy']);

    // Tests/Quizzes
    Route::get('/courses/{course}/modules/{module}/test', [TestController::class, 'show']);
    Route::post('/courses/{course}/modules/{module}/tests/{test}/submit', [TestController::class, 'submit']);

    // Topic Tests (Lesson Tests)
    Route::get('/courses/{course}/modules/{module}/topics/{topic}/test', [TestController::class, 'showTopicTest']);
    Route::post('/courses/{course}/modules/{module}/topics/{topic}/tests/{test}/submit', [TestController::class, 'submitTopicTest']);

    // Assignments
    Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index']);
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit']);
    Route::get('/my-submissions', [AssignmentController::class, 'mySubmissions']);

    // Messages
    Route::get('/courses/{course}/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::put('/messages/{message}/read', [MessageController::class, 'markAsRead']);

    // Facilitators and Instructors
    Route::get('/facilitators', [App\Http\Controllers\Api\FacilitatorController::class, 'index']);
    Route::get('/instructors', [App\Http\Controllers\Api\FacilitatorController::class, 'instructors']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/read/all', [NotificationController::class, 'deleteAllRead']);

    // Community Forum
    Route::get('/forum/posts', [ForumController::class, 'index']);
    Route::post('/forum/posts', [ForumController::class, 'store']);
    Route::get('/forum/posts/{post}', [ForumController::class, 'show']);
    Route::get('/forum/posts/{post}/comments', [ForumController::class, 'comments']);
    Route::post('/forum/posts/{post}/comments', [ForumController::class, 'addComment']);
    Route::post('/forum/posts/{post}/like', [ForumController::class, 'toggleLike']);
    Route::post('/forum/posts/{post}/share', [ForumController::class, 'share']);
    Route::post('/forum/comments/{comment}/like', [ForumController::class, 'toggleCommentLike']);

    // Organisations (self-service org profile)
    Route::get('/organisations/me', [OrganisationController::class, 'me']);
    Route::put('/organisations/me', [OrganisationController::class, 'update']);

    // Career Pathways: apprenticeship slots
    Route::get('/career/apprenticeships', [ApprenticeshipSlotController::class, 'index']);
    Route::get('/career/apprenticeships/{slot}', [ApprenticeshipSlotController::class, 'show']);
    Route::post('/career/apprenticeships/{slot}/express-interest', [ApprenticeshipController::class, 'expressInterest']);
    Route::get('/career/my-applications', [ApprenticeshipController::class, 'myApplications']);

    // Career Pathways: organisation portal (slot management)
    Route::get('/org/slots', [ApprenticeshipSlotController::class, 'mine']);
    Route::post('/org/slots', [ApprenticeshipSlotController::class, 'store']);
    Route::put('/org/slots/{slot}', [ApprenticeshipSlotController::class, 'update']);
    Route::post('/org/slots/{slot}/close', [ApprenticeshipSlotController::class, 'close']);
    Route::get('/org/slots/{slot}/applicants', [ApprenticeshipController::class, 'applicants']);
    Route::get('/org/interns', [ApprenticeshipController::class, 'interns']);
    Route::post('/org/applications/{apprenticeship}/review', [ApprenticeshipController::class, 'review']);

    // Career Pathways: apprenticeship daily logs (web)
    Route::get('/apprenticeships/{apprenticeship}/logs', [ApprenticeshipLogController::class, 'index']);
    Route::post('/apprenticeships/{apprenticeship}/logs', [ApprenticeshipLogController::class, 'store']);

    // Pusher channel authorization
    Route::post('/broadcasting/auth', function (Request $request) {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        // Validate channel name format: private-course.{courseId}.user.{userId}
        if (preg_match('/^private-course\.(\d+)\.user\.(\d+)$/', $channelName, $matches)) {
            $courseId = $matches[1];
            $userId = $matches[2];

            // Verify user has access to this channel
            if ($user->id != $userId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check if user is enrolled in the course
            $enrollment = $user->enrollments()->where('course_id', $courseId)->first();
            if (!$enrollment) {
                return response()->json(['message' => 'Not enrolled in course'], 403);
            }
        } else {
            return response()->json(['message' => 'Invalid channel name'], 400);
        }

        // Generate Pusher auth signature
        $pusher = app(\Pusher\Pusher::class);
        $auth = $pusher->authorizeChannel($channelName, $socketId);

        return response()->json($auth);
    });
});



