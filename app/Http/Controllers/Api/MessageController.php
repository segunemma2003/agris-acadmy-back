<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
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

        // Get messages where user is sender or recipient
        $messages = Message::where('course_id', $course->id)
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->with(['sender:id,name,avatar', 'recipient:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($request->course_id);

        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        // Verify recipient is tutor of the course
        if ($course->tutor_id != $request->recipient_id && $user->id != $course->tutor_id) {
            return response()->json(['message' => 'Invalid recipient'], 400);
        }

        $message = Message::create([
            'course_id' => $course->id,
            'sender_id' => $user->id,
            'recipient_id' => $request->recipient_id,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return response()->json($message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']), 201);
    }

    public function show(Request $request, Message $message)
    {
        $user = $request->user();

        if ($message->sender_id !== $user->id && $message->recipient_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark as read if user is recipient
        if ($message->recipient_id === $user->id && !$message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json($message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']));
    }

    public function markAsRead(Request $request, Message $message)
    {
        $user = $request->user();

        if ($message->recipient_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json($message);
    }
}

