<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Course;
use App\Models\CourseComment;
use App\Models\LessonComment;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses/{course}/topics/{topic}/comments",
     *     tags={"Comments"},
     *     summary="Get top-level comments (with nested replies) for a lesson/topic",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lesson comments with replies"),
     *     @OA\Response(response=404, description="Topic not in course")
     * )
     *
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
     * @OA\Post(
     *     path="/api/courses/{course}/topics/{topic}/comments",
     *     tags={"Comments"},
     *     summary="Add a comment (or reply) to a lesson/topic",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"comment"}, @OA\Property(property="comment", type="string", maxLength=2000), @OA\Property(property="parent_id", type="integer", nullable=true, description="ID of parent comment for replies"))),
     *     @OA\Response(response=201, description="Comment created"),
     *     @OA\Response(response=404, description="Topic not in course")
     * )
     *
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
     * @OA\Put(
     *     path="/api/courses/{course}/topics/{topic}/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Update a lesson comment (owner only)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"comment"}, @OA\Property(property="comment", type="string", maxLength=2000))),
     *     @OA\Response(response=200, description="Comment updated"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     *
     * @OA\Delete(
     *     path="/api/courses/{course}/topics/{topic}/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Delete a lesson comment and all its replies (owner only)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Comment deleted"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     *
     * @OA\Get(
     *     path="/api/courses/{course}/comments",
     *     tags={"Comments"},
     *     summary="Get top-level comments (with replies) for a whole course",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Course comments")
     * )
     *
     * @OA\Post(
     *     path="/api/courses/{course}/comments",
     *     tags={"Comments"},
     *     summary="Add a comment (or reply) to a course",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"comment"}, @OA\Property(property="comment", type="string", maxLength=2000), @OA\Property(property="parent_id", type="integer", nullable=true))),
     *     @OA\Response(response=201, description="Comment created")
     * )
     *
     * @OA\Put(
     *     path="/api/courses/{course}/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Update a course comment (owner only)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"comment"}, @OA\Property(property="comment", type="string"))),
     *     @OA\Response(response=200, description="Comment updated"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     *
     * @OA\Delete(
     *     path="/api/courses/{course}/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Delete a course comment and all its replies (owner only)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Comment deleted"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     *
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

    /**
     * Update lesson comment
     */
    public function updateLessonComment(Request $request, Course $course, Topic $topic, $commentId)
    {
        $user = $request->user();
        
        $comment = LessonComment::where('id', $commentId)
            ->where('topic_id', $topic->id)
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        
        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);
        
        $comment->update([
            'comment' => $request->comment,
        ]);
        
        // Clear cache
        Cache::forget("lesson_{$topic->id}_comments");
        
        return response()->json([
            'success' => true,
            'data' => $comment->load('user:id,name,avatar'),
            'message' => 'Comment updated successfully'
        ]);
    }

    /**
     * Delete lesson comment
     */
    public function deleteLessonComment(Request $request, Course $course, Topic $topic, $commentId)
    {
        $user = $request->user();
        
        $comment = LessonComment::where('id', $commentId)
            ->where('topic_id', $topic->id)
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        
        // Delete replies first
        LessonComment::where('parent_id', $comment->id)->delete();
        
        $comment->delete();
        
        // Clear cache
        Cache::forget("lesson_{$topic->id}_comments");
        
        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Update course comment
     */
    public function updateCourseComment(Request $request, Course $course, $commentId)
    {
        $user = $request->user();
        
        $comment = CourseComment::where('id', $commentId)
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        
        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);
        
        $comment->update([
            'comment' => $request->comment,
        ]);
        
        // Clear cache
        Cache::forget("course_{$course->id}_comments");
        
        return response()->json([
            'success' => true,
            'data' => $comment->load('user:id,name,avatar'),
            'message' => 'Comment updated successfully'
        ]);
    }

    /**
     * Delete course comment
     */
    public function deleteCourseComment(Request $request, Course $course, $commentId)
    {
        $user = $request->user();
        
        $comment = CourseComment::where('id', $commentId)
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        
        // Delete replies first
        CourseComment::where('parent_id', $comment->id)->delete();
        
        $comment->delete();
        
        // Clear cache
        Cache::forget("course_{$course->id}_comments");
        
        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }
}
