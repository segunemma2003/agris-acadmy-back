<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses/{course}/modules/{module}",
     *     tags={"Modules"},
     *     summary="Get module details with topics, tests, and per-topic progress",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="module", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Module details with topics and tests"),
     *     @OA\Response(response=403, description="Not enrolled, or module locked until the previous module's quiz is passed", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="locked", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="You need 80% to unlock the next module. Your score: 45%."),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="required_percentage", type="number", example=80),
     *             @OA\Property(property="best_percentage", type="number", example=45),
     *             @OA\Property(property="previous_module", type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="title", type="string", example="Module 3: Soil Health")
     *             )
     *         )
     *     )),
     *     @OA\Response(response=404, description="Course or module not found")
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
                'message' => 'You must be enrolled in this course to access module details'
            ], 403);
        }

        // Server-side enforcement: this module is gated behind passing the
        // previous module's quiz. This cannot be bypassed by hitting the API
        // directly — the check happens here regardless of what the frontend shows.
        $lockStatus = $module->lockStatusFor($user);
        if ($lockStatus['locked']) {
            return response()->json([
                'success' => false,
                'locked' => true,
                'message' => "You need {$lockStatus['required_percentage']}% to unlock the next module. Your score: {$lockStatus['best_percentage']}%.",
                'data' => $lockStatus,
            ], 403);
        }

        $module->load([
            'topics' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order');
            },
            'topics.resources',
        ]);
        
        // Load test (hasMany relationship - get first active test)
        $module->test = $module->test()
            ->where('is_active', true)
            ->with(['questions' => function ($q) {
                $q->orderBy('sort_order');
            }])
            ->first();

        // Load topic tests for each topic
        if ($module->topics) {
            $module->topics->each(function ($topic) {
                $topic->test = $topic->test()
                    ->where('is_active', true)
                    ->with(['questions' => function ($q) {
                        $q->orderBy('sort_order');
                    }])
                    ->first();
            });
        }

        // Get user's progress for topics in this module
        if ($user) {
            $topicIds = $module->topics->pluck('id');
            $progress = $user->progress()
                ->whereIn('topic_id', $topicIds)
                ->get()
                ->keyBy('topic_id');

            $module->topics->each(function ($topic) use ($progress) {
                $topicProgress = $progress->get($topic->id);
                $topic->is_completed = $topicProgress ? $topicProgress->is_completed : false;
                $topic->completion_percentage = $topicProgress ? $topicProgress->completion_percentage : 0;
            });
        }

        // Serve Hausa content when the learner has chosen that locale, falling
        // back to English (with a needs_translation flag) if not yet translated.
        \App\Services\ContentLocalizer::apply($module, $user, ['title', 'description']);
        if ($module->test) {
            \App\Services\ContentLocalizer::applyToCollection($module->test->questions, $user, ['question', 'options', 'explanation']);
        }
        $module->topics->each(function ($topic) use ($user) {
            \App\Services\ContentLocalizer::apply($topic, $user, ['title', 'write_up']);
            if ($topic->test) {
                \App\Services\ContentLocalizer::applyToCollection($topic->test->questions, $user, ['question', 'options', 'explanation']);
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'module' => $module,
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                ]
            ],
            'message' => 'Module details retrieved successfully'
        ]);
    }
}

