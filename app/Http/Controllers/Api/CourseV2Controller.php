<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CourseV2Controller extends Controller
{
    /**
     * Get daily recommended courses for authenticated user
     * Cached per user for 5 minutes
     */
    public function dailyRecommended(Request $request)
    {
        $user = $request->user();
        
        $cacheKey = "user_{$user->id}_daily_recommended_courses";
        
        $courses = Cache::remember($cacheKey, 300, function () use ($user) {
            $enrolledCourseIds = $user->enrollments()->pluck('course_id');
            
            // Get courses based on user's enrolled categories
            $userCategories = $user->enrollments()
                ->with('course.category')
                ->get()
                ->pluck('course.category_id')
                ->unique()
                ->filter();
            
            $recommended = Course::where('is_published', true)
                ->whereNotIn('id', $enrolledCourseIds)
                ->where(function ($query) use ($userCategories) {
                    if ($userCategories->isNotEmpty()) {
                        $query->whereIn('category_id', $userCategories);
                    }
                })
                ->with(['category:id,name,slug', 'tutor:id,name,avatar,bio', 'tutors:id,name,avatar,bio'])
                ->orderBy('rating', 'desc')
                ->orderBy('enrollment_count', 'desc')
                ->limit(10)
                ->get();
            
            // If not enough, add featured courses
            if ($recommended->count() < 10) {
                $featured = Course::where('is_published', true)
                    ->where('is_featured', true)
                    ->whereNotIn('id', $enrolledCourseIds)
                    ->whereNotIn('id', $recommended->pluck('id'))
                    ->with(['category:id,name,slug', 'tutor:id,name,avatar,bio', 'tutors:id,name,avatar,bio'])
                    ->limit(10 - $recommended->count())
                    ->get();
                
                $recommended = $recommended->merge($featured);
            }
            
            return $recommended->take(10)->map(function ($course) use ($user) {
                return $this->formatCourseWithEnrollment($course, $user);
            });
        });
        
        return response()->json([
            'success' => true,
            'data' => $courses,
            'message' => 'Daily recommended courses retrieved successfully'
        ]);
    }

    /**
     * Get latest 10 courses
     */
    public function latest(Request $request)
    {
        $user = $request->user();
        
        $cacheKey = 'latest_10_courses';
        
        $courses = Cache::remember($cacheKey, 300, function () {
            return Course::where('is_published', true)
                ->with(['category:id,name,slug', 'tutor:id,name,avatar,bio', 'tutors:id,name,avatar,bio'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });
        
        $formatted = $courses->map(function ($course) use ($user) {
            return $this->formatCourseWithEnrollment($course, $user);
        });
        
        return response()->json([
            'success' => true,
            'data' => $formatted,
            'message' => 'Latest courses retrieved successfully'
        ]);
    }

    /**
     * Get featured courses with enrollment status
     */
    public function featured(Request $request)
    {
        $user = $request->user();
        
        $cacheKey = 'featured_courses';
        
        $courses = Cache::remember($cacheKey, 300, function () {
            return Course::where('is_published', true)
                ->where('is_featured', true)
                ->with(['category:id,name,slug', 'tutor:id,name,avatar,bio', 'tutors:id,name,avatar,bio'])
                ->orderBy('rating', 'desc')
                ->orderBy('enrollment_count', 'desc')
                ->get();
        });
        
        $formatted = $courses->map(function ($course) use ($user) {
            return $this->formatCourseWithEnrollment($course, $user);
        });
        
        return response()->json([
            'success' => true,
            'data' => $formatted,
            'message' => 'Featured courses retrieved successfully'
        ]);
    }

    /**
     * Get course details with full information
     */
    public function show(Request $request, Course $course)
    {
        if (!$course->is_published) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $user = $request->user();
        
        $cacheKey = "course_{$course->id}_details";
        
        $courseData = Cache::remember($cacheKey, 300, function () use ($course) {
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
                'reviews' => function ($query) {
                    $query->with('user:id,name,avatar')
                        ->orderBy('created_at', 'desc')
                        ->limit(10);
                },
            ]);
            
            // Calculate total lessons
            $lessonsCount = $course->modules->sum(function ($module) {
                return $module->topics->count();
            });
            $course->lessons_count = $lessonsCount;
            
            return $course;
        });
        
        // Add enrollment status
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;
        $courseData->is_enrolled = $isEnrolled;
        
        // Add completion percentage if enrolled
        if ($isEnrolled) {
            $completion = $this->getCourseCompletion($user, $course);
            $courseData->completion_percentage = $completion;
        }
        
        return response()->json([
            'success' => true,
            'data' => $courseData,
            'message' => 'Course details retrieved successfully'
        ]);
    }

    /**
     * Get course curriculum with modules, lessons, assignments, VR, DIY, tests
     */
    public function curriculum(Request $request, Course $course)
    {
        $user = $request->user();
        
        // Check enrollment
        $isEnrolled = $user->enrollments()->where('course_id', $course->id)->exists();
        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to view curriculum'
            ], 403);
        }
        
        $cacheKey = "course_{$course->id}_curriculum";
        
        $curriculum = Cache::remember($cacheKey, 600, function () use ($course) {
            $modules = $course->modules()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with([
                    'topics' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('sort_order');
                    },
                    'test' => function ($query) {
                        $query->where('is_active', true)
                            ->with('questions');
                    },
                    'assignments' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('sort_order');
                    },
                ])
                ->get();
            
            // Add module-level data
            $modules->each(function ($module) use ($course) {
                // Module duration (sum of lesson durations)
                $module->duration_minutes = $module->topics->sum('duration_minutes');
                $module->lessons_count = $module->topics->count();
                
                // Add VR and DIY for module
                $module->vr_experience = $course->vrContent()
                    ->where('module_id', $module->id)
                    ->where('is_active', true)
                    ->first();
                
                $module->diy_instructions = $course->diyContent()
                    ->where('module_id', $module->id)
                    ->where('is_active', true)
                    ->get();
            });
            
            return $modules;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'modules' => $curriculum
            ],
            'message' => 'Course curriculum retrieved successfully'
        ]);
    }

    /**
     * Get course completion percentage for authenticated user
     */
    public function completion(Request $request, Course $course)
    {
        $user = $request->user();
        
        $isEnrolled = $user->enrollments()->where('course_id', $course->id)->exists();
        if (!$isEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course'
            ], 403);
        }
        
        $completion = $this->getCourseCompletion($user, $course);
        
        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $course->id,
                'completion_percentage' => $completion
            ],
            'message' => 'Course completion retrieved successfully'
        ]);
    }

    /**
     * Format course with enrollment status
     */
    private function formatCourseWithEnrollment($course, $user)
    {
        $isEnrolled = $user ? $user->enrollments()->where('course_id', $course->id)->exists() : false;
        
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'short_description' => $course->short_description,
            'image_url' => $course->image ? (str_starts_with($course->image, 'http') ? $course->image : asset('storage/' . $course->image)) : null,
            'preview_video_url' => $course->preview_video_url,
            'rating' => $course->rating,
            'rating_count' => $course->rating_count,
            'enrollment_count' => $course->enrollment_count,
            'duration_minutes' => $course->duration_minutes,
            'level' => $course->level,
            'lessons_count' => $course->lessons_count ?? 0,
            'certificate_included' => $course->certificate_included ?? false,
            'category' => $course->category,
            'main_instructor' => $course->tutor,
            'instructors' => $course->tutors,
            'is_enrolled' => $isEnrolled,
        ];
    }

    /**
     * Calculate course completion percentage
     */
    private function getCourseCompletion($user, $course)
    {
        $totalTopics = $course->modules()
            ->withCount('topics')
            ->get()
            ->sum('topics_count');
        
        if ($totalTopics == 0) {
            return 0;
        }
        
        $completedTopics = $user->progress()
            ->where('course_id', $course->id)
            ->where('is_completed', true)
            ->count();
        
        return round(($completedTopics / $totalTopics) * 100, 2);
    }

    /**
     * Get course reviews
     */
    public function reviews(Request $request, Course $course)
    {
        $perPage = $request->get('per_page', 10);
        
        $cacheKey = "course_{$course->id}_reviews_page_{$perPage}";
        
        $reviews = Cache::remember($cacheKey, 300, function () use ($course, $perPage) {
            return $course->reviews()
                ->with('user:id,name,avatar')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
        
        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'message' => 'Course reviews retrieved successfully'
        ]);
    }
}
