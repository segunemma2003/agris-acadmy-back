<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\ModuleTest;
use App\Models\TestAttempt;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function show(Request $request, Course $course, Module $module)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        // Check if module belongs to course
        if ($module->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found in this course'
            ], 404);
        }

        // Check if user is enrolled
        $user = $request->user();
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to access tests'
            ], 403);
        }

        $test = ModuleTest::where('module_id', $module->id)
            ->where('is_active', true)
            ->with(['questions' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->first();

        if (!$test) {
            return response()->json([
                'success' => false,
                'message' => 'No test available for this module'
            ], 404);
        }

        // Get user's attempts
        $attempts = [];
        if ($user) {
            $attempts = TestAttempt::where('module_test_id', $test->id)
                ->where('user_id', $user->id)
                ->orderBy('completed_at', 'desc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'test' => $test,
                'attempts' => $attempts,
                'has_attempted' => $attempts->isNotEmpty(),
                'best_score' => $attempts->isNotEmpty() ? $attempts->max('percentage') : null,
                'is_passed' => $attempts->isNotEmpty() ? $attempts->contains('is_passed', true) : false,
            ],
            'message' => 'Module test retrieved successfully'
        ]);
    }

    public function submit(Request $request, Course $course, Module $module, ModuleTest $test)
    {
        // Check if user is enrolled
        $user = $request->user();
        $isEnrolled = $user->enrollments()->where('course_id', $course->id)->exists();

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to take tests'
            ], 403);
        }

        // Check if test belongs to module
        if ($test->module_id !== $module->id) {
            return response()->json([
                'success' => false,
                'message' => 'Test not found in this module'
            ], 404);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'required',
        ]);

        // Calculate score
        $questions = $test->questions;
        $correctAnswers = 0;
        $totalQuestions = $questions->count();
        $userAnswers = $request->answers;

        foreach ($questions as $question) {
            $userAnswer = $userAnswers[$question->id] ?? null;
            if ($userAnswer == $question->correct_answer) {
                $correctAnswers++;
            }
        }

        $score = $correctAnswers;
        $percentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        $isPassed = $percentage >= $test->passing_score;

        // Create test attempt
        $attempt = TestAttempt::create([
            'module_test_id' => $test->id,
            'user_id' => $user->id,
            'answers' => $userAnswers,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage' => round($percentage, 2),
            'is_passed' => $isPassed,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $attempt,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage' => round($percentage, 2),
                'is_passed' => $isPassed,
                'passing_score' => $test->passing_score,
            ],
            'message' => $isPassed ? 'Test passed successfully' : 'Test completed'
        ], 201);
    }
}

