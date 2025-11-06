<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::where('is_published', true)
            ->with(['category', 'tutor:id,name,avatar']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($courses);
    }

    public function show(Course $course)
    {
        if (!$course->is_published) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->load([
            'category',
            'tutor:id,name,bio,avatar',
            'modules.topics',
            'resources',
            'reviews.user:id,name',
            'vrContent',
            'diyContent',
            'recommendations.recommendedCourse:id,title,image,slug',
        ]);

        // Get recommended courses (limit to 2)
        $recommendedCourses = $course->recommendations()
            ->with('recommendedCourse:id,title,image,slug,short_description')
            ->limit(2)
            ->get()
            ->pluck('recommendedCourse');

        $course->recommended_courses = $recommendedCourses;

        return response()->json($course);
    }
}



