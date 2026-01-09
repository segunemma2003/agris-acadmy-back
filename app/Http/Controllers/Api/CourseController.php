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
            ->with(['category', 'tutor:id,name,avatar', 'tutors:id,name,avatar']);

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Level filter
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Rating filter (minimum rating)
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Duration filters
        if ($request->has('min_duration')) {
            $query->where('duration_minutes', '>=', $request->min_duration);
        }

        if ($request->has('max_duration')) {
            $query->where('duration_minutes', '<=', $request->max_duration);
        }

        // Comprehensive search - searches in title, description, short_description, and tags
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('short_description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhereJsonContains('tags', $searchTerm);
            });
        }

        // Default pagination
        $perPage = $request->get('per_page', 20);
        $courses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'from' => $courses->firstItem(),
                'to' => $courses->lastItem(),
            ],
            'message' => 'Courses retrieved successfully'
        ]);
    }

    public function show(Course $course)
    {
        if (!$course->is_published) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->load([
            'category',
            'tutor:id,name,bio,avatar',
            'tutors:id,name,bio,avatar',
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

    public function recommendedCourses(Request $request)
    {
        $user = $request->user();
        
        // Get courses recommended for the user based on their enrollments
        $enrolledCourseIds = $user->enrollments()->pluck('course_id');
        
        // Get recommended courses from enrolled courses
        $recommendedFromEnrollments = Course::where('is_published', true)
            ->whereNotIn('id', $enrolledCourseIds)
            ->whereHas('recommendedCourses', function ($query) use ($enrolledCourseIds) {
                $query->whereIn('course_id', $enrolledCourseIds);
            })
            ->with(['category:id,name,slug', 'tutor:id,name,avatar', 'tutors:id,name,avatar'])
            ->get();

        // Get featured courses if not enough recommendations
        $featuredCourses = Course::where('is_published', true)
            ->where('is_featured', true)
            ->whereNotIn('id', $enrolledCourseIds)
            ->whereNotIn('id', $recommendedFromEnrollments->pluck('id'))
            ->with(['category:id,name,slug', 'tutor:id,name,avatar', 'tutors:id,name,avatar'])
            ->limit(10 - $recommendedFromEnrollments->count())
            ->get();

        $recommendedCourses = $recommendedFromEnrollments->merge($featuredCourses)
            ->sortByDesc('rating')
            ->sortByDesc('enrollment_count')
            ->take(10)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $recommendedCourses,
            'message' => 'Recommended courses retrieved successfully'
        ]);
    }

    public function modules(Request $request, Course $course)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        // Check if user is enrolled (for protected content)
        $user = $request->user();
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;

        $modules = $course->modules()
            ->where('is_active', true)
            ->with(['topics' => function ($query) use ($isEnrolled) {
                $query->where('is_active', true)
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'modules' => $modules
            ],
            'message' => 'Course modules retrieved successfully'
        ]);
    }

    public function courseInformation(Request $request, Course $course)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'what_you_will_learn' => $course->what_you_will_learn,
                'what_you_will_get' => $course->what_you_will_get,
                'course_information' => $course->course_information,
                'level' => $course->level,
                'duration_minutes' => $course->duration_minutes,
                'language' => $course->language,
                'rating' => $course->rating,
                'rating_count' => $course->rating_count,
                'enrollment_count' => $course->enrollment_count,
                'category' => $course->category,
                'tutor' => $course->tutor()->select('id', 'name', 'bio', 'avatar')->first(),
                'tutors' => $course->tutors()->select('id', 'name', 'bio', 'avatar')->get(),
            ],
            'message' => 'Course information retrieved successfully'
        ]);
    }

    public function diyContent(Request $request, Course $course)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        // Check if user is enrolled
        $user = $request->user();
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to access DIY content'
            ], 403);
        }

        $diyContent = $course->diyContent()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $diyContent,
            'message' => 'Course DIY content retrieved successfully'
        ]);
    }

    public function resources(Request $request, Course $course)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        // Check if user is enrolled
        $user = $request->user();
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;

        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to access resources'
            ], 403);
        }

        $resources = $course->resources()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resources,
            'message' => 'Course resources retrieved successfully'
        ]);
    }
}



