<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
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
                'message' => 'You must be enrolled in this course to access module details'
            ], 403);
        }

        $module->load([
            'topics' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order');
            }
        ]);
        
        // Load test (hasMany relationship - get first active test)
        $module->test = $module->test()
            ->where('is_active', true)
            ->with(['questions' => function ($q) {
                $q->orderBy('sort_order');
            }])
            ->first();

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

