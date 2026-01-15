<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SavedCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SavedCourseController extends Controller
{
    /**
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
