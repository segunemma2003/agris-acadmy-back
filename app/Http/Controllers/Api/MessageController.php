<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Events\MessageRead;
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
    public function index(Request $request, Course $course = null)
    {
        $user = $request->user();
        $courseId = $request->route('course') ? $course->id : $request->input('course_id', 0);

        // Handle location-based messaging (course_id = 0)
        if ($courseId == 0) {
            // Get location-based messages where user is sender or recipient
            $messages = Message::where('course_id', 0)
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhere('recipient_id', $user->id);
                })
                ->with(['sender:id,name,avatar', 'recipient:id,name,avatar'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($messages);
        }

        // Course-based messaging
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

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
            'course_id' => 'required',
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $user = $request->user();
        $recipient = User::findOrFail($request->recipient_id);
        
        // Handle location-based messaging (course_id = 0)
        if ($request->course_id == 0 || $request->course_id == '0') {
            // Location-based facilitator messaging
            if ($recipient->role !== 'facilitator') {
                return response()->json([
                    'message' => 'You can only message facilitators via location-based messaging'
                ], 403);
            }
            
            if (!$user->location || !$recipient->location || $user->location !== $recipient->location) {
                return response()->json([
                    'message' => 'You can only message facilitators in your location'
                ], 403);
            }
            
            // Create message with course_id = 0 for location-based messaging
            $message = Message::create([
                'course_id' => 0, // Special ID for location-based messaging
                'sender_id' => $user->id,
                'recipient_id' => $request->recipient_id,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);
            
            // Load relationships for broadcasting
            $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']);
            
            // Broadcast message sent event for real-time updates
            broadcast(new MessageSent($message))->toOthers();
            
            // Send email notification
            if ($recipient && $recipient->email) {
                try {
                    $subject = $request->subject ?? 'Message from Agrisiti Academy';
                    $body = $request->message;
                    Mail::to($recipient->email)->queue(new AdminNotificationMail(
                        $recipient,
                        $subject,
                        $body,
                        $user
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send message email notification', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            return response()->json($message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']), 201);
        }
        
        // Course-based messaging
        $course = Course::findOrFail($request->course_id);
        
        // Check if user is enrolled
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        // Allow messaging if:
        // 1. User is enrolled in the course AND recipient is tutor/facilitator of the course
        // 2. OR recipient is a facilitator in user's location (location-based messaging)
        $isEnrolledAndRecipientIsTutor = $enrollment && 
            ($course->tutor_id == $request->recipient_id || 
             $course->tutors()->where('tutor_id', $request->recipient_id)->exists());
        
        $isLocationBasedFacilitator = $recipient->role === 'facilitator' && 
            $user->location && 
            $recipient->location === $user->location;

        if (!$isEnrolledAndRecipientIsTutor && !$isLocationBasedFacilitator) {
            return response()->json([
                'message' => 'You can only message facilitators in your location or course instructors'
            ], 403);
        }

        $message = Message::create([
            'course_id' => $course->id,
            'sender_id' => $user->id,
            'recipient_id' => $request->recipient_id,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        // Load relationships for broadcasting
        $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']);

        // Broadcast message sent event for real-time updates
        broadcast(new MessageSent($message))->toOthers();

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
            
            // Broadcast message read event
            broadcast(new MessageRead($message))->toOthers();
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

        // Broadcast message read event
        broadcast(new MessageRead($message))->toOthers();

        return response()->json($message);
    }
}

