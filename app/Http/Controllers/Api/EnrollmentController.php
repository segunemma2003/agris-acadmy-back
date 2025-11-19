<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentCode;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function enroll(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'enrollment_code' => 'required|string',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($request->course_id);

        // Check if course is published
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'This course is not available for enrollment',
            ], 400);
        }

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this course',
                'data' => [
                    'enrollment' => $existingEnrollment->load('course:id,title,image,slug'),
                ],
            ], 400);
        }

        // Validate enrollment code - REQUIRED
        $enrollmentCode = EnrollmentCode::where('code', $request->enrollment_code)
            ->where('course_id', $course->id)
            ->where('is_used', false)
            ->first();

        if (!$enrollmentCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used enrollment code',
            ], 400);
        }

        // Check if code has expired
        if ($enrollmentCode->expires_at && $enrollmentCode->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Enrollment code has expired',
            ], 400);
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_code' => $enrollmentCode->code,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Mark code as used
        $enrollmentCode->update([
            'is_used' => true,
            'user_id' => $user->id,
            'used_at' => now(),
        ]);

        // Update course enrollment count
        $course->increment('enrollment_count');

        $enrollment->load(['course:id,title,image,slug,short_description,category_id', 'course.category:id,name,slug']);

        return response()->json([
            'success' => true,
            'message' => 'Successfully enrolled in course',
            'data' => [
                'enrollment' => $enrollment,
            ],
        ], 201);
    }

    public function myEnrollments(Request $request)
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['course:id,title,image,slug'])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return response()->json($enrollments);
    }

    public function myCourses(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = $request->user()
            ->enrollments()
            ->with(['course.category', 'course.tutor:id,name', 'course.modules.topics']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $enrollments = $query->orderBy('enrolled_at', 'desc')->get();

        // Calculate progress for each enrollment
        $user = $request->user();
        $enrollments->each(function ($enrollment) use ($user) {
            $totalTopics = $enrollment->course->modules->sum(function ($module) {
                return $module->topics->count();
            });

            $completedTopics = $user
                ->progress()
                ->where('course_id', $enrollment->course_id)
                ->where('is_completed', true)
                ->count();

            $progress = $totalTopics > 0 ? ($completedTopics / $totalTopics) * 100 : 0;

            $enrollment->progress_percentage = round($progress, 2);
            $enrollment->save();
        });

        return response()->json([
            'success' => true,
            'data' => $enrollments,
            'message' => 'Courses retrieved successfully'
        ]);
    }

    public function ongoingCourses(Request $request)
    {
        $user = $request->user();
        
        $enrollments = $user->enrollments()
            ->where('status', 'active')
            ->with([
                'course:id,title,image,slug,short_description,enrollment_count,rating,rating_count,duration_minutes,level,category_id',
                'course.category:id,name,slug',
                'course.tutor:id,name,avatar'
            ])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        // Calculate progress for each enrollment
        $enrollments->each(function ($enrollment) use ($user) {
            $course = $enrollment->course;
            
            // Get total topics
            $totalTopics = $course->modules()->withCount('topics')->get()->sum('topics_count');
            
            // Get completed topics
            $completedTopics = $user->progress()
                ->where('course_id', $course->id)
                ->where('is_completed', true)
                ->count();
            
            // Calculate progress percentage
            $progress = $totalTopics > 0 ? ($completedTopics / $totalTopics) * 100 : 0;
            
            $enrollment->progress_percentage = round($progress, 2);
            $enrollment->course->total_students = $course->enrollment_count;
        });

        return response()->json([
            'success' => true,
            'data' => $enrollments,
            'message' => 'Ongoing courses retrieved successfully'
        ]);
    }

    public function completedCourses(Request $request)
    {
        $user = $request->user();
        
        $enrollments = $user->enrollments()
            ->where('status', 'completed')
            ->with([
                'course:id,title,image,slug,short_description,enrollment_count,rating,rating_count,duration_minutes,level,category_id',
                'course.category:id,name,slug',
                'course.tutor:id,name,avatar'
            ])
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enrollments,
            'message' => 'Completed courses retrieved successfully'
        ]);
    }

    public function show(Request $request, Enrollment $enrollment)
    {
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $enrollment->load(['course.modules.topics', 'course.tutor']);

        return response()->json([
            'success' => true,
            'data' => $enrollment,
            'message' => 'Enrollment details retrieved successfully'
        ]);
    }
}

