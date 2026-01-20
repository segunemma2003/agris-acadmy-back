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

        // TEST CODE: Allow "20252025" for backend testing (bypasses all validation)
        $isTestCode = $request->enrollment_code === '20252025';
        
        if (!$isTestCode) {
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

            // Validate that the code matches the user's email
            // This ensures codes can only be used by the intended recipient
            if ($enrollmentCode->email && $enrollmentCode->email !== $user->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'This enrollment code is not valid for your account. Please use the code sent to your email address.',
                ], 403);
            }

            // If code has a user_id, validate it matches the authenticated user
            if ($enrollmentCode->user_id && $enrollmentCode->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This enrollment code is not valid for your account.',
                ], 403);
            }
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_code' => $isTestCode ? '20252025' : $enrollmentCode->code,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Mark code as used (skip for test code)
        if (!$isTestCode) {
            $enrollmentCode->update([
                'is_used' => true,
                'user_id' => $user->id,
                'used_at' => now(),
            ]);
        }

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
            ->with(['course.category', 'course.tutor:id,name', 'course.tutors:id,name,avatar', 'course.modules.topics']);

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
                'course.tutor:id,name,avatar',
                'course.tutors:id,name,avatar'
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
                'course.tutor:id,name,avatar',
                'course.tutors:id,name,avatar'
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

        $enrollment->load(['course.modules.topics', 'course.tutor', 'course.tutors']);

        return response()->json([
            'success' => true,
            'data' => $enrollment,
            'message' => 'Enrollment details retrieved successfully'
        ]);
    }

    /**
     * Get user's ongoing courses (alias for ongoingCourses)
     */
    public function myOngoingCourses(Request $request)
    {
        return $this->ongoingCourses($request);
    }

    /**
     * Get user's saved courses
     */
    public function savedCourses(Request $request)
    {
        $user = $request->user();
        
        $savedCourses = $user->savedCourses()
            ->with([
                'category:id,name,slug',
                'tutor:id,name,avatar,bio',
                'tutors:id,name,avatar,bio'
            ])
            ->orderBy('saved_courses.created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $savedCourses,
            'message' => 'Saved courses retrieved successfully'
        ]);
    }

    /**
     * Get courses user has certificates for
     */
    public function certifiedCourses(Request $request)
    {
        $user = $request->user();
        
        $certificates = $user->certificates()
            ->with([
                'course:id,title,image,slug,short_description',
                'course.category:id,name,slug',
                'course.tutor:id,name,avatar',
                'course.tutors:id,name,avatar'
            ])
            ->orderBy('issued_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $certificates,
            'message' => 'Certified courses retrieved successfully'
        ]);
    }
}

