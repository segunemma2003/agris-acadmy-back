<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\StudentNote;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        $notes = $user->notes()
            ->where('course_id', $course->id)
            ->with(['topic:id,title,module_id', 'topic.module:id,title'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
            'message' => 'All course notes retrieved successfully'
        ]);
    }

    public function moduleNotes(Request $request, Course $course, Module $module)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Check if module belongs to course
        if ($module->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found in this course'
            ], 404);
        }

        // Get notes for topics in this module
        $notes = $user->notes()
            ->where('course_id', $course->id)
            ->whereHas('topic', function ($query) use ($module) {
                $query->where('module_id', $module->id);
            })
            ->with(['topic:id,title,module_id', 'topic.module:id,title'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
            'message' => 'Module notes retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'topic_id' => 'required|exists:topics,id',
            'notes' => 'required|string',
            'timestamp_seconds' => 'nullable|integer',
            'is_public' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $request->course_id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        $note = StudentNote::create([
            'user_id' => $user->id,
            'course_id' => $request->course_id,
            'topic_id' => $request->topic_id,
            'notes' => $request->notes,
            'timestamp_seconds' => $request->timestamp_seconds,
            'is_public' => $request->is_public ?? false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $note->load('topic'),
            'message' => 'Note created successfully'
        ], 201);
    }

    public function update(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'notes' => 'required|string',
            'is_public' => 'nullable|boolean',
        ]);

        $note->update([
            'notes' => $request->notes,
            'is_public' => $request->is_public ?? $note->is_public,
        ]);

        return response()->json([
            'success' => true,
            'data' => $note->load('topic'),
            'message' => 'Note updated successfully'
        ]);
    }

    public function destroy(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    }

    /**
     * Get notes for a specific topic/lesson
     */
    public function topicNotes(Request $request, Course $course, Module $module, Topic $topic)
    {
        $user = $request->user();

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Check if module belongs to course
        if ($module->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found in this course'
            ], 404);
        }

        // Check if topic belongs to module
        if ($topic->module_id !== $module->id) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found in this module'
            ], 404);
        }

        $notes = $user->notes()
            ->where('course_id', $course->id)
            ->where('topic_id', $topic->id)
            ->with(['topic:id,title,module_id', 'topic.module:id,title'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
            'message' => 'Topic notes retrieved successfully'
        ]);
    }
}

