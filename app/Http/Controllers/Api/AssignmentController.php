<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses/{course}/assignments",
     *     tags={"Assignments"},
     *     summary="List assignments for a course with the user's submission status",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignments list with submission info"),
     *     @OA\Response(response=403, description="Not enrolled")
     * )
     */
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
            ->get()
            ->map(function ($assignment) {
                $submission = $assignment->submissions->first();
                $assignment->is_submitted = $submission !== null;
                $assignment->is_graded = $submission && in_array($submission->status, ['graded', 'returned']);
                
                // Format submission with file URL if exists
                if ($submission && $submission->file_path) {
                    $submission->file_url = $submission->file_url;
                }
                
                $assignment->submission = $submission; // Single submission object instead of array
                $assignment->unsetRelation('submissions'); // Remove the array
                return $assignment;
            });

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    /**
     * @OA\Get(
     *     path="/api/assignments/{assignment}",
     *     tags={"Assignments"},
     *     summary="Get a single assignment with the user's submission",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="assignment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment with submission"),
     *     @OA\Response(response=403, description="Not enrolled")
     * )
     */
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

        $submission = $assignment->submissions->first();
        $assignment->is_submitted = $submission !== null;
        $assignment->is_graded = $submission && in_array($submission->status, ['graded', 'returned']);
        
        // Format submission with file URL if exists
        if ($submission && $submission->file_path) {
            $submission->file_url = $submission->file_url;
        }
        
        $assignment->submission = $submission; // Single submission object instead of array
        $assignment->unsetRelation('submissions'); // Remove the array

        return response()->json(['success' => true, 'data' => $assignment]);
    }

    /**
     * @OA\Post(
     *     path="/api/assignments/{assignment}/submit",
     *     tags={"Assignments"},
     *     summary="Submit or re-submit an assignment (multipart/form-data to include a file)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="assignment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"submission_content"},
     *                 @OA\Property(property="submission_content", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary", description="Optional file attachment, max 10 MB")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Submission created/updated"),
     *     @OA\Response(response=403, description="Not enrolled")
     * )
     */
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
            $filePath = $file->store('assignments', 'public');
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

        return response()->json(['success' => true, 'data' => $submission], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/my-submissions",
     *     tags={"Assignments"},
     *     summary="Get all assignment submissions made by the authenticated user across all courses",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="List of submissions with assignment and course")
     * )
     */
    public function mySubmissions(Request $request)
    {
        $user = $request->user();

        $submissions = $user->assignmentSubmissions()
            ->with(['assignment.course:id,title'])
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($submission) {
                // Add file URL if exists
                if ($submission->file_path) {
                    $submission->file_url = $submission->file_url;
                }
                // Add helper fields
                $submission->is_graded = in_array($submission->status, ['graded', 'returned']);
                return $submission;
            });

        return response()->json(['success' => true, 'data' => $submissions]);
    }
}

