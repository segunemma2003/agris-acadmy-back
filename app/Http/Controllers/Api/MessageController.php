<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminNotificationMail;
use App\Mail\TutorNotificationMail;
use App\Models\Course;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // Send same message to recipient's email
        $recipient = User::find($request->recipient_id);
        if ($recipient && $recipient->email) {
            try {
                $subject = $request->subject ?? 'Message from Agrisiti Academy';
                $body = $request->message;
                if ($user->role === 'student') {
                    // Student messaging tutor/facilitator
                    Mail::to($recipient->email)->queue(new AdminNotificationMail(
                        $recipient,
                        $subject,
                        $body,
                        $user
                    ));
                } else {
                    // Tutor/facilitator messaging student
                    Mail::to($recipient->email)->queue(new TutorNotificationMail(
                        $recipient,
                        $course,
                        $subject,
                        $body,
                        $user
                    ));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send message email notification (API)', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

