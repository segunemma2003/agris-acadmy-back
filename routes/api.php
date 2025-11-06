<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Categories (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Courses (public)
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show']);
Route::get('/categories-with-courses', [CategoryController::class, 'withCourses']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Enrollments
    Route::post('/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/my-enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('/my-courses', [EnrollmentController::class, 'myCourses']);
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);

    // Progress
    Route::get('/courses/{course}/progress', [ProgressController::class, 'show']);
    Route::post('/topics/{topic}/complete', [ProgressController::class, 'complete']);
    Route::put('/progress/{progress}', [ProgressController::class, 'update']);

    // Notes
    Route::get('/courses/{course}/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::put('/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

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



