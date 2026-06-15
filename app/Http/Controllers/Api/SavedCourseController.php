<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Course;
use App\Models\SavedCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SavedCourseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/saved-courses",
     *     tags={"Saved Courses"},
     *     summary="Get all courses saved/bookmarked by the authenticated user",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Saved courses list")
     * )
     *
     * Get user's saved courses
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $cacheKey = "user_{$user->id}_saved_courses";
        
        $savedCourses = Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->savedCourses()
                ->with(['category:id,name,slug', 'tutor:id,name,avatar', 'tutors:id,name,avatar'])
                ->orderBy('saved_courses.created_at', 'desc')
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $savedCourses,
            'message' => 'Saved courses retrieved successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/courses/{course}/save",
     *     tags={"Saved Courses"},
     *     summary="Bookmark/save a course",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Course saved"),
     *     @OA\Response(response=400, description="Already saved")
     * )
     *
     * Save a course
     */
    public function store(Request $request, Course $course)
    {
        $user = $request->user();
        
        // Check if already saved
        $exists = SavedCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
        
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Course is already saved'
            ], 400);
        }
        
        SavedCourse::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        
        // Clear cache
        Cache::forget("user_{$user->id}_saved_courses");
        
        return response()->json([
            'success' => true,
            'message' => 'Course saved successfully'
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/courses/{course}/unsave",
     *     tags={"Saved Courses"},
     *     summary="Remove a course from bookmarks",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Course unsaved")
     * )
     *
     * Unsave a course
     */
    public function destroy(Request $request, Course $course)
    {
        $user = $request->user();
        
        SavedCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->delete();
        
        // Clear cache
        Cache::forget("user_{$user->id}_saved_courses");
        
        return response()->json([
            'success' => true,
            'message' => 'Course unsaved successfully'
        ]);
    }
}
