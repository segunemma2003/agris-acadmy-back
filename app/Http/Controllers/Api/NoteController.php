<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
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
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $notes = $user->notes()
            ->where('course_id', $course->id)
            ->with('topic:id,title,module_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notes);
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
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $note = StudentNote::create([
            'user_id' => $user->id,
            'course_id' => $request->course_id,
            'topic_id' => $request->topic_id,
            'notes' => $request->notes,
            'timestamp_seconds' => $request->timestamp_seconds,
            'is_public' => $request->is_public ?? false,
        ]);

        return response()->json($note->load('topic'), 201);
    }

    public function update(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'notes' => 'required|string',
            'is_public' => 'nullable|boolean',
        ]);

        $note->update([
            'notes' => $request->notes,
            'is_public' => $request->is_public ?? $note->is_public,
        ]);

        return response()->json($note->load('topic'));
    }

    public function destroy(Request $request, StudentNote $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }
}

