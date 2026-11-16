<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use App\Mail\AdminNotificationMail;
use App\Mail\TutorNotificationMail;
use App\Models\Course;
use App\Models\Message;
use App\Support\Messaging;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses/{course}/messages",
     *     tags={"Messages"},
     *     summary="Get messages for a course conversation (must be enrolled; or use course_id=0 for location-based facilitator messages)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="course", in="path", required=true, @OA\Schema(type="integer", description="Course ID or 0 for location-based messaging")),
     *     @OA\Response(response=200, description="Messages list"),
     *     @OA\Response(response=403, description="Not enrolled or course not found")
     * )
     */
    public function index(Request $request, ?Course $course = null)
    {
        $user = $request->user();
        $courseId = $request->route('course') ? $course->id : $request->input('course_id', 0);

        // Handle location-based messaging (course_id = 0)
        if ($courseId == 0) {
            // Get location-based messages where user is sender or recipient
            $messages = Message::where(fn ($q) => $q->whereNull('course_id')->orWhere('course_id', 0))
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhere('recipient_id', $user->id);
                })
                ->with(['sender:id,name,avatar', 'recipient:id,name,avatar'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $messages]);
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

        return response()->json(['success' => true, 'data' => $messages]);
    }

    /**
     * @OA\Post(
     *     path="/api/messages",
     *     tags={"Messages"},
     *     summary="Send a message to a course tutor, a classmate, or a facilitator in my location",
     *     description="Allowed: learner ↔ tutor of a course the learner is enrolled in; learner ↔ learner both enrolled in the course (classmates); learner ↔ facilitator in the same location (course_id 0, stored with no course). Admins may message anyone. The recipient is emailed (if their preferences allow) and a MessageSent event is broadcast.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","recipient_id","message"},
     *             @OA\Property(property="course_id", type="integer", description="Course ID or 0 for location-based messaging"),
     *             @OA\Property(property="recipient_id", type="integer"),
     *             @OA\Property(property="subject", type="string", nullable=true, maxLength=255),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Message sent"),
     *     @OA\Response(response=403, description="Recipient not reachable (not a tutor/classmate in this course, or not a facilitator in your location)")
     * )
     */
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
            // Location-based messaging: learner ↔ facilitator in the same location
            if (! Messaging::canMessage($user, $recipient, null)) {
                return response()->json([
                    'message' => 'You can only message facilitators in your location'
                ], 403);
            }
            
            // Create message with course_id = 0 for location-based messaging
            $message = Message::create([
                'course_id' => null, // location-based: no course (clients still send 0)
                'sender_id' => $user->id,
                'recipient_id' => $request->recipient_id,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);
            
            // Load relationships for broadcasting
            $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar']);
            
            // Broadcast message sent event for real-time updates
            broadcast(new MessageSent($message))->toOthers();
            
            // Send email notification (respect recipient prefs when they are a learner)
            if (
                $recipient
                && $recipient->email
                && \App\Support\NotificationPreferences::allowsEmail($recipient->notification_preferences, 'message_sent')
            ) {
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
            
            return response()->json(['success' => true, 'data' => $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar'])], 201);
        }
        
        // Course-based messaging
        $course = Course::findOrFail($request->course_id);
        
        // Course tutors, facilitators in the learner's location, and classmates (both enrolled)
        if (! Messaging::canMessage($user, $recipient, $course)) {
            return response()->json([
                'message' => 'You can only message your course instructors, classmates, or facilitators in your location'
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

        // Send same message to recipient's email (respect prefs for learners)
        $recipient = User::find($request->recipient_id);
        if (
            $recipient
            && $recipient->email
            && \App\Support\NotificationPreferences::allowsEmail($recipient->notification_preferences, 'message_sent')
        ) {
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

    /**
     * @OA\Get(
     *     path="/api/messages/{message}",
     *     tags={"Messages"},
     *     summary="Get a single message (auto-marks as read if the user is the recipient)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="message", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Message object"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
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

        return response()->json(['success' => true, 'data' => $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar'])]);
    }

    /**
     * @OA\Put(
     *     path="/api/messages/{message}/read",
     *     tags={"Messages"},
     *     summary="Mark a message as read (recipient only)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="message", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Message marked as read"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
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

        return response()->json(['success' => true, 'data' => $message->load(['sender:id,name,avatar', 'recipient:id,name,avatar'])]);
    }

    /**
     * People the signed-in user can message: per course (tutors + classmates,
     * or the class list for a tutor) and facilitators in their location.
     * `?search=` filters by name.
     */
    public function contacts(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:100']);

        return response()->json([
            'success' => true,
            'data' => Messaging::contacts($request->user(), $request->input('search')),
        ]);
    }

    /** Inbox: one row per conversation (person + course), newest first, with unread counts. */
    public function conversations(Request $request)
    {
        $user = $request->user();

        $messages = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))
            ->with(['sender:id,name,avatar,role', 'recipient:id,name,avatar,role', 'course:id,title'])
            ->orderByDesc('created_at')
            ->limit(2000)
            ->get();

        $conversations = $messages
            ->groupBy(fn (Message $m) => ($m->sender_id === $user->id ? $m->recipient_id : $m->sender_id) . ':' . (int) $m->course_id)
            ->map(function ($thread) use ($user) {
                /** @var Message $last */
                $last = $thread->first();
                $other = $last->sender_id === $user->id ? $last->recipient : $last->sender;

                return [
                    'with' => $other ? ['id' => $other->id, 'name' => $other->name, 'avatar' => $other->avatar, 'role' => $other->role] : null,
                    'course_id' => (int) $last->course_id,
                    'course_title' => $last->course_id ? $last->course?->title : null,
                    'last_message' => $last->message,
                    'last_at' => $last->created_at,
                    'last_from_me' => $last->sender_id === $user->id,
                    'unread' => $thread->where('recipient_id', $user->id)->where('is_read', false)->count(),
                ];
            })
            ->filter(fn ($c) => $c['with'] !== null)
            ->values();

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    /** One conversation (oldest first). Messages sent to the user are marked read. */
    public function thread(Request $request)
    {
        $request->validate([
            'with' => 'required|integer|exists:users,id',
            'course_id' => 'nullable|integer',
        ]);
        $user = $request->user();
        $otherId = (int) $request->input('with');
        $courseId = (int) $request->input('course_id', 0);

        $messages = Message::query()
            ->when($courseId === 0,
                fn ($q) => $q->where(fn ($q) => $q->whereNull('course_id')->orWhere('course_id', 0)),
                fn ($q) => $q->where('course_id', $courseId))
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('sender_id', $user->id)->where('recipient_id', $otherId))
                ->orWhere(fn ($q) => $q->where('sender_id', $otherId)->where('recipient_id', $user->id)))
            ->with(['sender:id,name,avatar', 'recipient:id,name,avatar'])
            ->orderBy('created_at')
            ->limit(500)
            ->get();

        $unread = $messages->where('recipient_id', $user->id)->where('is_read', false);
        if ($unread->isNotEmpty()) {
            Message::whereIn('id', $unread->pluck('id'))->update(['is_read' => true, 'read_at' => now()]);
            $unread->each(fn ($m) => $m->forceFill(['is_read' => true]));
        }

        $other = User::find($otherId);
        $course = $courseId ? Course::find($courseId) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'with' => ['id' => $other->id, 'name' => $other->name, 'avatar' => $other->avatar, 'role' => $other->role],
                'course_id' => $courseId,
                'course_title' => $course?->title,
                'can_reply' => Messaging::canMessage($user, $other, $course),
                'messages' => $messages,
            ],
        ]);
    }
}

