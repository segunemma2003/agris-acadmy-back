<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EnrollmentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/enroll",
     *     tags={"Enrollments"},
     *     summary="Enroll in a course using an enrollment code",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","enrollment_code"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="enrollment_code", type="string", example="ABC123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Enrolled successfully", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object", @OA\Property(property="enrollment", ref="#/components/schemas/Enrollment")))),
     *     @OA\Response(response=400, description="Already enrolled or invalid code", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Code not valid for this account, or the account is an organisation (organisations cannot enroll)", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function enroll(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'enrollment_code' => 'required|string',
        ]);

        $user = $request->user();

        if ($user->role === 'organisation') {
            return response()->json([
                'success' => false,
                'message' => 'Organisation accounts cannot enroll in courses. Please register a student account to enroll.',
            ], 403);
        }

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
            // COMMENTED OUT: CSV email verification disabled - allowing any user to use any code
            // if ($enrollmentCode->email && $enrollmentCode->email !== $user->email) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'This enrollment code is not valid for your account. Please use the code sent to your email address.',
            //     ], 403);
            // }

            // If code has a user_id, validate it matches the authenticated user
            if ($enrollmentCode->user_id && $enrollmentCode->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This enrollment code is not valid for your account.',
                ], 403);
            }
        }

        // For test code, generate a unique enrollment_code to avoid unique constraint violation
        $finalEnrollmentCode = $isTestCode 
            ? '20252025-' . $user->id . '-' . $course->id . '-' . time() 
            : $enrollmentCode->code;

        // Create enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_code' => $finalEnrollmentCode,
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
        
        // Refresh user relationship to ensure enrollments are up to date
        $user->refresh();
        $user->load('enrollments');

        $enrollment->load(['course:id,title,image,slug,short_description,category_id', 'course.category:id,name,slug']);

        // Send enrollment confirmation email via queue (non-blocking)
        // If email fails, enrollment still succeeds
        try {
            Mail::to($user->email)->queue(new \App\Mail\EnrollmentConfirmationMail($user, $course, $enrollment));
        } catch (\Exception $e) {
            // Log error but don't fail enrollment
            \Log::error('Failed to queue enrollment confirmation email to ' . $user->email . ': ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully enrolled in course',
            'data' => [
                'enrollment' => $enrollment,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/my-enrollments",
     *     tags={"Enrollments"},
     *     summary="Get all enrollments for the authenticated user (minimal, for quick list)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Enrollments list", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Enrollment")))
     * )
     */
    public function myEnrollments(Request $request)
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['course:id,title,image,slug'])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $enrollments]);
    }

    /**
     * @OA\Get(
     *     path="/api/my-courses",
     *     tags={"Enrollments"},
     *     summary="Get the authenticated user's courses with progress, optionally filtered by status",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"all","active","completed"}, default="all")),
     *     @OA\Response(response=200, description="Courses with progress")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/ongoing-courses",
     *     tags={"Enrollments"},
     *     summary="Get courses with status=active for the authenticated user",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Ongoing courses with progress")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/completed-courses",
     *     tags={"Enrollments"},
     *     summary="Get courses with status=completed for the authenticated user",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Completed courses")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/enrollments/{enrollment}",
     *     tags={"Enrollments"},
     *     summary="Get a specific enrollment with course details",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="enrollment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Enrollment details"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
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
     * @OA\Get(
     *     path="/api/my-ongoing-courses",
     *     tags={"Enrollments"},
     *     summary="Alias for /ongoing-courses",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Ongoing courses")
     * )
     *
     * @OA\Get(
     *     path="/api/saved-courses-list",
     *     tags={"Saved Courses"},
     *     summary="Get saved/bookmarked courses (via enrollment controller)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Saved courses")
     * )
     *
     * @OA\Get(
     *     path="/api/certified-courses",
     *     tags={"Enrollments"},
     *     summary="Get courses for which the authenticated user has certificates",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Certified courses with certificate objects")
     * )
     *
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
            ->orderBy('created_at', 'desc') // Use created_at instead of issued_date
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $certificates,
            'message' => 'Certified courses retrieved successfully'
        ]);
    }
}

