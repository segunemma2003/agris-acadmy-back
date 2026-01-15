<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseComment;
use App\Models\LessonComment;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{
    /**
     * Get comments for a lesson
     */
    public function lessonComments(Request $request, Course $course, Topic $topic)
    {
        $user = $request->user();
        
        // Check if topic belongs to course
        if ($topic->module->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found in this course'
            ], 404);
        }
        
        $cacheKey = "lesson_{$topic->id}_comments";
        
        $comments = Cache::remember($cacheKey, 300, function () use ($topic) {
            return LessonComment::where('topic_id', $topic->id)
                ->whereNull('parent_id')
                ->with(['user:id,name,avatar', 'replies.user:id,name,avatar'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $comments,
            'message' => 'Lesson comments retrieved successfully'
        ]);
    }

    /**
     * Add comment to a lesson
     */
    public function addLessonComment(Request $request, Course $course, Topic $topic)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:lesson_comments,id',
        ]);
        
        $user = $request->user();
        
        // Check if topic belongs to course
        if ($topic->module->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found in this course'
            ], 404);
        }
        
        $comment = LessonComment::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'comment' => $request->comment,
            'parent_id' => $request->parent_id,
        ]);
        
        // Clear cache
        Cache::forget("lesson_{$topic->id}_comments");
        
        return response()->json([
            'success' => true,
            'data' => $comment->load('user:id,name,avatar'),
            'message' => 'Comment added successfully'
        ], 201);
    }

    /**
     * Get comments for a course
     */
    public function courseComments(Request $request, Course $course)
    {
        $cacheKey = "course_{$course->id}_comments";
        
        $comments = Cache::remember($cacheKey, 300, function () use ($course) {
            return CourseComment::where('course_id', $course->id)
                ->whereNull('parent_id')
                ->with(['user:id,name,avatar', 'replies.user:id,name,avatar'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $comments,
            'message' => 'Course comments retrieved successfully'
        ]);
    }

    /**
     * Add comment to a course
     */
    public function addCourseComment(Request $request, Course $course)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:course_comments,id',
        ]);
        
        $user = $request->user();
        
        $comment = CourseComment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'comment' => $request->comment,
            'parent_id' => $request->parent_id,
        ]);
        
        // Clear cache
        Cache::forget("course_{$course->id}_comments");
        
        return response()->json([
            'success' => true,
            'data' => $comment->load('user:id,name,avatar'),
            'message' => 'Comment added successfully'
        ], 201);
    }
}
