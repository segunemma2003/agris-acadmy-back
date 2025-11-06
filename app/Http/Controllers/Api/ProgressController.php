<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudentProgress;
use App\Models\Topic;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
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
            'course_id' => $course->id,
            'overall_progress' => round($overallProgress, 2),
            'total_topics' => $totalTopics,
            'completed_topics' => $completedTopics,
            'topics' => $topicsWithProgress,
        ]);
    }

    public function complete(Request $request, Topic $topic)
    {
        $user = $request->user();
        $course = $topic->module->course;

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
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
            'message' => 'Topic marked as completed',
            'progress' => $progress,
        ]);
    }

    public function update(Request $request, StudentProgress $progress)
    {
        if ($progress->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'watch_time_seconds' => 'nullable|integer',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $progress->update([
            'watch_time_seconds' => $request->watch_time_seconds ?? $progress->watch_time_seconds,
            'completion_percentage' => $request->completion_percentage ?? $progress->completion_percentage,
            'last_accessed_at' => now(),
        ]);

        if ($progress->completion_percentage >= 100) {
            $progress->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        // Update enrollment progress
        $this->updateEnrollmentProgress($request->user(), $progress->course);

        return response()->json($progress);
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

            // Mark as completed if 100%
            if ($progressPercentage >= 100 && $enrollment->status !== 'completed') {
                $enrollment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        }
    }
}

