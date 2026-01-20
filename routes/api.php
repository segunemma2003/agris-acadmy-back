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
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);
    Route::delete('/user/account', [AuthController::class, 'deleteAccount']);
    Route::get('/user/certificates', [AuthController::class, 'certificates']);

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
    Route::get('/courses/{course}/resources', [CourseController::class, 'resources']);
    Route::get('/courses/{course}/curriculum', [CourseV2Controller::class, 'curriculum']);
    Route::get('/courses/{course}/completion', [CourseV2Controller::class, 'completion']);
    Route::get('/courses/{course}/reviews', [CourseV2Controller::class, 'reviews']);

    // Modules
    Route::get('/courses/{course}/modules/{module}', [ModuleController::class, 'show']);

    // Progress
    Route::get('/courses/{course}/progress', [ProgressController::class, 'show']);
    Route::post('/topics/{topic}/complete', [ProgressController::class, 'complete']);
    Route::put('/progress/{progress}', [ProgressController::class, 'update']);
    Route::post('/courses/{course}/modules/{module}/tests/{test}/complete-quiz', [ProgressController::class, 'completeQuiz']);

    // Notes
    Route::get('/courses/{course}/notes', [NoteController::class, 'index']);
    Route::get('/courses/{course}/modules/{module}/notes', [NoteController::class, 'moduleNotes']);
    Route::get('/courses/{course}/modules/{module}/topics/{topic}/notes', [NoteController::class, 'topicNotes']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::put('/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

    // Comments
    Route::get('/courses/{course}/topics/{topic}/comments', [CommentController::class, 'lessonComments']);
    Route::post('/courses/{course}/topics/{topic}/comments', [CommentController::class, 'addLessonComment']);
    Route::get('/courses/{course}/comments', [CommentController::class, 'courseComments']);
    Route::post('/courses/{course}/comments', [CommentController::class, 'addCourseComment']);

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
});



