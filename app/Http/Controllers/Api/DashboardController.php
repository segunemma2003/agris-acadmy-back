<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudentProgress;
use App\Models\TestAttempt;
use App\Models\TopicTestAttempt;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Learner dashboard summary: completion, modules completed, latest quiz score, time on platform, and activity streak",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard summary",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="overall_completion_percentage", type="number", description="Completed topics / total topics across every enrolled course"),
     *                 @OA\Property(property="modules_completed", type="integer"),
     *                 @OA\Property(property="total_modules", type="integer"),
     *                 @OA\Property(property="latest_quiz", type="object", nullable=true,
     *                     @OA\Property(property="percentage", type="number"),
     *                     @OA\Property(property="is_passed", type="boolean"),
     *                     @OA\Property(property="course_title", type="string", nullable=true),
     *                     @OA\Property(property="test_title", type="string", nullable=true),
     *                     @OA\Property(property="completed_at", type="string", format="date-time")
     *                 ),
     *                 @OA\Property(property="time_on_platform", type="object",
     *                     description="Video watch time summed across all lessons (quiz-page dwell time is not yet tracked)",
     *                     @OA\Property(property="total_seconds", type="integer"),
     *                     @OA\Property(property="hours", type="integer"),
     *                     @OA\Property(property="minutes", type="integer")
     *                 ),
     *                 @OA\Property(property="streak", type="object",
     *                     @OA\Property(property="current", type="integer", description="0 if a full calendar day has passed with no activity"),
     *                     @OA\Property(property="longest", type="integer")
     *                 ),
     *                 @OA\Property(property="inactivity_warning", type="boolean", description="True once 3+ days have passed since the last recorded activity"),
     *                 @OA\Property(property="days_since_last_active", type="integer", nullable=true),
     *                 @OA\Property(property="total_courses", type="integer"),
     *                 @OA\Property(property="ongoing_courses", type="integer"),
     *                 @OA\Property(property="completed_courses", type="integer"),
     *                 @OA\Property(property="certificates_acquired", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Not available for organisation accounts")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'organisation') {
            return response()->json([
                'success' => false,
                'message' => 'The learner dashboard is not available for organisation accounts',
            ], 403);
        }

        $enrollments = $user->enrollments()->get();
        $enrolledCourseIds = $enrollments->pluck('course_id');

        $courses = Course::whereIn('id', $enrolledCourseIds)
            ->with(['modules' => fn ($q) => $q->where('is_active', true), 'modules.topics'])
            ->get();

        $completedTopicIds = StudentProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->pluck('topic_id')
            ->all();

        $totalModules = 0;
        $completedModules = 0;
        $totalTopics = 0;

        foreach ($courses as $course) {
            foreach ($course->modules as $module) {
                if ($module->topics->isEmpty()) {
                    continue;
                }

                $totalModules++;
                $totalTopics += $module->topics->count();

                if ($module->topics->every(fn ($topic) => in_array($topic->id, $completedTopicIds, true))) {
                    $completedModules++;
                }
            }
        }

        $completedTopicsCount = StudentProgress::where('user_id', $user->id)
            ->whereIn('course_id', $enrolledCourseIds)
            ->where('is_completed', true)
            ->count();

        $overallCompletionPercentage = $totalTopics > 0
            ? round(($completedTopicsCount / $totalTopics) * 100, 2)
            : 0;

        // Latest quiz score across module-level and topic-level quizzes.
        $latestModuleAttempt = TestAttempt::where('user_id', $user->id)
            ->with('moduleTest.course:id,title')
            ->latest('completed_at')
            ->first();

        $latestTopicAttempt = TopicTestAttempt::where('user_id', $user->id)
            ->with('topicTest.course:id,title')
            ->latest('completed_at')
            ->first();

        $latestQuiz = collect([$latestModuleAttempt, $latestTopicAttempt])
            ->filter()
            ->sortByDesc(fn ($attempt) => $attempt->completed_at)
            ->first();

        $latestQuizData = null;
        if ($latestQuiz) {
            $test = $latestQuiz instanceof TestAttempt ? $latestQuiz->moduleTest : $latestQuiz->topicTest;
            $latestQuizData = [
                'percentage' => (float) $latestQuiz->percentage,
                'is_passed' => $latestQuiz->is_passed,
                'course_title' => $test?->course?->title,
                'test_title' => $test?->title,
                'completed_at' => $latestQuiz->completed_at,
            ];
        }

        $totalWatchSeconds = (int) StudentProgress::where('user_id', $user->id)->sum('watch_time_seconds');

        // The streak column only updates on the next activity; compute the
        // displayed value live so a visit with no new activity still shows 0
        // once a full calendar day has already been missed.
        $daysSinceActive = $user->last_active_date
            ? abs(now()->startOfDay()->diffInDays($user->last_active_date->copy()->startOfDay()))
            : null;
        $currentStreak = ($daysSinceActive !== null && $daysSinceActive <= 1) ? $user->current_streak : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'overall_completion_percentage' => $overallCompletionPercentage,
                'modules_completed' => $completedModules,
                'total_modules' => $totalModules,
                'latest_quiz' => $latestQuizData,
                'time_on_platform' => [
                    'total_seconds' => $totalWatchSeconds,
                    'hours' => intdiv($totalWatchSeconds, 3600),
                    'minutes' => intdiv($totalWatchSeconds % 3600, 60),
                ],
                'streak' => [
                    'current' => $currentStreak,
                    'longest' => $user->longest_streak,
                ],
                'inactivity_warning' => $daysSinceActive !== null && $daysSinceActive >= 3,
                'days_since_last_active' => $daysSinceActive,
                'total_courses' => $enrollments->count(),
                'ongoing_courses' => $enrollments->where('status', 'active')->count(),
                'completed_courses' => $enrollments->where('status', 'completed')->count(),
                'certificates_acquired' => $user->certificates()->count(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/course-breakdown",
     *     tags={"Dashboard"},
     *     summary="Per-course and per-module completion breakdown for the authenticated learner's enrolled courses",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Course/module completion breakdown")
     * )
     */
    public function courseBreakdown(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'organisation') {
            return response()->json(['success' => false, 'message' => 'Not available for organisation accounts'], 403);
        }

        $enrollments = $user->enrollments()->get()->keyBy('course_id');

        $courses = Course::whereIn('id', $enrollments->keys())
            ->with(['modules' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'modules.topics'])
            ->get();

        $completedTopicIds = StudentProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->pluck('topic_id')
            ->all();

        $breakdown = $courses->map(function ($course) use ($completedTopicIds, $enrollments) {
            $modules = $course->modules
                ->filter(fn ($module) => $module->topics->isNotEmpty())
                ->map(function ($module) use ($completedTopicIds) {
                    $totalTopics = $module->topics->count();
                    $completedTopics = $module->topics->filter(fn ($t) => in_array($t->id, $completedTopicIds, true))->count();

                    return [
                        'id' => $module->id,
                        'title' => $module->title,
                        'completed' => $totalTopics > 0 && $completedTopics === $totalTopics,
                        'completed_topics' => $completedTopics,
                        'total_topics' => $totalTopics,
                        'percentage' => $totalTopics > 0 ? round(($completedTopics / $totalTopics) * 100, 2) : 0,
                    ];
                })
                ->values();

            return [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'status' => $enrollments[$course->id]->status ?? 'active',
                'completion_percentage' => (float) ($enrollments[$course->id]->progress_percentage ?? 0),
                'modules_completed' => $modules->where('completed', true)->count(),
                'total_modules' => $modules->count(),
                'modules' => $modules,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $breakdown]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/quiz-history",
     *     tags={"Dashboard"},
     *     summary="Full quiz attempt history (module and topic tests) for the authenticated learner, most recent first",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Quiz attempt history")
     * )
     */
    public function quizHistory(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'organisation') {
            return response()->json(['success' => false, 'message' => 'Not available for organisation accounts'], 403);
        }

        $moduleAttempts = TestAttempt::where('user_id', $user->id)
            ->with('moduleTest.course:id,title')
            ->get()
            ->map(fn ($attempt) => [
                'percentage' => (float) $attempt->percentage,
                'is_passed' => $attempt->is_passed,
                'course_id' => $attempt->moduleTest?->course_id,
                'course_title' => $attempt->moduleTest?->course?->title,
                'test_title' => $attempt->moduleTest?->title,
                'completed_at' => $attempt->completed_at,
            ]);

        $topicAttempts = TopicTestAttempt::where('user_id', $user->id)
            ->with('topicTest.course:id,title')
            ->get()
            ->map(fn ($attempt) => [
                'percentage' => (float) $attempt->percentage,
                'is_passed' => $attempt->is_passed,
                'course_id' => $attempt->topicTest?->course_id,
                'course_title' => $attempt->topicTest?->course?->title,
                'test_title' => $attempt->topicTest?->title,
                'completed_at' => $attempt->completed_at,
            ]);

        $history = $moduleAttempts->concat($topicAttempts)
            ->sortByDesc('completed_at')
            ->values();

        return response()->json(['success' => true, 'data' => $history]);
    }
}
