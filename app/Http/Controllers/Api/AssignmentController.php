<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $assignments = $course->assignments()
            ->where('is_active', true)
            ->with(['submissions' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json($assignments);
    }

    public function show(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $assignment->course_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $assignment->load(['submissions' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }]);

        return response()->json($assignment);
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $assignment->course_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $request->validate([
            'submission_content' => 'required|string',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $filePath = null;
        $fileName = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('assignments', config('filesystems.default'));
            $fileName = $file->getClientOriginalName();
        }

        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'submission_content' => $request->submission_content,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        return response()->json($submission, 201);
    }

    public function mySubmissions(Request $request)
    {
        $user = $request->user();

        $submissions = $user->assignmentSubmissions()
            ->with(['assignment.course:id,title'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json($submissions);
    }
}

