<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     tags={"Categories"},
     *     summary="List all active categories",
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Categories list", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="array", @OA\Items(type="object"))))
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/categories/{category}",
     *     tags={"Categories"},
     *     summary="Get a single category with its courses",
     *     @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/categories-with-courses",
     *     tags={"Categories"},
     *     summary="List all categories with their published courses nested inside",
     *     @OA\Response(response=200, description="Categories with courses")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/featured-courses-public",
     *     tags={"Categories"},
     *     summary="Get featured courses grouped by category (public)",
     *     @OA\Response(response=200, description="Featured courses per category")
     * )
     */
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
     * @OA\Get(
     *     path="/api/categories/{category}/courses",
     *     tags={"Categories"},
     *     summary="List courses belonging to a category with filters",
     *     @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="level", in="query", required=false, @OA\Schema(type="string", enum={"beginner","intermediate","advanced"})),
     *     @OA\Parameter(name="min_rating", in="query", required=false, @OA\Schema(type="number")),
     *     @OA\Parameter(name="min_duration", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="max_duration", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Paginated courses in category"),
     *     @OA\Response(response=404, description="Category not found")
     * )
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



