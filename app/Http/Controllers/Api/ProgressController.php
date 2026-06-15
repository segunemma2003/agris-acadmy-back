<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Course;
use App\Models\StudentProgress;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses/{course}/progress",
     *     tags={"Progress"},
     *     summary="Get topic-level progress for a course (enrollment required)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Course progress",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="course_id", type="integer"),
     *                 @OA\Property(property="overall_progress", type="number"),
     *                 @OA\Property(property="total_topics", type="integer"),
     *                 @OA\Property(property="completed_topics", type="integer"),
     *                 @OA\Property(property="topics", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Not enrolled")
     * )
     */
    public function show(Request $request, Course $course)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        // Get all topics for the course
        $topics = $course->modules()
            ->with('topics')
            ->get()
            ->pluck('topics')
            ->flatten();

        // Get progress for each topic
        $progress = $user->progress()
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('topic_id');

        $topicsWithProgress = $topics->map(function ($topic) use ($progress) {
            $topicProgress = $progress->get($topic->id);
            return [
                'id' => $topic->id,
                'title' => $topic->title,
                'module_id' => $topic->module_id,
                'is_completed' => $topicProgress ? $topicProgress->is_completed : false,
                'completion_percentage' => $topicProgress ? $topicProgress->completion_percentage : 0,
                'last_accessed_at' => $topicProgress ? $topicProgress->last_accessed_at : null,
            ];
        });

        // Calculate overall progress
        $totalTopics = $topics->count();
        $completedTopics = $progress->where('is_completed', true)->count();
        $overallProgress = $totalTopics > 0 ? ($completedTopics / $totalTopics) * 100 : 0;

        // Update enrollment progress
        $enrollment->update(['progress_percentage' => round($overallProgress, 2)]);

        return response()->json([
            'success' => true,
            'data' => [
            'course_id' => $course->id,
            'overall_progress' => round($overallProgress, 2),
            'total_topics' => $totalTopics,
            'completed_topics' => $completedTopics,
            'topics' => $topicsWithProgress,
            ],
            'message' => 'Course progress retrieved successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/topics/{topic}/complete",
     *     tags={"Progress"},
     *     summary="Mark a topic/lesson as 100% complete",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Topic marked complete"),
     *     @OA\Response(response=403, description="Not enrolled in the course that owns this topic")
     * )
     */
    public function complete(Request $request, Topic $topic)
    {
        $user = $request->user();
        $course = $topic->module->course;

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Create or update progress
        $progress = StudentProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
            ],
            [
                'course_id' => $course->id,
                'is_completed' => true,
                'completion_percentage' => 100,
                'completed_at' => now(),
                'last_accessed_at' => now(),
            ]
        );

        // Update enrollment progress
        $this->updateEnrollmentProgress($user, $course);

        return response()->json([
            'success' => true,
            'message' => 'Topic marked as completed',
            'data' => [
            'progress' => $progress,
            ],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/progress/{studentProgress}",
     *     tags={"Progress"},
     *     summary="Update watch time and completion percentage for a progress record",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="studentProgress", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="watch_time_seconds", type="integer", nullable=true),
     *             @OA\Property(property="completion_percentage", type="number", minimum=0, maximum=100, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Progress updated"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function update(Request $request, StudentProgress $studentProgress)
    {
        if ($studentProgress->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'watch_time_seconds' => 'nullable|integer',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $studentProgress->update([
            'watch_time_seconds' => $request->watch_time_seconds ?? $studentProgress->watch_time_seconds,
            'completion_percentage' => $request->completion_percentage ?? $studentProgress->completion_percentage,
            'last_accessed_at' => now(),
        ]);

        if ($studentProgress->completion_percentage >= 100) {
            $studentProgress->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        // Update enrollment progress
        $this->updateEnrollmentProgress($request->user(), $studentProgress->course);

        return response()->json([
            'success' => true,
            'data' => $studentProgress,
            'message' => 'Progress updated successfully'
        ]);
    }

    public function completeQuiz(Request $request, Course $course, $moduleId, $testId)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Get the test attempt
        $testAttempt = \App\Models\TestAttempt::where('id', $testId)
            ->where('user_id', $user->id)
            ->first();

        if (!$testAttempt) {
            return response()->json([
                'success' => false,
                'message' => 'Test attempt not found'
            ], 404);
        }

        // Mark quiz as completed in progress (if needed)
        // The test attempt already tracks completion, so we just return success
        return response()->json([
            'success' => true,
            'data' => [
                'test_attempt' => $testAttempt,
                'is_passed' => $testAttempt->is_passed,
                'score' => $testAttempt->score,
                'percentage' => $testAttempt->percentage,
            ],
            'message' => 'Quiz marked as completed'
        ]);
    }

    private function updateEnrollmentProgress($user, $course)
    {
        $totalTopics = $course->modules()->withCount('topics')->get()->sum('topics_count');
        $completedTopics = $user->progress()
            ->where('course_id', $course->id)
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalTopics > 0 ? ($completedTopics / $totalTopics) * 100 : 0;

        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment) {
            $enrollment->update(['progress_percentage' => round($progressPercentage, 2)]);

            // Mark as completed if 100% (no automatic email; admin sends "course finished" to all when ready)
            if ($progressPercentage >= 100 && $enrollment->status !== 'completed') {
                $enrollment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        }
    }

    /**
     * @OA\Post(
     *     path="/api/courses/{course}/modules/{module}/tests/{test}/complete-quiz",
     *     tags={"Progress"},
     *     summary="Mark a module quiz as completed and retrieve the test attempt result",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="module", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="test", in="path", required=true, @OA\Schema(type="integer", description="TestAttempt ID")),
     *     @OA\Response(response=200, description="Quiz completion result"),
     *     @OA\Response(response=403, description="Not enrolled"),
     *     @OA\Response(response=404, description="Test attempt not found")
     * )
     *
     * @OA\Post(
     *     path="/api/progress/sync",
     *     tags={"Progress"},
     *     summary="Sync a single topic progress record (mobile app legacy endpoint)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","topic_id"},
     *             @OA\Property(property="course_id", type="integer"),
     *             @OA\Property(property="topic_id", type="integer"),
     *             @OA\Property(property="watch_time_seconds", type="integer", nullable=true),
     *             @OA\Property(property="completion_percentage", type="number", nullable=true),
     *             @OA\Property(property="is_completed", type="boolean", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Progress synced"),
     *     @OA\Response(response=403, description="Not enrolled")
     * )
     *
     * Sync course progress (legacy endpoint for mobile app)
     */
    public function sync(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'topic_id' => 'required|exists:topics,id',
            'watch_time_seconds' => 'nullable|integer',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
            'is_completed' => 'nullable|boolean',
        ]);

        $course = Course::findOrFail($request->course_id);
        $topic = Topic::findOrFail($request->topic_id);

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Create or update progress
        $progress = StudentProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
            ],
            [
                'course_id' => $course->id,
                'watch_time_seconds' => $request->watch_time_seconds ?? 0,
                'completion_percentage' => $request->completion_percentage ?? 0,
                'is_completed' => $request->is_completed ?? false,
                'last_accessed_at' => now(),
                'completed_at' => ($request->is_completed ?? false) ? now() : null,
            ]
        );

        // Update enrollment progress
        $this->updateEnrollmentProgress($user, $course);

        return response()->json([
            'success' => true,
            'message' => 'Progress synced successfully',
            'data' => [
                'progress' => $progress,
            ],
        ]);
    }
}

