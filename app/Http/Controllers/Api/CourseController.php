<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Course::where('is_published', true)
            ->with(['category', 'tutor:id,name,avatar,bio', 'tutors:id,name,avatar,bio']);

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

        // Add enrollment status and format image URLs
        $enrolledCourseIds = $user ? $user->enrollments()->pluck('course_id') : collect();
        
        $formattedCourses = $courses->getCollection()->map(function ($course) use ($enrolledCourseIds) {
            $course->is_enrolled = $enrolledCourseIds->contains($course->id);
            $course->image_url = $course->image ? (str_starts_with($course->image, 'http') ? $course->image : asset('storage/' . $course->image)) : null;
            return $course;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedCourses,
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

    public function show(Request $request, Course $course)
    {
        if (!$course->is_published) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $user = $request->user();

        $course->load([
            'category',
            'tutor:id,name,bio,avatar',
            'tutors:id,name,bio,avatar',
            'modules' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['topics' => function ($q) {
                        $q->where('is_active', true)
                            ->orderBy('sort_order');
                    }]);
            },
            'resources',
            'reviews' => function ($query) {
                $query->with('user:id,name,avatar')
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            },
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

        // Add enrollment status
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;
        $course->is_enrolled = $isEnrolled;
        
        // Format image URL
        $course->image_url = $course->image ? (str_starts_with($course->image, 'http') ? $course->image : asset('storage/' . $course->image)) : null;
        
        // Calculate lessons count
        $course->lessons_count = $course->modules->sum(function ($module) {
            return $module->topics->count();
        });

        return response()->json([
            'success' => true,
            'data' => $course,
            'message' => 'Course details retrieved successfully'
        ]);
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

        $user = $request->user();
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;
        
        // Calculate lessons count
        $lessonsCount = $course->modules()->withCount('topics')->get()->sum('topics_count');
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'about' => $course->about,
                'requirements' => $course->requirements,
                'what_to_expect' => $course->what_to_expect,
                'what_you_will_learn' => $course->what_you_will_learn,
                'what_you_will_get' => $course->what_you_will_get,
                'course_information' => $course->course_information,
                'image_url' => $course->image ? (str_starts_with($course->image, 'http') ? $course->image : asset('storage/' . $course->image)) : null,
                'preview_video_url' => $course->preview_video_url,
                'level' => $course->level,
                'duration_minutes' => $course->duration_minutes,
                'language' => $course->language,
                'rating' => $course->rating,
                'rating_count' => $course->rating_count,
                'enrollment_count' => $course->enrollment_count,
                'lessons_count' => $lessonsCount,
                'certificate_included' => $course->certificate_included ?? false,
                'category' => $course->category,
                'main_instructor' => $course->tutor ? [
                    'id' => $course->tutor->id,
                    'name' => $course->tutor->name,
                    'bio' => $course->tutor->bio,
                    'avatar' => $course->tutor->avatar,
                ] : null,
                'instructors' => $course->tutors->map(function ($tutor) {
                    return [
                        'id' => $tutor->id,
                        'name' => $tutor->name,
                        'bio' => $tutor->bio,
                        'avatar' => $tutor->avatar,
                    ];
                }),
                'is_enrolled' => $isEnrolled,
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



