<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::where('is_active', true);

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $categories = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ]);
    }

    public function show(Category $category)
    {
        if (!$category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $category->load(['courses' => function ($query) {
            $query->where('is_published', true)
                ->with(['tutor:id,name,avatar'])
                ->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category details retrieved successfully'
        ]);
    }

    public function withCourses()
    {
        $categories = Category::where('is_active', true)
            ->with(['courses' => function ($query) {
                $query->where('is_published', true)
                    ->with(['tutor:id,name,avatar', 'tutors:id,name,avatar'])
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories with courses retrieved successfully'
        ]);
    }

    public function featuredCourses()
    {
        $categories = Category::where('is_active', true)
            ->with(['courses' => function ($query) {
                $query->where('is_published', true)
                    ->where('is_featured', true)
                    ->with(['tutor:id,name,avatar', 'tutors:id,name,avatar'])
                    ->orderBy('created_at', 'desc');
            }])
            ->whereHas('courses', function ($query) {
                $query->where('is_published', true)
                    ->where('is_featured', true);
            })
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Featured courses per category retrieved successfully'
        ]);
    }

    /**
     * Get courses by category
     */
    public function courses(Request $request, Category $category)
    {
        $user = $request->user();
        
        if (!$category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $query = $category->courses()
            ->where('is_published', true)
            ->with(['tutor:id,name,avatar,bio', 'tutors:id,name,avatar,bio']);

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('short_description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereJsonContains('tags', $searchTerm);
            });
        }

        // Level filter
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Rating filter
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

        $perPage = $request->get('per_page', 20);
        $courses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add enrollment status for authenticated users
        // Query enrollments directly from database to avoid relationship cache issues
        if ($user) {
            $enrolledCourseIds = \App\Models\Enrollment::where('user_id', $user->id)->pluck('course_id');
            $courses->getCollection()->transform(function ($course) use ($enrolledCourseIds) {
                $course->is_enrolled = $enrolledCourseIds->contains($course->id);
                return $course;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
            'message' => 'Category courses retrieved successfully'
        ]);
    }
}



