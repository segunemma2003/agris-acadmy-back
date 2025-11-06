<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    public function enroll(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'enrollment_code' => 'nullable|string',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($request->course_id);

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'You are already enrolled in this course',
                'enrollment' => $existingEnrollment,
            ], 400);
        }

        // Validate enrollment code if provided
        $enrollmentCode = null;
        if ($request->enrollment_code) {
            $enrollmentCode = EnrollmentCode::where('code', $request->enrollment_code)
                ->where('course_id', $course->id)
                ->where('is_used', false)
                ->first();

            if (!$enrollmentCode) {
                return response()->json([
                    'message' => 'Invalid or already used enrollment code',
                ], 400);
            }

            if ($enrollmentCode->expires_at && $enrollmentCode->expires_at->isPast()) {
                return response()->json([
                    'message' => 'Enrollment code has expired',
                ], 400);
            }
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_code' => $enrollmentCode ? $enrollmentCode->code : Str::upper(Str::random(12)),
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Mark code as used if applicable
        if ($enrollmentCode) {
            $enrollmentCode->update([
                'is_used' => true,
                'user_id' => $user->id,
                'used_at' => now(),
            ]);
        }

        // Update course enrollment count
        $course->increment('enrollment_count');

        return response()->json([
            'message' => 'Successfully enrolled in course',
            'enrollment' => $enrollment->load('course'),
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

        return response()->json($enrollments);
    }

    public function show(Request $request, Enrollment $enrollment)
    {
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $enrollment->load(['course.modules.topics', 'course.tutor']);

        return response()->json($enrollment);
    }
}

