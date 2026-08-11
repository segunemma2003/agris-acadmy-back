<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Course;
use App\Models\Module;
use App\Models\Topic;
use App\Models\ModuleTest;
use App\Models\TopicTest;
use App\Models\TestAttempt;
use App\Models\TopicTestAttempt;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses/{course}/modules/{module}/test",
     *     tags={"Tests"},
     *     summary="Get the test for a module with questions and past attempts",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="module", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Module test with questions, attempts, quiz_passed and required 80% threshold"),
     *     @OA\Response(response=403, description="Not enrolled"),
     *     @OA\Response(response=404, description="No test found for this module")
     * )
     */
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
        $attempts = collect();
        if ($user) {
            $attempts = TestAttempt::where('module_test_id', $test->id)
                ->where('user_id', $user->id)
                ->orderBy('completed_at', 'desc')
                ->get();
        }

        $quizStatus = $module->quizStatusFor($user);
        $bestScore = $attempts->isNotEmpty() ? (float) $attempts->max('percentage') : null;
        $passingScore = $module->effectivePassingScore($test);
        $isPassed = $bestScore !== null && $bestScore >= $passingScore;

        return response()->json([
            'success' => true,
            'data' => [
                'test' => $test,
                'attempts' => $attempts,
                'has_attempted' => $attempts->isNotEmpty(),
                'best_score' => $bestScore,
                'is_passed' => $isPassed,
                'quiz_passed' => $isPassed,
                'quiz_completed' => $attempts->isNotEmpty(),
                'passing_score' => $passingScore,
                'required_percentage' => $passingScore,
                'message' => $quizStatus['message'] ?? null,
                'quiz_status' => $quizStatus,
            ],
            'message' => 'Module test retrieved successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/courses/{course}/modules/{module}/tests/{test}/submit",
     *     tags={"Tests"},
     *     summary="Submit answers for a module test",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="module", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="test", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(property="answers", type="object", description="Map of question_id => selected_answer", example={"1":"A","2":"C"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Test submitted with score, quiz_passed, and next-module lock messaging (80% threshold)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="You scored 45%. You need 80% to unlock the next module."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="score", type="integer", example=2),
     *                 @OA\Property(property="total_questions", type="integer", example=5),
     *                 @OA\Property(property="percentage", type="number", example=40),
     *                 @OA\Property(property="is_passed", type="boolean", example=false),
     *                 @OA\Property(property="quiz_passed", type="boolean", example=false),
     *                 @OA\Property(property="quiz_completed", type="boolean", example=true),
     *                 @OA\Property(property="passing_score", type="number", example=80),
     *                 @OA\Property(property="required_percentage", type="number", example=80),
     *                 @OA\Property(property="unlocks_next_module", type="boolean", example=false),
     *                 @OA\Property(property="next_module_locked", type="boolean", example=true),
     *                 @OA\Property(property="message", type="string", example="You scored 45%. You need 80% to unlock the next module."),
     *                 @OA\Property(property="next_module", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Not enrolled"),
     *     @OA\Response(response=404, description="Test not in this module")
     * )
     */
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

        if ($test->max_attempts) {
            $attemptCount = TestAttempt::where('module_test_id', $test->id)
                ->where('user_id', $user->id)
                ->count();

            if ($attemptCount >= $test->max_attempts) {
                return response()->json([
                    'success' => false,
                    'message' => "You've used all {$test->max_attempts} allowed attempts for this quiz.",
                ], 403);
            }
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
        $passingScore = max((float) $test->passing_score, Module::DEFAULT_PASSING_PERCENTAGE);
        $isPassed = $percentage >= $passingScore;

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

        $user->recordActivity();

        $nextModule = Module::where('course_id', $module->course_id)
            ->where('is_active', true)
            ->where('sort_order', '>', $module->sort_order)
            ->orderBy('sort_order')
            ->first();

        $lockMessage = $isPassed
            ? null
            : sprintf(
                'You scored %.0f%%. You need %.0f%% to unlock the next module.',
                round($percentage, 2),
                $passingScore
            );

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $attempt,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage' => round($percentage, 2),
                'is_passed' => $isPassed,
                'quiz_passed' => $isPassed,
                'quiz_completed' => true,
                'passing_score' => $passingScore,
                'required_percentage' => $passingScore,
                'unlocks_next_module' => $isPassed,
                'next_module_locked' => $nextModule ? ! $isPassed : false,
                'next_module' => $nextModule ? [
                    'id' => $nextModule->id,
                    'title' => $nextModule->title,
                ] : null,
                'message' => $lockMessage,
            ],
            'message' => $isPassed
                ? 'Test passed successfully. The next module is now unlocked.'
                : ($lockMessage ?? 'Test completed'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/courses/{course}/modules/{module}/topics/{topic}/test",
     *     tags={"Tests"},
     *     summary="Get the test for a specific topic/lesson with questions and past attempts",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="module", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Topic test with questions and past attempts"),
     *     @OA\Response(response=403, description="Not enrolled"),
     *     @OA\Response(response=404, description="No test found for this topic")
     * )
     */
    public function showTopicTest(Request $request, Course $course, Module $module, Topic $topic)
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

        // Check if topic belongs to module
        if ($topic->module_id !== $module->id) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found in this module'
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

        $test = TopicTest::where('topic_id', $topic->id)
            ->where('is_active', true)
            ->with(['questions' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->first();

        if (!$test) {
            return response()->json([
                'success' => false,
                'message' => 'No test available for this topic'
            ], 404);
        }

        \App\Services\ContentLocalizer::applyToCollection($test->questions, $user, ['question', 'options', 'explanation']);

        // Get user's attempts
        $attempts = [];
        if ($user) {
            $attempts = TopicTestAttempt::where('topic_test_id', $test->id)
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
            'message' => 'Topic test retrieved successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/courses/{course}/modules/{module}/topics/{topic}/tests/{test}/submit",
     *     tags={"Tests"},
     *     summary="Submit answers for a topic/lesson test",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="module", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="test", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(property="answers", type="object", description="Map of question_id => selected_answer", example={"1":"B","2":"D"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Test submitted with score and pass/fail result"),
     *     @OA\Response(response=403, description="Not enrolled"),
     *     @OA\Response(response=404, description="Test or topic not found")
     * )
     */
    public function submitTopicTest(Request $request, Course $course, Module $module, Topic $topic, TopicTest $test)
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

        // Check if topic belongs to module
        if ($topic->module_id !== $module->id) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found in this module'
            ], 404);
        }

        // Check if test belongs to topic
        if ($test->topic_id !== $topic->id) {
            return response()->json([
                'success' => false,
                'message' => 'Test not found for this topic'
            ], 404);
        }

        if ($test->max_attempts) {
            $attemptCount = TopicTestAttempt::where('topic_test_id', $test->id)
                ->where('user_id', $user->id)
                ->count();

            if ($attemptCount >= $test->max_attempts) {
                return response()->json([
                    'success' => false,
                    'message' => "You've used all {$test->max_attempts} allowed attempts for this quiz.",
                ], 403);
            }
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
        $attempt = TopicTestAttempt::create([
            'topic_test_id' => $test->id,
            'user_id' => $user->id,
            'answers' => $userAnswers,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage' => round($percentage, 2),
            'is_passed' => $isPassed,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $user->recordActivity();

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

